<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">إنشاء جرد مخزني</h3>
            <p class="page-subtitle mb-0">
                اختر المستودع ونطاق الجرد، وسيتم إنشاء قائمة الأصناف حسب الرصيد الحالي في النظام.
            </p>
        </div>

        <a href="{{ route('inventory-counts.index') }}" class="btn btn-light fw-bold">
            رجوع
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger fw-bold">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('inventory-counts.store') }}">
        @csrf

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">بيانات الجرد</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-bold">المستودع <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">اختر المستودع</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    @selected(old('warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->warehouse_name }}
                                    @if($warehouse->warehouse_code)
                                        - {{ $warehouse->warehouse_code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">تاريخ الجرد <span class="text-danger">*</span></label>
                        <input type="date"
                               name="count_date"
                               class="form-control"
                               value="{{ old('count_date', now()->format('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">نطاق الجرد <span class="text-danger">*</span></label>
                        <select name="scope_type" id="scope_type" class="form-select" required>
                            <option value="all" @selected(old('scope_type') === 'all')>
                                كل المنتجات
                            </option>
                            <option value="category" @selected(old('scope_type') === 'category')>
                                حسب التصنيف
                            </option>
                            <option value="brand" @selected(old('scope_type') === 'brand')>
                                حسب البراند
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4 d-none" id="category_box">
                        <label class="form-label fw-bold">التصنيف</label>
                        <select id="category_scope_id" class="form-select scope-select">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('scope_type') === 'category' && old('scope_id') == $category->id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-none" id="brand_box">
                        <label class="form-label fw-bold">البراند</label>
                        <select id="brand_scope_id" class="form-select scope-select">
                            <option value="">اختر البراند</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}"
                                    @selected(old('scope_type') === 'brand' && old('scope_id') == $brand->id)>
                                    {{ $brand->brand_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="scope_id" id="scope_id" value="{{ old('scope_id') }}">

                    <div class="col-md-12">
                        <label class="form-label fw-bold">ملاحظات</label>
                        <textarea name="notes"
                                  class="form-control"
                                  rows="3"
                                  placeholder="ملاحظات اختيارية">{{ old('notes') }}</textarea>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('inventory-counts.index') }}" class="btn btn-secondary">
                    إلغاء
                </a>

                <button type="submit" class="btn btn-success">
                    إنشاء الجرد
                </button>
            </div>
        </div>
    </form>

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

    .form-control,
    .form-select {
        border-radius: 12px;
        min-height: 42px;
        font-weight: 700;
    }

    .btn {
        border-radius: 12px;
        font-weight: 800;
        padding: 10px 18px;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function () {
        function toggleScope() {
            let type = $('#scope_type').val();

            $('#category_box').addClass('d-none');
            $('#brand_box').addClass('d-none');

            if (type === 'all') {
                $('#scope_id').val('');
            }

            if (type === 'category') {
                $('#category_box').removeClass('d-none');
                $('#scope_id').val($('#category_scope_id').val());
            }

            if (type === 'brand') {
                $('#brand_box').removeClass('d-none');
                $('#scope_id').val($('#brand_scope_id').val());
            }
        }

        $('#scope_type').on('change', function () {
            $('#category_scope_id').val('');
            $('#brand_scope_id').val('');
            toggleScope();
        });

        $('#category_scope_id').on('change', function () {
            if ($('#scope_type').val() === 'category') {
                $('#scope_id').val($(this).val());
            }
        });

        $('#brand_scope_id').on('change', function () {
            if ($('#scope_type').val() === 'brand') {
                $('#scope_id').val($(this).val());
            }
        });

        toggleScope();
    });
</script>
@endpush

</x-app-layout>