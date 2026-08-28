<x-app-layout>

@php
    $periodFrom = request('date_from') ?: 'البداية';
    $periodTo = request('date_to') ?: 'اليوم';

    $selectedStatus = request('status');

    $statusLabels = [
        'draft' => 'مسودة',
        'posted' => 'مرحلة',
        'cancelled' => 'ملغاة',
    ];
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">تقرير المبيعات</h3>
            <p class="page-subtitle mb-0">
                تحليل فواتير البيع حسب الفترة والعميل والفرع وحالة الفاتورة مع إجمالي المبيعات والضريبة والمدفوع والمتبقي.
            </p>
        </div>

        <button type="button" onclick="window.print()" class="btn btn-dark">
            طباعة التقرير
        </button>
    </div>


    {{-- Filters --}}
    <div class="card shadow-sm wazin-card mb-4 no-print">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">فلاتر التقرير</h5>
                <small>حدد الفترة والعميل والفرع وحالة الفاتورة لعرض النتائج المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('sales-reports.index') }}">

                <div class="row g-3">

                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">كل العملاء</option>

                            <option value="cash" {{ request('customer_id') === 'cash' ? 'selected' : '' }}>
                                العملاء النقديين
                            </option>

                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->customer_name
                                        ?? $customer->name
                                        ?? $customer->fullname
                                        ?? 'عميل رقم ' . $customer->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">كل الفروع</option>

                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? 'فرع رقم ' . $branch->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">حالة الفاتورة</label>
                        <select name="status" class="form-select">
                            <option value="">كل الحالات</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>مرحلة</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            بحث
                        </button>
                    </div>

                </div>

                <div class="filter-actions mt-3">
                    <a href="{{ route('sales-reports.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>

                    <button type="button" onclick="window.print()" class="btn btn-dark">
                        طباعة
                    </button>
                </div>

            </form>

        </div>

    </div>


    {{-- Print Header --}}
    <div class="print-header text-center mb-4">
        <h3 class="fw-bold mb-1">تقرير المبيعات</h3>
        <div>
            الفترة:
            {{ $periodFrom }}
            إلى
            {{ $periodTo }}
        </div>

        @if(request('customer_id') || request('branch_id') || request('status'))
            <div class="mt-1">
                الفلاتر:
                @if(request('customer_id'))
                    العميل: {{ request('customer_id') === 'cash' ? 'العملاء النقديين' : 'محدد' }}
                @endif

                @if(request('branch_id'))
                    - الفرع: محدد
                @endif

                @if(request('status'))
                    - الحالة: {{ $statusLabels[$selectedStatus] ?? $selectedStatus }}
                @endif
            </div>
        @endif
    </div>


    {{-- Summary Cards --}}
    <div class="summary-grid mb-4">

        <div class="summary-card primary-card">
            <span>إجمالي المبيعات</span>
            <strong>{{ number_format((float) $totalSales, 2) }}</strong>
        </div>

        <div class="summary-card danger-card">
            <span>إجمالي مردودات المبيعات</span>
            <strong>{{ number_format((float) $totalReturns, 2) }}</strong>
        </div>

        <div class="summary-card success-card">
            <span>صافي المبيعات</span>
            <strong>{{ number_format((float) $netSales, 2) }}</strong>
        </div>

        <div class="summary-card warning-card">
            <span>إجمالي الضريبة</span>
            <strong>{{ number_format((float) $totalVat, 2) }}</strong>
        </div>

    </div>


    <div class="summary-grid secondary-summary mb-4">

        <div class="summary-card light-card">
            <span>قبل الخصم والضريبة</span>
            <strong>{{ number_format((float) $totalSubtotal, 2) }}</strong>
        </div>

        <div class="summary-card light-card danger-text">
            <span>إجمالي الخصم</span>
            <strong>{{ number_format((float) $totalDiscount, 2) }}</strong>
        </div>

        <div class="summary-card light-card success-text">
            <span>المدفوع</span>
            <strong>{{ number_format((float) $totalPaid, 2) }}</strong>
        </div>

        <div class="summary-card light-card danger-text">
            <span>المتبقي</span>
            <strong>{{ number_format((float) $totalRemaining, 2) }}</strong>
        </div>

    </div>


    {{-- Invoices Table --}}
    <div class="card shadow-sm wazin-card report-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">تفاصيل فواتير البيع</h5>
                <small>قائمة الفواتير المطابقة للفلاتر المحددة</small>
            </div>

            <span class="result-count no-print">
                عدد النتائج: {{ $invoices->total() }}
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover align-middle text-center mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>الفرع</th>
                            <th>المستودع</th>
                            <th>الإجمالي</th>
                            <th>الضريبة</th>
                            <th>الخصم</th>
                            <th>الصافي</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>حالة الفاتورة</th>
                            <th>حالة السداد</th>
                            <th class="no-print">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($invoices as $index => $invoice)

                            @php
                                $invoiceDate = $invoice->invoice_date;

                                if ($invoiceDate instanceof \Carbon\CarbonInterface) {
                                    $invoiceDateDisplay = $invoiceDate->format('Y-m-d');
                                } else {
                                    $invoiceDateDisplay = $invoiceDate ? \Illuminate\Support\Str::of((string) $invoiceDate)->substr(0, 10) : '-';
                                }
                            @endphp

                            <tr>
                                <td class="fw-bold">
                                    {{ $invoices->firstItem() + $index }}
                                </td>

                                <td class="amount-cell">
                                    {{ $invoice->invoice_no }}
                                </td>

                                <td class="amount-cell">
                                    {{ $invoiceDateDisplay }}
                                </td>

                                <td class="text-start customer-cell">
                                    {{ $invoice->customer?->customer_name
                                        ?? $invoice->customer?->name
                                        ?? $invoice->customer_name
                                        ?? 'عميل نقدي' }}
                                </td>

                                <td>
                                    {{ $invoice->branch?->branch_name_ar
                                        ?? $invoice->branch?->branch_name
                                        ?? $invoice->branch?->name
                                        ?? '-' }}
                                </td>

                                <td>
                                    {{ $invoice->warehouse?->warehouse_name
                                        ?? $invoice->warehouse?->name
                                        ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $invoice->subtotal, 2) }}
                                </td>

                                <td class="amount-cell warning-amount">
                                    {{ number_format((float) $invoice->vat_amount, 2) }}
                                </td>

                                <td class="amount-cell danger-amount">
                                    {{ number_format((float) $invoice->discount_amount, 2) }}
                                </td>

                                <td class="amount-cell fw-bold primary-amount">
                                    {{ number_format((float) $invoice->total_amount, 2) }}
                                </td>

                                <td class="amount-cell success-amount">
                                    {{ number_format((float) $invoice->paid_amount, 2) }}
                                </td>

                                <td class="amount-cell danger-amount">
                                    {{ number_format((float) $invoice->remaining_amount, 2) }}
                                </td>

                                <td>
                                    @if($invoice->status === 'draft')
                                        <span class="badge bg-secondary">مسودة</span>
                                    @elseif($invoice->status === 'posted')
                                        <span class="badge bg-success">مرحلة</span>
                                    @elseif($invoice->status === 'cancelled')
                                        <span class="badge bg-danger">ملغاة</span>
                                    @else
                                        <span class="badge bg-light text-dark">
                                            {{ $invoice->status }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if($invoice->payment_status === 'paid')
                                        <span class="badge bg-success">مدفوعة</span>
                                    @elseif($invoice->payment_status === 'partial')
                                        <span class="badge bg-warning text-dark">جزئي</span>
                                    @elseif($invoice->payment_status === 'unpaid')
                                        <span class="badge bg-danger">غير مدفوعة</span>
                                    @else
                                        <span class="badge bg-light text-dark">
                                            {{ $invoice->payment_status ?? '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="no-print">
                                    <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                                       class="btn btn-primary btn-sm">
                                        عرض
                                    </a>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="15">
                                    <div class="empty-state">
                                        لا توجد فواتير بيع حسب الفلاتر المحددة.
                                    </div>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="6">الإجمالي</td>
                            <td class="amount-cell">{{ number_format((float) $totalSubtotal, 2) }}</td>
                            <td class="amount-cell warning-amount">{{ number_format((float) $totalVat, 2) }}</td>
                            <td class="amount-cell danger-amount">{{ number_format((float) $totalDiscount, 2) }}</td>
                            <td class="amount-cell primary-amount">{{ number_format((float) $totalSales, 2) }}</td>
                            <td class="amount-cell success-amount">{{ number_format((float) $totalPaid, 2) }}</td>
                            <td class="amount-cell danger-amount">{{ number_format((float) $totalRemaining, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>

                </table>

            </div>

            <div class="pagination-wrapper no-print">
                {{ $invoices->links() }}
            </div>

        </div>

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
        font-weight: 600;
        line-height: 1.8;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-card .card-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .form-label {
        color: #071633;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
        background-color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .summary-card span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .summary-card strong {
        display: block;
        direction: ltr;
        text-align: center;
        color: #071633;
        font-size: 24px;
        font-weight: 900;
    }

    .primary-card {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
    }

    .primary-card strong,
    .primary-amount {
        color: #2F6BFF !important;
    }

    .danger-card {
        border-right: 5px solid #E63B4A;
        background: rgba(230, 59, 74, 0.05);
    }

    .danger-card strong,
    .danger-text strong,
    .danger-amount {
        color: #E63B4A !important;
    }

    .success-card {
        border-right: 5px solid #16A34A;
        background: rgba(22, 163, 74, 0.05);
    }

    .success-card strong,
    .success-text strong,
    .success-amount {
        color: #16A34A !important;
    }

    .warning-card {
        border-right: 5px solid #F59E0B;
        background: rgba(245, 158, 11, 0.06);
    }

    .warning-card strong,
    .warning-amount {
        color: #F59E0B !important;
    }

    .light-card strong {
        font-size: 20px;
    }

    .result-count {
        background: #F1F5F9;
        color: #071633;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        font-size: 13px;
    }

    .wazin-table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    .wazin-table tbody td {
        vertical-align: middle;
        font-weight: 600;
        background: #fff;
    }

    .wazin-table tfoot td {
        background: #EEF2F7 !important;
        color: #071633;
        font-weight: 900;
        border-color: #D7DEE8;
        vertical-align: middle;
    }

    .amount-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
        white-space: nowrap;
    }

    .customer-cell {
        min-width: 180px;
    }

    .empty-state {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 18px;
        padding: 28px 16px;
        text-align: center;
        color: #64748B;
        font-weight: 800;
    }

    .pagination-wrapper {
        padding: 18px 20px;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .btn-dark,
    .btn-outline-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-outline-secondary {
        border-color: #CBD5E1;
        color: #071633;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        background: #F1F5F9;
        border-color: #CBD5E1;
        color: #071633;
    }

    .btn-sm {
        border-radius: 12px;
        padding: 7px 13px;
        font-weight: 900;
    }

    .print-header {
        display: none;
    }

    @media (max-width: 991px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions .btn {
            width: 100%;
        }
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        .no-print,
        nav,
        aside,
        header,
        .sidebar,
        .navbar,
        .app-sidebar {
            display: none !important;
        }

        .print-header {
            display: block;
        }

        body {
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .app-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .summary-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px !important;
        }

        .summary-card {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 8px !important;
        }

        .summary-card span {
            color: #000 !important;
            font-size: 10px;
        }

        .summary-card strong {
            color: #000 !important;
            font-size: 14px;
        }

        .wazin-card {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }

        .wazin-card-header {
            background: #f2f2f2 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            padding: 8px !important;
        }

        .wazin-card-header h5,
        .wazin-card-header small {
            color: #000 !important;
        }

        .wazin-card .card-body {
            background: #fff !important;
            padding: 0 !important;
        }

        table {
            font-size: 10px;
            width: 100% !important;
        }

        table th,
        table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
            color: #000 !important;
        }

        .wazin-table thead th,
        .wazin-table tfoot td {
            background: #f2f2f2 !important;
            color: #000 !important;
        }

        .badge {
            border: 1px solid #000;
            color: #000 !important;
            background: #fff !important;
            padding: 3px 6px !important;
        }
    }
</style>

</x-app-layout>