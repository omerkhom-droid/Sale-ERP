<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">
                سند صرف عميل رقم: {{ $customerRefundVoucher->voucher_no }}
            </h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات سند صرف العميل ومردود المبيعات المرتبط وحالة الترحيل أو الإلغاء.
            </p>
        </div>

        <div class="header-actions">

            <a href="{{ route('customer-refund-vouchers.index') }}" class="btn btn-secondary">
                رجوع
            </a>

            @can('customer_refund_vouchers.print')
                <a href="{{ route('customer-refund-vouchers.print', $customerRefundVoucher->id) }}"
                   target="_blank"
                   class="btn btn-dark">
                    طباعة
                </a>
            @endcan

            @if($customerRefundVoucher->status === 'draft')
                @can('customer_refund_vouchers.post')
                    <form method="POST"
                          action="{{ route('customer-refund-vouchers.post', $customerRefundVoucher->id) }}"
                          onsubmit="return confirm('هل أنت متأكد من ترحيل سند صرف العميل؟');">
                        @csrf

                        <button type="submit" class="btn btn-success">
                            ترحيل
                        </button>
                    </form>
                @endcan
            @endif

            @if($customerRefundVoucher->status !== 'cancelled')
                @can('customer_refund_vouchers.cancel')
                    <button type="button"
                            class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#cancelVoucherModal">
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


    @php
        $available = ($customerRefundVoucher->salesReturn?->refundable_amount ?? 0)
            - ($customerRefundVoucher->salesReturn?->refunded_amount ?? 0);
    @endphp


    {{-- Status Summary --}}
    <div class="status-grid mb-4">

        <div class="status-box">
            <span>حالة السند</span>
            <strong>
                @if($customerRefundVoucher->status === 'draft')
                    <span class="badge bg-secondary text-white">مسودة</span>
                @elseif($customerRefundVoucher->status === 'posted')
                    <span class="badge bg-success text-white">مرحلة</span>
                @elseif($customerRefundVoucher->status === 'cancelled')
                    <span class="badge bg-danger text-white">ملغاة</span>
                @else
                    <span class="badge bg-light text-dark">{{ $customerRefundVoucher->status }}</span>
                @endif
            </strong>
        </div>

        <div class="status-box">
            <span>تاريخ السند</span>
            <strong>{{ $customerRefundVoucher->refund_date?->format('Y-m-d') }}</strong>
        </div>

        <div class="status-box amount-summary">
            <span>مبلغ سند الصرف</span>
            <strong>{{ number_format((float) $customerRefundVoucher->amount, 2) }}</strong>
        </div>

        <div class="status-box remaining-summary">
            <span>المتبقي للصرف</span>
            <strong>{{ number_format(max((float) $available, 0), 2) }}</strong>
        </div>

    </div>


    {{-- Voucher Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات السند</h5>
                <small>بيانات طريقة الصرف والفرع المرتبط بسند صرف العميل</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">طريقة الصرف</label>
                    <div class="readonly-box">
                        @if($customerRefundVoucher->payment_method === 'cash')
                            نقدي
                        @elseif($customerRefundVoucher->payment_method === 'card')
                            شبكة
                        @elseif($customerRefundVoucher->payment_method === 'bank_transfer')
                            تحويل بنكي
                        @elseif($customerRefundVoucher->payment_method === 'other')
                            أخرى
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الفرع</label>
                    <div class="readonly-box">
                        {{ $customerRefundVoucher->branch?->branch_name_ar
                            ?? $customerRefundVoucher->branch?->branch_name
                            ?? $customerRefundVoucher->branch?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">مبلغ سند الصرف</label>
                    <div class="readonly-box amount-readonly">
                        {{ number_format((float) $customerRefundVoucher->amount, 2) }}
                    </div>
                </div>

            </div>

        </div>

    </div>


    {{-- Customer Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات العميل</h5>
                <small>العميل المرتبط بمردود المبيعات وسند الصرف</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">اسم العميل</label>
                    <div class="readonly-box">
                        {{ $customerRefundVoucher->customer?->customer_name
                            ?? $customerRefundVoucher->customer?->name
                            ?? $customerRefundVoucher->customer?->fullname
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_name
                            ?? 'عميل نقدي' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الجوال</label>
                    <div class="readonly-box ltr-cell">
                        {{ $customerRefundVoucher->customer?->mobile
                            ?? $customerRefundVoucher->customer?->phone
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_mobile
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الرقم الضريبي</label>
                    <div class="readonly-box ltr-cell">
                        {{ $customerRefundVoucher->customer?->tax_registration_number
                            ?? $customerRefundVoucher->customer?->tax_number
                            ?? $customerRefundVoucher->customer?->vat_number
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_tax_number
                            ?? '-' }}
                    </div>
                </div>

            </div>

        </div>

    </div>


    {{-- Sales Return Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات مردود المبيعات</h5>
                <small>المردود الذي تم صرف المبلغ بناءً عليه</small>
            </div>
        </div>

        <div class="card-body">

            <div class="return-info-grid">

                <div class="info-box">
                    <span>رقم المردود</span>
                    <strong>
                        @if($customerRefundVoucher->salesReturn)
                            <a href="{{ route('sales-returns.show', $customerRefundVoucher->salesReturn->id) }}" class="invoice-link">
                                {{ $customerRefundVoucher->salesReturn->return_no }}
                            </a>
                        @else
                            -
                        @endif
                    </strong>
                </div>

                <div class="info-box">
                    <span>تاريخ المردود</span>
                    <strong>{{ $customerRefundVoucher->salesReturn?->return_date?->format('Y-m-d') ?? '-' }}</strong>
                </div>

                <div class="info-box">
                    <span>فاتورة البيع</span>
                    <strong>
                        @if($customerRefundVoucher->salesReturn?->salesInvoice)
                            <a href="{{ route('sales-invoices.show', $customerRefundVoucher->salesReturn->salesInvoice->id) }}" class="invoice-link">
                                {{ $customerRefundVoucher->salesReturn->salesInvoice->invoice_no }}
                            </a>
                        @else
                            -
                        @endif
                    </strong>
                </div>

                <div class="info-box amount-box">
                    <span>إجمالي المردود</span>
                    <strong>{{ number_format((float) ($customerRefundVoucher->salesReturn?->total_amount ?? 0), 2) }}</strong>
                </div>

                <div class="info-box refundable-box">
                    <span>المستحق للعميل</span>
                    <strong>{{ number_format((float) ($customerRefundVoucher->salesReturn?->refundable_amount ?? 0), 2) }}</strong>
                </div>

                <div class="info-box refunded-box">
                    <span>تم صرفه</span>
                    <strong>{{ number_format((float) ($customerRefundVoucher->salesReturn?->refunded_amount ?? 0), 2) }}</strong>
                </div>

                <div class="info-box remaining-box">
                    <span>المتبقي للصرف</span>
                    <strong>{{ number_format(max((float) $available, 0), 2) }}</strong>
                </div>

            </div>

        </div>

    </div>


    {{-- Amount --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">مبلغ السند</h5>
                <small>المبلغ المصروف للعميل بموجب هذا السند</small>
            </div>
        </div>

        <div class="card-body">

            <div class="amount-main-box">
                <span>مبلغ سند الصرف</span>
                <strong>{{ number_format((float) $customerRefundVoucher->amount, 2) }}</strong>
            </div>

        </div>

    </div>


    {{-- System Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">معلومات النظام</h5>
                <small>بيانات الإنشاء والترحيل</small>
            </div>
        </div>

        <div class="card-body">

            <div class="system-info-grid">

                <div class="system-info-item">
                    <span>أنشئ بواسطة</span>
                    <strong>{{ $customerRefundVoucher->creator?->name ?? '-' }}</strong>
                </div>

                <div class="system-info-item">
                    <span>تاريخ الإنشاء</span>
                    <strong class="ltr-cell">
                        {{ $customerRefundVoucher->created_at?->format('Y-m-d H:i') }}
                    </strong>
                </div>

                <div class="system-info-item">
                    <span>رحّل بواسطة</span>
                    <strong>{{ $customerRefundVoucher->postedBy?->name ?? '-' }}</strong>
                </div>

                <div class="system-info-item">
                    <span>تاريخ الترحيل</span>
                    <strong class="ltr-cell">
                        {{ $customerRefundVoucher->posted_at?->format('Y-m-d H:i') ?? '-' }}
                    </strong>
                </div>

            </div>

        </div>

    </div>


    {{-- Notes / Cancellation --}}
    @if($customerRefundVoucher->notes || $customerRefundVoucher->status === 'cancelled')
        <div class="card shadow-sm wazin-card mb-5">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات وحالة الإلغاء</h5>
                    <small>أي تفاصيل إضافية مرتبطة بسند الصرف</small>
                </div>
            </div>

            <div class="card-body">

                @if($customerRefundVoucher->notes)
                    <div class="journal-status-note info-note mb-3">
                        <strong>ملاحظات:</strong>
                        <br>
                        {{ $customerRefundVoucher->notes }}
                    </div>
                @endif

                @if($customerRefundVoucher->status === 'cancelled')
                    <div class="journal-status-note danger-note">
                        <strong>سبب الإلغاء:</strong>
                        <br>
                        {{ $customerRefundVoucher->cancel_reason ?? 'لم يتم تحديد سبب.' }}
                    </div>
                @endif

            </div>
        </div>
    @endif

</div>


{{-- Cancel Modal --}}
<div class="modal fade" id="cancelVoucherModal" tabindex="-1" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">

        <form method="POST" action="{{ route('customer-refund-vouchers.cancel', $customerRefundVoucher->id) }}" class="modal-content">
            @csrf

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">إلغاء سند صرف العميل</h5>
                    <small>سيتم عكس أثر سند الصرف حسب حالته</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="journal-status-note warning-note mb-3">
                    هل أنت متأكد من إلغاء سند صرف العميل؟

                    @if($customerRefundVoucher->status === 'posted')
                        <br>
                        سيتم عكس أثر الصرف على مردود المبيعات والقيود المحاسبية.
                    @endif
                </div>

                <label class="form-label">سبب الإلغاء</label>
                <textarea name="cancel_reason"
                          class="form-control"
                          rows="3"
                          placeholder="اختياري"></textarea>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    رجوع
                </button>

                <button type="submit" class="btn btn-danger">
                    تأكيد الإلغاء
                </button>
            </div>

        </form>

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

    .header-actions form {
        margin: 0;
    }

    .status-grid,
    .system-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .return-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .status-box,
    .system-info-item,
    .info-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .status-box span,
    .system-info-item span,
    .info-box span,
    .amount-main-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .status-box strong,
    .system-info-item strong,
    .info-box strong {
        direction: ltr;
        display: block;
        color: #071633;
        font-size: 19px;
        font-weight: 900;
        min-height: 28px;
    }

    .system-info-item strong {
        font-size: 15px;
    }

    .amount-summary,
    .amount-box {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
    }

    .refundable-box {
        border-right: 5px solid #16A34A;
    }

    .refunded-box {
        border-right: 5px solid #F59E0B;
    }

    .remaining-summary,
    .remaining-box {
        border-right: 5px solid #E63B4A;
    }

    .amount-summary strong,
    .amount-box strong {
        color: #2F6BFF;
    }

    .refundable-box strong {
        color: #16A34A;
    }

    .refunded-box strong {
        color: #F59E0B;
    }

    .remaining-summary strong,
    .remaining-box strong {
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

    .amount-readonly,
    .ltr-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
    }

    .invoice-link {
        color: #2F6BFF;
        text-decoration: none;
        font-weight: 900;
    }

    .invoice-link:hover {
        text-decoration: underline;
    }

    .amount-main-box {
        background: rgba(47, 107, 255, 0.08);
        border: 1px solid rgba(47, 107, 255, 0.22);
        border-radius: 22px;
        padding: 24px;
        text-align: center;
    }

    .amount-main-box strong {
        direction: ltr;
        display: block;
        color: #2F6BFF;
        font-size: 30px;
        font-weight: 900;
    }

    .journal-status-note {
        border-radius: 16px;
        padding: 14px 16px;
        font-weight: 700;
        line-height: 1.8;
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

    .info-note {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #BFDBFE;
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

    .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(7, 22, 51, 0.22);
    }

    .modal-header {
        background: #E63B4A;
        color: #fff;
        border-bottom: 0;
        padding: 18px 22px;
    }

    .modal-header small {
        color: #FFE4E6;
        font-weight: 700;
    }

    .modal-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .modal-footer {
        background: #fff;
        border-top: 1px solid #E5E7EB;
        padding: 16px 22px;
    }

    .form-control {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
    }

    .form-control:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    @media (max-width: 991px) {
        .status-grid,
        .system-info-grid,
        .return-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions,
        .header-actions .btn,
        .header-actions form {
            width: 100%;
        }

        .header-actions {
            flex-direction: column;
        }

        .header-actions form .btn {
            width: 100%;
        }

        .status-grid,
        .system-info-grid,
        .return-info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

</x-app-layout>