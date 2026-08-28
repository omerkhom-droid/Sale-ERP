<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form method="POST" action="{{ route('general-payment-vouchers.store') }}" id="generalPaymentVoucherForm">
        @csrf

        @php
            $accountLabel = function ($account) {
                $code = $account->account_code ?? $account->code ?? '';
                $name = $account->account_name_ar
                    ?? $account->account_name
                    ?? $account->name
                    ?? '';

                return trim($code . ' - ' . $name, ' -');
            };

            $branchLabel = function ($branch) {
                return $branch->branch_name_ar
                    ?? $branch->branch_name
                    ?? $branch->name
                    ?? '-';
            };
        @endphp


        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إنشاء سند صرف عام</h3>
                <p class="page-subtitle mb-0">
                    تسجيل سند صرف غير مرتبط بمورد مع تحديد حساب الصرف والحساب المقابل.
                </p>
            </div>

            <a href="{{ route('general-payment-vouchers.index') }}" class="btn btn-secondary">
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
                    <small>البيانات الأساسية للسند وطريقة الصرف والمبلغ</small>
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
                               placeholder="اتركه فارغ للتوليد التلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ السند <span class="text-danger">*</span></label>
                        <input type="date"
                               name="voucher_date"
                               class="form-control"
                               value="{{ old('voucher_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">طريقة الصرف <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>
                                نقدي
                            </option>
                            <option value="card" @selected(old('payment_method') === 'card')>
                                شبكة
                            </option>
                            <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>
                                تحويل بنكي
                            </option>
                            <option value="other" @selected(old('payment_method') === 'other')>
                                أخرى
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
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


        {{-- Branch / Cost Center --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">الفرع ومركز التكلفة</h5>
                    <small>تحديد البعد الإداري المستخدم في القيد المحاسبي</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">بدون فرع</option>

                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branchLabel($branch) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
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

                </div>

            </div>
        </div>


        {{-- Accounts --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">حسابات السند</h5>
                    <small>تحديد الطرف المدين والطرف الدائن في القيد المتوقع</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">اسم المستفيد</label>
                        <input type="text"
                               name="payee_name"
                               class="form-control"
                               value="{{ old('payee_name') }}"
                               placeholder="مثال: شخص / جهة / مصروف">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">حساب الصرف <span class="text-danger">*</span></label>
                        <select name="cash_bank_account_id"
                                id="cash_bank_account_id"
                                class="form-select"
                                required>
                            <option value="">اختر حساب الصندوق أو البنك</option>

                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('cash_bank_account_id') == $account->id)>
                                    {{ $accountLabel($account) }}
                                </option>
                            @endforeach
                        </select>

                        <small class="field-hint">هذا الحساب سيكون دائنًا في القيد.</small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">الحساب المقابل <span class="text-danger">*</span></label>
                        <select name="opposite_account_id"
                                id="opposite_account_id"
                                class="form-select"
                                required>
                            <option value="">اختر الحساب المقابل</option>

                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('opposite_account_id') == $account->id)>
                                    {{ $accountLabel($account) }}
                                </option>
                            @endforeach
                        </select>

                        <small class="field-hint">
                            هذا الحساب سيكون مدينًا في القيد: مصروف / أصل / عهدة / أي حساب عام.
                        </small>
                    </div>

                </div>

            </div>
        </div>


        {{-- Journal Preview --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">القيد المحاسبي المتوقع</h5>
                    <small>معاينة الطرف المدين والدائن قبل حفظ السند</small>
                </div>
            </div>

            <div class="card-body">

                <div class="journal-preview-grid">

                    <div class="journal-preview-box debit-box">
                        <div class="preview-label">الطرف المدين</div>
                        <div class="preview-title">من حـ / الحساب المقابل</div>
                        <div class="preview-amount text-success" id="debitAmountPreview">0.00</div>
                    </div>

                    <div class="journal-preview-box credit-box">
                        <div class="preview-label">الطرف الدائن</div>
                        <div class="preview-title">إلى حـ / حساب الصرف</div>
                        <div class="preview-amount text-danger" id="creditAmountPreview">0.00</div>
                    </div>

                </div>

            </div>
        </div>


        {{-- Notes --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات السند</h5>
                    <small>أي تفاصيل إضافية تظهر ضمن بيانات السند</small>
                </div>
            </div>

            <div class="card-body">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes"
                          class="form-control"
                          rows="3"
                          placeholder="ملاحظات السند">{{ old('notes') }}</textarea>
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">

            <a href="{{ route('general-payment-vouchers.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" name="save_action" value="draft" class="btn btn-outline-secondary">
                حفظ مسودة
            </button>

            @can('general_payment_vouchers.post')
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
        text-align: center;
        font-weight: 900;
    }

    .field-hint {
        display: block;
        color: #64748B !important;
        font-weight: 700;
        margin-top: 7px;
        line-height: 1.7;
    }

    .journal-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .journal-preview-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .journal-preview-box.debit-box {
        border-right: 5px solid #16A34A;
    }

    .journal-preview-box.credit-box {
        border-right: 5px solid #E63B4A;
    }

    .preview-label {
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 6px;
    }

    .preview-title {
        color: #071633;
        font-size: 16px;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .preview-amount {
        direction: ltr;
        font-size: 24px;
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

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .journal-preview-grid {
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

    function updatePreview() {
        let amount = parseFloat($('#amount').val()) || 0;

        $('#debitAmountPreview').text(amount.toFixed(2));
        $('#creditAmountPreview').text(amount.toFixed(2));
    }


    function showValidationMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: message
            });
        } else {
            alert(message);
        }
    }


    $('#amount').on('input', function () {
        updatePreview();
    });


    $('#generalPaymentVoucherForm').on('submit', function (e) {
        let cashBankAccountId = $('#cash_bank_account_id').val();
        let oppositeAccountId = $('#opposite_account_id').val();
        let amount = parseFloat($('#amount').val()) || 0;

        if (amount <= 0) {
            e.preventDefault();
            showValidationMessage('يجب إدخال مبلغ أكبر من صفر.');
            return false;
        }

        if (cashBankAccountId && oppositeAccountId && cashBankAccountId === oppositeAccountId) {
            e.preventDefault();
            showValidationMessage('لا يمكن أن يكون حساب الصرف والحساب المقابل نفس الحساب.');
            return false;
        }
    });


    updatePreview();

});
</script>
@endpush

</x-app-layout>