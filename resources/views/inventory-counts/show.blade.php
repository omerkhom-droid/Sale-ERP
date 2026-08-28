<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">جرد رقم: {{ $inventoryCount->count_no }}</h3>
            <p class="page-subtitle mb-0">
                أدخل الكمية الفعلية لكل صنف، ثم احفظ، ثم رحّل الجرد.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory-counts.index') }}" class="btn btn-light fw-bold">
                رجوع
            </a>

            @can('inventory_counts.print')
                <a href="{{ route('inventory-counts.print', $inventoryCount) }}"
                   target="_blank"
                   class="btn btn-outline-light fw-bold">
                    طباعة
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fw-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger fw-bold">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="info-card">
                <span>المستودع</span>
                <strong>{{ $inventoryCount->warehouse?->warehouse_name ?? '-' }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>تاريخ الجرد</span>
                <strong>{{ optional($inventoryCount->count_date)->format('Y-m-d') }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>الحالة</span>
                <strong>
                    @if($inventoryCount->status === 'draft')
                        <span class="badge bg-warning text-white">مسودة</span>
                    @elseif($inventoryCount->status === 'posted')
                        <span class="badge bg-success text-white">مرحل</span>
                    @elseif($inventoryCount->status === 'cancelled')
                        <span class="badge bg-danger text-white">ملغي</span>
                    @else
                        <span class="badge bg-secondary">{{ $inventoryCount->status }}</span>
                    @endif
                </strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>عدد الأصناف</span>
                <strong>{{ $inventoryCount->items->count() }}</strong>
            </div>
        </div>

    </div>

    <form method="POST" action="{{ route('inventory-counts.items.update', $inventoryCount) }}">
        @csrf
        @method('PUT')

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">أصناف الجرد</h5>

                @if($inventoryCount->isDraft())
                    @can('inventory_counts.edit')
                        <button type="submit" class="btn btn-primary">
                            حفظ الكميات
                        </button>
                    @endcan
                @endif
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>الكود</th>
                            <th>الصنف</th>
                            <th>الوحدة</th>
                            <th>كمية النظام</th>
                            <th width="160">الكمية الفعلية</th>
                            <th>الفرق</th>
                            <th>تكلفة الوحدة</th>
                            <th>قيمة الفرق</th>
                            <th width="220">ملاحظات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($inventoryCount->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td class="fw-bold">
                                    {{ $item->product?->sku ?? '-' }}
                                </td>

                                <td>
                                    {{ $item->product?->product_name_ar ?? '-' }}
                                </td>

                                <td>
                                    {{ $item->productUnit?->unit?->unit_name ?? '-' }}
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control text-center system-quantity"
                                           value="{{ number_format((float) $item->system_quantity, 3, '.', '') }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="number"
                                           step="0.001"
                                           min="0"
                                           name="items[{{ $item->id }}][actual_quantity]"
                                           class="form-control text-center actual-quantity"
                                           value="{{ $item->actual_quantity !== null
                                            ? number_format((float) $item->actual_quantity, 3, '.', '')
                                            : ((float) $item->system_quantity == 0.0 ? '0.000' : '') }}"
                                           @disabled(! $inventoryCount->isDraft())>
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control text-center variance-quantity"
                                           value="{{ number_format((float) $item->variance_quantity, 3, '.', '') }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control text-center unit-cost"
                                           value="{{ number_format((float) $item->unit_cost, 2, '.', '') }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control text-center total-variance-cost"
                                           value="{{ number_format((float) $item->total_variance_cost, 2, '.', '') }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="text"
                                           name="items[{{ $item->id }}][notes]"
                                           class="form-control"
                                           value="{{ $item->notes }}"
                                           @disabled(! $inventoryCount->isDraft())>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">الإجمالي</th>
                            <th id="totalVarianceQty">0.000</th>
                            <th></th>
                            <th id="totalVarianceCost">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($inventoryCount->isDraft())
                <div class="card-footer bg-white d-flex justify-content-between flex-wrap gap-2">
                    @can('inventory_counts.edit')
                        <button type="submit" class="btn btn-primary">
                            حفظ الكميات
                        </button>
                    @endcan

                    @can('inventory_counts.post')
                        <button type="submit"
                                form="postInventoryCountForm"
                                class="btn btn-success"
                                onclick="return confirm('هل تريد ترحيل الجرد؟ بعد الترحيل سيتم إنشاء حركات تسوية مخزون.');">
                            ترحيل الجرد
                        </button>
                    @endcan
                </div>
            @elseif($inventoryCount->isPosted())
                <div class="card-footer bg-white d-flex justify-content-end">
                    @can('inventory_counts.cancel')
                        <button type="submit"
                                form="cancelInventoryCountForm"
                                class="btn btn-danger"
                                onclick="return confirm('هل تريد إلغاء الجرد؟ سيتم إنشاء حركة عكسية للفروقات.');">
                            إلغاء الجرد المرحل
                        </button>
                    @endcan
                </div>
            @endif
        </div>
    </form>


    @if($inventoryCount->isDraft())
        @can('inventory_counts.post')
            <form id="postInventoryCountForm"
                  method="POST"
                  action="{{ route('inventory-counts.post', $inventoryCount) }}">
                @csrf
            </form>
        @endcan
    @endif

    @if($inventoryCount->isPosted())
        @can('inventory_counts.cancel')
            <form id="cancelInventoryCountForm"
                  method="POST"
                  action="{{ route('inventory-counts.cancel', $inventoryCount) }}">
                @csrf
            </form>
        @endcan
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

    .info-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .info-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .info-card strong {
        color: #0F172A;
        font-weight: 900;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-weight: 700;
    }

    .form-control {
        border-radius: 10px;
        font-weight: 700;
        min-height: 38px;
    }

    .btn {
        border-radius: 12px;
        font-weight: 800;
        padding: 9px 16px;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function () {
        function toNumber(value) {
            value = parseFloat(value);
            return isNaN(value) ? 0 : value;
        }

        function recalcRow(row) {
            let systemQty = toNumber(row.find('.system-quantity').val());
            let actualInput = row.find('.actual-quantity').val();
            let unitCost = toNumber(row.find('.unit-cost').val());

            if (actualInput === '') {
                row.find('.variance-quantity').val('0.000');
                row.find('.total-variance-cost').val('0.00');
                return;
            }

            let actualQty = toNumber(actualInput);
            let variance = actualQty - systemQty;
            let totalCost = variance * unitCost;

            row.find('.variance-quantity').val(variance.toFixed(3));
            row.find('.total-variance-cost').val(totalCost.toFixed(2));
        }

        function recalcTotals() {
            let totalQty = 0;
            let totalCost = 0;

            $('#itemsTable tbody tr').each(function () {
                let row = $(this);

                totalQty += toNumber(row.find('.variance-quantity').val());
                totalCost += toNumber(row.find('.total-variance-cost').val());
            });

            $('#totalVarianceQty').text(totalQty.toFixed(3));
            $('#totalVarianceCost').text(totalCost.toFixed(2));
        }

        $('.actual-quantity').on('input', function () {
            let row = $(this).closest('tr');
            recalcRow(row);
            recalcTotals();
        });

        $('#itemsTable tbody tr').each(function () {
            recalcRow($(this));
        });

        recalcTotals();
    });
</script>
@endpush

</x-app-layout>