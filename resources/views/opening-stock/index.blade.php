<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">

        <div>
            <h3 class="page-title mb-1">الرصيد الافتتاحي للمخزون</h3>
            <p class="page-subtitle mb-0">
                إدخال كميات وتكاليف بداية التشغيل لكل صنف داخل المستودعات.
            </p>
        </div>

        @can('opening_stock.create')
            <button type="button" id="add_button" class="btn btn-primary">
                + إضافة رصيد افتتاحي
            </button>
        @endcan

    </div>


    {{-- Summary --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-box">
                <div class="summary-icon">📦</div>
                <div>
                    <div class="summary-label">عدد المنتجات النشطة</div>
                    <div class="summary-value">{{ $productsCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">
                <div class="summary-icon">🏬</div>
                <div>
                    <div class="summary-label">عدد المستودعات النشطة</div>
                    <div class="summary-value">{{ $warehouses->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">
                <div class="summary-icon">📥</div>
                <div>
                    <div class="summary-label">نوع الحركة</div>
                    <div class="summary-value fs-6">رصيد افتتاحي</div>
                </div>
            </div>
        </div>

    </div>


    {{-- Table --}}
    <div class="card shadow-sm wazin-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">سجل الأرصدة الافتتاحية</h5>
                <small>جميع الحركات المسجلة تظهر هنا حسب الأحدث</small>
            </div>

            <div class="table-hint">
                لا يفضل حذف أو تعديل الرصيد بعد اعتماده
            </div>
        </div>

        <div class="card-body">

            <div id="alert_action"></div>

            <div class="table-responsive">
                <table id="openingStockTable"
                       class="table table-bordered table-striped table-hover align-middle text-center w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصنف</th>
                            <th>المستودع</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>كمية الأساس</th>
                            <th>تكلفة الوحدة</th>
                            <th>الإجمالي</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>

    </div>

</div>


{{-- Modal --}}
@can('opening_stock.create')
<div class="modal fade" id="openingStockModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">

    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">

        <form id="opening_stock_form" class="modal-content">
            @csrf

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        إضافة رصيد افتتاحي
                    </h5>
                    <small>
                        اختر الصنف والمستودع والوحدة ثم أدخل الكمية والتكلفة
                    </small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>


            <div class="modal-body">

                <div id="form_errors"></div>

                <div class="form-section-title">
                    بيانات الصنف
                </div>

                <div class="row g-3 mb-4">

                    <div class="col-md-12"><div class="col-md-12">
                        <label class="form-label">إضافة بالباركود</label>
                        <input type="text"
                               id="barcode_input"
                               class="form-control"
                               placeholder="امسح الباركود هنا ثم Enter"
                               autocomplete="off">
                    </div>
                        <label class="form-label">الصنف <span class="text-danger">*</span></label>
                        <select name="product_id"
                                id="product_id"
                                class="form-select"
                                required>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">المستودع <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                            <option value="">اختر المستودع</option>

                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الوحدة <span class="text-danger">*</span></label>
                        <select name="product_unit_id" id="product_unit_id" class="form-select" required>
                            <option value="">اختر الوحدة</option>
                        </select>
                    </div>

                </div>


                <div class="form-section-title">
                    الكمية والتكلفة
                </div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number"
                               step="0.001"
                               min="0.001"
                               name="quantity"
                               id="quantity"
                               class="form-control"
                               placeholder="0.000"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تكلفة الوحدة <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="unit_cost"
                                   id="unit_cost"
                                   class="form-control"
                                   value="0"
                                   required>
                            <span class="input-group-text">ر.س</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الإجمالي</label>
                        <div class="input-group">
                            <input type="text"
                                   id="total_cost_preview"
                                   class="form-control total-preview"
                                   value="0.00"
                                   readonly>
                            <span class="input-group-text">ر.س</span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes"
                                  id="notes"
                                  class="form-control"
                                  rows="3"
                                  placeholder="أي ملاحظات تخص الرصيد الافتتاحي"></textarea>
                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    إلغاء
                </button>

                <button type="submit" id="action" class="btn btn-primary">
                    حفظ الرصيد الافتتاحي
                </button>

            </div>

        </form>

    </div>

</div>
@endcan


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

    .summary-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
        height: 100%;
    }

    .summary-icon {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
        flex: 0 0 auto;
    }

    .summary-label {
        color: #64748B;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .summary-value {
        color: #071633;
        font-size: 24px;
        font-weight: 900;
        line-height: 1.2;
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

    .table-hint {
        background: rgba(230, 59, 74, 0.08);
        color: #CC2F3D;
        border: 1px solid rgba(230, 59, 74, 0.20);
        padding: 8px 13px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    #openingStockTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    #openingStockTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #openingStockTable td:nth-child(5),
    #openingStockTable td:nth-child(6),
    #openingStockTable td:nth-child(7),
    #openingStockTable td:nth-child(8) {
        direction: ltr;
        font-weight: 900;
        color: #071633;
    }

    .modal-header {
        background: #071633;
        color: #fff;
        border-bottom: 0;
        padding: 18px 22px;
    }

    .modal-header small {
        color: #CFEFF3;
        font-weight: 600;
    }

    .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(7, 22, 51, 0.22);
    }

    .modal-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .modal-footer {
        background: #fff;
        border-top: 1px solid #E5E7EB;
        padding: 16px 22px;
    }

    .form-section-title {
        color: #071633;
        font-weight: 900;
        margin: 0 0 14px;
        padding: 10px 14px;
        background: #fff;
        border-right: 5px solid #2F6BFF;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
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

    textarea.form-control {
        min-height: 90px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .input-group .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .input-group .input-group-text {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-color: #E5E7EB;
        background: #fff;
        color: #071633;
        font-weight: 900;
    }

    .total-preview {
        background: #F1F5F9 !important;
        color: #2F6BFF !important;
        font-weight: 900;
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

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    /* DataTables Wazin Style */
    #openingStockTable_wrapper {
        direction: rtl;
    }

    #openingStockTable_wrapper .dataTables_length,
    #openingStockTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #openingStockTable_wrapper .dataTables_length label,
    #openingStockTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #openingStockTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #openingStockTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #openingStockTable_wrapper .dataTables_filter input {
        width: 280px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 8px 14px;
        margin: 0;
        outline: none;
        color: #111827;
        font-weight: 700;
        background: #fff;
        transition: all .18s ease-in-out;
    }

    #openingStockTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #openingStockTable_wrapper .dataTables_length select {
        width: 90px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 6px 12px;
        margin: 0 8px;
        outline: none;
        color: #111827;
        font-weight: 800;
        background: #fff;
    }

    #openingStockTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #openingStockTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #openingStockTable_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #E5E7EB !important;
        background: #fff !important;
        color: #071633 !important;
        border-radius: 12px !important;
        padding: 8px 14px !important;
        margin: 0 2px !important;
        font-weight: 900;
        cursor: pointer;
        transition: all .18s ease-in-out;
    }

    #openingStockTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #openingStockTable_wrapper .dataTables_paginate .paginate_button.current,
    #openingStockTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #openingStockTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #openingStockTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #openingStockTable_wrapper::after {
        content: "";
        display: block;
        clear: both;
    }

    @media (max-width: 768px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .table-hint {
            width: fit-content;
        }

        #openingStockTable_wrapper .dataTables_filter,
        #openingStockTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #openingStockTable_wrapper .dataTables_length label,
        #openingStockTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #openingStockTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #openingStockTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')

<script>
$(function () {

    const AJAX_PRODUCTS_URL = "{{ route('ajax-lookup.products') }}";
    const AJAX_PRODUCT_BARCODE_URL = "{{ route('ajax-lookup.product-by-barcode') }}";
    const PRODUCT_UNITS_URL = "{{ route('opening-stock.product-units', ':id') }}";

    const table = $('#openingStockTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('opening-stock.fetch') }}",
        language: {
            processing: 'جاري المعالجة...',
            search: 'بحث:',
            lengthMenu: 'عرض _MENU_ سجل',
            info: 'إظهار _START_ إلى _END_ من أصل _TOTAL_ سجل',
            infoEmpty: 'لا توجد سجلات',
            infoFiltered: '(تمت التصفية من أصل _MAX_ سجل)',
            loadingRecords: 'جاري التحميل...',
            zeroRecords: 'لا توجد بيانات مطابقة',
            emptyTable: 'لا توجد بيانات متاحة',
            paginate: {
                first: 'الأول',
                previous: 'السابق',
                next: 'التالي',
                last: 'الأخير'
            }
        },
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'product_name'},
            {data: 'warehouse_name'},
            {data: 'unit_name'},
            {data: 'quantity'},
            {data: 'base_quantity'},
            {data: 'unit_cost'},
            {data: 'total_cost'},
            {data: 'created_at'},
        ],
        order: [[8, 'desc']]
    });


    function initProductSelect() {
        $('#product_id').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#openingStockModal'),
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

    function setProductSelectValue(productId, productText) {
        let select = $('#product_id');

        if (!select.find(`option[value="${productId}"]`).length) {
            let option = new Option(productText, productId, true, true);
            select.append(option);
        }

        select.val(productId).trigger('change.select2');
    }

    function loadProductUnits(productId, selectedUnitId = null) {
        $('#product_unit_id').html('<option value="">جاري التحميل...</option>');
        $('#unit_cost').val('0');

        if (!productId) {
            $('#product_unit_id').html('<option value="">اختر الوحدة</option>');
            calculateTotal();
            return;
        }

        let url = PRODUCT_UNITS_URL.replace(':id', productId);

        $.get(url, function (response) {
            $('#product_unit_id').html('<option value="">اختر الوحدة</option>');

            if (response.status === 'success') {
                response.data.forEach(function (unit) {
                    let selected = String(selectedUnitId) === String(unit.id) ? 'selected' : '';

                    $('#product_unit_id').append(`
                        <option value="${unit.id}"
                                ${selected}
                                data-factor="${unit.factor ?? 1}"
                                data-cost="${unit.purchase_price ?? 0}">
                            ${unit.unit_name ?? '-'} - معامل ${unit.factor ?? 1}
                        </option>
                    `);
                });

                if (selectedUnitId) {
                    $('#product_unit_id').val(selectedUnitId).trigger('change');
                } else if (response.data.length === 1) {
                    $('#product_unit_id').val(response.data[0].id).trigger('change');
                }
            }

            calculateTotal();
        }).fail(function () {
            $('#product_unit_id').html('<option value="">خطأ</option>');
            calculateTotal();
        });
    }

    $('#add_button').on('click', function () {
        resetOpeningStockForm();
        $('#openingStockModal').modal('show');
    });


    $('#product_id').on('change', function () {
        let productId = $(this).val();

        $('#product_unit_id').html('<option value="">اختر الوحدة</option>');
        $('#unit_cost').val('0');

        if (!productId) {
            calculateTotal();
            return;
        }

        loadProductUnits(productId);
    });


    $('#barcode_input').on('keydown', function (e) {
        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();

        let barcode = $(this).val().trim();

        if (!barcode) {
            return;
        }

        $.ajax({
            url: AJAX_PRODUCT_BARCODE_URL,
            method: 'GET',
            data: {
                barcode: barcode
            },
            success: function (product) {
                $('#barcode_input').val('').focus();

                setProductSelectValue(product.id, product.text);

                loadProductUnits(product.id, product.product_unit_id);
            },
            error: function (xhr) {
                let message = xhr.responseJSON?.message || 'لم يتم العثور على الصنف بهذا الباركود.';

                showFormError(message);

                $('#barcode_input').val('').focus();
            }
        });
    });


    $('#product_unit_id').on('change', function () {
        let cost = $(this).find(':selected').data('cost') ?? 0;

        $('#unit_cost').val(cost);

        calculateTotal();
    });


    $('#quantity, #unit_cost').on('input', function () {
        calculateTotal();
    });


    $('#opening_stock_form').on('submit', function (e) {
        e.preventDefault();

        $('#action').prop('disabled', true).text('جاري الحفظ...');
        $('#form_errors').html('');

        $.ajax({
            url: "{{ route('opening-stock.store') }}",
            type: "POST",
            data: $(this).serialize(),
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {

                $('#openingStockModal').modal('hide');

                resetOpeningStockForm();

                table.ajax.reload(null, false);

                showSuccess(response.message ?? 'تم حفظ الرصيد الافتتاحي بنجاح.');

                $('#action').prop('disabled', false).text('حفظ الرصيد الافتتاحي');
            },
            error: function (xhr) {

                $('#action').prop('disabled', false).text('حفظ الرصيد الافتتاحي');

                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors);
                    return;
                }

                if (xhr.status === 403) {
                    showFormError('لا توجد لديك صلاحية لتنفيذ هذه العملية.');
                    return;
                }

                showFormError(xhr.responseJSON?.message ?? 'حدث خطأ غير متوقع أثناء الحفظ.');
            }
        });
    });


    function resetOpeningStockForm() {
        $('#opening_stock_form')[0].reset();

        $('#product_id').val(null).trigger('change');
        $('#product_unit_id').html('<option value="">اختر الوحدة</option>');

        $('#total_cost_preview').val('0.00');
        $('#form_errors').html('');
        $('#action').prop('disabled', false).text('حفظ الرصيد الافتتاحي');

        setTimeout(function () {
            $('#barcode_input').focus();
        }, 400);
    }


    function calculateTotal() {
        let quantity = parseFloat($('#quantity').val()) || 0;
        let unitCost = parseFloat($('#unit_cost').val()) || 0;
        let total = quantity * unitCost;

        $('#total_cost_preview').val(total.toFixed(2));
    }


    function showValidationErrors(errors) {
        let html = '<div class="alert alert-danger"><strong>يرجى مراجعة البيانات التالية:</strong><ul class="mb-0 mt-2">';

        $.each(errors, function (key, value) {
            html += `<li>${value[0]}</li>`;
        });

        html += '</ul></div>';

        $('#form_errors').html(html);
    }


    function showFormError(message) {
        $('#form_errors').html(`
            <div class="alert alert-danger">
                ${message}
            </div>
        `);
    }


    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم',
                text: message,
                timer: 1200,
                showConfirmButton: false
            });
        } else {
            showPageAlert(message, 'success');
        }
    }

    function showPageAlert(message, type = 'success') {
        $('#alert_action').html(`
            <div class="alert alert-${type} mb-4">
                ${message}
            </div>
        `);

        setTimeout(function () {
            $('#alert_action').html('');
        }, 3500);
    }
    
    initProductSelect();

});
</script>
@endpush

</x-app-layout>