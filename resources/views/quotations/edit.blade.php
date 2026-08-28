<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('quotations.update', $quotation->id) }}" id="quotationForm">
        @csrf
        @method('PUT')

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">تعديل عرض سعر</h3>
                <p class="page-subtitle mb-0">
                    تعديل بيانات عرض السعر رقم: {{ $quotation->quotation_no }}
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('quotations.show', $quotation->id) }}" class="btn btn-info">
                    عرض
                </a>

                <a href="{{ route('quotations.index') }}" class="btn btn-secondary">
                    رجوع
                </a>
            </div>
        </div>


        {{-- Quotation Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات عرض السعر</h5>
                    <small>الفرع والمستودع والتاريخ ورقم عرض السعر</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">الفرع <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">اختر الفرع</option>
                            @foreach($branches as $branch)
                                @php
                                    $branchId = $branch->branch_id ?? $branch->id;
                                @endphp

                                <option value="{{ $branchId }}"
                                    {{ old('branch_id', $quotation->branch_id) == $branchId ? 'selected' : '' }}>
                                    {{ $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? ('فرع رقم ' . $branchId) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">مركز تكلفة</label>
                        <select name="cost_center_id" class="form-select">
                            <option value="">بدون مركز تكلفة</option>
                            @foreach($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}"
                                    @selected(old('cost_center_id', $quotation->cost_center_id) == $costCenter->id)>
                                    {{ $costCenter->code ? $costCenter->code . ' - ' : '' }}{{ $costCenter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المستودع</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">بدون مستودع</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ old('warehouse_id', $quotation->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ العرض <span class="text-danger">*</span></label>
                        <input type="date"
                               name="quotation_date"
                               class="form-control"
                               value="{{ old('quotation_date', optional($quotation->quotation_date)->format('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">صالح إلى</label>
                        <input type="date"
                               name="valid_until"
                               class="form-control"
                               value="{{ old('valid_until', optional($quotation->valid_until)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">رقم عرض السعر</label>
                        <input type="text"
                               name="quotation_no"
                               class="form-control"
                               value="{{ old('quotation_no', $quotation->quotation_no) }}"
                               placeholder="تلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الحالة الحالية</label>
                        <input type="text"
                               class="form-control"
                               value="@switch($quotation->status)
                                    @case('draft') مسودة @break
                                    @case('sent') مرسل @break
                                    @case('approved') معتمد @break
                                    @case('rejected') مرفوض @break
                                    @case('converted') محول لفاتورة @break
                                    @case('cancelled') ملغي @break
                                    @default {{ $quotation->status }}
                               @endswitch"
                               readonly>
                    </div>

                </div>
            </div>
        </div>


        {{-- Customer --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات العميل</h5>
                    <small>تحديد نوع العميل والبيانات الضريبية ومعلومات التواصل</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">نوع العميل <span class="text-danger">*</span></label>
                        <select name="customer_type" id="customer_type" class="form-select" required>
                            <option value="cash" {{ old('customer_type', $quotation->customer_type) == 'cash' ? 'selected' : '' }}>
                                نقدي
                            </option>
                            <option value="credit" {{ old('customer_type', $quotation->customer_type) == 'credit' ? 'selected' : '' }}>
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
                                    {{ old('customer_id', $quotation->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->customer_name ?? $customer->name ?? $customer->fullname ?? 'عميل #' . $customer->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">اسم العميل</label>
                        <input type="text"
                               name="customer_name"
                               id="customer_name"
                               class="form-control"
                               value="{{ old('customer_name', $quotation->customer_name) }}"
                               placeholder="عميل نقدي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">جوال العميل</label>
                        <input type="text"
                               name="customer_mobile"
                               id="customer_mobile"
                               class="form-control"
                               value="{{ old('customer_mobile', $quotation->customer_mobile) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text"
                               name="customer_tax_number"
                               id="customer_tax_number"
                               class="form-control"
                               value="{{ old('customer_tax_number', $quotation->customer_tax_number) }}">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">عنوان العميل</label>
                        <textarea name="customer_address"
                                  id="customer_address"
                                  class="form-control"
                                  rows="2">{{ old('customer_address', $quotation->customer_address) }}</textarea>
                    </div>

                </div>
            </div>
        </div>


        {{-- Items --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">أصناف عرض السعر</h5>
                    <small>تعديل الأصناف والكميات والأسعار والضريبة</small>
                </div>

                <button type="button" class="btn btn-primary" id="addRowBtn">
                    + إضافة صنف
                </button>
            </div>

            <div class="card-body p-0">
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
                    <h5 class="mb-0 fw-bold">إجماليات عرض السعر</h5>
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
                    <h5 class="mb-0 fw-bold">ملاحظات وشروط عرض السعر</h5>
                    <small>أي ملاحظات أو شروط تظهر ضمن عرض السعر</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes', $quotation->notes) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الشروط والأحكام</label>
                        <textarea name="terms" class="form-control" rows="4">{{ old('terms', $quotation->terms) }}</textarea>
                    </div>
                </div>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('quotations.show', $quotation->id) }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ كمسودة
            </button>

            <button type="submit" name="save_action" value="sent" class="btn btn-primary">
                حفظ وإرسال
            </button>

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
        border: 1px solid #CBD5E1;
        min-height: 44px;
        font-weight: 700;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
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
        background: #fff;
    }

    .wazin-table .form-select,
    .wazin-table .form-control {
        min-width: 110px;
        font-size: 13px;
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
        padding: 16px;
    }

    .total-box span {
        display: block;
        color: #64748B;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .total-input {
        background: #F8FAFC !important;
        font-weight: 900;
        text-align: center;
        direction: ltr;
    }

    .total-box-final {
        background: #071633;
        border-color: #071633;
    }

    .total-box-final span {
        color: #CFEFF3;
    }

    .final-total {
        background: #fff !important;
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
    .btn-outline-secondary,
    .btn-info {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-info {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
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
    $oldItems = old('items');

    if (is_null($oldItems)) {
        $oldItems = $quotation->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_unit_id' => $item->product_unit_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'vat_rate' => $item->vat_rate,
            ];
        })->values()->toArray();
    }

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
        confirmButtonColor: icon === 'error' ? '#E63B4A' : '#16A34A',
        didOpen: function (popup) {
            popup.setAttribute('dir', 'rtl');
        }
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

    const products = @json($products);
    const OLD_ITEMS_RAW = @json($oldItems);
    const OLD_ITEMS = Array.isArray(OLD_ITEMS_RAW) ? OLD_ITEMS_RAW : Object.values(OLD_ITEMS_RAW || {});


    function getProductSellingPrice(product) {
        if (!product) {
            return 0;
        }

        return parseFloat(
            product.sale_price
            ?? product.selling_price
            ?? product.sales_price
            ?? product.price
            ?? product.unit_price
            ?? 0
        ) || 0;
    }


    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function productOptions(selectedProductId = '') {
        let html = '<option value="">اختر الصنف</option>';

        products.forEach(function (product) {

            let name = product.product_name_ar
                ?? product.product_name
                ?? product.name
                ?? ('صنف #' + product.id);

            let sku = product.sku ?? product.product_code ?? '';

            let selected = String(selectedProductId) === String(product.id)
                ? 'selected'
                : '';

            html += `<option value="${escapeHtml(product.id)}" ${selected}>${escapeHtml(sku)} - ${escapeHtml(name)}</option>`;
        });

        return html;
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
                        ${productOptions(productId)}
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

        if (productId) {
            loadProductUnits(currentRow, productId, productUnitId, true);
        }

        rowIndex++;
        calculateTotals();
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

        let url = "{{ route('quotations.product-units', ':id') }}";
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

        let selectedProduct = products.find(function (product) {
            return parseInt(product.id) === parseInt(productId);
        });

        let sellingPrice = getProductSellingPrice(selectedProduct);

        row.find('.item-price').val(sellingPrice.toFixed(2));

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

        let url = "{{ route('quotations.customer-data', ':id') }}";
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


    if (OLD_ITEMS && OLD_ITEMS.length > 0) {
        OLD_ITEMS.forEach(function (item) {
            addRow(item);
        });
    } else {
        addRow();
    }

});
</script>
@endpush

</x-app-layout>