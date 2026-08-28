<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form id="purchase_return_form">
        @csrf

        <input type="hidden" name="save_action" id="save_action" value="draft">

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">مردود مشتريات جديد</h3>
                <p class="page-subtitle mb-0">
                    إنشاء مردود مشتريات من فاتورة مرحلة، مع تحديد الأصناف والكميات المرتجعة وحساب الإجماليات تلقائيًا.
                </p>
            </div>

            <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary">
                رجوع للقائمة
            </a>
        </div>


        {{-- Errors --}}
        <div id="form_errors"></div>


        {{-- Return Info --}}
        <div class="card shadow-sm wazin-card mb-4">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات المردود</h5>
                    <small>رقم المردود والتاريخ وفاتورة المشتريات الأصلية</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">رقم المردود</label>
                        <input type="text"
                               name="return_no"
                               id="return_no"
                               class="form-control"
                               placeholder="تلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            تاريخ المردود <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="return_date"
                               id="return_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            فاتورة المشتريات <span class="text-danger">*</span>
                        </label>

                        <select name="purchase_invoice_id"
                                id="purchase_invoice_id"
                                class="form-select"
                                required>

                            <option value="">اختر فاتورة المشتريات</option>

                            @foreach($purchaseInvoices as $invoice)
                                <option value="{{ $invoice->id }}">
                                    {{ $invoice->invoice_no }}
                                    -
                                    {{ $invoice->supplier?->supplier_name ?? $invoice->supplier?->name ?? '-' }}
                                    -
                                    {{ $invoice->invoice_date }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes"
                                  id="notes"
                                  class="form-control"
                                  rows="2"
                                  placeholder="مثال: إرجاع أصناف تالفة أو زائدة"></textarea>
                    </div>

                </div>

            </div>

        </div>


        {{-- Invoice Info --}}
        <div class="card shadow-sm wazin-card mb-4" id="invoiceInfoCard" style="display:none;">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات الفاتورة الأصلية</h5>
                    <small>ملخص فاتورة المشتريات التي سيتم إنشاء المردود عليها</small>
                </div>
            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info-box">
                        <span>رقم الفاتورة</span>
                        <strong id="info_invoice_no">-</strong>
                    </div>

                    <div class="info-box supplier-box">
                        <span>المورد</span>
                        <strong id="info_supplier_name">-</strong>
                    </div>

                    <div class="info-box warehouse-box">
                        <span>المستودع</span>
                        <strong id="info_warehouse_name">-</strong>
                    </div>

                    <div class="info-box">
                        <span>تاريخ الفاتورة</span>
                        <strong id="info_invoice_date">-</strong>
                    </div>

                </div>

            </div>

        </div>


        {{-- Items --}}
        <div class="card shadow-sm wazin-card mb-4">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">الأصناف المتاحة للإرجاع</h5>
                    <small>اختر فاتورة مشتريات أولاً، ثم أدخل كمية المردود لكل صنف</small>
                </div>
            </div>

            <div class="card-body p-0">

                <div class="table-note">
                    عند الترحيل سيتم إنقاص المخزون، وإنشاء قيد محاسبي، وتحديث حالة الفاتورة الأصلية.
                </div>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table"
                           id="returnItemsTable">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصنف</th>
                                <th>الوحدة</th>
                                <th>كمية الفاتورة</th>
                                <th>مرتجع سابقًا</th>
                                <th>المتاح للإرجاع</th>
                                <th>تكلفة الوحدة</th>
                                <th>الضريبة</th>
                                <th>كمية المردود</th>
                                <th>الإجمالي المتوقع</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        لم يتم اختيار فاتورة
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- Summary / Actions --}}
        <div class="row g-3 align-items-start mb-5">

            <div class="col-lg-8">
                <div class="journal-status-note warning-note">
                    يتم إرسال الأصناف التي تم إدخال كمية مردود لها فقط، أما الأصناف التي كميتها صفر فلن تُحفظ ضمن المردود.
                </div>
            </div>

            <div class="col-lg-4">

                <div class="card shadow-sm wazin-card totals-card">

                    <div class="card-header wazin-card-header">
                        <div>
                            <h5 class="mb-0 fw-bold">ملخص المردود</h5>
                            <small>الإجمالي قبل الضريبة والضريبة والإجمالي النهائي</small>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="summary-line">
                            <span>الإجمالي قبل الضريبة</span>
                            <strong id="subtotalPreview">0.00</strong>
                        </div>

                        <div class="summary-line vat-line">
                            <span>الضريبة</span>
                            <strong id="vatPreview">0.00</strong>
                        </div>

                        <hr>

                        <div class="summary-line total-line">
                            <span>الإجمالي</span>
                            <strong id="totalPreview">0.00</strong>
                        </div>

                    </div>

                </div>

                <div class="save-side-actions">
                    <button type="submit" id="saveDraftBtn" class="btn btn-outline-secondary w-100">
                        حفظ كمسودة
                    </button>

                    @can('purchase_returns.post')
                        <button type="submit" id="savePostBtn" class="btn btn-success w-100">
                            حفظ وترحيل
                        </button>
                    @endcan
                </div>

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
        min-height: 80px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .info-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .info-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .info-box strong {
        display: block;
        direction: ltr;
        text-align: center;
        color: #071633;
        font-size: 15px;
        font-weight: 900;
        min-height: 24px;
    }

    .supplier-box strong,
    .warehouse-box strong {
        direction: rtl;
        text-align: right;
    }

    .table-note {
        margin: 18px;
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FED7AA;
        border-radius: 16px;
        padding: 14px 16px;
        font-weight: 800;
        line-height: 1.8;
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

    .item-name-cell {
        text-align: right !important;
        min-width: 240px;
    }

    .item-name-cell small {
        color: #64748B;
        font-weight: 700;
    }

    .amount-cell,
    .returnQtyInput {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
    }

    .returnQtyInput {
        min-width: 115px;
        border-radius: 12px;
    }

    .empty-state {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 18px;
        padding: 28px 16px;
        text-align: center;
        color: #64748B;
        font-weight: 800;
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

    .vat-line strong {
        color: #F59E0B;
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

    .warning-note {
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FED7AA;
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

    .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
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

    @media (max-width: 991px) {
        .info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    let invoiceItems = [];


    function formatNumber(value) {
        return parseFloat(value || 0).toFixed(2);
    }


    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


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


    /*
    |--------------------------------------------------------------------------
    | تحميل أصناف الفاتورة
    |--------------------------------------------------------------------------
    */
    $('#purchase_invoice_id').on('change', function () {

        let invoiceId = $(this).val();

        invoiceItems = [];
        $('#invoiceInfoCard').hide();

        $('#returnItemsTable tbody').html(`
            <tr>
                <td colspan="10">
                    <div class="empty-state">جاري تحميل الأصناف...</div>
                </td>
            </tr>
        `);

        calculateTotals();

        if (!invoiceId) {
            $('#returnItemsTable tbody').html(`
                <tr>
                    <td colspan="10">
                        <div class="empty-state">لم يتم اختيار فاتورة</div>
                    </td>
                </tr>
            `);
            return;
        }

        $.ajax({
            url: "{{ url('/purchase-returns/invoice-items') }}/" + invoiceId,
            type: "GET",

            success: function (response) {

                $('#invoiceInfoCard').show();

                $('#info_invoice_no').text(response.invoice?.invoice_no ?? '-');
                $('#info_supplier_name').text(response.invoice?.supplier_name ?? '-');
                $('#info_warehouse_name').text(response.invoice?.warehouse_name ?? '-');
                $('#info_invoice_date').text(response.invoice?.invoice_date ?? '-');

                invoiceItems = response.items || [];

                renderItems();
            },

            error: function (xhr) {

                invoiceItems = [];

                $('#returnItemsTable tbody').html(`
                    <tr>
                        <td colspan="10">
                            <div class="empty-state text-danger">حدث خطأ أثناء تحميل أصناف الفاتورة</div>
                        </td>
                    </tr>
                `);

                let message = 'حدث خطأ أثناء تحميل أصناف الفاتورة';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                showError(message);
            }
        });

    });


    /*
    |--------------------------------------------------------------------------
    | رسم الأصناف
    |--------------------------------------------------------------------------
    */
    function renderItems() {

        let tbody = $('#returnItemsTable tbody');
        tbody.html('');

        if (invoiceItems.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="10">
                        <div class="empty-state">لا توجد أصناف متاحة للإرجاع في هذه الفاتورة</div>
                    </td>
                </tr>
            `);

            calculateTotals();
            return;
        }

        invoiceItems.forEach(function (item, index) {

            tbody.append(`
                <tr>
                    <td class="fw-bold">${index + 1}</td>

                    <td class="item-name-cell">
                        <strong>${escapeHtml(item.product_name)}</strong>
                        <br>
                        <small>${escapeHtml(item.product_sku)}</small>
                    </td>

                    <td>${escapeHtml(item.unit_name)}</td>

                    <td class="amount-cell">${formatNumber(item.invoice_quantity)}</td>

                    <td class="amount-cell text-danger">${formatNumber(item.returned_quantity)}</td>

                    <td class="amount-cell">
                        <span class="availableQty"
                              data-available="${escapeHtml(item.available_quantity)}">
                            ${formatNumber(item.available_quantity)}
                        </span>
                    </td>

                    <td class="amount-cell">${formatNumber(item.unit_cost)}</td>

                    <td class="amount-cell text-warning">${formatNumber(item.vat_rate)}%</td>

                    <td>
                        <input type="number"
                               step="0.001"
                               min="0"
                               max="${escapeHtml(item.available_quantity)}"
                               class="form-control returnQtyInput"
                               value="0"
                               data-index="${index}"
                               data-invoice-item-id="${escapeHtml(item.purchase_invoice_item_id)}">
                    </td>

                    <td class="amount-cell fw-bold lineTotalPreview">
                        0.00
                    </td>
                </tr>
            `);

        });

        calculateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | حساب السطر
    |--------------------------------------------------------------------------
    */
    function calculateLine(item, quantity) {

        let invoiceQuantity = parseFloat(item.invoice_quantity) || 0;
        let unitCost = parseFloat(item.unit_cost) || 0;
        let originalDiscount = parseFloat(item.discount_amount) || 0;
        let vatRate = parseFloat(item.vat_rate) || 0;

        let discountPerUnit = 0;

        if (invoiceQuantity > 0) {
            discountPerUnit = originalDiscount / invoiceQuantity;
        }

        let returnDiscount = discountPerUnit * quantity;

        let netBeforeVat = (quantity * unitCost) - returnDiscount;

        if (netBeforeVat < 0) {
            netBeforeVat = 0;
        }

        let vatAmount = netBeforeVat * vatRate / 100;
        let lineTotal = netBeforeVat + vatAmount;

        return {
            netBeforeVat: netBeforeVat,
            vatAmount: vatAmount,
            lineTotal: lineTotal
        };
    }


    /*
    |--------------------------------------------------------------------------
    | حساب الإجماليات
    |--------------------------------------------------------------------------
    */
    function calculateTotals() {

        let subtotal = 0;
        let vatTotal = 0;
        let total = 0;

        $('.returnQtyInput').each(function () {

            let input = $(this);
            let row = input.closest('tr');
            let index = input.data('index');

            let item = invoiceItems[index];

            if (!item) {
                return;
            }

            let quantity = parseFloat(input.val()) || 0;
            let available = parseFloat(row.find('.availableQty').data('available')) || 0;

            if (quantity < 0) {
                quantity = 0;
                input.val('0');
            }

            if (quantity > available) {
                quantity = available;
                input.val(quantity);
            }

            let line = calculateLine(item, quantity);

            subtotal += line.netBeforeVat;
            vatTotal += line.vatAmount;
            total += line.lineTotal;

            row.find('.lineTotalPreview').text(formatNumber(line.lineTotal));
        });

        $('#subtotalPreview').text(formatNumber(subtotal));
        $('#vatPreview').text(formatNumber(vatTotal));
        $('#totalPreview').text(formatNumber(total));
    }


    $(document).on('input', '.returnQtyInput', function () {
        calculateTotals();
    });


    $('#saveDraftBtn').on('click', function () {
        $('#save_action').val('draft');
    });


    $('#savePostBtn').on('click', function () {
        $('#save_action').val('post');
    });


    /*
    |--------------------------------------------------------------------------
    | إرسال الفورم
    |--------------------------------------------------------------------------
    */
    $('#purchase_return_form').on('submit', function (e) {
        e.preventDefault();

        $('#form_errors').html('');

        let selectedItems = [];

        $('.returnQtyInput').each(function () {

            let quantity = parseFloat($(this).val()) || 0;

            if (quantity > 0) {
                selectedItems.push({
                    purchase_invoice_item_id: $(this).data('invoice-item-id'),
                    quantity: quantity
                });
            }

        });

        if (!$('#purchase_invoice_id').val()) {
            showWarning('يرجى اختيار فاتورة المشتريات.');
            return;
        }

        if (selectedItems.length === 0) {
            showWarning('يجب إدخال كمية مرتجعة لصنف واحد على الأقل.');
            return;
        }

        let total = parseFloat($('#totalPreview').text()) || 0;

        if (total <= 0) {
            showWarning('يجب أن يكون إجمالي المردود أكبر من صفر.');
            return;
        }

        $('#saveDraftBtn, #savePostBtn').prop('disabled', true);

        let formData = [
            {
                name: '_token',
                value: "{{ csrf_token() }}"
            },
            {
                name: 'save_action',
                value: $('#save_action').val()
            },
            {
                name: 'return_no',
                value: $('#return_no').val()
            },
            {
                name: 'purchase_invoice_id',
                value: $('#purchase_invoice_id').val()
            },
            {
                name: 'return_date',
                value: $('#return_date').val()
            },
            {
                name: 'notes',
                value: $('#notes').val()
            }
        ];

        selectedItems.forEach(function (item, index) {

            formData.push({
                name: `items[${index}][purchase_invoice_item_id]`,
                value: item.purchase_invoice_item_id
            });

            formData.push({
                name: `items[${index}][quantity]`,
                value: item.quantity
            });

        });

        $.ajax({
            url: "{{ route('purchase-returns.store') }}",
            type: "POST",
            data: $.param(formData),

            success: function (response) {

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: response.message ?? 'تم حفظ مردود المشتريات بنجاح',
                        timer: 1300,
                        showConfirmButton: false
                    });
                } else {
                    alert(response.message ?? 'تم حفظ مردود المشتريات بنجاح');
                }

                setTimeout(function () {
                    window.location.href = "{{ url('/purchase-returns') }}/" + response.purchase_return_id;
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

                    let message = xhr.responseJSON?.message ?? 'حدث خطأ غير متوقع أثناء حفظ مردود المشتريات';

                    showError(message);
                }
            }
        });

    });

});
</script>
@endpush

</x-app-layout>