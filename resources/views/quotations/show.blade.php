<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">عرض سعر رقم: {{ $quotation->quotation_no }}</h3>
            <p class="page-subtitle mb-0">
                تفاصيل عرض السعر والعميل والأصناف والإجماليات.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('quotations.index') }}" class="btn btn-secondary">
                رجوع
            </a>

            @can('quotations.print')
                <a href="{{ route('quotations.print', $quotation->id) }}"
                   target="_blank"
                   class="btn btn-dark">
                    طباعة
                </a>
            @endcan

            @can('quotations.edit')
                @if(! in_array($quotation->status, ['converted', 'cancelled'], true))
                    <a href="{{ route('quotations.edit', $quotation->id) }}"
                       class="btn btn-warning">
                        تعديل
                    </a>
                @endif
            @endcan

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


    {{-- Quotation Info --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات عرض السعر</h5>
                <small>رقم العرض، التاريخ، الفرع، الحالة، والصلاحية</small>
            </div>

            {!! match($quotation->status) {
                'draft' => '<span class="badge bg-secondary">مسودة</span>',
                'sent' => '<span class="badge bg-info">مرسل</span>',
                'approved' => '<span class="badge bg-success">معتمد</span>',
                'rejected' => '<span class="badge bg-danger">مرفوض</span>',
                'converted' => '<span class="badge bg-primary">محول لفاتورة</span>',
                'cancelled' => '<span class="badge bg-dark">ملغي</span>',
                default => '<span class="badge bg-light text-dark">' . e($quotation->status) . '</span>',
            } !!}
        </div>

        <div class="card-body">
            <div class="info-grid">

                <div class="info-box">
                    <span>رقم العرض</span>
                    <strong>{{ $quotation->quotation_no }}</strong>
                </div>

                <div class="info-box">
                    <span>تاريخ العرض</span>
                    <strong>{{ optional($quotation->quotation_date)->format('Y-m-d') }}</strong>
                </div>

                <div class="info-box">
                    <span>صالح إلى</span>
                    <strong>{{ optional($quotation->valid_until)->format('Y-m-d') ?? '-' }}</strong>
                </div>

                <div class="info-box">
                    <span>الفرع</span>
                    <strong>
                        {{ $quotation->branch?->branch_name_ar
                            ?? $quotation->branch?->branch_name
                            ?? $quotation->branch?->name
                            ?? '-' }}
                    </strong>
                </div>

                <div class="info-box">
                    <span>المستودع</span>
                    <strong>{{ $quotation->warehouse?->warehouse_name ?? '-' }}</strong>
                </div>

                <div class="info-box">
                    <span>مركز التكلفة</span>
                    <strong>
                        {{ $quotation->costCenter
                            ? (($quotation->costCenter->code ? $quotation->costCenter->code . ' - ' : '') . $quotation->costCenter->name)
                            : '-' }}
                    </strong>
                </div>

            </div>
        </div>
    </div>


    {{-- Customer Info --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات العميل</h5>
                <small>بيانات العميل المحفوظة داخل عرض السعر</small>
            </div>
        </div>

        <div class="card-body">
            <div class="info-grid">

                <div class="info-box">
                    <span>نوع العميل</span>
                    <strong>
                        {{ $quotation->customer_type === 'credit' ? 'آجل' : 'نقدي' }}
                    </strong>
                </div>

                <div class="info-box">
                    <span>اسم العميل</span>
                    <strong>{{ $quotation->customer_name ?? 'عميل نقدي' }}</strong>
                </div>

                <div class="info-box">
                    <span>الجوال</span>
                    <strong>{{ $quotation->customer_mobile ?? '-' }}</strong>
                </div>

                <div class="info-box">
                    <span>الرقم الضريبي</span>
                    <strong>{{ $quotation->customer_tax_number ?? '-' }}</strong>
                </div>

                <div class="info-box info-box-wide">
                    <span>العنوان</span>
                    <strong>{{ $quotation->customer_address ?? '-' }}</strong>
                </div>

            </div>
        </div>
    </div>


    {{-- Items --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">أصناف عرض السعر</h5>
                <small>الأصناف والكميات والأسعار والضريبة</small>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصنف</th>
                            <th>الكود</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>الخصم</th>
                            <th>الصافي</th>
                            <th>الضريبة %</th>
                            <th>قيمة الضريبة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($quotation->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">
                                    {{ $item->product_name ?? '-' }}
                                </td>
                                <td>{{ $item->product_sku ?? '-' }}</td>
                                <td>{{ $item->unit_name ?? '-' }}</td>
                                <td>{{ number_format((float) $item->quantity, 3) }}</td>
                                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>{{ number_format((float) $item->discount_amount, 2) }}</td>
                                <td>{{ number_format((float) $item->net_amount, 2) }}</td>
                                <td>{{ number_format((float) $item->vat_rate, 2) }}</td>
                                <td>{{ number_format((float) $item->vat_amount, 2) }}</td>
                                <td class="fw-bold">{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-muted">
                                    لا توجد أصناف في عرض السعر.
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
                <h5 class="mb-0 fw-bold">إجماليات عرض السعر</h5>
                <small>ملخص الخصم والضريبة والإجمالي النهائي</small>
            </div>
        </div>

        <div class="card-body">
            <div class="totals-grid">

                <div class="total-box">
                    <span>الإجمالي قبل الضريبة</span>
                    <strong>{{ number_format((float) $quotation->subtotal, 2) }}</strong>
                </div>

                <div class="total-box">
                    <span>إجمالي الخصم</span>
                    <strong>{{ number_format((float) $quotation->discount_amount, 2) }}</strong>
                </div>

                <div class="total-box">
                    <span>إجمالي الضريبة</span>
                    <strong>{{ number_format((float) $quotation->vat_amount, 2) }}</strong>
                </div>

                <div class="total-box total-box-final">
                    <span>الإجمالي النهائي</span>
                    <strong>{{ number_format((float) $quotation->total_amount, 2) }}</strong>
                </div>

            </div>
        </div>
    </div>


    {{-- Notes --}}
    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">الملاحظات والشروط</h5>
                <small>الملاحظات والشروط الخاصة بعرض السعر</small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="note-box">
                        <span>ملاحظات</span>
                        <p>{{ $quotation->notes ?: '-' }}</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="note-box">
                        <span>الشروط والأحكام</span>
                        <p>{{ $quotation->terms ?: '-' }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- Actions --}}
    <div class="save-actions mb-5">

        <a href="{{ route('quotations.index') }}" class="btn btn-secondary">
            رجوع
        </a>

        @can('quotations.edit')
            @if(! in_array($quotation->status, ['converted', 'cancelled'], true))
                <a href="{{ route('quotations.edit', $quotation->id) }}"
                   class="btn btn-warning">
                    تعديل
                </a>
            @endif
        @endcan

        @can('quotations.print')
            <a href="{{ route('quotations.print', $quotation->id) }}"
               target="_blank"
               class="btn btn-dark">
                طباعة
            </a>
        @endcan

        @can('quotations.approve')
            @if(in_array($quotation->status, ['draft', 'sent', 'rejected'], true))
                <form method="POST"
                      action="{{ route('quotations.approve', $quotation->id) }}"
                      id="approveQuotationForm">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        اعتماد عرض السعر
                    </button>
                </form>
            @endif
        @endcan

        @can('quotations.reject')
            @if(in_array($quotation->status, ['draft', 'sent', 'approved'], true))
                <form method="POST"
                      action="{{ route('quotations.reject', $quotation->id) }}"
                      id="rejectQuotationForm">
                    @csrf

                    <button type="submit" class="btn btn-danger">
                        رفض عرض السعر
                    </button>
                </form>
            @endif
        @endcan

        @can('quotations.convert')
            @if(in_array($quotation->status, ['sent', 'approved'], true))
                <form method="POST"
                      action="{{ route('quotations.convert-to-invoice', $quotation->id) }}"
                      id="convertQuotationForm">
                    @csrf

                    <button type="submit" class="btn btn-primary">
                        تحويل إلى فاتورة
                    </button>
                </form>
            @endif
        @endcan

        @can('quotations.cancel')
            @if(! in_array($quotation->status, ['cancelled', 'converted'], true))
                <form method="POST"
                      action="{{ route('quotations.cancel', $quotation->id) }}"
                      id="cancelQuotationForm">
                    @csrf

                    <button type="submit" class="btn btn-danger">
                        إلغاء عرض السعر
                    </button>
                </form>
            @endif
        @endcan

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

    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .info-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 16px;
        min-height: 86px;
    }

    .info-box-wide {
        grid-column: span 3;
    }

    .info-box span,
    .note-box span,
    .total-box span {
        display: block;
        color: #64748B;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .info-box strong,
    .total-box strong {
        color: #071633;
        font-weight: 900;
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
        background: #fff;
        font-weight: 700;
    }

    .totals-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .total-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 18px;
        text-align: center;
    }

    .total-box strong {
        direction: ltr;
        display: block;
        font-size: 18px;
    }

    .total-box-final {
        background: #071633;
        border-color: #071633;
    }

    .total-box-final span {
        color: #CFEFF3;
    }

    .total-box-final strong {
        color: #fff;
        font-size: 22px;
    }

    .note-box {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 16px;
        min-height: 130px;
    }

    .note-box p {
        margin: 0;
        color: #071633;
        font-weight: 700;
        line-height: 1.9;
        white-space: pre-line;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-secondary,
    .btn-danger,
    .btn-dark {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
    }

    .badge {
        border-radius: 999px;
        padding: 8px 13px;
        font-weight: 900;
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
        .info-grid,
        .totals-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .info-box-wide {
            grid-column: span 2;
        }
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .info-grid,
        .totals-grid {
            grid-template-columns: 1fr;
        }

        .info-box-wide {
            grid-column: span 1;
        }

        .save-actions {
            flex-direction: column;
            position: static;
        }

        .save-actions .btn,
        .save-actions form,
        .page-header-card .btn {
            width: 100%;
        }
    }
</style>


@push('scripts')
<script>
$(document).ready(function () {

    $('#cancelQuotationForm').on('submit', function (e) {
        e.preventDefault();

        let form = this;

        Swal.fire({
            icon: 'warning',
            title: 'إلغاء عرض السعر',
            html: 'هل أنت متأكد من إلغاء عرض السعر؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، إلغاء',
            cancelButtonText: 'تراجع',
            confirmButtonColor: '#E63B4A',
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });


    $('#convertQuotationForm').on('submit', function (e) {
        e.preventDefault();

        let form = this;

        Swal.fire({
            icon: 'question',
            title: 'تحويل إلى فاتورة',
            html: 'هل تريد تحويل عرض السعر إلى فاتورة بيع مسودة؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، تحويل',
            cancelButtonText: 'تراجع',
            confirmButtonColor: '#2F6BFF',
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $('#approveQuotationForm').on('submit', function (e) {
        e.preventDefault();

        let form = this;

        Swal.fire({
            icon: 'question',
            title: 'اعتماد عرض السعر',
            html: 'هل تريد اعتماد عرض السعر؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، اعتماد',
            cancelButtonText: 'تراجع',
            confirmButtonColor: '#16A34A',
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $('#rejectQuotationForm').on('submit', function (e) {
        e.preventDefault();

        let form = this;

        Swal.fire({
            icon: 'warning',
            title: 'رفض عرض السعر',
            html: 'هل تريد رفض عرض السعر؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، رفض',
            cancelButtonText: 'تراجع',
            confirmButtonColor: '#E63B4A',
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

});
</script>
@endpush

</x-app-layout>