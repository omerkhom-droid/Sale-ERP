<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form id="purchase_invoice_form">
        @csrf

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">فاتورة مشتريات جديدة</h3>
                <p class="page-subtitle mb-0">
                    إنشاء فاتورة مشتريات وإضافة الأصناف مع احتساب الضريبة والخصم والمدفوع والمتبقي تلقائيًا.
                </p>
            </div>

            <a href="{{ route('purchase-invoices.index') }}" class="btn btn-secondary">
                رجوع
            </a>
        </div>


        {{-- Errors --}}
        <div id="form_errors"></div>


        {{-- Invoice Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات الفاتورة</h5>
                    <small>المورد والفرع والمستودع والتاريخ وحساب الدفع</small>
                </div>
            </div>

            <div class="card-body">

                <input type="hidden"
                       name="invoice_no"
                       id="invoice_no"
                       class="form-control"
                       placeholder="اتركه فارغ للتوليد التلقائي">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">المورد <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">اختر المورد</option>

                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">
                                    {{ $supplier->supplier_name ?? $supplier->name ?? 'مورد #' . $supplier->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الفرع <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">اختر الفرع</option>

                            @foreach($branches as $branch)
                                @php
                                    $branchId = $branch->branch_id ?? $branch->id;
                                    $branchName = $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? '-';
                                @endphp

                                <option value="{{ $branchId }}"
                                    {{ old('branch_id') == $branchId ? 'selected' : '' }}>
                                    {{ $branchName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المستودع <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                            <option value="">اختر المستودع</option>

                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->warehouse_name ?? $warehouse->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">مركز تكلفة</label>
                        <select name="cost_center_id" class="form-select">
                            <option value="">بدون مركز تكلفة</option>

                            @foreach($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}" @selected(old('cost_center_id') == $costCenter->id)>
                                    {{ $costCenter->code ? $costCenter->code . ' - ' : '' }}{{ $costCenter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date"
                               name="invoice_date"
                               id="invoice_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="date"
                               name="due_date"
                               id="due_date"
                               class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">المبلغ المدفوع</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control amount-input"
                               value="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">حساب الدفع</label>
                        <select name="payment_account_id" id="payment_account_id" class="form-select">
                            <option value="">بدون دفع / فاتورة آجلة</option>

                            @foreach($paymentAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }} - {{ $account->account_name_ar }}
                                </option>
                            @endforeach
                        </select>

                        <small class="field-hint">
                            اختر الصندوق أو البنك فقط عند وجود مبلغ مدفوع.
                        </small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>

                </div>

            </div>
        </div>


        {{-- Items --}}
        <div class="card shadow-sm wazin-card mb-4">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">تفاصيل الأصناف</h5>
                    <small>أضف الأصناف والكميات والتكلفة والخصم ونسبة الضريبة</small>
                </div>

                <button type="button" id="addRow" class="btn btn-primary">
                    + إضافة صنف
                </button>
            </div>

            <div class="card-body p-0">
                <div class="p-3 border-bottom bg-light">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">إضافة بالباركود</label>
                            <input type="text"
                                   id="barcode_input"
                                   class="form-control"
                                   placeholder="امسح الباركود هنا ثم Enter"
                                   autocomplete="off"
                                   autofocus>
                        </div>

                        <div class="col-md-8">
                            <div class="alert alert-info mb-0 py-2">
                                امسح الباركود أو اكتب كود الصنف ثم اضغط Enter. إذا كان الصنف موجودًا في الجدول سيتم زيادة الكمية تلقائيًا.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table" id="itemsTable">

                        <thead>
                            <tr>
                                <th width="260">الصنف</th>
                                <th width="170">الوحدة</th>
                                <th width="110">الكمية</th>
                                <th width="120">التكلفة</th>
                                <th width="120">الخصم</th>
                                <th width="100">الضريبة %</th>
                                <th width="130">الإجمالي</th>
                                <th width="70">حذف</th>
                            </tr>
                        </thead>

                        <tbody></tbody>

                    </table>
                </div>

            </div>
        </div>


        {{-- Totals / Save --}}
        <div class="row g-3 align-items-start">

            <div class="col-lg-8">
                <div class="journal-status-note info-note">
                    عند حفظ الفاتورة وترحيلها سيتم تحديث المخزون وإنشاء حركة مخزون وقيد محاسبي تلقائيًا.
                </div>
            </div>

            <div class="col-lg-4">

                <div class="card shadow-sm wazin-card totals-card">
                    <div class="card-header wazin-card-header">
                        <div>
                            <h5 class="mb-0 fw-bold">ملخص الفاتورة</h5>
                            <small>الإجمالي والخصم والضريبة والمدفوع والمتبقي</small>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="summary-line">
                            <span>الإجمالي قبل الخصم</span>
                            <strong id="subtotalPreview">0.00</strong>
                        </div>

                        <div class="summary-line discount-line">
                            <span>إجمالي الخصم</span>
                            <strong id="discountPreview">0.00</strong>
                        </div>

                        <div class="summary-line vat-line">
                            <span>ضريبة القيمة المضافة</span>
                            <strong id="vatPreview">0.00</strong>
                        </div>

                        <hr>

                        <div class="summary-line total-line">
                            <span>صافي الفاتورة</span>
                            <strong id="totalPreview">0.00</strong>
                        </div>

                        <div class="summary-line paid-line">
                            <span>المدفوع</span>
                            <strong id="paidPreview">0.00</strong>
                        </div>

                        <div class="summary-line remaining-line">
                            <span>المتبقي</span>
                            <strong id="remainingPreview">0.00</strong>
                        </div>

                    </div>
                </div>

                <input type="hidden" name="save_action" id="save_action" value="draft">

                <div class="save-side-actions">
                    <button type="submit" id="saveDraftBtn" class="btn btn-outline-secondary w-100">
                        حفظ كمسودة
                    </button>

                    @can('purchase_invoices.post')
                        <button type="submit" id="savePostBtn" class="btn btn-success w-100">
                            حفظ وترحيل
                        </button>
                    @endcan
                </div>

            </div>

        </div>

    </form>

</div>


<template id="itemRowTemplate">
    <tr>
        <td>
            <select name="items[{index}][product_id]"
                    class="form-select productSelect"
                    required>
            </select>
        </td>

        <td>
            <select name="items[{index}][product_unit_id]" class="form-select unitSelect" required>
                <option value="">اختر الوحدة</option>
            </select>
        </td>

        <td>
            <input type="number"
                   step="0.001"
                   min="0.001"
                   name="items[{index}][quantity]"
                   class="form-control quantityInput amount-input"
                   value="1"
                   required>
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="items[{index}][unit_cost]"
                   class="form-control costInput amount-input"
                   value="0"
                   required>
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="items[{index}][discount_amount]"
                   class="form-control discountInput amount-input"
                   value="0">
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   min="0"
                   max="100"
                   name="items[{index}][vat_rate]"
                   class="form-control vatRateInput amount-input"
                   value="15">
        </td>

        <td>
            <input type="text"
                   class="form-control lineTotalPreview amount-input"
                   value="0.00"
                   readonly>
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRow">
                ×
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

    .field-hint {
        display: block;
        color: #64748B;
        font-weight: 700;
        margin-top: 7px;
        line-height: 1.7;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
        background-color: #fff;
    }

    textarea.form-control {
        min-height: 80px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .amount-input {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
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

    #itemsTable .productSelect,
    #itemsTable .unitSelect {
        min-width: 190px;
    }

    .summary-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 0;
        color: #071633;
        font-weight: 800;
    }

    .summary-line strong {
        direction: ltr;
        color: #071633;
        font-size: 16px;
        font-weight: 900;
    }

    .discount-line strong,
    .remaining-line strong {
        color: #E63B4A;
    }

    .vat-line strong {
        color: #F59E0B;
    }

    .paid-line strong {
        color: #16A34A;
    }

    .total-line {
        background: rgba(47, 107, 255, 0.08);
        border: 1px solid rgba(47, 107, 255, 0.16);
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 5px;
    }

    .total-line strong {
        color: #2F6BFF;
        font-size: 20px;
    }

    .journal-status-note {
        border-radius: 16px;
        padding: 16px 18px;
        font-weight: 800;
        line-height: 1.9;
    }

    .info-note {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #BFDBFE;
    }

    .save-side-actions {
        display: grid;
        gap: 10px;
        margin-top: 14px;
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

    .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-secondary,
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

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .is-invalid {
        border-color: #E63B4A !important;
        box-shadow: 0 0 0 .2rem rgba(230, 59, 74, .12) !important;
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn,
        .wazin-card-header .btn {
            width: 100%;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    const AJAX_PRODUCTS_URL = "{{ route('ajax-lookup.products') }}";
    const AJAX_PRODUCT_BARCODE_URL = "{{ route('ajax-lookup.product-by-barcode') }}";
    const PRODUCT_UNITS_URL = "{{ route('purchase-invoices.product-units', ':id') }}";

    let rowIndex = 0;


    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: message,
                confirmButtonColor: '#E63B4A'
            });
        } else {
            alert(message);
        }
    }


    function showWarning(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه',
                text: message,
                confirmButtonColor: '#2F6BFF'
            });
        } else {
            alert(message);
        }
    }

    function initProductSelect(row) {
        row.find('.productSelect').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'ابحث باسم المنتج أو الكود أو الباركود',
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

    function setProductSelectValue(row, productId, productText) {
        let select = row.find('.productSelect');

        if (!select.find(`option[value="${productId}"]`).length) {
            let option = new Option(productText, productId, true, true);
            select.append(option);
        }

        select.val(productId).trigger('change.select2');
    }

    function loadProductUnits(row, productId, selectedUnitId = null) {
        let unitSelect = row.find('.unitSelect');

        unitSelect.html('<option value="">جاري التحميل...</option>');
        row.find('.costInput').val('0');

        if (!productId) {
            unitSelect.html('<option value="">اختر الوحدة</option>');
            calculateTotals();
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
                                data-cost="${unit.purchase_price ?? 0}">
                            ${unit.unit_name ?? '-'} - معامل ${unit.factor ?? 1}
                        </option>
                    `);
                });

                if (!selectedUnitId && response.data.length === 1) {
                    unitSelect.val(response.data[0].id).trigger('change');
                }

                if (selectedUnitId) {
                    unitSelect.val(selectedUnitId).trigger('change');
                }
            }

            calculateTotals();
        }).fail(function () {
            unitSelect.html('<option value="">خطأ</option>');
            calculateTotals();
        });
    }

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

                addProductByBarcode(product.id, product.text, product.product_unit_id);
            },
            error: function (xhr) {
                let message = xhr.responseJSON?.message || 'لم يتم العثور على الصنف بهذا الباركود.';

                showSweetAlert('warning', 'تنبيه', message);

                $('#barcode_input').val('').focus();
            }
        });
    });


    function addRow() {
        let template = $('#itemRowTemplate').html();
        template = template.replaceAll('{index}', rowIndex);

        $('#itemsTable tbody').append(template);

        let currentRow = $('#itemsTable tbody tr:last');

        initProductSelect(currentRow);

        rowIndex++;
    }


    function calculateRow(row) {
        let quantity = parseFloat(row.find('.quantityInput').val()) || 0;
        let cost = parseFloat(row.find('.costInput').val()) || 0;
        let discount = parseFloat(row.find('.discountInput').val()) || 0;
        let vatRate = parseFloat(row.find('.vatRateInput').val()) || 0;

        let gross = quantity * cost;

        if (discount > gross) {
            discount = gross;
            row.find('.discountInput').val(discount.toFixed(2));
        }

        let net = gross - discount;
        let vat = net * (vatRate / 100);
        let total = net + vat;

        row.find('.lineTotalPreview').val(total.toFixed(2));
    }


    function calculateTotals() {
        let subtotal = 0;
        let discountTotal = 0;
        let vatTotal = 0;
        let total = 0;

        $('#itemsTable tbody tr').each(function () {
            let row = $(this);

            let quantity = parseFloat(row.find('.quantityInput').val()) || 0;
            let cost = parseFloat(row.find('.costInput').val()) || 0;
            let discount = parseFloat(row.find('.discountInput').val()) || 0;
            let vatRate = parseFloat(row.find('.vatRateInput').val()) || 0;

            let gross = quantity * cost;

            if (discount > gross) {
                discount = gross;
                row.find('.discountInput').val(discount.toFixed(2));
            }

            let net = gross - discount;
            let vat = net * (vatRate / 100);
            let lineTotal = net + vat;

            subtotal += gross;
            discountTotal += discount;
            vatTotal += vat;
            total += lineTotal;

            calculateRow(row);
        });

        let paid = parseFloat($('#paid_amount').val()) || 0;

        if (paid > total) {
            paid = total;
            $('#paid_amount').val(paid.toFixed(2));
        }

        if (paid < 0) {
            paid = 0;
            $('#paid_amount').val('0.00');
        }

        let remaining = total - paid;

        $('#subtotalPreview').text(subtotal.toFixed(2));
        $('#discountPreview').text(discountTotal.toFixed(2));
        $('#vatPreview').text(vatTotal.toFixed(2));
        $('#totalPreview').text(total.toFixed(2));
        $('#paidPreview').text(paid.toFixed(2));
        $('#remainingPreview').text(remaining.toFixed(2));
    }


    $('#addRow').on('click', function () {
        addRow();
    });


    $(document).on('click', '.removeRow', function () {
        if ($('#itemsTable tbody tr').length <= 1) {
            showWarning('يجب أن تحتوي الفاتورة على صنف واحد على الأقل');
            return;
        }

        $(this).closest('tr').remove();
        calculateTotals();
    });


    $(document).on('change', '.productSelect', function () {
        let row = $(this).closest('tr');
        let productId = $(this).val();

        loadProductUnits(row, productId, null);
    });


    $(document).on('change', '.unitSelect', function () {
        let row = $(this).closest('tr');
        let cost = $(this).find(':selected').data('cost') || 0;

        row.find('.costInput').val(cost);
        calculateTotals();
    });


    $(document).on('input', '.quantityInput, .costInput, .discountInput, .vatRateInput, #paid_amount', function () {
        calculateTotals();
    });


    $('#saveDraftBtn').on('click', function () {
        $('#save_action').val('draft');
    });


    $('#savePostBtn').on('click', function () {
        $('#save_action').val('post');
    });

    
    function addProductByBarcode(productId, productText, productUnitId = null) {
        let existingRow = null;

        $('#itemsTable tbody tr').each(function () {
            let row = $(this);
            let currentProductId = row.find('.productSelect').val();
            let currentUnitId = row.find('.unitSelect').val();

            if (
                String(currentProductId) === String(productId)
                && (!productUnitId || String(currentUnitId) === String(productUnitId))
            ) {
                existingRow = row;
                return false;
            }
        });

        if (existingRow) {
            let qtyInput = existingRow.find('.quantityInput');
            let currentQty = parseFloat(qtyInput.val()) || 0;

            qtyInput.val((currentQty + 1).toFixed(3));

            calculateTotals();

            existingRow.addClass('table-success');

            setTimeout(function () {
                existingRow.removeClass('table-success');
            }, 700);

            return;
        }

        let emptyRow = null;

        $('#itemsTable tbody tr').each(function () {
            let row = $(this);

            if (!row.find('.productSelect').val()) {
                emptyRow = row;
                return false;
            }
        });

        let targetRow = emptyRow || null;

        if (!targetRow) {
            addRow();
            targetRow = $('#itemsTable tbody tr:last');
        }

        setProductSelectValue(targetRow, productId, productText);

        targetRow.find('.quantityInput').val('1.000');

        loadProductUnits(targetRow, productId, productUnitId);

        calculateTotals();

        targetRow.addClass('table-success');

        setTimeout(function () {
            targetRow.removeClass('table-success');
        }, 700);
    }



    $('#purchase_invoice_form').on('submit', function (e) {
        e.preventDefault();

        $('#form_errors').html('');

        if ($('#itemsTable tbody tr').length === 0) {
            showError('يجب إضافة صنف واحد على الأقل.');
            return;
        }

        let total = parseFloat($('#totalPreview').text()) || 0;
        let paid = parseFloat($('#paid_amount').val()) || 0;

        if (total <= 0) {
            showError('يجب أن يكون صافي الفاتورة أكبر من صفر.');
            return;
        }

        if (paid > total) {
            showError('المبلغ المدفوع لا يمكن أن يكون أكبر من صافي الفاتورة.');
            return;
        }

        $('#saveDraftBtn, #savePostBtn').prop('disabled', true);

        $.ajax({
            url: "{{ route('purchase-invoices.store') }}",
            type: "POST",
            data: $(this).serialize(),

            success: function (response) {

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: response.message ?? 'تم حفظ الفاتورة بنجاح',
                        timer: 1300,
                        showConfirmButton: false
                    });
                } else {
                    alert(response.message ?? 'تم حفظ الفاتورة بنجاح');
                }

                setTimeout(function () {
                    window.location.href = "{{ url('/purchase-invoices') }}/" + response.invoice_id;
                }, 900);
            },

            error: function (xhr) {
                $('#saveDraftBtn, #savePostBtn').prop('disabled', false);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    let html = '<div class="alert alert-danger mb-4"><strong>يوجد أخطاء:</strong><ul class="mb-0 mt-2">';

                    $.each(errors, function (key, value) {
                        html += `<li>${value[0]}</li>`;
                    });

                    html += '</ul></div>';
                    $('#form_errors').html(html);

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                } else {
                    let message = xhr.responseJSON?.message ?? 'حدث خطأ غير متوقع';

                    showError(message);
                }
            }
        });
    });


    addRow();
    calculateTotals();

    setTimeout(function () {
        $('#barcode_input').focus();
    }, 500);


});
</script>
@endpush

</x-app-layout>