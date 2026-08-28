<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('customer-refund-vouchers.store') }}" id="refundVoucherForm">
        @csrf

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إنشاء سند صرف عميل</h3>
                <p class="page-subtitle mb-0">
                    تسجيل مبلغ مصروف للعميل بناءً على مردود مبيعات مستحق وغير مصروف بالكامل.
                </p>
            </div>

            <a href="{{ route('customer-refund-vouchers.index') }}" class="btn btn-secondary">
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
                    <small>رقم السند وتاريخه وطريقة الصرف ومبلغ الصرف</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">رقم السند</label>
                        <input type="text"
                               name="voucher_no"
                               class="form-control"
                               value="{{ old('voucher_no') }}"
                               placeholder="تلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ السند <span class="text-danger">*</span></label>
                        <input type="date"
                               name="refund_date"
                               class="form-control"
                               value="{{ old('refund_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">طريقة الصرف <span class="text-danger">*</span></label>
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

                    <div class="col-md-3">
                        <label class="form-label">مبلغ الصرف <span class="text-danger">*</span></label>
                        <input type="number"
                               name="amount"
                               id="amount"
                               class="form-control amount-input"
                               step="0.01"
                               min="0.01"
                               value="{{ old('amount', 0) }}"
                               required>
                    </div>

                </div>

            </div>
        </div>


        {{-- Sales Return Selection --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">مردود المبيعات المستحق للعميل</h5>
                    <small>اختر مردود المبيعات الذي سيتم صرف المبلغ بناءً عليه</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-12">
                        <label class="form-label">مردود المبيعات <span class="text-danger">*</span></label>
                        <select name="sales_return_id" id="sales_return_id" class="form-select" required>
                            <option value="">اختر مردود المبيعات</option>

                            @foreach($salesReturns as $return)
                                @php
                                    $availableAmount = (float) $return->refundable_amount - (float) ($return->refunded_amount ?? 0);
                                @endphp

                                <option value="{{ $return->id }}"
                                    {{ old('sales_return_id') == $return->id ? 'selected' : '' }}>
                                    {{ $return->return_no }}
                                    -
                                    {{ $return->return_date?->format('Y-m-d') }}
                                    -
                                    {{ $return->customer?->customer_name
                                        ?? $return->customer?->name
                                        ?? $return->salesInvoice?->customer_name
                                        ?? 'عميل نقدي' }}
                                    -
                                    المتاح: {{ number_format($availableAmount, 2) }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                </div>

            </div>
        </div>


        {{-- Sales Return Info --}}
        <div class="card shadow-sm wazin-card mb-4" id="salesReturnInfoBox" style="display: none;">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات مردود المبيعات</h5>
                    <small>ملخص المردود والمبلغ المتاح للصرف</small>
                </div>
            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info-box">
                        <span>رقم المردود</span>
                        <strong id="return_no_display">-</strong>
                    </div>

                    <div class="info-box">
                        <span>تاريخ المردود</span>
                        <strong id="return_date_display">-</strong>
                    </div>

                    <div class="info-box">
                        <span>فاتورة البيع</span>
                        <strong id="invoice_no_display">-</strong>
                    </div>

                    <div class="info-box customer-box">
                        <span>العميل</span>
                        <strong id="customer_name_display">-</strong>
                    </div>

                    <div class="info-box amount-box">
                        <span>إجمالي المردود</span>
                        <strong id="total_amount_display">0.00</strong>
                    </div>

                    <div class="info-box refundable-box">
                        <span>المستحق للعميل</span>
                        <strong id="refundable_amount_display">0.00</strong>
                    </div>

                    <div class="info-box refunded-box">
                        <span>تم صرفه سابقًا</span>
                        <strong id="refunded_amount_display">0.00</strong>
                    </div>

                    <div class="info-box available-box">
                        <span>المتاح للصرف</span>
                        <strong id="available_amount_display">0.00</strong>
                    </div>

                </div>

            </div>
        </div>


        {{-- Notes --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات السند</h5>
                    <small>أي تفاصيل إضافية تخص سند الصرف</small>
                </div>
            </div>

            <div class="card-body">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('customer-refund-vouchers.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ مسودة
            </button>

            @can('customer_refund_vouchers.post')
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

    .amount-input {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
        font-size: 16px;
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

    .customer-box strong {
        direction: rtl;
        text-align: right;
    }

    .amount-box {
        border-right: 5px solid #2F6BFF;
    }

    .refundable-box {
        border-right: 5px solid #16A34A;
    }

    .refunded-box {
        border-right: 5px solid #F59E0B;
    }

    .available-box {
        border-right: 5px solid #16A34A;
        background: rgba(22, 163, 74, 0.07);
    }

    .refundable-box strong,
    .available-box strong {
        color: #16A34A;
    }

    .refunded-box strong {
        color: #F59E0B;
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
        .info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
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

        .info-grid {
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

    let availableAmount = 0;


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
    |--------------------------------------------------------------------------
    | تحميل بيانات مردود المبيعات
    |--------------------------------------------------------------------------
    */
    $('#sales_return_id').on('change', function () {

        let salesReturnId = $(this).val();

        $('#salesReturnInfoBox').hide();
        $('#amount').removeClass('is-invalid');
        availableAmount = 0;

        if (!salesReturnId) {
            return;
        }

        let url = "{{ route('customer-refund-vouchers.sales-return-data', ':id') }}";
        url = url.replace(':id', salesReturnId);

        $.get(url, function (response) {

            if (response.status !== 'success') {
                showError('تعذر تحميل بيانات مردود المبيعات.');
                return;
            }

            let data = response.data;

            availableAmount = parseFloat(data.available_amount) || 0;

            $('#return_no_display').text(data.return_no ?? '-');
            $('#return_date_display').text(data.return_date ?? '-');
            $('#invoice_no_display').text(data.invoice_no ?? '-');
            $('#customer_name_display').text(data.customer_name ?? '-');

            $('#total_amount_display').text(parseFloat(data.total_amount || 0).toFixed(2));
            $('#refundable_amount_display').text(parseFloat(data.refundable_amount || 0).toFixed(2));
            $('#refunded_amount_display').text(parseFloat(data.refunded_amount || 0).toFixed(2));
            $('#available_amount_display').text(availableAmount.toFixed(2));

            let currentAmount = parseFloat($('#amount').val()) || 0;

            if (currentAmount <= 0) {
                $('#amount').val(availableAmount.toFixed(2));
            }

            $('#amount').attr('max', availableAmount.toFixed(2));

            validateAmount();

            $('#salesReturnInfoBox').show();

        }).fail(function () {
            showError('حدث خطأ أثناء تحميل بيانات مردود المبيعات.');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | التحقق من مبلغ الصرف
    |--------------------------------------------------------------------------
    */
    function validateAmount() {

        let amount = parseFloat($('#amount').val()) || 0;

        if (amount < 0) {
            amount = 0;
            $('#amount').val('0.00');
        }

        if (availableAmount > 0 && amount > availableAmount) {
            $('#amount').val(availableAmount.toFixed(2));
            amount = availableAmount;
        }

        if (amount <= 0) {
            $('#amount').addClass('is-invalid');
        } else {
            $('#amount').removeClass('is-invalid');
        }
    }


    $('#amount').on('input', function () {
        validateAmount();
    });


    $('#refundVoucherForm').on('submit', function (e) {

        let salesReturnId = $('#sales_return_id').val();
        let amount = parseFloat($('#amount').val()) || 0;

        if (!salesReturnId) {
            e.preventDefault();
            showError('يرجى اختيار مردود المبيعات.');
            return false;
        }

        if (amount <= 0) {
            e.preventDefault();
            showError('يجب إدخال مبلغ صرف أكبر من صفر.');
            return false;
        }

        if (availableAmount > 0 && amount > availableAmount) {
            e.preventDefault();
            showError('مبلغ الصرف لا يمكن أن يتجاوز المبلغ المتاح للصرف.');
            return false;
        }
    });


    /*
        في حالة الرجوع بسبب validation error.
    */
    if ($('#sales_return_id').val()) {
        $('#sales_return_id').trigger('change');
    }

});
</script>
@endpush

</x-app-layout>