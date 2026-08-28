<x-app-layout>

<div class="container-fluid py-4 inventory-report-page" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card no-print mb-4">

        <div>
            <h3 class="page-title mb-1">تقرير حركة مخزون الصنف</h3>
            <p class="page-subtitle mb-0">
                عرض حركة الدخول والخروج والرصيد لصنف محدد حسب المستودع والفترة.
            </p>
        </div>

        @if($productId)
            <button type="button"
                    onclick="window.print()"
                    class="btn btn-primary">
                طباعة التقرير
            </button>
        @endif

    </div>


    {{-- Filters --}}
    <div class="card shadow-sm wazin-card no-print mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">خيارات التقرير</h5>
                <small>حدد الصنف والمستودع والفترة لعرض حركة المخزون</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('inventory-movements.index') }}">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label">
                            الصنف <span class="text-danger">*</span>
                        </label>
                        <select name="product_id"
                                id="product_id"
                                class="form-select"
                                data-placeholder="ابحث باسم الصنف أو الكود أو الباركود"
                                required>
                            <option value="">اختر الصنف</option>

                            @if($selectedProduct)
                                <option value="{{ $selectedProduct->id }}" selected>
                                    {{ $selectedProduct->sku }} - {{ $selectedProduct->product_name_ar }}
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المستودع</label>

                        <select name="warehouse_id" class="form-select">
                            <option value="">كل المستودعات</option>

                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ (int) $warehouseId === (int) $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>

                        <input type="date"
                               name="from_date"
                               class="form-control"
                               value="{{ $fromDate }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>

                        <input type="date"
                               name="to_date"
                               class="form-control"
                               value="{{ $toDate }}">
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            عرض
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>


    @if(!$productId)

        <div class="empty-state no-print">
            <div class="empty-icon">📦</div>
            <h5>اختر الصنف لعرض التقرير</h5>
            <p>حدد الصنف من خيارات التقرير ثم اضغط عرض لمشاهدة حركة المخزون.</p>
        </div>

    @else

        @php
            $totalIn = $transactions
                ->filter(fn($row) => (float) $row->quantity > 0)
                ->sum('quantity');

            $totalOut = abs(
                $transactions
                    ->filter(fn($row) => (float) $row->quantity < 0)
                    ->sum('quantity')
            );

            /*
            |--------------------------------------------------------------------------
            | آخر رصيد بعد الحركة
            |--------------------------------------------------------------------------
            | عند اختيار مستودع محدد:
            | نأخذ آخر balance_after مباشرة.
            |
            | عند اختيار كل المستودعات:
            | لا يصح أخذ آخر حركة فقط؛ لأن الرصيد يكون خاصًا بمستودع واحد.
            | لذلك نجمع آخر balance_after لكل مستودع.
            */
            if (!empty($warehouseId)) {
                $lastBalance = (float) ($transactions->last()?->balance_after ?? 0);
            } else {
                $lastBalance = (float) $transactions
                    ->groupBy('warehouse_id')
                    ->sum(function ($warehouseTransactions) {
                        $lastTransaction = $warehouseTransactions
                            ->sortBy([
                                ['created_at', 'asc'],
                                ['id', 'asc'],
                            ])
                            ->last();

                        return (float) ($lastTransaction?->balance_after ?? 0);
                    });
            }

            $movementCount = $transactions->count();
        @endphp

        <div class="print-area">

            {{-- Report Header --}}
            <div class="report-title-card mb-4">

                <div>
                    <h3 class="mb-1">تقرير حركة مخزون الصنف</h3>
                    <p class="mb-0">وازن ERP - نظام إدارة المبيعات والمخزون والمحاسبة</p>
                </div>

                <div class="report-print-date">
                    <span>تاريخ الطباعة</span>
                    <strong>{{ now()->format('Y-m-d h:i A') }}</strong>
                </div>

            </div>


            {{-- Report Info --}}
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="info-box">
                        <div class="info-label">الصنف</div>
                        <div class="info-value">
                            {{ $selectedProduct?->sku }} - {{ $selectedProduct?->product_name_ar }}
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-label">المستودع</div>
                        <div class="info-value">
                            {{ $selectedWarehouse?->warehouse_name ?? 'كل المستودعات' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-label">الفترة</div>
                        <div class="info-value">
                            من: {{ $fromDate ?: 'البداية' }}
                            <br>
                            إلى: {{ $toDate ?: 'اليوم' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="info-box">
                        <div class="info-label">عدد الحركات</div>
                        <div class="info-value">
                            {{ number_format($movementCount) }}
                        </div>
                    </div>
                </div>

            </div>


            {{-- Summary --}}
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="summary-box">
                        <div class="summary-icon in-icon">⬇️</div>
                        <div>
                            <div class="summary-label">إجمالي الداخل</div>
                            <div class="summary-value text-success">
                                {{ number_format($totalIn, 3) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-box">
                        <div class="summary-icon out-icon">⬆️</div>
                        <div>
                            <div class="summary-label">إجمالي الخارج</div>
                            <div class="summary-value text-danger">
                                {{ number_format($totalOut, 3) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-box">
                        <div class="summary-icon balance-icon">📊</div>
                        <div>
                            <div class="summary-label">
                                {{ !empty($warehouseId) ? 'آخر رصيد بعد الحركة' : 'إجمالي آخر رصيد حسب المستودعات' }}
                            </div>
                            <div class="summary-value">
                                {{ number_format($lastBalance, 3) }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            {{-- Movement Table --}}
            <div class="card shadow-sm wazin-card report-table-card">

                <div class="card-header wazin-card-header">
                    <div>
                        <h5 class="mb-0 fw-bold">تفاصيل حركة المخزون</h5>
                        <small>جميع الحركات المسجلة للصنف حسب الفلاتر المحددة</small>
                    </div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped table-hover text-center align-middle report-table">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ</th>
                                    <th>نوع الحركة</th>
                                    <th>رقم الحركة</th>
                                    <th>المستودع</th>
                                    <th>الوحدة</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>الرصيد قبل</th>
                                    <th>الرصيد بعد</th>
                                    <th>تكلفة الوحدة</th>
                                    <th>إجمالي التكلفة</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>

                            <tbody>

                                @if($transactions->isEmpty())

                                    <tr>
                                        <td colspan="13" class="text-muted py-4">
                                            لا توجد حركات لهذا الصنف خلال الفترة المحددة.
                                        </td>
                                    </tr>

                                @else

                                    @foreach($transactions as $index => $transaction)

                                        @php
                                            $quantity = (float) $transaction->quantity;

                                            $inQty = $quantity > 0 ? $quantity : 0;
                                            $outQty = $quantity < 0 ? abs($quantity) : 0;

                                            $typeName = match ($transaction->transaction_type) {
                                                'opening_balance' => 'رصيد افتتاحي',
                                                'purchase' => 'فاتورة مشتريات',
                                                'purchase_return' => 'مردود مشتريات',
                                                'sale' => 'فاتورة بيع',
                                                'sale_return' => 'مردود بيع',
                                                'stock_adjustment' => 'تسوية جرد مخزني ',
                                                'inventory_count' => 'تسوية مخزون',
                                                'transfer_in' => 'تحويل وارد',
                                                'transfer_out' => 'تحويل صادر',
                                                'damage' => 'إتلاف المخزون',
                                                default => $transaction->transaction_type,
                                            };
                                        @endphp

                                        <tr>
                                            <td>{{ $index + 1 }}</td>

                                            <td>
                                                {{ $transaction->created_at?->format('Y-m-d h:i A') }}
                                            </td>

                                            <td>
                                                @if($quantity > 0)
                                                    <span class="badge bg-success">
                                                        {{ $typeName }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        {{ $typeName }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="fw-bold ltr-cell">
                                                {{ $transaction->transaction_no }}
                                            </td>

                                            <td>
                                                {{ $transaction->warehouse?->warehouse_name ?? '-' }}
                                            </td>

                                            <td>
                                                {{ $transaction->productUnit?->unit?->unit_name ?? '-' }}
                                            </td>

                                            <td class="text-success fw-bold ltr-cell">
                                                {{ number_format($inQty, 3) }}
                                            </td>

                                            <td class="text-danger fw-bold ltr-cell">
                                                {{ number_format($outQty, 3) }}
                                            </td>

                                            <td class="ltr-cell">
                                                {{ number_format($transaction->balance_before, 3) }}
                                            </td>

                                            <td class="fw-bold ltr-cell">
                                                {{ number_format($transaction->balance_after, 3) }}
                                            </td>

                                            <td class="ltr-cell">
                                                {{ number_format($transaction->unit_cost, 2) }}
                                            </td>

                                            <td class="ltr-cell">
                                                {{ number_format($transaction->total_cost, 2) }}
                                            </td>

                                            <td class="text-start">
                                                {{ $transaction->notes ?? '-' }}
                                            </td>
                                        </tr>

                                    @endforeach

                                @endif

                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">
                                        الإجمالي
                                    </td>

                                    <td class="text-success ltr-cell">
                                        {{ number_format($totalIn, 3) }}
                                    </td>

                                    <td class="text-danger ltr-cell">
                                        {{ number_format($totalOut, 3) }}
                                    </td>

                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    @endif

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
        font-weight: 600;
        background-color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .empty-state {
        background: #fff;
        border: 1px dashed #CBD5E1;
        border-radius: 22px;
        padding: 50px 20px;
        text-align: center;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
    }

    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 24px;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin-bottom: 16px;
    }

    .empty-state h5 {
        color: #071633;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #64748B;
        font-weight: 700;
        margin-bottom: 0;
    }

    .report-title-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 22px;
        padding: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
    }

    .report-title-card h3 {
        color: #071633;
        font-weight: 900;
    }

    .report-title-card p {
        color: #64748B;
        font-weight: 700;
    }

    .report-print-date {
        background: #F8FAFC;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        padding: 12px 16px;
        min-width: 190px;
    }

    .report-print-date span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .report-print-date strong {
        color: #071633;
        font-weight: 900;
        direction: ltr;
        display: block;
    }

    .info-box,
    .summary-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        height: 100%;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
    }

    .info-label,
    .summary-label {
        color: #64748B;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .info-value {
        color: #071633;
        font-weight: 900;
        line-height: 1.8;
    }

    .summary-box {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .summary-icon {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
        flex: 0 0 auto;
    }

    .in-icon {
        background: rgba(22, 163, 74, 0.10);
        color: #16A34A;
    }

    .out-icon {
        background: rgba(230, 59, 74, 0.10);
        color: #E63B4A;
    }

    .balance-icon {
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
    }

    .summary-value {
        color: #071633;
        font-size: 24px;
        font-weight: 900;
        line-height: 1.2;
        direction: ltr;
        text-align: right;
    }

    .report-table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
        font-size: 13px;
    }

    .report-table tbody td {
        vertical-align: middle;
        font-weight: 600;
        font-size: 13px;
    }

    .report-table tfoot td {
        background: #F8FAFC !important;
        color: #071633;
        font-weight: 900;
        border-top: 2px solid #E5E7EB;
    }

    .ltr-cell {
        direction: ltr;
        text-align: center;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 10px;
        font-weight: 900;
    }

    .text-success {
        color: #16A34A !important;
    }

    .text-danger {
        color: #E63B4A !important;
    }

    @media (max-width: 768px) {
        .page-header-card,
        .wazin-card-header,
        .report-title-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .report-print-date {
            min-width: auto;
        }
    }


    @media print {
        body {
            background: #fff !important;
        }

        .no-print,
        .app-sidebar,
        nav,
        header,
        .navbar,
        .sidebar,
        .app-header {
            display: none !important;
        }

        .app-content {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }

        .print-area {
            padding: 0 !important;
        }

        .card,
        .wazin-card,
        .report-title-card,
        .info-box,
        .summary-box {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            border-radius: 8px !important;
        }

        .wazin-card-header,
        .card-body {
            padding: 10px !important;
        }

        .report-title-card {
            margin-bottom: 10px !important;
        }

        .report-title-card h3 {
            font-size: 18px !important;
        }

        .report-title-card p,
        .info-label,
        .summary-label,
        .report-print-date span {
            font-size: 11px !important;
        }

        .summary-value,
        .info-value {
            font-size: 13px !important;
        }

        table {
            font-size: 10px !important;
        }

        .report-table thead th,
        .report-table tbody td,
        .report-table tfoot td {
            font-size: 9px !important;
            padding: 4px !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }
    }
</style>
@push('scripts')
<script>
    $(document).ready(function () {
        const AJAX_PRODUCTS_URL = "{{ route('ajax-lookup.products') }}";

        $('#product_id').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: $('#product_id').data('placeholder') || 'ابحث عن الصنف',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: AJAX_PRODUCTS_URL,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            }
        });
    });
</script>
@endpush
</x-app-layout>