<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">سند تالف جديد</h3>
            <p class="page-subtitle mb-0">
                اختر المستودع، ثم أضف الأصناف والكميات التالفة.
            </p>
        </div>

        <a href="{{ route('inventory-damages.index') }}" class="btn btn-light fw-bold">
            رجوع
        </a>
    </div>

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

    <form method="POST" action="{{ route('inventory-damages.store') }}" id="damageForm">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">بيانات السند</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-bold">تاريخ السند <span class="text-danger">*</span></label>
                        <input type="date"
                               name="damage_date"
                               class="form-control"
                               value="{{ old('damage_date', now()->format('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">المستودع <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                            <option value="">اختر المستودع</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->warehouse_name }}
                                    @if($warehouse->warehouse_code)
                                        - {{ $warehouse->warehouse_code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">سبب التلف</label>
                        <input type="text"
                               name="reason"
                               class="form-control"
                               value="{{ old('reason') }}"
                               placeholder="مثال: كسر / انتهاء صلاحية / تلف أثناء النقل">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">إضافة بالباركود</label>
                        <input type="text"
                               id="barcode_input"
                               class="form-control"
                               placeholder="امسح الباركود هنا ثم Enter"
                               autocomplete="off">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">ملاحظات عامة</label>
                        <textarea name="notes"
                                  class="form-control"
                                  rows="3"
                                  placeholder="ملاحظات اختيارية">{{ old('notes') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">أصناف التالف</h5>

                <button type="button" class="btn btn-primary" id="addRowBtn">
                    + إضافة صنف
                </button>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle mb-0" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th width="320">الصنف</th>
                            <th width="180">الوحدة</th>
                            <th width="150">الكمية التالفة</th>
                            <th>ملاحظات</th>
                            <th width="80">حذف</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">إجمالي الكمية</th>
                            <th id="totalQty">0.000</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('inventory-damages.index') }}" class="btn btn-secondary">
                    إلغاء
                </a>

                <button type="submit" class="btn btn-success">
                    حفظ السند كمسودة
                </button>
            </div>
        </div>

    </form>

</div>

<template id="itemRowTemplate">
    <tr>
        <td class="row-number text-center"></td>

        <td>
            <select class="form-select productSelect" required></select>
        </td>

        <td>
            <select class="form-select unitSelect" required>
                <option value="">اختر الوحدة</option>
            </select>
        </td>

        <td>
            <input type="number"
                   step="0.001"
                   min="0.001"
                   class="form-control text-center quantityInput"
                   value="1.000"
                   required>
        </td>

        <td>
            <input type="text"
                   class="form-control notesInput"
                   placeholder="ملاحظات الصنف">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger removeRowBtn">
                حذف
            </button>
        </td>
    </tr>
</template>

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

@push('scripts')
<script>
    $(document).ready(function () {
        const AJAX_PRODUCTS_URL = "{{ route('ajax-lookup.products') }}";
        const AJAX_PRODUCT_BARCODE_URL = "{{ route('ajax-lookup.product-by-barcode') }}";
        const PRODUCT_UNITS_URL = "{{ route('inventory-damages.product-units', ':id') }}";

        function toNumber(value) {
            value = parseFloat(value);
            return isNaN(value) ? 0 : value;
        }

        function initProductSelect(row) {
            row.find('.productSelect').select2({
                dir: 'rtl',
                width: '100%',
                placeholder: 'ابحث باسم الصنف أو الكود أو الباركود',
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
        }

        function refreshRowNames() {
            $('#itemsTable tbody tr').each(function (index) {
                let row = $(this);

                row.find('.row-number').text(index + 1);

                row.find('.productSelect').attr('name', `items[${index}][product_id]`);
                row.find('.unitSelect').attr('name', `items[${index}][product_unit_id]`);
                row.find('.quantityInput').attr('name', `items[${index}][quantity]`);
                row.find('.notesInput').attr('name', `items[${index}][notes]`);
            });
        }

        function recalcTotals() {
            let totalQty = 0;

            $('#itemsTable tbody tr').each(function () {
                totalQty += toNumber($(this).find('.quantityInput').val());
            });

            $('#totalQty').text(totalQty.toFixed(3));
        }

        function loadProductUnits(row, productId, selectedUnitId = null) {
            let unitSelect = row.find('.unitSelect');

            unitSelect.html('<option value="">جاري التحميل...</option>');

            if (!productId) {
                unitSelect.html('<option value="">اختر الوحدة</option>');
                return;
            }

            let url = PRODUCT_UNITS_URL.replace(':id', productId);

            $.get(url, function (response) {
                unitSelect.html('<option value="">اختر الوحدة</option>');

                if (response.status === 'success') {
                    response.data.forEach(function (unit) {
                        let selected = String(selectedUnitId) === String(unit.id) ? 'selected' : '';

                        unitSelect.append(`
                            <option value="${unit.id}"
                                    ${selected}
                                    data-factor="${unit.factor ?? 1}">
                                ${unit.unit_name ?? '-'} - معامل ${unit.factor ?? 1}
                            </option>
                        `);
                    });

                    if (selectedUnitId) {
                        unitSelect.val(selectedUnitId).trigger('change');
                    } else if (response.data.length === 1) {
                        unitSelect.val(response.data[0].id).trigger('change');
                    } else {
                        let defaultUnit = response.data.find(unit => Number(unit.is_default) === 1);
                        if (defaultUnit) {
                            unitSelect.val(defaultUnit.id).trigger('change');
                        }
                    }
                }
            }).fail(function () {
                unitSelect.html('<option value="">خطأ في تحميل الوحدات</option>');
            });
        }

        function setProductSelectValue(row, productId, productText) {
            let select = row.find('.productSelect');

            if (!select.find(`option[value="${productId}"]`).length) {
                let option = new Option(productText, productId, true, true);
                select.append(option);
            }

            select.val(productId).trigger('change.select2');
        }

        function addRow(productData = null) {
            let template = $('#itemRowTemplate').html();
            let row = $(template);

            $('#itemsTable tbody').append(row);

            initProductSelect(row);
            refreshRowNames();

            if (productData) {
                setProductSelectValue(row, productData.id, productData.text);
                loadProductUnits(row, productData.id, productData.product_unit_id);
                row.find('.quantityInput').val('1.000');
            }

            recalcTotals();

            return row;
        }

        function findEmptyRow() {
            let emptyRow = null;

            $('#itemsTable tbody tr').each(function () {
                let row = $(this);

                if (!row.find('.productSelect').val()) {
                    emptyRow = row;
                    return false;
                }
            });

            return emptyRow;
        }

        function addProductByBarcode(product) {
            let existingRow = null;

            $('#itemsTable tbody tr').each(function () {
                let row = $(this);
                let productId = row.find('.productSelect').val();
                let unitId = row.find('.unitSelect').val();

                if (String(productId) === String(product.id)
                    && String(unitId) === String(product.product_unit_id)) {
                    existingRow = row;
                    return false;
                }
            });

            if (existingRow) {
                let qtyInput = existingRow.find('.quantityInput');
                qtyInput.val((toNumber(qtyInput.val()) + 1).toFixed(3));
                recalcTotals();
                return;
            }

            let row = findEmptyRow() || addRow();

            setProductSelectValue(row, product.id, product.text);
            loadProductUnits(row, product.id, product.product_unit_id);

            row.find('.quantityInput').val('1.000');

            recalcTotals();
        }

        $('#addRowBtn').on('click', function () {
            addRow();
        });

        $('#itemsTable').on('change', '.productSelect', function () {
            let row = $(this).closest('tr');
            let productId = $(this).val();

            loadProductUnits(row, productId);
        });

        $('#itemsTable').on('input', '.quantityInput', function () {
            recalcTotals();
        });

        $('#itemsTable').on('click', '.removeRowBtn', function () {
            $(this).closest('tr').remove();

            if ($('#itemsTable tbody tr').length === 0) {
                addRow();
            }

            refreshRowNames();
            recalcTotals();
        });

        $('#barcode_input').on('keydown', function (e) {
            if (e.key !== 'Enter') return;

            e.preventDefault();

            let barcode = $(this).val().trim();

            if (!barcode) return;

            $.ajax({
                url: AJAX_PRODUCT_BARCODE_URL,
                method: 'GET',
                data: { barcode },
                success: function (product) {
                    $('#barcode_input').val('').focus();
                    addProductByBarcode(product);
                },
                error: function (xhr) {
                    let message = xhr.responseJSON?.message || 'لم يتم العثور على الصنف بهذا الباركود.';
                    alert(message);
                    $('#barcode_input').val('').focus();
                }
            });
        });

        $('#damageForm').on('submit', function (e) {
            let validItems = 0;

            $('#itemsTable tbody tr').each(function () {
                let row = $(this);

                if (row.find('.productSelect').val()
                    && row.find('.unitSelect').val()
                    && toNumber(row.find('.quantityInput').val()) > 0) {
                    validItems++;
                }
            });

            if (validItems === 0) {
                e.preventDefault();
                alert('يرجى إضافة صنف واحد على الأقل.');
                return false;
            }
        });

        addRow();

        setTimeout(function () {
            $('#barcode_input').focus();
        }, 500);
    });
</script>
@endpush

</x-app-layout>