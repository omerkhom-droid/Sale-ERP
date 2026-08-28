<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">عرض سند صرف عام</h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات سند الصرف العام والحسابات المرتبطة وحالة الترحيل أو الإلغاء.
            </p>
        </div>

        <div class="header-actions">
            <a href="{{ route('general-payment-vouchers.index') }}" class="btn btn-secondary">
                رجوع
            </a>

            @if($voucher->status === 'draft')
                @can('general_payment_vouchers.post')
                    <button type="button"
                            class="btn btn-success"
                            id="postVoucherBtn"
                            data-id="{{ $voucher->id }}">
                        ترحيل
                    </button>
                @endcan
            @endif

            @if($voucher->status !== 'cancelled')
                @can('general_payment_vouchers.cancel')
                    <button type="button"
                            class="btn btn-danger"
                            id="cancelVoucherBtn"
                            data-id="{{ $voucher->id }}">
                        إلغاء
                    </button>
                @endcan
            @endif
        </div>
    </div>


    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif


    {{-- Status --}}
    <div class="status-card mb-4">
        <div>
            <span class="status-label">حالة السند</span>

            @if($voucher->status === 'draft')
                <span class="badge bg-secondary fs-6">مسودة</span>
            @elseif($voucher->status === 'posted')
                <span class="badge bg-success fs-6">مرحل</span>
            @elseif($voucher->status === 'cancelled')
                <span class="badge bg-danger fs-6">ملغى</span>
            @else
                <span class="badge bg-light text-dark fs-6">{{ $voucher->status }}</span>
            @endif
        </div>

        <div class="voucher-amount-box">
            <span>المبلغ</span>
            <strong>{{ number_format((float) $voucher->amount, 2) }}</strong>
        </div>
    </div>


    {{-- Voucher Info --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات السند</h5>
                <small>البيانات الأساسية وطريقة الصرف</small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">رقم السند</label>
                    <div class="readonly-box ltr-cell">
                        {{ $voucher->voucher_no }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ السند</label>
                    <div class="readonly-box ltr-cell">
                        {{ optional($voucher->voucher_date)->format('Y-m-d') }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">طريقة الصرف</label>
                    <div class="readonly-box">
                        @if($voucher->payment_method === 'cash')
                            نقدي
                        @elseif($voucher->payment_method === 'card')
                            شبكة
                        @elseif($voucher->payment_method === 'bank_transfer')
                            تحويل بنكي
                        @elseif($voucher->payment_method === 'other')
                            أخرى
                        @else
                            {{ $voucher->payment_method }}
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">المبلغ</label>
                    <div class="readonly-box amount-box">
                        {{ number_format((float) $voucher->amount, 2) }}
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- Branch / Cost Center --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">الفرع ومركز التكلفة</h5>
                <small>الأبعاد الإدارية المرتبطة بالسند</small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">الفرع</label>
                    <div class="readonly-box">
                        {{ $voucher->branch->branch_name_ar
                            ?? $voucher->branch->branch_name
                            ?? $voucher->branch->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">مركز التكلفة</label>
                    <div class="readonly-box">
                        @if($voucher->costCenter)
                            {{ $voucher->costCenter->code ? $voucher->costCenter->code . ' - ' : '' }}
                            {{ $voucher->costCenter->name }}
                        @else
                            -
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- Accounts --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">حسابات السند</h5>
                <small>حساب الصرف والحساب المقابل والمستفيد</small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">اسم المستفيد</label>
                    <div class="readonly-box">
                        {{ $voucher->payee_name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">حساب الصرف</label>
                    <div class="readonly-box account-box">
                        {{ $voucher->cashBankAccount->account_code ?? $voucher->cashBankAccount->code ?? '' }}
                        -
                        {{ $voucher->cashBankAccount->account_name_ar
                            ?? $voucher->cashBankAccount->account_name
                            ?? $voucher->cashBankAccount->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">الحساب المقابل</label>
                    <div class="readonly-box account-box">
                        {{ $voucher->oppositeAccount->account_code ?? $voucher->oppositeAccount->code ?? '' }}
                        -
                        {{ $voucher->oppositeAccount->account_name_ar
                            ?? $voucher->oppositeAccount->account_name
                            ?? $voucher->oppositeAccount->name
                            ?? '-' }}
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- Journal Preview --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">القيد المحاسبي</h5>
                <small>الأثر المحاسبي المتوقع أو المرحل للسند</small>
            </div>
        </div>

        <div class="card-body">

            <div class="journal-preview-grid">

                <div class="journal-preview-box debit-box">
                    <div class="preview-label">مدين</div>
                    <div class="preview-title">
                        من حـ /
                        {{ $voucher->oppositeAccount->account_name_ar
                            ?? $voucher->oppositeAccount->account_name
                            ?? $voucher->oppositeAccount->name
                            ?? 'الحساب المقابل' }}
                    </div>
                    <div class="preview-amount text-success">
                        {{ number_format((float) $voucher->amount, 2) }}
                    </div>
                </div>

                <div class="journal-preview-box credit-box">
                    <div class="preview-label">دائن</div>
                    <div class="preview-title">
                        إلى حـ /
                        {{ $voucher->cashBankAccount->account_name_ar
                            ?? $voucher->cashBankAccount->account_name
                            ?? $voucher->cashBankAccount->name
                            ?? 'حساب الصرف' }}
                    </div>
                    <div class="preview-amount text-danger">
                        {{ number_format((float) $voucher->amount, 2) }}
                    </div>
                </div>

            </div>

        </div>
    </div>


    {{-- Notes / System Info --}}
    <div class="row g-4 mb-5">

        <div class="col-md-7">
            <div class="card shadow-sm wazin-card h-100">
                <div class="card-header wazin-card-header">
                    <div>
                        <h5 class="mb-0 fw-bold">ملاحظات</h5>
                        <small>ملاحظات السند إن وجدت</small>
                    </div>
                </div>

                <div class="card-body">
                    <div class="readonly-box notes-box">
                        {{ $voucher->notes ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm wazin-card h-100">
                <div class="card-header wazin-card-header">
                    <div>
                        <h5 class="mb-0 fw-bold">معلومات النظام</h5>
                        <small>بيانات الإنشاء والترحيل والإلغاء</small>
                    </div>
                </div>

                <div class="card-body">

                    <div class="system-info-list">

                        <div class="system-info-item">
                            <span>أنشئ بواسطة</span>
                            <strong>{{ $voucher->creator->name ?? '-' }}</strong>
                        </div>

                        <div class="system-info-item">
                            <span>تاريخ الإنشاء</span>
                            <strong class="ltr-cell">
                                {{ optional($voucher->created_at)->format('Y-m-d H:i') }}
                            </strong>
                        </div>

                        <div class="system-info-item">
                            <span>رحل بواسطة</span>
                            <strong>{{ $voucher->poster->name ?? '-' }}</strong>
                        </div>

                        <div class="system-info-item">
                            <span>تاريخ الترحيل</span>
                            <strong class="ltr-cell">
                                {{ optional($voucher->posted_at)->format('Y-m-d H:i') ?? '-' }}
                            </strong>
                        </div>

                        <div class="system-info-item">
                            <span>ألغي بواسطة</span>
                            <strong>{{ $voucher->canceller->name ?? '-' }}</strong>
                        </div>

                        <div class="system-info-item">
                            <span>سبب الإلغاء</span>
                            <strong>{{ $voucher->cancel_reason ?? '-' }}</strong>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

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

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .status-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        box-shadow: 0 10px 26px rgba(7, 22, 51, 0.06);
    }

    .status-label {
        display: block;
        color: #64748B;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .voucher-amount-box {
        min-width: 180px;
        background: rgba(230, 59, 74, 0.10);
        border: 1px solid rgba(230, 59, 74, 0.18);
        border-radius: 18px;
        padding: 12px 16px;
        text-align: center;
    }

    .voucher-amount-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .voucher-amount-box strong {
        direction: ltr;
        display: block;
        color: #E63B4A;
        font-size: 22px;
        font-weight: 900;
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

    .readonly-box {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        background: #fff;
        padding: 10px 14px;
        color: #071633;
        font-weight: 800;
        line-height: 1.7;
    }

    .ltr-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
    }

    .amount-box {
        direction: ltr;
        text-align: center;
        background: #E63B4A;
        border-color: #E63B4A;
        color: #fff;
        font-weight: 900;
    }

    .account-box {
        text-align: right;
        font-weight: 900;
    }

    .notes-box {
        min-height: 148px;
        white-space: pre-wrap;
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
        line-height: 1.8;
    }

    .preview-amount {
        direction: ltr;
        font-size: 24px;
        font-weight: 900;
    }

    .system-info-list {
        display: grid;
        gap: 10px;
    }

    .system-info-item {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 11px 13px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .system-info-item span {
        color: #64748B;
        font-weight: 900;
    }

    .system-info-item strong {
        color: #071633;
        font-weight: 900;
        text-align: left;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
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

    @media (max-width: 767px) {
        .page-header-card,
        .status-card {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions,
        .header-actions .btn {
            width: 100%;
        }

        .header-actions {
            flex-direction: column;
        }

        .voucher-amount-box {
            width: 100%;
        }

        .journal-preview-grid {
            grid-template-columns: 1fr;
        }

        .system-info-item {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>


@push('scripts')
<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json'
        }
    });


    $('#postVoucherBtn').on('click', function () {
        let id = $(this).data('id');

        if (typeof Swal === 'undefined') {
            if (confirm('هل تريد ترحيل سند الصرف العام؟')) {
                sendPostRequest(id);
            }

            return;
        }

        Swal.fire({
            title: 'تأكيد الترحيل',
            text: 'هل تريد ترحيل سند الصرف العام؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، ترحيل',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#16A34A',
            cancelButtonColor: '#64748B'
        }).then((result) => {
            if (! result.isConfirmed) {
                return;
            }

            sendPostRequest(id);
        });
    });


    $('#cancelVoucherBtn').on('click', function () {
        let id = $(this).data('id');

        if (typeof Swal === 'undefined') {
            let reason = prompt('سبب الإلغاء - اختياري');

            if (reason === null) {
                return;
            }

            sendCancelRequest(id, reason);
            return;
        }

        Swal.fire({
            title: 'إلغاء سند الصرف',
            input: 'text',
            inputLabel: 'سبب الإلغاء',
            inputPlaceholder: 'اختياري',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'تأكيد الإلغاء',
            cancelButtonText: 'رجوع',
            confirmButtonColor: '#E63B4A',
            cancelButtonColor: '#64748B'
        }).then((result) => {
            if (! result.isConfirmed) {
                return;
            }

            sendCancelRequest(id, result.value);
        });
    });


    function sendPostRequest(id) {
        let url = "{{ route('general-payment-vouchers.post', ':id') }}";
        url = url.replace(':id', id);

        $.post(url)
            .done(function (response) {
                showSuccess(response.message ?? 'تم ترحيل سند الصرف بنجاح.');
            })
            .fail(function (xhr) {
                showError(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الترحيل.');
            });
    }


    function sendCancelRequest(id, reason) {
        let url = "{{ route('general-payment-vouchers.cancel', ':id') }}";
        url = url.replace(':id', id);

        $.post(url, {
            cancel_reason: reason
        })
        .done(function (response) {
            showSuccess(response.message ?? 'تم إلغاء سند الصرف بنجاح.');
        })
        .fail(function (xhr) {
            showError(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الإلغاء.');
        });
    }


    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم',
                text: message,
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert(message);
            window.location.reload();
        }
    }


    function showError(message) {
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

});
</script>
@endpush

</x-app-layout>