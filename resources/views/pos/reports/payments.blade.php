
<x-app-layout>

@php
    function posPaymentMethodLabel($method) {
        return match ($method) {
            'cash' => 'نقدي',
            'card' => 'شبكة',
            'bank_transfer' => 'تحويل',
            default => $method,
        };
    }
@endphp

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">تقرير طرق الدفع POS</h3>
            <p class="page-subtitle mb-0">
                ملخص المدفوعات حسب النقدي، الشبكة، والتحويل.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" onclick="window.print()" class="btn btn-light fw-bold">
                طباعة
            </button>

            <a href="{{ route('pos.index') }}" class="btn btn-secondary fw-bold">
                رجوع
            </a>
        </div>
    </div>

    <div class="card shadow-sm wazin-card mb-4 no-print">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">فلاتر التقرير</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('pos.reports.payments') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="from_date"
                               class="form-control"
                               value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date"
                               name="to_date"
                               class="form-control"
                               value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">كل الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="">كل الطرق</option>
                            <option value="cash" @selected(request('payment_method') === 'cash')>نقدي</option>
                            <option value="card" @selected(request('payment_method') === 'card')>شبكة</option>
                            <option value="bank_transfer" @selected(request('payment_method') === 'bank_transfer')>تحويل</option>
                        </select>
                    </div>

                    <div class="col-md-12 d-flex gap-2">
                        <button class="btn btn-primary px-4">
                            عرض التقرير
                        </button>

                        <a href="{{ route('pos.reports.payments') }}" class="btn btn-outline-secondary px-4">
                            تصفية
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="summary-card highlight">
                <span>إجمالي المدفوعات</span>
                <strong>{{ number_format((float) $totals['total_amount'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>النقدي</span>
                <strong>{{ number_format((float) $totals['cash'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>الشبكة</span>
                <strong>{{ number_format((float) $totals['card'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>التحويل</span>
                <strong>{{ number_format((float) $totals['bank_transfer'], 2) }}</strong>
            </div>
        </div>

    </div>

    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">ملخص طرق الدفع</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>طريقة الدفع</th>
                        <th>عدد الطلبات</th>
                        <th>الإجمالي</th>
                        <th>أول عملية</th>
                        <th>آخر عملية</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ posPaymentMethodLabel($row->payment_method) }}</td>
                            <td>{{ number_format((int) $row->orders_count) }}</td>
                            <td class="fw-bold">{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->first_payment_at ? \Carbon\Carbon::parse($row->first_payment_at)->format('Y-m-d H:i') : '-' }}</td>
                            <td>{{ $row->last_payment_at ? \Carbon\Carbon::parse($row->last_payment_at)->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-muted fw-bold">
                                لا توجد بيانات حسب الفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">الإجمالي</th>
                        <th>{{ number_format((int) $totals['orders_count']) }}</th>
                        <th>{{ number_format((float) $totals['total_amount'], 2) }}</th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">تفاصيل المدفوعات</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الطلب</th>
                        <th>الوردية</th>
                        <th>الفرع</th>
                        <th>الكاشير</th>
                        <th>وقت الدفع</th>
                        <th>طريقة الدفع</th>
                        <th>الإجمالي</th>
                        <th>الخصم</th>
                        <th>الضريبة</th>
                        <th>مبلغ الدفع</th>
                        <th>المرجع</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $loop->iteration + ($payments->currentPage() - 1) * $payments->perPage() }}</td>
                            <td class="fw-bold">{{ $payment->order_no }}</td>
                            <td>{{ $payment->shift_no ?? '-' }}</td>
                            <td>{{ $payment->branch_name ?? '-' }}</td>
                            <td>{{ $payment->cashier_name ?? '-' }}</td>
                            <td>{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('Y-m-d H:i') : '-' }}</td>
                            <td>{{ posPaymentMethodLabel($payment->payment_method) }}</td>
                            <td>{{ number_format((float) $payment->total_amount, 2) }}</td>
                            <td>{{ number_format((float) $payment->discount_amount, 2) }}</td>
                            <td>{{ number_format((float) $payment->vat_amount, 2) }}</td>
                            <td class="fw-bold">{{ number_format((float) $payment->payment_amount, 2) }}</td>
                            <td>{{ $payment->reference_no ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-4 text-muted fw-bold">
                                لا توجد مدفوعات حسب الفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="card-footer no-print">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>

<style>
    .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
    }

    .page-title {
        color: #fff;
        font-weight: 900;
    }

    .page-subtitle {
        color: #CFEFF3;
        font-weight: 700;
    }

    .wazin-card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
    }

    .wazin-card-header {
        background: #fff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 20px;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        min-height: 86px;
    }

    .summary-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .summary-card strong {
        color: #071633;
        font-weight: 900;
        font-size: 18px;
    }

    .summary-card.highlight {
        border-color: #2F6BFF;
        background: #EFF6FF;
    }

    .form-label {
        font-weight: 900;
        color: #071633;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        min-height: 42px;
        font-weight: 800;
    }

    .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF;
        border-color: #2F6BFF;
    }

    .table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    .table td {
        font-weight: 700;
        vertical-align: middle;
    }

    .table tfoot th {
        background: #F8FAFC;
        color: #071633;
        font-weight: 900;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .card,
        .summary-card {
            box-shadow: none !important;
        }

        .table thead th {
            background: #eee !important;
            color: #000 !important;
        }
    }
</style>

</x-app-layout>