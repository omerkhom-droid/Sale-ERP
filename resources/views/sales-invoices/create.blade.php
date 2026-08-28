<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('sales-invoices.store') }}" id="salesInvoiceForm">
        @csrf

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إنشاء فاتورة بيع</h3>
                <p class="page-subtitle mb-0">
                    إنشاء فاتورة بيع مع تحديد العميل، المستودع، الأصناف، الضرائب، وطريقة السداد.
                </p>
            </div>

            <a href="{{ route('sales-invoices.index') }}" class="btn btn-secondary">
                رجوع
            </a>
        </div>


        {{-- Invoice Info --}}
        <div class="card shadow-sm wazin-card mb-4">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات الفاتورة</h5>
                    <small>الفرع والمستودع والتاريخ ورقم الفاتورة</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->branch_id ?? $branch->id }}"
                                    {{ old('branch_id') == ($branch->branch_id ?? $branch->id) ? 'selected' : '' }}>
                                    {{ $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? ('فرع رقم ' . ($branch->branch_id ?? $branch->id)) }}
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
                        <label class="form-label">المستودع <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">اختر المستودع</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date"
                               name="invoice_date"
                               class="form-control"
                               value="{{ old('invoice_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">رقم الفاتورة</label>
                        <input type="text"
                               name="invoice_no"
                               class="form-control"
                               value="{{ old('invoice_no') }}"
                               placeholder="تلقائي">
                    </div>

                </div>
            </div>
        </div>


        {{-- Customer / Payment --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات العميل والدفع</h5>
                    <small>تحديد نوع العميل وطريقة السداد والبيانات الضريبية</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">نوع العميل <span class="text-danger">*</span></label>
                        <select name="customer_type" id="customer_type" class="form-select" required>
                            <option value="cash" {{ old('customer_type', 'cash') == 'cash' ? 'selected' : '' }}>
                                نقدي
                            </option>
                            <option value="credit" {{ old('customer_type') == 'credit' ? 'selected' : '' }}>
                                آجل
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" id="customer_id" class="form-select">
                            <option value="">اختر العميل</option>

                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->customer_name
                                        ?? $customer->name
                                        ?? $customer->fullname
                                        ?? 'عميل #' . $customer->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نوع الدفع <span class="text-danger">*</span></label>
                        <select name="payment_type" id="payment_type" class="form-select" required>
                            <option value="cash" {{ old('payment_type', 'cash') == 'cash' ? 'selected' : '' }}>
                                نقدي بالكامل
                            </option>
                            <option value="credit" {{ old('payment_type') == 'credit' ? 'selected' : '' }}>
                                آجل بالكامل
                            </option>
                            <option value="partial" {{ old('payment_type') == 'partial' ? 'selected' : '' }}>
                                دفع جزئي
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">اختر</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>نقدي</option>
                            <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>شبكة</option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                            <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">اسم العميل</label>
                        <input type="text"
                               name="customer_name"
                               id="customer_name"
                               class="form-control"
                               value="{{ old('customer_name') }}"
                               placeholder="عميل نقدي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">جوال العميل</label>
                        <input type="text"
                               name="customer_mobile"
                               id="customer_mobile"
                               class="form-control"
                               value="{{ old('customer_mobile') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text"
                               name="customer_tax_number"
                               id="customer_tax_number"
                               class="form-control"
                               value="{{ old('customer_tax_number') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المبلغ المدفوع</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control amount-input"
                               value="{{ old('paid_amount', 0) }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">عنوان العميل</label>
                        <textarea name="customer_address"
                                  id="customer_address"
                                  class="form-control"
                                  rows="2">{{ old('customer_address') }}</textarea>
                    </div>

                </div>
            </div>
        </div>


        {{-- Items --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">أصناف الفاتورة</h5>
                    <small>إضافة الأصناف والكميات والأسعار والضريبة</small>
                </div>

                <button type="button" class="btn btn-primary" id="addRowBtn">
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
                                   autocomplete="off" autofocus>
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
                                <th style="width: 23%">الصنف</th>
                                <th style="width: 12%">الوحدة</th>
                                <th style="width: 9%">الكمية</th>
                                <th style="width: 11%">سعر البيع</th>
                                <th style="width: 10%">الخصم</th>
                                <th style="width: 9%">الضريبة %</th>
                                <th style="width: 11%">الصافي</th>
                                <th style="width: 11%">الإجمالي</th>
                                <th style="width: 4%">حذف</th>
                            </tr>
                        </thead>

                        <tbody id="itemsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>


        {{-- Totals --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">إجماليات الفاتورة</h5>
                    <small>ملخص الخصم والضريبة والإجمالي النهائي</small>
                </div>
            </div>

            <div class="card-body">
                <div class="totals-grid">

                    <div class="total-box">
                        <span>الإجمالي قبل الضريبة</span>
                        <input type="text" id="subtotal_display" class="form-control total-input" value="0.00" readonly>
                    </div>

                    <div class="total-box">
                        <span>إجمالي الخصم</span>
                        <input type="text" id="discount_display" class="form-control total-input" value="0.00" readonly>
                    </div>

                    <div class="total-box">
                        <span>إجمالي الضريبة</span>
                        <input type="text" id="vat_display" class="form-control total-input" value="0.00" readonly>
                    </div>

                    <div class="total-box total-box-final">
                        <span>الإجمالي النهائي</span>
                        <input type="text" id="total_display" class="form-control total-input final-total" value="0.00" readonly>
                    </div>

                </div>
            </div>
        </div>


        {{-- Notes --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات الفاتورة</h5>
                    <small>أي ملاحظات إضافية تظهر ضمن بيانات الفاتورة</small>
                </div>
            </div>

            <div class="card-body">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('sales-invoices.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ مسودة
            </button>

            @can('sales_invoices.post')
                <button type="submit" name="save_action" value="post" class="btn btn-primary">
                    حفظ وترحيل
                </button>
            @endcan

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

    textarea.form-control {
        min-height: 92px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .amount-input,
    .total-input,
    #itemsTable .item-qty,
    #itemsTable .item-price,
    #itemsTable .item-discount,
    #itemsTable .item-vat-rate,
    #itemsTable .item-net,
    #itemsTable .item-total {
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

    #itemsTable .form-select,
    #itemsTable .form-control {
        min-height: 40px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
    }

    #itemsTable td:first-child {
        min-width: 260px;
    }

    .totals-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .total-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .total-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .total-box-final {
        border-right: 5px solid #2F6BFF;
    }

    .final-total {
        background: rgba(47, 107, 255, 0.08) !important;
        color: #071633;
        font-size: 18px;
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

    .save-actions {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        box-shadow: 0 12px 32px rgba(7, 22, 51, 0.08);
        position: sticky;
        bottom: 18px;
        z-index: 20;
    }

    .save-actions .btn {
        min-width: 150px;
    }

    @media (max-width: 991px) {
        .totals-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
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

        .totals-grid {
            grid-template-columns: 1fr;
        }

        .save-actions {
            flex-direction: column;
            position: static;
        }

        .save-actions .btn {
            width: 100%;
        }
    }
</style>


@push('scripts')
@php
    $oldItems = old('items', []);
    $sweetAlertErrors = $errors->any() ? $errors->all() : [];
    $sweetAlertSessionError = session('error');
    $sweetAlertSuccess = session('success');
@endphp

<script>
const SWEET_ALERT_SESSION_ERROR = @json($sweetAlertSessionError);
const SWEET_ALERT_VALIDATION_ERRORS = @json($sweetAlertErrors);
const SWEET_ALERT_SUCCESS = @json($sweetAlertSuccess);

function showSweetAlert(icon, title, html) {
    if (typeof Swal === 'undefined') {
        let plainText = String(html || '').replace(/<[^>]*>/g, '');
        alert(plainText);
        return;
    }

    Swal.fire({
        icon: icon,
        title: title,
        html: html,
        confirmButtonText: 'حسناً',
        confirmButtonColor: icon === 'error' ? '#E63B4A' : '#16A34A'
    });
}

$(document).ready(function () {
    

    if (SWEET_ALERT_SESSION_ERROR || SWEET_ALERT_VALIDATION_ERRORS.length > 0) {
        let html = '';

        if (SWEET_ALERT_SESSION_ERROR) {
            html += `<div class="text-end" dir="rtl">${SWEET_ALERT_SESSION_ERROR}</div>`;
        }

        if (SWEET_ALERT_VALIDATION_ERRORS.length > 0) {
            html += '<ul class="text-end mb-0" dir="rtl" style="list-style-position: inside;">';

            SWEET_ALERT_VALIDATION_ERRORS.forEach(function (error) {
                html += `<li>${error}</li>`;
            });

            html += '</ul>';
        }

        showSweetAlert('error', 'يوجد خطأ', html);
    }

    if (SWEET_ALERT_SUCCESS) {
        showSweetAlert('success', 'تم بنجاح', SWEET_ALERT_SUCCESS);
    }



    let rowIndex = 0;

    const OLD_ITEMS_RAW = @json($oldItems);
    const OLD_ITEMS = Array.isArray(OLD_ITEMS_RAW) ? OLD_ITEMS_RAW : Object.values(OLD_ITEMS_RAW || {});

    const AJAX_PRODUCTS_URL = "{{ route('ajax-lookup.products') }}";
    const AJAX_PRODUCT_BARCODE_URL = "{{ route('ajax-lookup.product-by-barcode') }}";



    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function initProductSelect(row) {
        row.find('.product-select').select2({
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
        let select = row.find('.product-select');

        if (!select.find(`option[value="${productId}"]`).length) {
            let option = new Option(productText, productId, true, true);
            select.append(option);
        }

        select.val(productId).trigger('change.select2');
    }

    function addRow(oldItem = null) {

        oldItem = oldItem || {};

        let productId = oldItem.product_id ?? '';
        let productUnitId = oldItem.product_unit_id ?? '';
        let quantity = oldItem.quantity ?? 1;
        let unitPrice = oldItem.unit_price ?? 0;
        let discountAmount = oldItem.discount_amount ?? 0;
        let vatRate = oldItem.vat_rate ?? 15;

        let row = `
            <tr data-row="${rowIndex}">

                <td>
                    <select name="items[${rowIndex}][product_id]"
                            class="form-select product-select"
                            required>
                        ${productId ? `<option value="${escapeHtml(productId)}" selected>صنف رقم ${escapeHtml(productId)}</option>` : ''}
                    </select>
                </td>

                <td>
                    <select name="items[${rowIndex}][product_unit_id]"
                            class="form-select unit-select"
                            required>
                        <option value="">اختر</option>
                    </select>
                </td>

                <td>
                    <input type="number"
                           name="items[${rowIndex}][quantity]"
                           class="form-control item-qty"
                           step="0.001"
                           min="0.001"
                           value="${escapeHtml(quantity)}"
                           required>
                </td>

                <td>
                    <input type="number"
                           name="items[${rowIndex}][unit_price]"
                           class="form-control item-price"
                           step="0.01"
                           min="0"
                           value="${escapeHtml(unitPrice)}"
                           required>
                </td>

                <td>
                    <input type="number"
                           name="items[${rowIndex}][discount_amount]"
                           class="form-control item-discount"
                           step="0.01"
                           min="0"
                           value="${escapeHtml(discountAmount)}">
                </td>

                <td>
                    <input type="number"
                           name="items[${rowIndex}][vat_rate]"
                           class="form-control item-vat-rate"
                           step="0.01"
                           min="0"
                           max="100"
                           value="${escapeHtml(vatRate)}">
                </td>

                <td>
                    <input type="text"
                           class="form-control item-net"
                           value="0.00"
                           readonly>
                </td>

                <td>
                    <input type="text"
                           class="form-control item-total"
                           value="0.00"
                           readonly>
                </td>

                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        ×
                    </button>
                </td>

            </tr>
        `;

        $('#itemsTableBody').append(row);

        let currentRow = $('#itemsTableBody tr:last');

        initProductSelect(currentRow);

        if (productId) {
            loadProductUnits(currentRow, productId, productUnitId, true);
        }

        rowIndex++;
        calculateTotals();

        return currentRow;
    }


    function loadProductUnits(row, productId, selectedUnitId = null, keepCurrentPrice = false) {

        let unitSelect = row.find('.unit-select');

        unitSelect.html('<option value="">جاري التحميل...</option>');

        if (!productId) {
            unitSelect.html('<option value="">اختر</option>');
            row.find('.item-price').val('0.00');
            calculateTotals();
            return;
        }

        let url = "{{ route('sales-invoices.product-units', ':id') }}";
        url = url.replace(':id', productId);

        $.get(url, function (response) {

            unitSelect.html('<option value="">اختر</option>');

            if (response.status === 'success') {

                response.data.forEach(function (unit) {

                    let selected = String(selectedUnitId) === String(unit.id)
                        ? 'selected'
                        : '';

                    unitSelect.append(`
                        <option value="${escapeHtml(unit.id)}"
                                ${selected}
                                data-sale-price="${escapeHtml(unit.sale_price ?? 0)}"
                                data-minimum-sale-price="${escapeHtml(unit.minimum_sale_price ?? 0)}"
                                data-is-default="${escapeHtml(unit.is_default ?? 0)}">
                            ${escapeHtml(unit.unit_name)}
                        </option>
                    `);
                });

                if (!selectedUnitId) {
                    let defaultOption = unitSelect.find('option[data-is-default="1"]').first();

                    if (defaultOption.length) {
                        unitSelect.val(defaultOption.val());
                    }
                }

                let selectedOption = unitSelect.find('option:selected');
                let salePrice = parseFloat(selectedOption.data('sale-price')) || 0;
                let currentPrice = parseFloat(row.find('.item-price').val()) || 0;

                if (!keepCurrentPrice || currentPrice <= 0) {
                    row.find('.item-price').val(salePrice.toFixed(2));
                }
            }

            calculateTotals();

        }).fail(function () {
            unitSelect.html('<option value="">خطأ</option>');
            calculateTotals();
        });
    }


    $(document).on('change', '.product-select', function () {

        let productId = $(this).val();
        let row = $(this).closest('tr');

        row.find('.item-price').val('0.00');

        loadProductUnits(row, productId, null, false);

        calculateTotals();
    });


    $(document).on('change', '.unit-select', function () {

        let row = $(this).closest('tr');
        let selectedOption = $(this).find('option:selected');

        let salePrice = parseFloat(selectedOption.data('sale-price')) || 0;

        row.find('.item-price').val(salePrice.toFixed(2));

        calculateTotals();
    });


    $('#customer_id').on('change', function () {

        let customerId = $(this).val();

        if (!customerId) {
            return;
        }

        let url = "{{ route('sales-invoices.customer-data', ':id') }}";
        url = url.replace(':id', customerId);

        $.get(url, function (response) {

            if (response.status === 'success') {
                $('#customer_name').val(response.data.customer_name);
                $('#customer_mobile').val(response.data.customer_mobile);
                $('#customer_tax_number').val(response.data.customer_tax_number);
                $('#customer_address').val(response.data.customer_address);
            }

        });
    });


    function updatePaymentFields() {

        let paymentType = $('#payment_type').val();
        let total = parseFloat($('#total_display').val()) || 0;

        if (paymentType === 'cash') {
            $('#paid_amount').val(total.toFixed(2)).prop('readonly', true);
            $('#payment_method').prop('disabled', false);
        }

        if (paymentType === 'credit') {
            $('#paid_amount').val('0.00').prop('readonly', true);
            $('#payment_method').val('').prop('disabled', true);
        }

        if (paymentType === 'partial') {
            $('#paid_amount').prop('readonly', false);
            $('#payment_method').prop('disabled', false);
        }
    }


    function calculateTotals() {

        let subtotal = 0;
        let discountTotal = 0;
        let vatTotal = 0;
        let grandTotal = 0;

        $('#itemsTableBody tr').each(function () {

            let row = $(this);

            let qty = parseFloat(row.find('.item-qty').val()) || 0;
            let price = parseFloat(row.find('.item-price').val()) || 0;
            let discount = parseFloat(row.find('.item-discount').val()) || 0;
            let vatRate = parseFloat(row.find('.item-vat-rate').val()) || 0;

            let gross = qty * price;

            if (discount > gross) {
                discount = gross;
                row.find('.item-discount').val(discount.toFixed(2));
            }

            let net = gross - discount;
            let vat = net * (vatRate / 100);
            let lineTotal = net + vat;

            row.find('.item-net').val(net.toFixed(2));
            row.find('.item-total').val(lineTotal.toFixed(2));

            subtotal += net;
            discountTotal += discount;
            vatTotal += vat;
            grandTotal += lineTotal;
        });

        $('#subtotal_display').val(subtotal.toFixed(2));
        $('#discount_display').val(discountTotal.toFixed(2));
        $('#vat_display').val(vatTotal.toFixed(2));
        $('#total_display').val(grandTotal.toFixed(2));

        updatePaymentFields();
    }


    $('#addRowBtn').on('click', function () {
        addRow();
    });


    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();

        if ($('#itemsTableBody tr').length === 0) {
            addRow();
        }

        calculateTotals();
    });


    $(document).on('input', '.item-qty, .item-price, .item-discount, .item-vat-rate', function () {
        calculateTotals();
    });


    $('#payment_type').on('change', function () {
        updatePaymentFields();
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

                addProductByBarcode(product.id, product.text, product.product_unit_id);
            },
            error: function (xhr) {
                let message = xhr.responseJSON?.message || 'لم يتم العثور على الصنف بهذا الباركود.';

                showSweetAlert('warning', 'تنبيه', message);

                $('#barcode_input').val('').focus();
            }
        });
    });

    function addProductByBarcode(productId, productText, productUnitId = null) {

        let existingRow = null;

        $('#itemsTableBody tr').each(function () {
            let row = $(this);
            let currentProductId = row.find('.product-select').val();
            let currentUnitId = row.find('.unit-select').val();

            if (
                String(currentProductId) === String(productId)
                && (!productUnitId || String(currentUnitId) === String(productUnitId))
            ) {
                existingRow = row;
                return false;
            }
        });

        if (existingRow) {
            let qtyInput = existingRow.find('.item-qty');
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

        $('#itemsTableBody tr').each(function () {
            let row = $(this);

            if (!row.find('.product-select').val()) {
                emptyRow = row;
                return false;
            }
        });

        let targetRow = emptyRow || addRow();

        setProductSelectValue(targetRow, productId, productText);

        targetRow.find('.item-qty').val('1.000');

        loadProductUnits(targetRow, productId, productUnitId, false);

        calculateTotals();

        targetRow.addClass('table-success');

        setTimeout(function () {
            targetRow.removeClass('table-success');
        }, 700);
    }


    if (OLD_ITEMS && OLD_ITEMS.length > 0) {
        OLD_ITEMS.forEach(function (item) {
            addRow(item);
        });
    } else {
        addRow();
    }

    updatePaymentFields();

    setTimeout(function () {
        $('#barcode_input').focus();
    }, 500);

    });


</script>
@endpush

</x-app-layout>