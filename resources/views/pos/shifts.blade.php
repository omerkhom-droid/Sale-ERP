<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">سجل ورديات POS</h3>
            <p class="page-subtitle mb-0">
                عرض الورديات السابقة والحالية مع إمكانية فتح تقرير كل وردية.
            </p>
        </div>

        <a href="{{ route('pos.index') }}" class="btn btn-light fw-bold">
            شاشة POS
        </a>
    </div>

    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">الفلاتر</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('pos.shifts') }}">
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
                                <option value="{{ $branch->id }}" @selected((int) request('branch_id') === (int) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">كل الحالات</option>
                            <option value="open" @selected(request('status') === 'open')>مفتوحة</option>
                            <option value="closed" @selected(request('status') === 'closed')>مغلقة</option>
                        </select>
                    </div>

                    <div class="col-md-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            عرض
                        </button>

                        <a href="{{ route('pos.shifts') }}" class="btn btn-secondary">
                            مسح الفلاتر
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">الورديات</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الوردية</th>
                        <th>الكاشير</th>
                        <th>الفرع</th>
                        <th>المستودع</th>
                        <th>وقت الفتح</th>
                        <th>وقت الإغلاق</th>
                        <th>الحالة</th>
                        <th>طلبات مدفوعة</th>
                        <th>المبيعات</th>
                        <th>النقدي</th>
                        <th>الشبكة</th>
                        <th>فرق الصندوق</th>
                        <th width="120">الإجراء</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $loop->iteration + (($shifts->currentPage() - 1) * $shifts->perPage()) }}</td>

                            <td class="fw-bold">{{ $shift->shift_no }}</td>

                            <td>{{ $shift->user?->name ?? '-' }}</td>

                            <td>{{ $shift->branch?->branch_name ?? '-' }}</td>

                            <td>{{ $shift->warehouse?->warehouse_name ?? '-' }}</td>

                            <td>{{ optional($shift->opened_at)->format('Y-m-d H:i') }}</td>

                            <td>{{ optional($shift->closed_at)->format('Y-m-d H:i') ?? '-' }}</td>

                            <td>
                                @if($shift->status === 'open')
                                    <span class="badge bg-success">مفتوحة</span>
                                @else
                                    <span class="badge bg-danger">مغلقة</span>
                                @endif
                            </td>

                            <td>{{ $shift->paid_orders_count }}</td>

                            <td>{{ number_format((float) $shift->total_sales, 2) }}</td>

                            <td>{{ number_format((float) $shift->total_cash, 2) }}</td>

                            <td>{{ number_format((float) $shift->total_card, 2) }}</td>

                            <td class="{{ abs((float) $shift->cash_difference) <= 0.01 ? 'text-success' : 'text-danger' }} fw-bold">
                                {{ number_format((float) $shift->cash_difference, 2) }}
                            </td>

                            <td>
                                <a href="{{ route('pos.shift-report', $shift->id) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-primary">
                                    التقرير
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="py-4 text-muted fw-bold">
                                لا توجد ورديات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shifts->hasPages())
            <div class="card-footer bg-white">
                {{ $shifts->links() }}
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

    .form-label {
        color: #071633;
        font-weight: 900;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
    }

    .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
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

    .badge {
        font-weight: 900;
        padding: 7px 10px;
        border-radius: 10px;
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }
    }
</style>

</x-app-layout>