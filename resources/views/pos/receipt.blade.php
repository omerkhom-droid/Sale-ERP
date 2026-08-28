@php
    $receiptTitle = $posSetting?->receipt_title ?: config('app.name', 'Wazin ERP');
    $receiptFooter = $posSetting?->receipt_footer ?: 'شكرًا لزيارتكم';

    $receiptCopies = max(1, min(5, (int) ($receiptCopies ?? 1)));
    $autoPrint = (bool) ($autoPrint ?? true);
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إيصال POS - {{ $posOrder->order_no }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff;
            color: #000;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        .no-print {
            width: 80mm;
            margin: 10px auto;
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn {
            border: 1px solid #000;
            background: #fff;
            color: #000;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
        }

        .receipt {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 7px 8px;
        }

        .receipt-copy {
            page-break-after: always;
        }

        .receipt-copy:last-child {
            page-break-after: auto;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: left;
        }

        .fw-bold {
            font-weight: 700;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 7px;
            margin-bottom: 7px;
        }

        .header h3 {
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 900;
        }

        .header div {
            font-size: 11px;
        }

        .section {
            margin-bottom: 7px;
        }

        .info-row,
        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }

        .info-row span,
        .total-row span {
            white-space: nowrap;
        }

        .info-row strong,
        .total-row strong {
            text-align: left;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin: 7px 0;
        }

        .items th,
        .items td {
            padding: 4px 0;
            border-bottom: 1px dashed #999;
            vertical-align: top;
        }

        .items th {
            font-size: 11px;
            font-weight: 900;
        }

        .product-name {
            font-weight: 800;
            word-break: break-word;
        }

        .unit-name {
            font-size: 10px;
        }

        .totals {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 7px 0;
            margin: 7px 0;
        }

        .grand-total {
            font-size: 15px;
            font-weight: 900;
        }

        .payment-box {
            margin-top: 7px;
        }

        .footer {
            margin-top: 9px;
            border-top: 1px dashed #000;
            padding-top: 7px;
            text-align: center;
        }

        .footer .system {
            font-size: 10px;
            margin-top: 4px;
        }

        .copy-label {
            margin-top: 5px;
            font-size: 10px;
            text-align: center;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .receipt {
                margin: 0;
                width: 80mm;
                max-width: 80mm;
            }
        }
    </style>
</head>

<body>

<div class="no-print">
    <button type="button" class="btn" onclick="window.print()">طباعة</button>
    <button type="button" class="btn" onclick="window.close()">إغلاق</button>
</div>

@for($copy = 1; $copy <= $receiptCopies; $copy++)

    <div class="receipt receipt-copy">

        <div class="header">
            <h3>{{ $receiptTitle }}</h3>
            <div>{{ $posOrder->branch?->branch_name ?? '-' }}</div>
            <div>{{ $posOrder->warehouse?->warehouse_name ?? '-' }}</div>
        </div>

        <div class="section">

            <div class="info-row">
                <span>رقم الطلب:</span>
                <strong>{{ $posOrder->order_no }}</strong>
            </div>

            <div class="info-row">
                <span>رقم الفاتورة:</span>
                <strong>{{ $posOrder->salesInvoice?->invoice_no ?? '-' }}</strong>
            </div>

            <div class="info-row">
                <span>التاريخ:</span>
                <strong>{{ optional($posOrder->paid_at ?? $posOrder->created_at)->format('Y-m-d H:i') }}</strong>
            </div>

            <div class="info-row">
                <span>الكاشير:</span>
                <strong>{{ $posOrder->creator?->name ?? '-' }}</strong>
            </div>

            <div class="info-row">
                <span>نوع الطلب:</span>
                <strong>
                    @switch($posOrder->order_type)
                        @case('dine_in') محلي @break
                        @case('delivery') توصيل @break
                        @default سفري
                    @endswitch
                </strong>
            </div>

            @if($posOrder->table_no)
                <div class="info-row">
                    <span>الطاولة:</span>
                    <strong>{{ $posOrder->table_no }}</strong>
                </div>
            @endif

        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 43%;">الصنف</th>
                    <th style="width: 15%;" class="text-center">كمية</th>
                    <th style="width: 19%;" class="text-center">سعر</th>
                    <th style="width: 23%;" class="text-end">الإجمالي</th>
                </tr>
            </thead>

            <tbody>
                @foreach($posOrder->items as $item)
                    <tr>
                        <td>
                            <div class="product-name">
                                {{ $item->product?->product_name_ar ?? $item->product?->product_name_en ?? '-' }}
                            </div>

                            <div class="unit-name">
                                {{ $item->productUnit?->unit?->unit_name ?? '' }}
                            </div>
                        </td>

                        <td class="text-center">
                            {{ number_format((float) $item->quantity, 2) }}
                        </td>

                        <td class="text-center">
                            {{ number_format((float) $item->unit_price, 2) }}
                        </td>

                        <td class="text-end">
                            {{ number_format((float) $item->line_total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">

            <div class="total-row">
                <span>الإجمالي قبل الخصم:</span>
                <strong>{{ number_format((float) $posOrder->subtotal, 2) }}</strong>
            </div>

            <div class="total-row">
                <span>الخصم:</span>
                <strong>{{ number_format((float) $posOrder->discount_amount, 2) }}</strong>
            </div>

            <div class="total-row">
                <span>ضريبة القيمة المضافة:</span>
                <strong>{{ number_format((float) $posOrder->vat_amount, 2) }}</strong>
            </div>

            <div class="total-row grand-total">
                <span>الإجمالي:</span>
                <strong>{{ number_format((float) $posOrder->total_amount, 2) }}</strong>
            </div>

            <div class="total-row">
                <span>المدفوع:</span>
                <strong>{{ number_format((float) $posOrder->paid_amount, 2) }}</strong>
            </div>

            <div class="total-row">
                <span>الباقي:</span>
                <strong>{{ number_format((float) $posOrder->change_amount, 2) }}</strong>
            </div>

        </div>

        <div class="payment-box">
            @foreach($posOrder->payments as $payment)
                <div class="info-row">
                    <span>
                        @switch($payment->payment_method)
                            @case('cash') نقدي @break
                            @case('card') شبكة @break
                            @case('bank_transfer') تحويل @break
                            @default {{ $payment->payment_method }}
                        @endswitch
                    </span>

                    <strong>{{ number_format((float) $payment->amount, 2) }}</strong>
                </div>
            @endforeach
        </div>

        <div class="footer">
            <div class="fw-bold">{{ $receiptFooter }}</div>
            <div class="system">تمت الطباعة من نظام وازن ERP</div>

            @if($receiptCopies > 1)
                <div class="copy-label">
                    نسخة {{ $copy }} من {{ $receiptCopies }}
                </div>
            @endif
        </div>

    </div>

@endfor

<script>
    const autoPrint = @json($autoPrint);

    window.addEventListener('load', function () {
        if (! autoPrint) {
            return;
        }

        setTimeout(function () {
            window.print();
        }, 400);
    });
</script>

</body>
</html>