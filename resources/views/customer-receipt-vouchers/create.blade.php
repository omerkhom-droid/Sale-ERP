<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('customer-receipt-vouchers.store') }}" id="receiptVoucherForm">
        @csrf

        @php
            $oldAllocations = old('allocations', []);
        @endphp

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إنشاء سند قبض عميل</h3>
                <p class="page-subtitle mb-0">
                    تسجيل سند قبض من عميل وتوزيع المبلغ على الفواتير غير المسددة.
                </p>
            </div>

            <a href="{{ route('customer-receipt-vouchers.index') }}" class="btn btn-secondary">
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


        {{-- Voucher Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات السند</h5>
                    <small>رقم السند والتاريخ والفرع ومركز التكلفة وطريقة القبض</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-2">
                        <label class="form-label">رقم السند</label>
                        <input type="text"
                               name="voucher_no"
                               class="form-control"
                               value="{{ old('voucher_no') }}"
                               placeholder="تلقائي">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">تاريخ السند <span class="text-danger">*</span></label>
                        <input type="date"
                               name="receipt_date"
                               class="form-control"
                               value="{{ old('receipt_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">اختر الفرع</option>

                            @foreach($branches as $branch)
                                @php
                                    $branchId = $branch->id ?? $branch->branch_id;
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
                        <label class="form-label">مركز التكلفة</label>
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
                        <label class="form-label">طريقة القبض <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'selected' : '' }}>
                                نقدي
                            </option>
                            <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>
                                شبكة
                            </option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>
                                تحويل بنكي
                            </option>
                            <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>
                                أخرى
                            </option>
                        </select>
                    </div>

                </div>
            </div>
        </div>


        {{-- Customer / Amount --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات العميل والمبلغ</h5>
                    <small>اختيار العميل وتحديد مبلغ السند قبل توزيعه على الفواتير</small>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">العميل <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">اختر العميل</option>

                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->customer_name ?? $customer->name ?? $customer->fullname ?? 'عميل #' . $customer->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">مبلغ السند <span class="text-danger">*</span></label>
                        <input type="number"
                               name="amount"
                               id="voucher_amount"
                               class="form-control amount-input"
                               step="0.01"
                               min="0.01"
                               value="{{ old('amount', 0) }}"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">غير موزع</label>
                        <input type="text"
                               id="unallocated_display"
                               class="form-control amount-input unallocated-input"
                               value="0.00"
                               readonly>
                    </div>

                </div>
            </div>
        </div>


        {{-- Invoices Allocation --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">توزيع السند على فواتير العميل</h5>
                    <small>يمكن التوزيع يدويًا أو استخدام التوزيع التلقائي حسب أقدم الفواتير</small>
                </div>

                <button type="button" class="btn btn-success" id="autoDistributeBtn">
                    توزيع تلقائي
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table" id="invoicesTable">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>التاريخ</th>
                                <th>إجمالي الفاتورة</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>مبلغ التوزيع</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        اختر العميل لعرض الفواتير غير المسددة.
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                    </table>

                </div>
            </div>
        </div>


        {{-- Summary --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملخص السند</h5>
                    <small>ملخص مبلغ السند والتوزيع والمبلغ المتبقي غير الموزع</small>
                </div>
            </div>

            <div class="card-body">
                <div class="totals-grid">

                    <div class="total-box">
                        <span>مبلغ السند</span>
                        <input type="text"
                               id="amount_display"
                               class="form-control total-input"
                               value="0.00"
                               readonly>
                    </div>

                    <div class="total-box allocated-box">
                        <span>إجمالي التوزيع</span>
                        <input type="text"
                               id="allocated_display"
                               class="form-control total-input"
                               value="0.00"
                               readonly>
                    </div>

                    <div class="total-box unallocated-box">
                        <span>غير موزع</span>
                        <input type="text"
                               id="unallocated_display_2"
                               class="form-control total-input"
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
                    <h5 class="mb-0 fw-bold">ملاحظات السند</h5>
                    <small>أي تفاصيل إضافية تخص سند القبض</small>
                </div>
            </div>

            <div class="card-body">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('customer-receipt-vouchers.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ مسودة
            </button>

            @can('customer_receipt_vouchers.post')
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
        min-height: 100px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .amount-input,
    .total-input,
    .allocation-amount,
    .amount-cell {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
    }

    .unallocated-input {
        background: rgba(47, 107, 255, 0.06) !important;
        color: #071633;
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

    #invoicesTable td:nth-child(2) {
        direction: ltr;
        font-weight: 900;
    }

    #invoicesTable .allocation-amount {
        min-width: 120px;
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

    .totals-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
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

    .allocated-box {
        border-right: 5px solid #16A34A;
    }

    .unallocated-box {
        border-right: 5px solid #2F6BFF;
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

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .is-invalid {
        border-color: #E63B4A !important;
        box-shadow: 0 0 0 .2rem rgba(230, 59, 74, .12) !important;
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
            grid-template-columns: 1fr;
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

    const OLD_ALLOCATIONS_RAW = @json($oldAllocations);
    const OLD_ALLOCATIONS = Array.isArray(OLD_ALLOCATIONS_RAW)
        ? OLD_ALLOCATIONS_RAW
        : Object.values(OLD_ALLOCATIONS_RAW || {});


    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function oldAllocationAmount(invoiceId, index) {
        let oldAllocation = OLD_ALLOCATIONS.find(function (allocation) {
            return String(allocation.sales_invoice_id ?? '') === String(invoiceId);
        });

        if (!oldAllocation && OLD_ALLOCATIONS[index]) {
            oldAllocation = OLD_ALLOCATIONS[index];
        }

        return parseFloat(oldAllocation?.amount ?? 0) || 0;
    }


    /*
    |--------------------------------------------------------------------------
    | تحميل فواتير العميل
    |--------------------------------------------------------------------------
    */
    $('#customer_id').on('change', function () {

        let customerId = $(this).val();

        $('#invoicesTable tbody').html(`
            <tr>
                <td colspan="7">
                    <div class="empty-state">جاري التحميل...</div>
                </td>
            </tr>
        `);

        if (!customerId) {
            $('#invoicesTable tbody').html(`
                <tr>
                    <td colspan="7">
                        <div class="empty-state">اختر العميل لعرض الفواتير غير المسددة.</div>
                    </td>
                </tr>
            `);

            calculateTotals();
            return;
        }

        let url = "{{ route('customer-receipt-vouchers.customer-invoices', ':id') }}";
        url = url.replace(':id', customerId);

        $.get(url, function (response) {

            if (response.status !== 'success' || !response.data || response.data.length === 0) {
                $('#invoicesTable tbody').html(`
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">لا توجد فواتير غير مسددة لهذا العميل.</div>
                        </td>
                    </tr>
                `);

                calculateTotals();
                return;
            }

            let rows = '';

            response.data.forEach(function (invoice, index) {

                let remainingAmount = parseFloat(invoice.remaining_amount || 0);
                let oldAmount = oldAllocationAmount(invoice.id, index);

                if (oldAmount > remainingAmount) {
                    oldAmount = remainingAmount;
                }

                rows += `
                    <tr>
                        <td class="fw-bold">${index + 1}</td>

                        <td>
                            ${escapeHtml(invoice.invoice_no)}

                            <input type="hidden"
                                   name="allocations[${index}][sales_invoice_id]"
                                   value="${escapeHtml(invoice.id)}">
                        </td>

                        <td class="amount-cell">${escapeHtml(invoice.invoice_date ?? '-')}</td>

                        <td class="amount-cell">
                            ${parseFloat(invoice.total_amount || 0).toFixed(2)}
                        </td>

                        <td class="amount-cell text-success">
                            ${parseFloat(invoice.paid_amount || 0).toFixed(2)}
                        </td>

                        <td class="amount-cell text-danger">
                            ${remainingAmount.toFixed(2)}
                        </td>

                        <td>
                            <input type="number"
                                   name="allocations[${index}][amount]"
                                   class="form-control allocation-amount"
                                   step="0.01"
                                   min="0"
                                   max="${remainingAmount.toFixed(2)}"
                                   data-remaining="${remainingAmount.toFixed(2)}"
                                   value="${oldAmount.toFixed(2)}">
                        </td>
                    </tr>
                `;
            });

            $('#invoicesTable tbody').html(rows);

            calculateTotals();

        }).fail(function () {
            $('#invoicesTable tbody').html(`
                <tr>
                    <td colspan="7">
                        <div class="empty-state text-danger">حدث خطأ أثناء تحميل فواتير العميل.</div>
                    </td>
                </tr>
            `);

            calculateTotals();
        });
    });


    /*
    |--------------------------------------------------------------------------
    | توزيع تلقائي
    |--------------------------------------------------------------------------
    */
    $('#autoDistributeBtn').on('click', function () {

        let remainingVoucherAmount = parseFloat($('#voucher_amount').val()) || 0;

        $('.allocation-amount').each(function () {

            let invoiceRemaining = parseFloat($(this).data('remaining')) || 0;

            if (remainingVoucherAmount <= 0) {
                $(this).val('0.00');
                return;
            }

            let allocation = Math.min(remainingVoucherAmount, invoiceRemaining);

            $(this).val(allocation.toFixed(2));

            remainingVoucherAmount -= allocation;
        });

        calculateTotals();
    });


    /*
    |--------------------------------------------------------------------------
    | حساب الإجماليات
    |--------------------------------------------------------------------------
    */
    function calculateTotals() {

        let voucherAmount = parseFloat($('#voucher_amount').val()) || 0;
        let allocatedTotal = 0;

        $('.allocation-amount').each(function () {

            let input = $(this);
            let amount = parseFloat(input.val()) || 0;
            let invoiceRemaining = parseFloat(input.data('remaining')) || 0;

            if (amount > invoiceRemaining) {
                amount = invoiceRemaining;
                input.val(amount.toFixed(2));
            }

            if (amount < 0) {
                amount = 0;
                input.val('0.00');
            }

            allocatedTotal += amount;
        });

        if (allocatedTotal > voucherAmount) {
            $('#allocated_display').addClass('is-invalid');
        } else {
            $('#allocated_display').removeClass('is-invalid');
        }

        let unallocated = voucherAmount - allocatedTotal;

        if (unallocated < 0) {
            unallocated = 0;
        }

        $('#amount_display').val(voucherAmount.toFixed(2));
        $('#allocated_display').val(allocatedTotal.toFixed(2));
        $('#unallocated_display').val(unallocated.toFixed(2));
        $('#unallocated_display_2').val(unallocated.toFixed(2));
    }


    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    $('#voucher_amount').on('input', function () {
        calculateTotals();
    });

    $(document).on('input', '.allocation-amount', function () {
        calculateTotals();
    });


    $('#receiptVoucherForm').on('submit', function (e) {
        let voucherAmount = parseFloat($('#voucher_amount').val()) || 0;
        let allocatedTotal = parseFloat($('#allocated_display').val()) || 0;

        if (voucherAmount <= 0) {
            e.preventDefault();
            showError('يجب إدخال مبلغ سند أكبر من صفر.');
            return false;
        }

        if (allocatedTotal > voucherAmount) {
            e.preventDefault();
            showError('لا يمكن أن يكون إجمالي التوزيع أكبر من مبلغ السند.');
            return false;
        }
    });


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


    /*
        لو رجع المستخدم بسبب validation error ومعه customer_id قديم.
    */
    if ($('#customer_id').val()) {
        $('#customer_id').trigger('change');
    }

    calculateTotals();

});
</script>
@endpush

</x-app-layout>