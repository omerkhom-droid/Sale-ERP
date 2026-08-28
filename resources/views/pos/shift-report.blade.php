<x-app-layout>

@php
    $paidOrders = $posShift->orders->where('status', 'paid');
    $cancelledOrders = $posShift->orders->where('status', 'cancelled');

    $cashDifference = round((float) $posShift->cash_difference, 2);

    if (abs($cashDifference) <= 0.01) {
        $cashStatusClass = 'matched';
        $cashStatusText = 'مطابق';
        $cashStatusBadge = 'bg-success';
    } elseif ($cashDifference > 0) {
        $cashStatusClass = 'surplus';
        $cashStatusText = 'فائض';
        $cashStatusBadge = 'bg-primary';
    } else {
        $cashStatusClass = 'shortage';
        $cashStatusText = 'عجز';
        $cashStatusBadge = 'bg-danger';
    }
@endphp

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">تقرير وردية الكاشير</h3>
            <p class="page-subtitle mb-0">
                ملخص المبيعات والمدفوعات والنقدية للوردية رقم {{ $posShift->shift_no }}.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" onclick="window.print()" class="btn btn-light fw-bold">
                طباعة
            </button>

            <a href="{{ route('pos.index') }}" class="btn btn-secondary fw-bold">
                رجوع
            </a>
        </div>
    </div>

    <div class="print-header d-none d-print-block mb-4">
        <h3>تقرير وردية الكاشير</h3>
        <p>رقم الوردية: {{ $posShift->shift_no }}</p>
        <p>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">بيانات الوردية</h5>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <div class="info-box">
                        <span>رقم الوردية</span>
                        <strong>{{ $posShift->shift_no }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>الكاشير</span>
                        <strong>{{ $posShift->user?->name ?? '-' }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>الفرع</span>
                        <strong>{{ $posShift->branch?->branch_name ?? '-' }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>المستودع</span>
                        <strong>{{ $posShift->warehouse?->warehouse_name ?? '-' }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>وقت الفتح</span>
                        <strong>{{ optional($posShift->opened_at)->format('Y-m-d H:i') }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>وقت الإغلاق</span>
                        <strong>{{ optional($posShift->closed_at)->format('Y-m-d H:i') ?? '-' }}</strong>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>الحالة</span>
                        @if($posShift->status === 'open')
                            <strong class="text-success">مفتوحة</strong>
                        @else
                            <strong class="text-danger">مغلقة</strong>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-box">
                        <span>عدد الطلبات المدفوعة</span>
                        <strong>{{ $paidOrders->count() }}</strong>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي المبيعات</span>
                <strong>{{ number_format((float) $posShift->total_sales, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي النقدي</span>
                <strong>{{ number_format((float) $posShift->total_cash, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي الشبكة</span>
                <strong>{{ number_format((float) $posShift->total_card, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي التحويل</span>
                <strong>{{ number_format((float) $posShift->total_bank_transfer, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>بداية الصندوق</span>
                <strong>{{ number_format((float) $posShift->opening_cash, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>النقد المتوقع</span>
                <strong>{{ number_format((float) $posShift->expected_cash, 2) }}</strong>
                <small>بداية الصندوق + مبيعات الكاش</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>النقد الفعلي</span>
                @if($posShift->status === 'closed')
                    <strong>{{ number_format((float) $posShift->actual_cash, 2) }}</strong>
                @else
                    <strong>-</strong>
                    <small>يظهر بعد الإغلاق</small>
                @endif
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card {{ $posShift->status === 'closed' ? $cashStatusClass : '' }}">
                <span>حالة الصندوق</span>

                @if($posShift->status === 'closed')
                    <strong>{{ number_format($cashDifference, 2) }}</strong>
                    <small>
                        <span class="badge {{ $cashStatusBadge }} text-white">{{ $cashStatusText }}</span>
                    </small>
                @else
                    <strong>غير مغلقة</strong>
                    <small>لم يتم إدخال النقد الفعلي</small>
                @endif
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي الخصومات</span>
                <strong>{{ number_format((float) $posShift->total_discount, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي الضريبة</span>
                <strong>{{ number_format((float) $posShift->total_vat, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي الملغي</span>
                <strong>{{ number_format((float) $posShift->total_cancelled, 2) }}</strong>
                <small>{{ $cancelledOrders->count() }} طلب ملغي</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <span>إجمالي الطلبات</span>
                <strong>{{ $posShift->orders->count() }}</strong>
                <small>مدفوع / ملغي / مسودة</small>
            </div>
        </div>

    </div>

    <div class="cash-reconciliation-card mb-4">
        <div>
            <span>مطابقة الصندوق</span>
            <strong>
                المتوقع: {{ number_format((float) $posShift->expected_cash, 2) }}
                /
                الفعلي:
                @if($posShift->status === 'closed')
                    {{ number_format((float) $posShift->actual_cash, 2) }}
                @else
                    -
                @endif
            </strong>
        </div>

        <div>
            @if($posShift->status === 'closed')
                <span class="badge {{ $cashStatusBadge }}">
                    {{ $cashStatusText }}: {{ number_format($cashDifference, 2) }}
                </span>
            @else
                <span class="badge bg-warning text-dark">
                    الوردية مفتوحة
                </span>
            @endif
        </div>
    </div>

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">طلبات الوردية</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الطلب</th>
                        <th>الفاتورة</th>
                        <th>الوقت</th>
                        <th>نوع الطلب</th>
                        <th>طريقة الدفع</th>
                        <th>الإجمالي</th>
                        <th>المدفوع</th>
                        <th>الباقي</th>
                        <th>الحالة</th>
                        <th width="160" class="no-print">الإجراء</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($posShift->orders as $order)
                        <tr class="{{ $order->status === 'cancelled' ? 'table-danger' : '' }}">
                            <td>{{ $loop->iteration }}</td>

                            <td class="fw-bold">{{ $order->order_no }}</td>

                            <td>
                                {{ $order->salesInvoice?->invoice_no ?? '-' }}
                            </td>

                            <td>
                                {{ optional($order->paid_at ?? $order->created_at)->format('Y-m-d H:i') }}
                            </td>

                            <td>
                                @switch($order->order_type)
                                    @case('dine_in') محلي @break
                                    @case('delivery') توصيل @break
                                    @default سفري
                                @endswitch
                            </td>

                            <td>
                                @foreach($order->payments as $payment)
                                    <div>
                                        @switch($payment->payment_method)
                                            @case('cash') نقدي @break
                                            @case('card') شبكة @break
                                            @case('bank_transfer') تحويل @break
                                            @default {{ $payment->payment_method }}
                                        @endswitch

                                        -
                                        {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                @endforeach
                            </td>

                            <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td>{{ number_format((float) $order->paid_amount, 2) }}</td>
                            <td>{{ number_format((float) $order->change_amount, 2) }}</td>

                            <td>
                                @if($order->status === 'paid')
                                    <span class="badge bg-success">مدفوع</span>
                                @elseif($order->status === 'cancelled')
                                    <span class="badge bg-danger">ملغي</span>
                                @else
                                    <span class="badge bg-secondary">مسودة</span>
                                @endif
                            </td>

                            <td class="no-print">
                                @if($order->status === 'paid' && $posShift->status === 'open')
                                    @can('pos.cancel_order')
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                onclick="openCancelOrderModal(
                                                    '{{ route('pos.orders.cancel', $order->id) }}',
                                                    '{{ $order->order_no }}'
                                                )">
                                            إلغاء
                                        </button>
                                    @endcan
                                @elseif($order->status === 'cancelled')
                                    <span class="badge bg-danger">ملغي</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-4 text-muted fw-bold">
                                لا توجد طلبات في هذه الوردية.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">إجمالي الطلبات المدفوعة</th>
                        <th>{{ number_format((float) $paidOrders->sum('total_amount'), 2) }}</th>
                        <th>{{ number_format((float) $paidOrders->sum('paid_amount'), 2) }}</th>
                        <th>{{ number_format((float) $paidOrders->sum('change_amount'), 2) }}</th>
                        <th></th>
                        <th class="no-print"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="cancelOrderForm" class="modal-content">
            @csrf

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white fw-bold">إلغاء طلب POS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-warning fw-bold">
                    سيتم إلغاء الطلب وعكس الفاتورة والمخزون والقيود المرتبطة به.
                </div>

                <div class="mb-3">
                    <label class="form-label">رقم الطلب</label>
                    <input type="text" id="cancel_order_no" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">سبب الإلغاء <span class="text-danger">*</span></label>
                    <textarea name="cancel_reason"
                              id="cancel_reason"
                              class="form-control"
                              rows="4"
                              required
                              placeholder="اكتب سبب الإلغاء"></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    تراجع
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
        font-weight: 700;
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

    .info-box,
    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        min-height: 86px;
    }

    .info-box span,
    .summary-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .summary-card small {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-top: 5px;
        font-size: 12px;
    }

    .info-box strong,
    .summary-card strong {
        color: #071633;
        font-weight: 900;
        font-size: 18px;
    }

    .summary-card.matched {
        border-color: #22C55E;
        background: #F0FDF4;
    }

    .summary-card.surplus {
        border-color: #2F6BFF;
        background: #EFF6FF;
    }

    .summary-card.shortage,
    .summary-card.different {
        border-color: #EF4444;
        background: #FEF2F2;
    }

    .cash-reconciliation-card {
        background: #071633;
        color: #fff;
        border-radius: 20px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 12px 32px rgba(7, 22, 51, 0.16);
    }

    .cash-reconciliation-card span {
        display: block;
        color: #CFEFF3;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .cash-reconciliation-card strong {
        color: #fff;
        font-size: 20px;
        font-weight: 900;
    }

    .table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    .table td {
        font-weight: 700;
        vertical-align: middle;
    }

    .table tfoot th {
        background: #F8FAFC;
        color: #071633;
        font-weight: 900;
    }

    .badge {
        font-weight: 900;
        padding: 7px 10px;
        border-radius: 10px;
    }

    .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .card,
        .summary-card,
        .info-box,
        .cash-reconciliation-card {
            box-shadow: none !important;
        }

        .cash-reconciliation-card {
            background: #fff !important;
            color: #000 !important;
            border: 1px solid #000;
        }

        .cash-reconciliation-card span,
        .cash-reconciliation-card strong {
            color: #000 !important;
        }

        .table thead th {
            background: #eee !important;
            color: #000 !important;
        }

        .table-danger td {
            background: #fff !important;
        }
    }
</style>

<script>
    let cancelOrderUrl = null;

    function openCancelOrderModal(url, orderNo) {
        cancelOrderUrl = url;

        document.getElementById('cancel_order_no').value = orderNo;
        document.getElementById('cancel_reason').value = '';

        const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
        modal.show();
    }

    document.getElementById('cancelOrderForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        if (! cancelOrderUrl) {
            alert('رابط الإلغاء غير صحيح.');
            return;
        }

        const form = this;
        const button = form.querySelector('button[type="submit"]');

        button.disabled = true;
        button.innerText = 'جاري الإلغاء...';

        fetch(cancelOrderUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
            },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json();

            if (! response.ok || data.success === false) {
                throw new Error(data.message || 'تعذر إلغاء الطلب.');
            }

            alert(data.message || 'تم إلغاء الطلب بنجاح.');

            window.location.reload();
        })
        .catch(error => {
            alert(error.message);
            button.disabled = false;
            button.innerText = 'تأكيد الإلغاء';
        });
    });
</script>

</x-app-layout>