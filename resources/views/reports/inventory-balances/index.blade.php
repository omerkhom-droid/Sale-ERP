<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">تقرير أرصدة وتقييم المخزون</h3>
            <p class="page-subtitle mb-0">
                عرض الرصيد الحالي لكل صنف حسب المستودع مع التقييم بمتوسط التكلفة.
            </p>
        </div>

        <button type="button" onclick="window.print()" class="btn btn-light fw-bold">
            طباعة
        </button>
    </div>

    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">الفلاتر</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('reports.inventory-balances.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label fw-bold">المستودع</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">كل المستودعات</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((int) $warehouseId === (int) $warehouse->id)>
                                    {{ $warehouse->warehouse_name }}
                                    @if($warehouse->warehouse_code)
                                        - {{ $warehouse->warehouse_code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">الصنف</label>
                        <select name="product_id" id="product_id" class="form-select">
                            @if($selectedProduct)
                                <option value="{{ $selectedProduct->id }}" selected>
                                    {{ $selectedProduct->sku ? $selectedProduct->sku . ' - ' : '' }}
                                    {{ $selectedProduct->product_name_ar ?? $selectedProduct->product_name_en }}
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="show_zero"
                                   value="1"
                                   id="show_zero"
                                   @checked($showZero)>
                            <label class="form-check-label fw-bold" for="show_zero">
                                إظهار الأرصدة الصفرية
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            عرض التقرير
                        </button>

                        <a href="{{ route('reports.inventory-balances.index') }}" class="btn btn-secondary">
                            مسح الفلاتر
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="print-header d-none d-print-block mb-4">
        <h3>تقرير أرصدة وتقييم المخزون</h3>
        <p>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-card">
                <span>عدد السجلات</span>
                <strong>{{ number_format($stocks->total()) }}</strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <span>إجمالي قيمة المخزون</span>
                <strong>{{ number_format((float) $totalValue, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <span>طريقة التقييم</span>
                <strong>متوسط التكلفة</strong>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">نتائج التقرير</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>كود الصنف</th>
                        <th>اسم الصنف</th>
                        <th>المستودع</th>
                        <th>الرصيد الحالي</th>
                        <th>متوسط التكلفة</th>
                        <th>قيمة المخزون</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($stocks as $row)
                        <tr>
                            <td>{{ $loop->iteration + (($stocks->currentPage() - 1) * $stocks->perPage()) }}</td>
                            <td class="fw-bold">{{ $row->sku ?? '-' }}</td>
                            <td>{{ $row->product_name_ar ?? $row->product_name_en ?? '-' }}</td>
                            <td>
                                {{ $row->warehouse_name ?? '-' }}
                                @if($row->warehouse_code)
                                    <span class="text-muted">- {{ $row->warehouse_code }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format((float) $row->quantity, 3) }}</td>
                            <td class="text-center">{{ number_format((float) $row->average_cost, 2) }}</td>
                            <td class="text-center fw-bold">{{ number_format((float) $row->stock_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted fw-bold py-4">
                                لا توجد أرصدة مطابقة للفلاتر.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">إجمالي قيمة المخزون</th>
                        <th class="text-center">{{ number_format((float) $totalValue, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card-footer bg-white no-print">
            {{ $stocks->links() }}
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

    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .summary-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .summary-card strong {
        color: #0F172A;
        font-weight: 900;
        font-size: 20px;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        min-height: 42px;
        font-weight: 700;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-weight: 700;
    }

    .btn {
        border-radius: 12px;
        font-weight: 800;
        padding: 9px 16px;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
        }

        .table th {
            background: #f1f5f9 !important;
            color: #000 !important;
        }
    }
</style>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#product_id').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'كل الأصناف',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('ajax-lookup.products') }}",
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