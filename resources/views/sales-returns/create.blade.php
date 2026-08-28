<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('sales-returns.store') }}" id="salesReturnForm">
        @csrf

        @php
            $oldItems = old('items', []);
        @endphp

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إنشاء مردود مبيعات</h3>
                <p class="page-subtitle mb-0">
                    إنشاء مردود لفاتورة بيع مرحلة مع تحديد الكميات المراد إرجاعها وحساب إجمالي المردود تلقائيًا.
                </p>
            </div>

            <a href="{{ route('sales-returns.index') }}" class="btn btn-secondary">
                رجوع
            </a>
        </div>


        {{-- Alerts --}}
        @if(session('error'))
            <div class="alert alert-danger mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <strong>يوجد أخطاء:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Return Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات المردود</h5>
                    <small>رقم المردود والتاريخ وفاتورة البيع المرتبطة</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">رقم المردود</label>
                        <input type="text"
                               name="return_no"
                               class="form-control"
                               value="{{ old('return_no') }}"
                               placeholder="اتركه فارغ للتوليد التلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ المردود <span class="text-danger">*</span></label>
                        <input type="date"
                               name="return_date"
                               class="form-control"
                               value="{{ old('return_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">فاتورة البيع <span class="text-danger">*</span></label>
                        <select name="sales_invoice_id" id="sales_invoice_id" class="form-select" required>
                            <option value="">اختر فاتورة البيع</option>

                            @foreach($salesInvoices as $invoice)
                                <option value="{{ $invoice->id }}"
                                    {{ old('sales_invoice_id') == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_no }}
                                    -
                                    {{ $invoice->invoice_date?->format('Y-m-d') }}
                                    -
                                    {{ $invoice->customer?->customer_name
                                        ?? $invoice->customer?->name
                                        ?? $invoice->customer_name
                                        ?? 'عميل نقدي' }}
                                    -
                                    إجمالي: {{ number_format((float) $invoice->total_amount, 2) }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                </div>

            </div>
        </div>


        {{-- Invoice Info --}}
        <div class="card shadow-sm wazin-card mb-4" id="invoiceInfoBox" style="display: none;">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات الفاتورة المختارة</h5>
                    <small>ملخص الفاتورة الأصلية قبل إنشاء المردود</small>
                </div>
            </div>

            <div class="card-body">

                <div class="invoice-info-grid">

                    <div class="info-box">
                        <span>رقم الفاتورة</span>
                        <strong id="invoice_no_display">-</strong>
                    </div>

                    <div class="info-box">
                        <span>تاريخ الفاتورة</span>
                        <strong id="invoice_date_display">-</strong>
                    </div>

                    <div class="info-box amount-box">
                        <span>الإجمالي</span>
                        <strong id="invoice_total_display">0.00</strong>
                    </div>

                    <div class="info-box paid-box">
                        <span>المدفوع</span>
                        <strong id="invoice_paid_display">0.00</strong>
                    </div>

                    <div class="info-box remaining-box">
                        <span>المتبقي</span>
                        <strong id="invoice_remaining_display">0.00</strong>
                    </div>

                </div>

            </div>
        </div>


        {{-- Items --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">أصناف الفاتورة المتاحة للإرجاع</h5>
                    <small>أدخل كمية المردود لكل صنف حسب الكمية المتاحة للإرجاع</small>
                </div>
            </div>

            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table" id="returnItemsTable">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصنف</th>
                                <th>الوحدة</th>
                                <th>كمية الفاتورة</th>
                                <th>مرتجع سابق</th>
                                <th>المتاح</th>
                                <th>كمية المردود</th>
                                <th>سعر البيع</th>
                                <th>الصافي</th>
                                <th>الضريبة</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        اختر فاتورة البيع لعرض الأصناف.
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>


        {{-- Totals --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">إجماليات المردود</h5>
                    <small>يتم احتسابها تلقائيًا بناءً على كميات الإرجاع</small>
                </div>
            </div>

            <div class="card-body">

                <div class="totals-grid">

                    <div class="total-box">
                        <span>إجمالي الصافي</span>
                        <input type="text"
                               id="subtotal_display"
                               class="form-control total-input"
                               value="0.00"
                               readonly>
                    </div>

                    <div class="total-box vat-box">
                        <span>إجمالي الضريبة</span>
                        <input type="text"
                               id="vat_display"
                               class="form-control total-input"
                               value="0.00"
                               readonly>
                    </div>

                    <div class="total-box final-box">
                        <span>إجمالي المردود</span>
                        <input type="text"
                               id="total_display"
                               class="form-control total-input final-total"
                               value="0.00"
                               readonly>
                    </div>

                </div>

            </div>
        </div>


        {{-- Notes --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات المردود</h5>
                    <small>أي تفاصيل إضافية مرتبطة بعملية الإرجاع</small>
                </div>
            </div>

            <div class="card-body">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('sales-returns.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ مسودة
            </button>

            @can('sales_returns.post')
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
        min-height: 100px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .invoice-info-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
    }

    .info-box,
    .total-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .info-box span,
    .total-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .info-box strong {
        display: block;
        direction: ltr;
        color: #071633;
        font-size: 16px;
        font-weight: 900;
        text-align: center;
    }

    .amount-box {
        border-right: 5px solid #2F6BFF;
    }

    .paid-box {
        border-right: 5px solid #16A34A;
    }

    .remaining-box {
        border-right: 5px solid #E63B4A;
    }

    .paid-box strong {
        color: #16A34A;
    }

    .remaining-box strong {
        color: #E63B4A;
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

    #returnItemsTable td:nth-child(2) {
        min-width: 240px;
        text-align: right;
    }

    #returnItemsTable .return-qty {
        direction: ltr;
        text-align: center;
        font-weight: 900;
        border-radius: 12px;
        min-width: 110px;
    }

    .amount-cell,
    .row-net,
    .row-vat,
    .row-total {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
    }

    .available-qty {
        color: #16A34A;
        font-weight: 900;
        direction: ltr;
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

    .totals-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .total-input {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
        font-size: 18px;
    }

    .vat-box {
        border-right: 5px solid #F59E0B;
    }

    .final-box {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
    }

    .final-total {
        background: rgba(47, 107, 255, 0.08) !important;
        color: #071633;
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
        .invoice-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .totals-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .invoice-info-grid {
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
<script>
$(document).ready(function () {

    const OLD_ITEMS_RAW = @json($oldItems);
    const OLD_ITEMS = Array.isArray(OLD_ITEMS_RAW) ? OLD_ITEMS_RAW : Object.values(OLD_ITEMS_RAW || {});


    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function oldQuantityForInvoiceItem(invoiceItemId, index) {
        let oldItem = OLD_ITEMS.find(function (item) {
            return String(item.sales_invoice_item_id ?? '') === String(invoiceItemId);
        });

        if (!oldItem && OLD_ITEMS[index]) {
            oldItem = OLD_ITEMS[index];
        }

        return parseFloat(oldItem?.quantity ?? 0) || 0;
    }


    /*
    |--------------------------------------------------------------------------
    | تحميل بنود فاتورة البيع
    |--------------------------------------------------------------------------
    */
    $('#sales_invoice_id').on('change', function () {

        let invoiceId = $(this).val();

        $('#invoiceInfoBox').hide();

        $('#returnItemsTable tbody').html(`
            <tr>
                <td colspan="11">
                    <div class="empty-state">جاري التحميل...</div>
                </td>
            </tr>
        `);

        if (!invoiceId) {
            $('#returnItemsTable tbody').html(`
                <tr>
                    <td colspan="11">
                        <div class="empty-state">اختر فاتورة البيع لعرض الأصناف.</div>
                    </td>
                </tr>
            `);

            calculateTotals();
            return;
        }

        let url = "{{ route('sales-returns.invoice-items', ':id') }}";
        url = url.replace(':id', invoiceId);

        $.get(url, function (response) {

            if (response.status !== 'success') {
                $('#returnItemsTable tbody').html(`
                    <tr>
                        <td colspan="11">
                            <div class="empty-state text-danger">حدث خطأ أثناء تحميل الأصناف.</div>
                        </td>
                    </tr>
                `);

                calculateTotals();
                return;
            }

            if (response.invoice) {
                $('#invoiceInfoBox').show();

                $('#invoice_no_display').text(response.invoice.invoice_no ?? '-');
                $('#invoice_date_display').text(response.invoice.invoice_date ?? '-');
                $('#invoice_total_display').text(parseFloat(response.invoice.total_amount || 0).toFixed(2));
                $('#invoice_paid_display').text(parseFloat(response.invoice.paid_amount || 0).toFixed(2));
                $('#invoice_remaining_display').text(parseFloat(response.invoice.remaining_amount || 0).toFixed(2));
            }

            if (!response.data || response.data.length === 0) {
                $('#returnItemsTable tbody').html(`
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                لا توجد أصناف متاحة للإرجاع في هذه الفاتورة.
                            </div>
                        </td>
                    </tr>
                `);

                calculateTotals();
                return;
            }

            let rows = '';

            response.data.forEach(function (item, index) {

                let productName = item.product_name ?? '-';
                let sku = item.product_sku
                    ? `<small class="text-muted d-block mt-1">كود: ${escapeHtml(item.product_sku)}</small>`
                    : '';

                let originalQty = parseFloat(item.quantity || 0);
                let previousReturnedQty = parseFloat(item.previous_returned_qty || 0);
                let availableQty = parseFloat(item.available_qty || 0);
                let oldQty = oldQuantityForInvoiceItem(item.id, index);

                if (oldQty > availableQty) {
                    oldQty = availableQty;
                }

                rows += `
                    <tr>
                        <td class="fw-bold">${index + 1}</td>

                        <td>
                            <div class="fw-bold">${escapeHtml(productName)}</div>
                            ${sku}

                            <input type="hidden"
                                   name="items[${index}][sales_invoice_item_id]"
                                   value="${escapeHtml(item.id)}">
                        </td>

                        <td>${escapeHtml(item.unit_name ?? '-')}</td>

                        <td class="amount-cell">${originalQty.toFixed(3)}</td>

                        <td class="amount-cell">${previousReturnedQty.toFixed(3)}</td>

                        <td class="available-qty">${availableQty.toFixed(3)}</td>

                        <td>
                            <input type="number"
                                   name="items[${index}][quantity]"
                                   class="form-control return-qty"
                                   step="0.001"
                                   min="0"
                                   max="${availableQty.toFixed(3)}"
                                   value="${oldQty.toFixed(3)}"
                                   data-original-qty="${originalQty.toFixed(3)}"
                                   data-available-qty="${availableQty.toFixed(3)}"
                                   data-net-amount="${parseFloat(item.net_amount || 0).toFixed(2)}"
                                   data-vat-amount="${parseFloat(item.vat_amount || 0).toFixed(2)}"
                                   data-line-total="${parseFloat(item.line_total || 0).toFixed(2)}">
                        </td>

                        <td class="amount-cell">
                            ${parseFloat(item.unit_price || 0).toFixed(2)}
                        </td>

                        <td class="row-net">0.00</td>

                        <td class="row-vat">0.00</td>

                        <td class="row-total">0.00</td>
                    </tr>
                `;
            });

            $('#returnItemsTable tbody').html(rows);

            calculateTotals();

        }).fail(function () {
            $('#returnItemsTable tbody').html(`
                <tr>
                    <td colspan="11">
                        <div class="empty-state text-danger">
                            حدث خطأ أثناء تحميل أصناف الفاتورة.
                        </div>
                    </td>
                </tr>
            `);

            calculateTotals();
        });
    });


    /*
    |--------------------------------------------------------------------------
    | حساب الإجماليات
    |--------------------------------------------------------------------------
    */
    function calculateTotals() {

        let subtotal = 0;
        let vatTotal = 0;
        let grandTotal = 0;

        $('.return-qty').each(function () {

            let input = $(this);
            let row = input.closest('tr');

            let returnQty = parseFloat(input.val()) || 0;
            let originalQty = parseFloat(input.data('original-qty')) || 0;
            let availableQty = parseFloat(input.data('available-qty')) || 0;

            let originalNet = parseFloat(input.data('net-amount')) || 0;
            let originalVat = parseFloat(input.data('vat-amount')) || 0;
            let originalTotal = parseFloat(input.data('line-total')) || 0;

            if (returnQty > availableQty) {
                returnQty = availableQty;
                input.val(returnQty.toFixed(3));
            }

            if (returnQty < 0) {
                returnQty = 0;
                input.val('0.000');
            }

            let ratio = 0;

            if (originalQty > 0 && returnQty > 0) {
                ratio = returnQty / originalQty;
            }

            let rowNet = originalNet * ratio;
            let rowVat = originalVat * ratio;
            let rowTotal = originalTotal * ratio;

            row.find('.row-net').text(rowNet.toFixed(2));
            row.find('.row-vat').text(rowVat.toFixed(2));
            row.find('.row-total').text(rowTotal.toFixed(2));

            subtotal += rowNet;
            vatTotal += rowVat;
            grandTotal += rowTotal;
        });

        $('#subtotal_display').val(subtotal.toFixed(2));
        $('#vat_display').val(vatTotal.toFixed(2));
        $('#total_display').val(grandTotal.toFixed(2));
    }


    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    $(document).on('input', '.return-qty', function () {
        calculateTotals();
    });


    $('#salesReturnForm').on('submit', function (e) {
        let grandTotal = parseFloat($('#total_display').val()) || 0;

        if (grandTotal <= 0) {
            e.preventDefault();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يجب إدخال كمية مردود أكبر من صفر.'
                });
            } else {
                alert('يجب إدخال كمية مردود أكبر من صفر.');
            }

            return false;
        }
    });


    /*
        في حالة رجوع الصفحة بعد validation error.
    */
    if ($('#sales_invoice_id').val()) {
        $('#sales_invoice_id').trigger('change');
    }

    calculateTotals();

});
</script>
@endpush

</x-app-layout>