<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">
                فاتورة بيع رقم: {{ $salesInvoice->invoice_no }}
            </h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات فاتورة البيع والأصناف والإجماليات وحالة السداد والترحيل.
            </p>
        </div>

        <div class="header-actions">

            <a href="{{ route('sales-invoices.index') }}" class="btn btn-secondary">
                رجوع
            </a>

            @can('sales_invoices.print')
                <a href="{{ route('sales-invoices.print', $salesInvoice->id) }}"
                   target="_blank"
                   class="btn btn-dark">
                    طباعة
                </a>
            @endcan

            @if($salesInvoice->status === 'draft')
                @can('sales_invoices.post')
                    <form method="POST"
                          action="{{ route('sales-invoices.post', $salesInvoice->id) }}"
                          onsubmit="return confirm('هل أنت متأكد من ترحيل فاتورة البيع؟');">
                        @csrf

                        <button type="submit" class="btn btn-success">
                            ترحيل
                        </button>
                    </form>
                @endcan
            @endif

            @if($salesInvoice->status !== 'cancelled')
                @can('sales_invoices.cancel')
                    <button type="button"
                            class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#cancelInvoiceModal">
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


    {{-- Status Summary --}}
    <div class="status-grid mb-4">

        <div class="status-box">
            <span>حالة الفاتورة</span>
            <strong>
                @if($salesInvoice->status === 'draft')
                    <span class="badge bg-secondary text-white">مسودة</span>
                @elseif($salesInvoice->status === 'posted')
                    <span class="badge bg-success text-white" >مرحلة</span>
                @elseif($salesInvoice->status === 'cancelled')
                    <span class="badge bg-danger text-white">ملغاة</span>
                @else
                    <span class="badge bg-light text-dark">{{ $salesInvoice->status }}</span>
                @endif
            </strong>
        </div>

        <div class="status-box">
            <span>حالة السداد</span>
            <strong>
                @if($salesInvoice->payment_status === 'unpaid')
                    <span class="badge bg-danger text-white">غير مدفوعة</span>
                @elseif($salesInvoice->payment_status === 'partial')
                    <span class="badge bg-warning text-white">مدفوعة جزئياً</span>
                @elseif($salesInvoice->payment_status === 'paid')
                    <span class="badge bg-success  text-white">مدفوعة</span>
                @else
                    <span class="badge bg-light text-dark">{{ $salesInvoice->payment_status }}</span>
                @endif
            </strong>
        </div>

        <div class="status-box amount-summary">
            <span>إجمالي الفاتورة</span>
            <strong>{{ number_format((float) $salesInvoice->total_amount, 2) }}</strong>
        </div>

        <div class="status-box remaining-summary">
            <span>المتبقي</span>
            <strong>{{ number_format((float) $salesInvoice->remaining_amount, 2) }}</strong>
        </div>

    </div>


    {{-- Invoice Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات الفاتورة</h5>
                <small>بيانات التاريخ والفرع والمستودع وطريقة الدفع</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">تاريخ الفاتورة</label>
                    <div class="readonly-box ltr-cell">
                        {{ $salesInvoice->invoice_date?->format('Y-m-d') }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الفرع</label>
                    <div class="readonly-box">
                        {{ $salesInvoice->branch?->branch_name_ar
                            ?? $salesInvoice->branch?->branch_name
                            ?? $salesInvoice->branch?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">المستودع</label>
                    <div class="readonly-box">
                        {{ $salesInvoice->warehouse?->warehouse_name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">طريقة الدفع</label>
                    <div class="readonly-box">
                        @if($salesInvoice->payment_method === 'cash')
                            نقدي
                        @elseif($salesInvoice->payment_method === 'card')
                            شبكة
                        @elseif($salesInvoice->payment_method === 'bank_transfer')
                            تحويل بنكي
                        @elseif($salesInvoice->payment_method === 'other')
                            أخرى
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">نوع العميل</label>
                    <div class="readonly-box">
                        {{ $salesInvoice->customer_type === 'credit' ? 'آجل' : 'نقدي' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">نوع الدفع</label>
                    <div class="readonly-box">
                        @if($salesInvoice->payment_type === 'cash')
                            نقدي بالكامل
                        @elseif($salesInvoice->payment_type === 'credit')
                            آجل بالكامل
                        @elseif($salesInvoice->payment_type === 'partial')
                            دفع جزئي
                        @else
                            {{ $salesInvoice->payment_type }}
                        @endif
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
                <small>بيانات العميل النقدي أو العميل المسجل المرتبط بالفاتورة</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">اسم العميل</label>
                    <div class="readonly-box">
                        {{ $salesInvoice->customer_name ?? 'عميل نقدي' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الجوال</label>
                    <div class="readonly-box ltr-cell">
                        {{ $salesInvoice->customer_mobile ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الرقم الضريبي</label>
                    <div class="readonly-box ltr-cell">
                        {{ $salesInvoice->customer_tax_number ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">العميل المسجل</label>
                    <div class="readonly-box">
                        {{ $salesInvoice->customer?->customer_name
                            ?? $salesInvoice->customer?->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">العنوان</label>
                    <div class="readonly-box notes-box">
                        {{ $salesInvoice->customer_address ?? '-' }}
                    </div>
                </div>

            </div>

        </div>

    </div>


    {{-- Items --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">أصناف الفاتورة</h5>
                <small>تفاصيل الأصناف والكميات والأسعار والضريبة والتكلفة</small>
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
                            <th>الكمية</th>
                            <th>سعر البيع</th>
                            <th>الخصم</th>
                            <th>الصافي</th>
                            <th>الضريبة %</th>
                            <th>قيمة الضريبة</th>
                            <th>الإجمالي</th>
                            <th>التكلفة</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($salesInvoice->items as $index => $item)
                            <tr>
                                <td class="fw-bold">{{ $index + 1 }}</td>

                                <td class="text-start item-name-cell">
                                    <div class="fw-bold">
                                        {{ $item->product_name
                                            ?? $item->product?->product_name_ar
                                            ?? $item->product?->product_name
                                            ?? '-' }}
                                    </div>

                                    @if($item->product_sku)
                                        <small>
                                            كود: {{ $item->product_sku }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    {{ $item->unit_name
                                        ?? $item->productUnit?->unit?->unit_name
                                        ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->quantity, 3) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->unit_price, 2) }}
                                </td>

                                <td class="amount-cell text-danger">
                                    {{ number_format((float) $item->discount_amount, 2) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->net_amount, 2) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->vat_rate, 2) }}%
                                </td>

                                <td class="amount-cell text-warning">
                                    {{ number_format((float) $item->vat_amount, 2) }}
                                </td>

                                <td class="amount-cell fw-bold">
                                    {{ number_format((float) $item->line_total, 2) }}
                                </td>

                                <td class="amount-cell">
                                    {{ number_format((float) $item->total_cost, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        لا توجد أصناف في هذه الفاتورة.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>

    </div>


    {{-- Totals --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">إجماليات الفاتورة</h5>
                <small>ملخص الفاتورة والخصم والضريبة والسداد</small>
            </div>
        </div>

        <div class="card-body">

            <div class="totals-grid mb-4">

                <div class="total-box">
                    <span>الإجمالي قبل الضريبة</span>
                    <strong>{{ number_format((float) $salesInvoice->subtotal, 2) }}</strong>
                </div>

                <div class="total-box discount-box">
                    <span>إجمالي الخصم</span>
                    <strong>{{ number_format((float) $salesInvoice->discount_amount, 2) }}</strong>
                </div>

                <div class="total-box vat-box">
                    <span>إجمالي الضريبة</span>
                    <strong>{{ number_format((float) $salesInvoice->vat_amount, 2) }}</strong>
                </div>

                <div class="total-box final-box">
                    <span>الإجمالي النهائي</span>
                    <strong>{{ number_format((float) $salesInvoice->total_amount, 2) }}</strong>
                </div>

            </div>

            <div class="totals-grid payment-grid">

                <div class="total-box paid-box">
                    <span>المدفوع</span>
                    <strong>{{ number_format((float) $salesInvoice->paid_amount, 2) }}</strong>
                </div>

                <div class="total-box remaining-box">
                    <span>المتبقي</span>
                    <strong>{{ number_format((float) $salesInvoice->remaining_amount, 2) }}</strong>
                </div>

            </div>

        </div>

    </div>


    {{-- Notes / Cancellation --}}
    @if($salesInvoice->notes || $salesInvoice->status === 'cancelled')
        <div class="card shadow-sm wazin-card mb-5">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">ملاحظات وحالة الإلغاء</h5>
                    <small>أي تفاصيل إضافية مرتبطة بالفاتورة</small>
                </div>
            </div>

            <div class="card-body">

                @if($salesInvoice->notes)
                    <div class="journal-status-note info-note mb-3">
                        <strong>ملاحظات:</strong>
                        <br>
                        {{ $salesInvoice->notes }}
                    </div>
                @endif

                @if($salesInvoice->status === 'cancelled')
                    <div class="journal-status-note danger-note">
                        <strong>سبب الإلغاء:</strong>
                        <br>
                        {{ $salesInvoice->cancel_reason ?? 'لم يتم تحديد سبب.' }}
                    </div>
                @endif

            </div>
        </div>
    @endif

</div>


{{-- Cancel Modal --}}
<div class="modal fade" id="cancelInvoiceModal" tabindex="-1" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">

        <form method="POST" action="{{ route('sales-invoices.cancel', $salesInvoice->id) }}" class="modal-content">
            @csrf

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">إلغاء فاتورة البيع</h5>
                    <small>سيتم عكس أثر الفاتورة حسب حالتها</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="journal-status-note warning-note mb-3">
                    هل أنت متأكد من إلغاء هذه الفاتورة؟
                    @if($salesInvoice->status === 'posted')
                        <br>
                        سيتم إرجاع المخزون وعكس القيود المحاسبية.
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
    .totals-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .payment-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .status-box,
    .total-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .status-box span,
    .total-box span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .status-box strong,
    .total-box strong {
        direction: ltr;
        display: block;
        color: #071633;
        font-size: 21px;
        font-weight: 900;
    }

    .amount-summary {
        border-right: 5px solid #2F6BFF;
    }

    .remaining-summary,
    .remaining-box,
    .discount-box {
        border-right: 5px solid #E63B4A;
    }

    .paid-box {
        border-right: 5px solid #16A34A;
    }

    .vat-box {
        border-right: 5px solid #F59E0B;
    }

    .final-box {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.06);
    }

    .discount-box strong,
    .remaining-box strong,
    .remaining-summary strong {
        color: #E63B4A;
    }

    .paid-box strong {
        color: #16A34A;
    }

    .vat-box strong {
        color: #F59E0B;
    }

    .final-box strong {
        color: #2F6BFF;
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

    .notes-box {
        white-space: pre-wrap;
    }

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
        min-width: 240px;
        text-align: right !important;
    }

    .item-name-cell small {
        color: #64748B;
        font-weight: 700;
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
        .totals-grid {
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
        .totals-grid,
        .payment-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

</x-app-layout>