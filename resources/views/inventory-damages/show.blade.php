<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">سند تالف رقم: {{ $inventoryDamage->damage_no }}</h3>
            <p class="page-subtitle mb-0">
                مراجعة تفاصيل سند التالف وترحيله أو إلغاؤه.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory-damages.index') }}" class="btn btn-light fw-bold">
                رجوع
            </a>

            @can('inventory_damages.print')
                <a href="{{ route('inventory-damages.print', $inventoryDamage) }}"
                   target="_blank"
                   class="btn btn-outline-light fw-bold">
                    طباعة
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fw-bold">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger fw-bold">{{ session('error') }}</div>
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
                <span>تاريخ السند</span>
                <strong>{{ optional($inventoryDamage->damage_date)->format('Y-m-d') }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>المستودع</span>
                <strong>{{ $inventoryDamage->warehouse?->warehouse_name ?? '-' }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>سبب التلف</span>
                <strong>{{ $inventoryDamage->reason ?? '-' }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-card">
                <span>الحالة</span>
                <strong>
                    @if($inventoryDamage->status === 'draft')
                        <span class="badge bg-warning text-dark">مسودة</span>
                    @elseif($inventoryDamage->status === 'posted')
                        <span class="badge bg-success">مرحل</span>
                    @elseif($inventoryDamage->status === 'cancelled')
                        <span class="badge bg-danger">ملغي</span>
                    @else
                        <span class="badge bg-secondary">{{ $inventoryDamage->status }}</span>
                    @endif
                </strong>
            </div>
        </div>

    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">أصناف التالف</h5>

            @if($inventoryDamage->isDraft())
                @can('inventory_damages.post')
                    <button type="submit"
                            form="postInventoryDamageForm"
                            class="btn btn-success"
                            onclick="return confirm('هل تريد ترحيل سند التالف؟ سيتم خصم الكميات من المخزون.');">
                        ترحيل السند
                    </button>
                @endcan
            @elseif($inventoryDamage->isPosted())
                @can('inventory_damages.cancel')
                    <button type="submit"
                            form="cancelInventoryDamageForm"
                            class="btn btn-danger"
                            onclick="return confirm('هل تريد إلغاء سند التالف؟ سيتم إرجاع الكميات للمخزون.');">
                        إلغاء السند المرحل
                    </button>
                @endcan
            @endif
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>الكود</th>
                        <th>الصنف</th>
                        <th>الوحدة</th>
                        <th>الكمية المدخلة</th>
                        <th>كمية الأساس</th>
                        <th>تكلفة الوحدة</th>
                        <th>إجمالي التكلفة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $totalQty = 0;
                        $totalBaseQty = 0;
                        $totalCost = 0;
                    @endphp

                    @foreach($inventoryDamage->items as $item)
                        @php
                            $totalQty += (float) $item->quantity;
                            $totalBaseQty += (float) $item->base_quantity;
                            $totalCost += (float) $item->total_cost;
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $item->product?->sku ?? '-' }}</td>
                            <td>{{ $item->product?->product_name_ar ?? '-' }}</td>
                            <td>{{ $item->productUnit?->unit?->unit_name_ar ?? $item->productUnit?->unit?->unit_name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->quantity, 3) }}</td>
                            <td>{{ number_format((float) $item->base_quantity, 3) }}</td>
                            <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td>{{ number_format((float) $item->total_cost, 2) }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">الإجمالي</th>
                        <th>{{ number_format($totalQty, 3) }}</th>
                        <th>{{ number_format($totalBaseQty, 3) }}</th>
                        <th></th>
                        <th>{{ number_format($totalCost, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($inventoryDamage->notes)
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">ملاحظات</h5>
            </div>
            <div class="card-body fw-bold">
                {{ $inventoryDamage->notes }}
            </div>
        </div>
    @endif

    @if($inventoryDamage->isDraft())
        @can('inventory_damages.post')
            <form id="postInventoryDamageForm"
                  method="POST"
                  action="{{ route('inventory-damages.post', $inventoryDamage) }}">
                @csrf
            </form>
        @endcan
    @endif

    @if($inventoryDamage->isPosted())
        @can('inventory_damages.cancel')
            <form id="cancelInventoryDamageForm"
                  method="POST"
                  action="{{ route('inventory-damages.cancel', $inventoryDamage) }}">
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

    .btn {
        border-radius: 12px;
        font-weight: 800;
        padding: 9px 16px;
    }
</style>

</x-app-layout>