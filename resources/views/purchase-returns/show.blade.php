<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">
                مردود مشتريات رقم: {{ $purchaseReturn->return_no }}
            </h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات مردود المشتريات والأصناف المرتجعة وحالة الترحيل أو الإلغاء.
            </p>
        </div>

        <div class="header-actions">

            <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary">
                رجوع للقائمة
            </a>

            @can('purchase_returns.print')
                <a href="{{ route('purchase-returns.print', $purchaseReturn->id) }}"
                   target="_blank"
                   class="btn btn-dark">
                    طباعة
                </a>
            @endcan

            @if($purchaseReturn->invoice)
                <a href="{{ route('purchase-invoices.show', $purchaseReturn->invoice->id) }}"
                   class="btn btn-primary">
                    الفاتورة الأصلية
                </a>
            @endif

            @if($purchaseReturn->status === 'draft')
                @can('purchase_returns.post')
                    <button type="button"
                            id="postReturnBtn"
                            data-id="{{ $purchaseReturn->id }}"
                            class="btn btn-success">
                        ترحيل المردود
                    </button>
                @endcan
            @endif

            @if($purchaseReturn->status !== 'cancelled')
                @can('purchase_returns.cancel')
                    <button type="button"
                            id="cancelReturnBtn"
                            data-id="{{ $purchaseReturn->id }}"
                            class="btn btn-danger">
                        إلغاء المردود
                    </button>
                @endcan
            @endif

        </div>
    </div>


    {{-- Status Summary --}}
    <div class="status-grid mb-4">

        <div class="status-box">
            <span>حالة المردود</span>
            <strong>
                @if($purchaseReturn->status === 'draft')
                    <span class="badge bg-secondary">مسودة</span>
                @elseif($purchaseReturn->status === 'posted')
                    <span class="badge bg-success text-white">مرحل</span>
                @elseif($purchaseReturn->status === 'cancelled')
                    <span class="badge bg-danger text-white">ملغى</span>
                @else
                    <span class="badge bg-light text-dark">{{ $purchaseReturn->status }}</span>
                @endif
            </strong>
        </div>

        <div class="status-box">
            <span>تاريخ المردود</span>
            <strong>{{ $purchaseReturn->return_date }}</strong>
        </div>

        <div class="status-box amount-summary">
            <span>إجمالي المردود</span>
            <strong>{{ number_format((float) $purchaseReturn->total_amount, 2) }}</strong>
        </div>

        <div class="status-box vat-summary">
            <span>ضريبة القيمة المضافة</span>
            <strong>{{ number_format((float) $purchaseReturn->vat_amount, 2) }}</strong>
        </div>

    </div>


    {{-- Return Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات المردود</h5>
                <small>بيانات المردود وفاتورة المشتريات الأصلية والمورد والمستودع</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">رقم المردود</label>
                    <div class="readonly-box ltr-cell">
                        {{ $purchaseReturn->return_no }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ المردود</label>
                    <div class="readonly-box ltr-cell">
                        {{ $purchaseReturn->return_date }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">فاتورة المشتريات</label>
                    <div class="readonly-box ltr-cell">
                        {{ $purchaseReturn->invoice?->invoice_no ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ الفاتورة الأصلية</label>
                    <div class="readonly-box ltr-cell">
                        {{ $purchaseReturn->invoice?->invoice_date ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">المورد</label>
                    <div class="readonly-box">
                        {{ $purchaseReturn->supplier?->supplier_name
                            ?? $purchaseReturn->supplier?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">المستودع</label>
                    <div class="readonly-box">
                        {{ $purchaseReturn->warehouse?->warehouse_name
                            ?? $purchaseReturn->warehouse?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">إجمالي المردود</label>
                    <div class="readonly-box amount-readonly text-danger">
                        {{ number_format((float) $purchaseReturn->total_amount, 2) }}
                    </div>
                </div>

                @if($purchaseReturn->notes)
                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <div class="readonly-box">
                            {{ $purchaseReturn->notes }}
                        </div>
                    </div>
                @endif

                @if($purchaseReturn->status === 'cancelled')
                    <div class="col-md-12">
                        <label class="form-label text-danger">سبب الإلغاء</label>
                        <div class="readonly-box cancel-box">
                            {{ $purchaseReturn->cancel_reason ?? '-' }}
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>


    {{-- Items --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">الأصناف المرتجعة</h5>
                <small>تفاصيل الأصناف التي تم إرجاعها من فاتورة المشتريات</small>
            </div>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصنف</th>
                            <th>الوحدة</th>
                            <th>كمية الفاتورة</th>
                            <th>كمية المردود</th>
                            <th>كمية الأساس</th>
                            <th>تكلفة الوحدة</th>
                            <th>الخصم</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($purchaseReturn->items as $index => $item)
                            <tr>
                                <td class="fw-bold">{{ $index + 1 }}</td>

                                <td class="item-name-cell">
                                    <div class="fw-bold">
                                        {{ $item->product?->product_name_ar
                                            ?? $item->product?->product_name
                                            ?? '-' }}
                                    </div>

                                    <small>
                                        {{ $item->product?->sku ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    {{ $item->productUnit?->unit?->unit_name ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) ($item->invoiceItem?->quantity ?? 0), 3) }}
                                </td>

                                <td class="amount-cell fw-bold text-danger">
                                    {{ number_format((float) $item->quantity, 3) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->base_quantity, 3) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->unit_cost, 2) }}
                                </td>

                                <td class="amount-cell text-danger">
                                    {{ number_format((float) $item->discount_amount, 2) }}
                                </td>

                                <td class="amount-cell">
                                    <div class="fw-bold text-warning">
                                        {{ number_format((float) $item->vat_amount, 2) }}
                                    </div>

                                    <small>
                                        {{ number_format((float) $item->vat_rate, 2) }}%
                                    </small>
                                </td>

                                <td class="amount-cell fw-bold">
                                    {{ number_format((float) $item->line_total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        لا توجد أصناف في هذا المردود.
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

            @if($purchaseReturn->status === 'draft')
                <div class="journal-status-note warning-note">
                    هذا المردود مسودة. لم يتم إنقاص المخزون ولم يتم إنشاء قيد محاسبي.
                </div>
            @elseif($purchaseReturn->status === 'posted')
                <div class="journal-status-note success-note">
                    هذا المردود مرحل. تم إنقاص المخزون، وإنشاء القيد المحاسبي، وتحديث الفاتورة الأصلية.
                </div>
            @elseif($purchaseReturn->status === 'cancelled')
                <div class="journal-status-note danger-note">
                    هذا المردود ملغى. إذا كان مرحلاً سابقًا فقد تم عكس أثره.
                </div>
            @endif

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
                        <strong>{{ number_format((float) $purchaseReturn->subtotal, 2) }}</strong>
                    </div>

                    <div class="summary-line vat-line">
                        <span>ضريبة القيمة المضافة</span>
                        <strong>{{ number_format((float) $purchaseReturn->vat_amount, 2) }}</strong>
                    </div>

                    <hr>

                    <div class="summary-line total-line">
                        <span>الإجمالي</span>
                        <strong>{{ number_format((float) $purchaseReturn->total_amount, 2) }}</strong>
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
        border-right: 5px solid #E63B4A;
        background: rgba(230, 59, 74, 0.05);
    }

    .vat-summary {
        border-right: 5px solid #F59E0B;
    }

    .amount-summary strong {
        color: #E63B4A;
    }

    .vat-summary strong {
        color: #F59E0B;
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

    .item-name-cell {
        text-align: right !important;
        min-width: 240px;
    }

    .item-name-cell small,
    .amount-cell small {
        color: #64748B;
        font-weight: 700;
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
        background: rgba(230, 59, 74, 0.08);
        border: 1px solid rgba(230, 59, 74, 0.16);
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 5px;
    }

    .total-line strong {
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
$(document).on('click', '#postReturnBtn', function () {

    let id = $(this).data('id');

    if (typeof Swal === 'undefined') {
        if (!confirm('هل أنت متأكد من ترحيل مردود المشتريات؟')) {
            return;
        }

        postPurchaseReturn(id);
        return;
    }

    Swal.fire({
        title: 'ترحيل مردود المشتريات؟',
        text: 'بعد الترحيل سيتم إنقاص المخزون وإنشاء القيد المحاسبي.',
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

        postPurchaseReturn(id);
    });

});


function postPurchaseReturn(id) {

    $('#postReturnBtn').prop('disabled', true);

    $.ajax({
        url: "{{ url('/purchase-returns') }}/" + id + "/post",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}"
        },

        success: function (response) {

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم',
                    text: response.message ?? 'تم ترحيل مردود المشتريات بنجاح',
                    timer: 1300,
                    showConfirmButton: false
                });
            } else {
                alert(response.message ?? 'تم ترحيل مردود المشتريات بنجاح');
            }

            setTimeout(function () {
                location.reload();
            }, 900);
        },

        error: function (xhr) {

            $('#postReturnBtn').prop('disabled', false);

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


$(document).on('click', '#cancelReturnBtn', function () {

    let id = $(this).data('id');

    if (typeof Swal === 'undefined') {
        let reason = prompt('اكتب سبب الإلغاء');

        if (!reason) {
            alert('يجب كتابة سبب الإلغاء');
            return;
        }

        cancelPurchaseReturn(id, reason);
        return;
    }

    Swal.fire({
        title: 'إلغاء مردود المشتريات؟',
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

        cancelPurchaseReturn(id, result.value);
    });

});


function cancelPurchaseReturn(id, reason) {

    $('#cancelReturnBtn').prop('disabled', true);

    $.ajax({
        url: "{{ url('/purchase-returns') }}/" + id + "/cancel",
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
                    text: response.message ?? 'تم إلغاء مردود المشتريات بنجاح',
                    timer: 1300,
                    showConfirmButton: false
                });
            } else {
                alert(response.message ?? 'تم إلغاء مردود المشتريات بنجاح');
            }

            setTimeout(function () {
                location.reload();
            }, 900);
        },

        error: function (xhr) {

            $('#cancelReturnBtn').prop('disabled', false);

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