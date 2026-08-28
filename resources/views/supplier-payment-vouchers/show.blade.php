<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">
                سند صرف مورد رقم: {{ $supplierPaymentVoucher->voucher_no }}
            </h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات سند صرف المورد والتوزيع على فواتير المشتريات وحالة الترحيل أو الإلغاء.
            </p>
        </div>

        <div class="header-actions">

            <a href="{{ route('supplier-payment-vouchers.index') }}" class="btn btn-secondary">
                رجوع للقائمة
            </a>

            @can('supplier_payment_vouchers.print')
                <a href="{{ route('supplier-payment-vouchers.print', $supplierPaymentVoucher->id) }}"
                   target="_blank"
                   class="btn btn-dark">
                    طباعة
                </a>
            @endcan

            @if($supplierPaymentVoucher->status === 'draft')
                @can('supplier_payment_vouchers.post')
                    <button type="button"
                            id="postVoucherBtn"
                            data-id="{{ $supplierPaymentVoucher->id }}"
                            class="btn btn-success">
                        ترحيل السند
                    </button>
                @endcan
            @endif

            @if($supplierPaymentVoucher->status !== 'cancelled')
                @can('supplier_payment_vouchers.cancel')
                    <button type="button"
                            id="cancelVoucherBtn"
                            data-id="{{ $supplierPaymentVoucher->id }}"
                            class="btn btn-danger">
                        إلغاء السند
                    </button>
                @endcan
            @endif

        </div>
    </div>


    {{-- Status Summary --}}
    <div class="status-grid mb-4">

        <div class="status-box">
            <span>حالة السند</span>
            <strong>
                @if($supplierPaymentVoucher->status === 'draft')
                    <span class="badge bg-secondary  text-white">مسودة</span>
                @elseif($supplierPaymentVoucher->status === 'posted')
                    <span class="badge bg-success  text-white">مرحل</span>
                @elseif($supplierPaymentVoucher->status === 'cancelled')
                    <span class="badge bg-danger  text-white">ملغى</span>
                @else
                    <span class="badge bg-light text-dark">{{ $supplierPaymentVoucher->status }}</span>
                @endif
            </strong>
        </div>

        <div class="status-box amount-summary">
            <span>مبلغ السند</span>
            <strong>{{ number_format((float) $supplierPaymentVoucher->amount, 2) }}</strong>
        </div>

        <div class="status-box allocated-summary">
            <span>إجمالي التوزيع</span>
            <strong>{{ number_format((float) $supplierPaymentVoucher->allocated_amount, 2) }}</strong>
        </div>

        <div class="status-box unallocated-summary">
            <span>غير موزع</span>
            <strong>{{ number_format((float) $supplierPaymentVoucher->unallocated_amount, 2) }}</strong>
        </div>

    </div>


    {{-- Voucher Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات السند</h5>
                <small>بيانات المورد وحساب الدفع والمبلغ والتوزيع</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">رقم السند</label>
                    <div class="readonly-box ltr-cell">
                        {{ $supplierPaymentVoucher->voucher_no }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ السند</label>
                    <div class="readonly-box ltr-cell">
                        {{ $supplierPaymentVoucher->voucher_date }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">المورد</label>
                    <div class="readonly-box">
                        {{ $supplierPaymentVoucher->supplier?->supplier_name
                            ?? $supplierPaymentVoucher->supplier?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">حالة السند</label>
                    <div class="readonly-box">
                        @if($supplierPaymentVoucher->status === 'draft')
                            <span class="badge bg-secondary">مسودة</span>
                        @elseif($supplierPaymentVoucher->status === 'posted')
                            <span class="badge bg-success">مرحل</span>
                        @elseif($supplierPaymentVoucher->status === 'cancelled')
                            <span class="badge bg-danger">ملغى</span>
                        @else
                            <span class="badge bg-light text-dark">{{ $supplierPaymentVoucher->status }}</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">حساب الدفع</label>
                    <div class="readonly-box">
                        @if($supplierPaymentVoucher->paymentAccount)
                            {{ $supplierPaymentVoucher->paymentAccount->account_code }}
                            -
                            {{ $supplierPaymentVoucher->paymentAccount->account_name_ar }}
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">مبلغ السند</label>
                    <div class="readonly-box amount-readonly text-success">
                        {{ number_format((float) $supplierPaymentVoucher->amount, 2) }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">إجمالي التوزيع</label>
                    <div class="readonly-box amount-readonly text-primary">
                        {{ number_format((float) $supplierPaymentVoucher->allocated_amount, 2) }}
                    </div>
                </div>

                @if($supplierPaymentVoucher->costCenter ?? false)
                    <div class="col-md-6">
                        <label class="form-label">مركز التكلفة</label>
                        <div class="readonly-box">
                            {{ $supplierPaymentVoucher->costCenter->code ? $supplierPaymentVoucher->costCenter->code . ' - ' : '' }}
                            {{ $supplierPaymentVoucher->costCenter->name }}
                        </div>
                    </div>
                @endif

                @if($supplierPaymentVoucher->notes)
                    <div class="col-md-12">
                        <label class="form-label">البيان</label>
                        <div class="readonly-box">
                            {{ $supplierPaymentVoucher->notes }}
                        </div>
                    </div>
                @endif

                @if($supplierPaymentVoucher->status === 'cancelled')
                    <div class="col-md-12">
                        <label class="form-label text-danger">سبب الإلغاء</label>
                        <div class="readonly-box cancel-box">
                            {{ $supplierPaymentVoucher->cancel_reason ?? '-' }}
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>


    {{-- Allocations --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">الفواتير المسددة من هذا السند</h5>
                <small>تفاصيل توزيع مبلغ السند على فواتير المشتريات</small>
            </div>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الفاتورة</th>
                            <th>تاريخ الفاتورة</th>
                            <th>إجمالي الفاتورة</th>
                            <th>المدفوع الحالي بالفاتورة</th>
                            <th>المتبقي الحالي بالفاتورة</th>
                            <th>المبلغ المسدد بهذا السند</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($supplierPaymentVoucher->allocations as $index => $allocation)

                            @php
                                $invoice = $allocation->purchaseInvoice;
                            @endphp

                            <tr>
                                <td class="fw-bold">{{ $index + 1 }}</td>

                                <td class="amount-cell">
                                    @if($invoice)
                                        <a href="{{ route('purchase-invoices.show', $invoice->id) }}"
                                           class="fw-bold text-decoration-none">
                                            {{ $invoice->invoice_no }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="amount-cell">
                                    {{ $invoice?->invoice_date ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) ($invoice?->total_amount ?? 0), 2) }}
                                </td>

                                <td class="amount-cell text-success">
                                    {{ number_format((float) ($invoice?->paid_amount ?? 0), 2) }}
                                </td>

                                <td class="amount-cell text-danger">
                                    {{ number_format((float) ($invoice?->remaining_amount ?? 0), 2) }}
                                </td>

                                <td class="amount-cell fw-bold text-primary">
                                    {{ number_format((float) $allocation->amount, 2) }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        لا توجد توزيعات على فواتير لهذا السند.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- Summary --}}
    <div class="row g-3 align-items-start mb-5">

        <div class="col-lg-8">

            @if($supplierPaymentVoucher->status === 'draft')
                <div class="journal-status-note warning-note">
                    هذا السند مسودة، لم يؤثر على الفواتير ولم يتم إنشاء قيد محاسبي بعد.
                </div>
            @elseif($supplierPaymentVoucher->status === 'posted')
                <div class="journal-status-note success-note">
                    هذا السند مرحل، وتم تحديث الفواتير وإنشاء القيد المحاسبي.
                </div>
            @elseif($supplierPaymentVoucher->status === 'cancelled')
                <div class="journal-status-note danger-note">
                    هذا السند ملغى. إذا كان مرحلًا سابقًا فقد تم عكس أثره المحاسبي.
                </div>
            @endif

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm wazin-card totals-card">

                <div class="card-header wazin-card-header">
                    <div>
                        <h5 class="mb-0 fw-bold">ملخص السند</h5>
                        <small>مبلغ السند والتوزيع والمبلغ غير الموزع</small>
                    </div>
                </div>

                <div class="card-body">

                    <div class="summary-line">
                        <span>مبلغ السند</span>
                        <strong>{{ number_format((float) $supplierPaymentVoucher->amount, 2) }}</strong>
                    </div>

                    <div class="summary-line allocated-line">
                        <span>إجمالي التوزيع</span>
                        <strong>{{ number_format((float) $supplierPaymentVoucher->allocated_amount, 2) }}</strong>
                    </div>

                    <div class="summary-line unallocated-line">
                        <span>غير موزع</span>
                        <strong>{{ number_format((float) $supplierPaymentVoucher->unallocated_amount, 2) }}</strong>
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

    .status-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .status-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .status-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .status-box strong {
        direction: ltr;
        display: block;
        color: #071633;
        font-size: 20px;
        font-weight: 900;
    }

    .amount-summary {
        border-right: 5px solid #16A34A;
        background: rgba(22, 163, 74, 0.05);
    }

    .allocated-summary {
        border-right: 5px solid #2F6BFF;
    }

    .unallocated-summary {
        border-right: 5px solid #E63B4A;
    }

    .amount-summary strong {
        color: #16A34A;
    }

    .allocated-summary strong {
        color: #2F6BFF;
    }

    .unallocated-summary strong {
        color: #E63B4A;
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

    .cancel-box {
        color: #B91C1C;
        background: #FEF2F2;
        border-color: #FECACA;
    }

    .amount-readonly,
    .ltr-cell,
    .amount-cell {
        direction: ltr;
        text-align: center;
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

    .allocated-line strong {
        color: #2F6BFF;
    }

    .unallocated-line {
        background: rgba(230, 59, 74, 0.08);
        border: 1px solid rgba(230, 59, 74, 0.16);
        border-radius: 14px;
        padding: 12px 14px;
        margin-top: 8px;
    }

    .unallocated-line strong {
        color: #E63B4A;
        font-size: 20px;
    }

    .journal-status-note {
        border-radius: 16px;
        padding: 16px 18px;
        font-weight: 800;
        line-height: 1.9;
    }

    .success-note {
        background: #ECFDF5;
        color: #047857;
        border: 1px solid #A7F3D0;
    }

    .warning-note {
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FED7AA;
    }

    .danger-note {
        background: #FEF2F2;
        color: #B91C1C;
        border: 1px solid #FECACA;
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

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-secondary,
    .btn-dark {
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

    @media (max-width: 991px) {
        .status-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .page-header-card {
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

        .status-grid {
            grid-template-columns: 1fr;
        }
    }
</style>


@push('scripts')
<script>
$(document).on('click', '#postVoucherBtn', function () {

    let id = $(this).data('id');

    if (typeof Swal === 'undefined') {
        if (!confirm('هل أنت متأكد من ترحيل سند الصرف؟')) {
            return;
        }

        postSupplierPaymentVoucher(id);
        return;
    }

    Swal.fire({
        title: 'ترحيل سند الصرف؟',
        text: 'بعد الترحيل سيتم تحديث الفواتير وإنشاء القيد المحاسبي.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، رحّل',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#16A34A',
        cancelButtonColor: '#64748B'
    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        postSupplierPaymentVoucher(id);
    });

});


function postSupplierPaymentVoucher(id) {

    $('#postVoucherBtn').prop('disabled', true);

    $.ajax({
        url: "{{ url('/supplier-payment-vouchers') }}/" + id + "/post",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}"
        },

        success: function (response) {

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم',
                    text: response.message ?? 'تم ترحيل السند بنجاح',
                    timer: 1300,
                    showConfirmButton: false
                });
            } else {
                alert(response.message ?? 'تم ترحيل السند بنجاح');
            }

            setTimeout(function () {
                location.reload();
            }, 900);
        },

        error: function (xhr) {

            $('#postVoucherBtn').prop('disabled', false);

            let message = 'حدث خطأ أثناء الترحيل';

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors)[0][0];
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            showError(message);
        }
    });
}


$(document).on('click', '#cancelVoucherBtn', function () {

    let id = $(this).data('id');

    if (typeof Swal === 'undefined') {
        let reason = prompt('اكتب سبب الإلغاء');

        if (!reason) {
            alert('يجب كتابة سبب الإلغاء');
            return;
        }

        cancelSupplierPaymentVoucher(id, reason);
        return;
    }

    Swal.fire({
        title: 'إلغاء سند الصرف؟',
        input: 'textarea',
        inputLabel: 'سبب الإلغاء',
        inputPlaceholder: 'اكتب سبب الإلغاء...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، إلغاء',
        cancelButtonText: 'رجوع',
        confirmButtonColor: '#E63B4A',
        cancelButtonColor: '#64748B',

        inputValidator: (value) => {
            if (!value) {
                return 'يجب كتابة سبب الإلغاء';
            }
        }

    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        cancelSupplierPaymentVoucher(id, result.value);
    });

});


function cancelSupplierPaymentVoucher(id, reason) {

    $('#cancelVoucherBtn').prop('disabled', true);

    $.ajax({
        url: "{{ url('/supplier-payment-vouchers') }}/" + id + "/cancel",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            cancel_reason: reason
        },

        success: function (response) {

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم',
                    text: response.message ?? 'تم إلغاء السند بنجاح',
                    timer: 1300,
                    showConfirmButton: false
                });
            } else {
                alert(response.message ?? 'تم إلغاء السند بنجاح');
            }

            setTimeout(function () {
                location.reload();
            }, 900);
        },

        error: function (xhr) {

            $('#cancelVoucherBtn').prop('disabled', false);

            let message = 'حدث خطأ أثناء الإلغاء';

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors)[0][0];
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            showError(message);
        }
    });
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
</script>
@endpush

</x-app-layout>