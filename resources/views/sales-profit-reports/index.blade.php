<x-app-layout>

@php
    $periodFrom = request('date_from') ?: 'البداية';
    $periodTo = request('date_to') ?: 'اليوم';

    $grossProfitClass = (float) $grossProfit >= 0 ? 'success' : 'danger';
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">تقرير أرباح المبيعات</h3>
            <p class="page-subtitle mb-0">
                تحليل ربحية المبيعات حسب الفترة والعميل والفرع، مع مقارنة صافي البيع بتكلفة البضاعة المباعة.
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
                <small>حدد الفترة والعميل والفرع لعرض ربحية المبيعات حسب البيانات المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('sales-profit-reports.index') }}">

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

                    <div class="col-md-3">
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

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            بحث
                        </button>
                    </div>

                </div>

                <div class="filter-actions mt-3">
                    <a href="{{ route('sales-profit-reports.index') }}" class="btn btn-outline-secondary">
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
        <h3 class="fw-bold mb-1">تقرير أرباح المبيعات</h3>

        <div>
            الفترة:
            {{ $periodFrom }}
            إلى
            {{ $periodTo }}
        </div>

        @if(request('customer_id') || request('branch_id'))
            <div class="mt-1">
                الفلاتر:
                @if(request('customer_id'))
                    العميل: {{ request('customer_id') === 'cash' ? 'العملاء النقديين' : 'محدد' }}
                @endif

                @if(request('branch_id'))
                    - الفرع: محدد
                @endif
            </div>
        @endif
    </div>


    {{-- Main Summary --}}
    <div class="summary-grid mb-4">

        <div class="summary-card primary-card">
            <span>إجمالي المبيعات بدون ضريبة</span>
            <strong>{{ number_format((float) $totalSalesWithoutVat, 2) }}</strong>
        </div>

        <div class="summary-card danger-card">
            <span>تكلفة البضاعة المباعة</span>
            <strong>{{ number_format((float) $totalSalesCost, 2) }}</strong>
        </div>

        <div class="summary-card success-card">
            <span>مجمل الربح قبل المردودات</span>
            <strong>{{ number_format((float) ($totalSalesWithoutVat - $totalSalesCost), 2) }}</strong>
        </div>

        <div class="summary-card info-card">
            <span>نسبة الربح</span>
            <strong>{{ number_format((float) $profitPercent, 2) }}%</strong>
        </div>

    </div>


    {{-- Secondary Summary --}}
    <div class="summary-grid secondary-summary mb-4">

        <div class="summary-card light-card danger-text">
            <span>مردودات المبيعات بدون ضريبة</span>
            <strong>{{ number_format((float) $totalReturnsWithoutVat, 2) }}</strong>
        </div>

        <div class="summary-card light-card success-text">
            <span>تكلفة المردودات</span>
            <strong>{{ number_format((float) $totalReturnsCost, 2) }}</strong>
        </div>

        <div class="summary-card light-card primary-text">
            <span>صافي المبيعات بدون ضريبة</span>
            <strong>{{ number_format((float) $netSalesWithoutVat, 2) }}</strong>
        </div>

        <div class="summary-card light-card danger-text">
            <span>صافي التكلفة</span>
            <strong>{{ number_format((float) $netCost, 2) }}</strong>
        </div>

    </div>


    {{-- Gross Profit --}}
    <div class="gross-profit-card gross-profit-{{ $grossProfitClass }} mb-4">
        <span>صافي مجمل الربح</span>
        <strong>{{ number_format((float) $grossProfit, 2) }}</strong>
    </div>


    {{-- Items Table --}}
    <div class="card shadow-sm wazin-card report-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">تفاصيل ربح الأصناف المباعة</h5>
                <small>عرض تكلفة وربح كل صنف مباع حسب فواتير البيع المطابقة للفلاتر</small>
            </div>

            <span class="result-count no-print">
                عدد النتائج: {{ $items->total() }}
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
                            <th>الصنف</th>
                            <th>الكمية</th>
                            <th>سعر البيع</th>
                            <th>صافي البيع</th>
                            <th>تكلفة الوحدة</th>
                            <th>إجمالي التكلفة</th>
                            <th>الربح</th>
                            <th>نسبة الربح</th>
                            <th class="no-print">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($items as $index => $item)

                            @php
                                $lineProfit = (float) $item->net_amount - (float) $item->total_cost;

                                $lineProfitPercent = (float) $item->net_amount > 0
                                    ? ($lineProfit / (float) $item->net_amount) * 100
                                    : 0;

                                $invoiceDate = $item->salesInvoice?->invoice_date;

                                if ($invoiceDate instanceof \Carbon\CarbonInterface) {
                                    $invoiceDateDisplay = $invoiceDate->format('Y-m-d');
                                } else {
                                    $invoiceDateDisplay = $invoiceDate
                                        ? \Illuminate\Support\Str::of((string) $invoiceDate)->substr(0, 10)
                                        : '-';
                                }
                            @endphp

                            <tr>
                                <td class="fw-bold">
                                    {{ $items->firstItem() + $index }}
                                </td>

                                <td class="amount-cell">
                                    {{ $item->salesInvoice?->invoice_no ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ $invoiceDateDisplay }}
                                </td>

                                <td class="text-start customer-cell">
                                    {{ $item->salesInvoice?->customer?->customer_name
                                        ?? $item->salesInvoice?->customer?->name
                                        ?? $item->salesInvoice?->customer_name
                                        ?? 'عميل نقدي' }}
                                </td>

                                <td>
                                    {{ $item->salesInvoice?->branch?->branch_name_ar
                                        ?? $item->salesInvoice?->branch?->branch_name
                                        ?? $item->salesInvoice?->branch?->name
                                        ?? '-' }}
                                </td>

                                <td class="text-start product-cell">
                                    <div class="fw-bold">
                                        {{ $item->product_name
                                            ?? $item->product?->product_name_ar
                                            ?? $item->product?->product_name
                                            ?? $item->product?->name
                                            ?? '-' }}
                                    </div>

                                    @if($item->product_sku)
                                        <small>
                                            {{ $item->product_sku }}
                                        </small>
                                    @endif
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->quantity, 2) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->unit_price, 2) }}
                                </td>

                                <td class="amount-cell fw-bold primary-amount">
                                    {{ number_format((float) $item->net_amount, 2) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->unit_cost, 2) }}
                                </td>

                                <td class="amount-cell danger-amount">
                                    {{ number_format((float) $item->total_cost, 2) }}
                                </td>

                                <td class="amount-cell fw-bold {{ $lineProfit >= 0 ? 'success-amount' : 'danger-amount' }}">
                                    {{ number_format((float) $lineProfit, 2) }}
                                </td>

                                <td class="amount-cell {{ $lineProfitPercent >= 0 ? 'success-amount' : 'danger-amount' }}">
                                    {{ number_format((float) $lineProfitPercent, 2) }}%
                                </td>

                                <td class="no-print">
                                    @if($item->salesInvoice)
                                        <a href="{{ route('sales-invoices.show', $item->salesInvoice->id) }}"
                                           class="btn btn-primary btn-sm">
                                            عرض
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="14">
                                    <div class="empty-state">
                                        لا توجد بيانات حسب الفلاتر المحددة.
                                    </div>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="8">الإجمالي</td>

                            <td class="amount-cell primary-amount">
                                {{ number_format((float) $totalSalesWithoutVat, 2) }}
                            </td>

                            <td></td>

                            <td class="amount-cell danger-amount">
                                {{ number_format((float) $totalSalesCost, 2) }}
                            </td>

                            <td class="amount-cell {{ ($totalSalesWithoutVat - $totalSalesCost) >= 0 ? 'success-amount' : 'danger-amount' }}">
                                {{ number_format((float) ($totalSalesWithoutVat - $totalSalesCost), 2) }}
                            </td>

                            <td></td>

                            <td class="no-print"></td>
                        </tr>
                    </tfoot>

                </table>

            </div>

            <div class="pagination-wrapper no-print">
                {{ $items->links() }}
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
    .primary-text strong,
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

    .info-card {
        border-right: 5px solid #0891B2;
        background: rgba(8, 145, 178, 0.06);
    }

    .info-card strong {
        color: #0891B2;
    }

    .light-card strong {
        font-size: 20px;
    }

    .gross-profit-card {
        background: #fff;
        border-radius: 24px;
        padding: 26px;
        text-align: center;
        box-shadow: 0 14px 34px rgba(7, 22, 51, 0.08);
    }

    .gross-profit-card span {
        display: block;
        color: #64748B;
        font-size: 14px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .gross-profit-card strong {
        display: block;
        direction: ltr;
        font-size: 42px;
        font-weight: 900;
    }

    .gross-profit-success {
        border: 1px solid rgba(22, 163, 74, 0.22);
        background: linear-gradient(135deg, #fff, rgba(22, 163, 74, 0.06));
    }

    .gross-profit-success strong {
        color: #16A34A;
    }

    .gross-profit-danger {
        border: 1px solid rgba(230, 59, 74, 0.22);
        background: linear-gradient(135deg, #fff, rgba(230, 59, 74, 0.06));
    }

    .gross-profit-danger strong {
        color: #E63B4A;
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

    .product-cell {
        min-width: 210px;
    }

    .product-cell small {
        color: #64748B;
        font-weight: 700;
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

        .gross-profit-card strong {
            font-size: 32px;
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

        .summary-card,
        .gross-profit-card {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 8px !important;
            background: #fff !important;
        }

        .summary-card span,
        .gross-profit-card span {
            color: #000 !important;
            font-size: 10px;
        }

        .summary-card strong,
        .gross-profit-card strong {
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
    }
</style>

</x-app-layout>