<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سند صرف عميل - {{ $customerRefundVoucher->voucher_no }}</title>

    <style>
        :root {
            --text: #111827;
            --muted: #6B7280;
            --border: #D1D5DB;
            --soft-border: #E5E7EB;
            --bg: #F3F4F6;
            --light: #F9FAFB;
            --lighter: #F8FAFC;
            --success: #166534;
            --danger: #B91C1C;
            --warning: #B45309;
            --blue: #1D4ED8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, Arial, sans-serif;
            font-size: 12.5px;
            color: var(--text);
            background: var(--bg);
            margin: 0;
            padding: 20px;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 16px;
        }

        .print-actions button {
            border: 1px solid #CBDbg);
            margin: 0;
            padding: 20px;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 16px;
       5E1;
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            padding: 9px 24px;
            font-weight: 800;
            cursor: pointer;
        }

        .print-actions button:hover {
            background: var(--light);
        }

        .invoice-container {
            width: 100%;
            max-width: 950px;
            margin: auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .invoice-top {
            background: #fff;
            border-bottom: 2px solid #9CA3AF;
            padding: 22px 26px;
        }

        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 22px;
        }

        .company-side {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .logo-box {
            width: 82px;
            min-width: 82px;
            height: 82px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 6px;
        }

        .company-logo {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .logo-placeholder {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            background: var(--light);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
        }

        .company-info {
            flex: 1;
            min-width: 0;
        }

        .company-name {
            color: var(--text);
            font-size: 25px;
            font-weight: 900;
            margin: 0 0 4px;
            line-height: 1.4;
        }

        .company-name-en {
            direction: ltr;
            text-align: right;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .company-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 5px 14px;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.8;
        }

        .company-address {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.7;
        }

        .document-side {
            min-width: 220px;
            text-align: left;
        }

        .document-title {
            color: var(--text);
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 3px;
        }

        .document-title-en {
            color: var(--muted);
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .document-number {
            direction: ltr;
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .status {
            display: inline-block;
            min-width: 90px;
            text-align: center;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 900;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
        }

        .status-draft {
            color: #374151;
            border-color: #9CA3AF;
            background: #F9FAFB;
        }

        .status-posted {
            color: var(--blue);
            border-color: #93C5FD;
            background: #EFF6FF;
        }

        .status-paid,
        .status-approved {
            color: var(--success);
            border-color: #86EFAC;
            background: #F0FDF4;
        }

        .status-cancelled {
            color: var(--danger);
            border-color: #FCA5A5;
            background: #FEF2F2;
        }

        .invoice-body {
            padding: 22px 26px 26px;
        }

        .section {
            margin-bottom: 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .section-title {
            background: var(--lighter);
            color: var(--text);
            padding: 10px 13px;
            border-bottom: 1px solid var(--border);
            font-weight: 900;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid var(--border);
            padding: 8px;
            vertical-align: middle;
        }

        table th {
            background: var(--light);
            color: var(--text);
            font-weight: 900;
            white-space: nowrap;
            text-align: center;
        }

        .info-table td {
            width: 25%;
        }

        .label-cell {
            background: var(--light);
            color: var(--muted);
            font-weight: 900;
            white-space: nowrap;
        }

        .value-cell {
            color: var(--text);
            font-weight: 700;
            background: #fff;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: left;
        }

        .ltr {
            direction: ltr;
            text-align: center;
            font-weight: 900;
        }

        .fw-bold {
            font-weight: 900;
        }

        .amount-box {
            border: 2px solid #BFDBFE;
            background: #EFF6FF;
            color: var(--text);
            border-radius: 14px;
            padding: 18px;
            margin: 18px 0;
            text-align: center;
        }

        .amount-box span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .amount-box strong {
            display: block;
            direction: ltr;
            font-size: 27px;
            font-weight: 900;
            color: var(--blue);
        }

        .summary-wrapper {
            display: flex;
            justify-content: flex-start;
            margin-top: 0;
        }

        .summary-table {
            width: 55%;
            min-width: 420px;
        }

        .summary-table td {
            padding: 9px 10px;
        }

        .summary-final td {
            background: #F3F4F6;
            color: var(--text);
            font-weight: 900;
            font-size: 14px;
        }

        .amount-success {
            color: var(--success);
            font-weight: 900;
        }

        .amount-danger {
            color: var(--danger);
            font-weight: 900;
        }

        .amount-warning {
            color: var(--warning);
            font-weight: 900;
        }

        .note-box {
            padding: 12px;
            line-height: 1.8;
            white-space: pre-wrap;
            background: #fff;
            color: #374151;
            font-weight: 700;
        }

        .watermark {
            position: fixed;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            z-index: 5;
            pointer-events: none;
            min-width: 320px;
            text-align: center;
            padding: 16px 30px;
            font-size: 68px;
            font-weight: 900;
            letter-spacing: 3px;
            border: 6px solid rgba(185, 28, 28, 0.18);
            color: rgba(185, 28, 28, 0.14);
            border-radius: 20px;
        }

        .invoice-body,
        .invoice-top {
            position: relative;
            z-index: 1;
        }

        .signatures {
            margin-top: 42px;
        }

        .signatures td {
            border: 0;
            padding: 10px 0;
            width: 50%;
            font-weight: 900;
        }

        .signature-line {
            display: inline-block;
            margin-top: 22px;
            min-width: 260px;
            border-bottom: 1px dashed #111827;
            height: 24px;
        }

        .footer-note {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            text-align: center;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                background: #fff;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-actions {
                display: none;
            }

            .invoice-container {
                max-width: 100%;
                width: 100%;
                border: 0;
                border-radius: 0;
            }

            .invoice-top {
                border-radius: 0;
                padding: 0 0 14px;
                margin-bottom: 16px;
            }

            .invoice-body {
                padding: 0;
            }

            .section {
                page-break-inside: avoid;
            }

            tr {
                page-break-inside: avoid;
            }

            .footer-note {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

@php
    $available = ($customerRefundVoucher->salesReturn?->refundable_amount ?? 0)
        - ($customerRefundVoucher->salesReturn?->refunded_amount ?? 0);
@endphp

@if($customerRefundVoucher->status === 'cancelled')
    <div class="watermark">ملغى</div>
@endif

@if($customerRefundVoucher->status === 'draft')
    <div class="watermark">مسودة</div>
@endif

<div class="print-actions">
    <button onclick="window.print()">طباعة</button>
</div>

<div class="invoice-container">

    <div class="invoice-top">
        <div class="print-header">
            @include('partials.print-company-header', [
                'document' => $customerRefundVoucher,
                'documentTitle' => 'سند صرف عميل',
                'documentTitleEn' => 'Customer Refund Voucher',
                'documentNumber' => $customerRefundVoucher->voucher_no,
                'documentStatus' => $customerRefundVoucher->status,
            ])
        </div>
    </div>

    <div class="invoice-body">

        <div class="section">
            <div class="section-title">بيانات سند الصرف</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">رقم السند</td>
                    <td class="value-cell ltr">{{ $customerRefundVoucher->voucher_no }}</td>

                    <td class="label-cell">تاريخ السند</td>
                    <td class="value-cell ltr">{{ $customerRefundVoucher->refund_date?->format('Y-m-d') }}</td>
                </tr>

                <tr>
                    <td class="label-cell">طريقة الصرف</td>
                    <td class="value-cell">
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
                    </td>

                    <td class="label-cell">الفرع</td>
                    <td class="value-cell">
                        {{ $customerRefundVoucher->branch?->branch_name
                            ?? $customerRefundVoucher->branch?->branch_name_ar
                            ?? $customerRefundVoucher->branch?->name
                            ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">بيانات العميل</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">اسم العميل</td>
                    <td class="value-cell">
                        {{ $customerRefundVoucher->customer?->customer_name
                            ?? $customerRefundVoucher->customer?->name
                            ?? $customerRefundVoucher->customer?->fullname
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_name
                            ?? 'عميل نقدي' }}
                    </td>

                    <td class="label-cell">الجوال</td>
                    <td class="value-cell ltr">
                        {{ $customerRefundVoucher->customer?->mobile
                            ?? $customerRefundVoucher->customer?->phone
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_mobile
                            ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">الرقم الضريبي</td>
                    <td class="value-cell ltr">
                        {{ $customerRefundVoucher->customer?->tax_registration_number
                            ?? $customerRefundVoucher->customer?->tax_number
                            ?? $customerRefundVoucher->customer?->vat_number
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_tax_number
                            ?? '-' }}
                    </td>

                    <td class="label-cell">العنوان</td>
                    <td class="value-cell">
                        {{ $customerRefundVoucher->customer?->address
                            ?? $customerRefundVoucher->customer?->full_address
                            ?? $customerRefundVoucher->salesReturn?->salesInvoice?->customer_address
                            ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">بيانات مردود المبيعات</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">رقم المردود</td>
                    <td class="value-cell ltr">
                        {{ $customerRefundVoucher->salesReturn?->return_no ?? '-' }}
                    </td>

                    <td class="label-cell">تاريخ المردود</td>
                    <td class="value-cell ltr">
                        {{ $customerRefundVoucher->salesReturn?->return_date?->format('Y-m-d') ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">فاتورة البيع</td>
                    <td class="value-cell ltr">
                        {{ $customerRefundVoucher->salesReturn?->salesInvoice?->invoice_no ?? '-' }}
                    </td>

                    <td class="label-cell">إجمالي المردود</td>
                    <td class="value-cell ltr">
                        {{ number_format((float) ($customerRefundVoucher->salesReturn?->total_amount ?? 0), 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">المستحق للعميل</td>
                    <td class="value-cell ltr amount-success">
                        {{ number_format((float) ($customerRefundVoucher->salesReturn?->refundable_amount ?? 0), 2) }}
                    </td>

                    <td class="label-cell">تم صرفه</td>
                    <td class="value-cell ltr amount-warning">
                        {{ number_format((float) ($customerRefundVoucher->salesReturn?->refunded_amount ?? 0), 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">المتبقي للصرف</td>
                    <td class="value-cell ltr amount-danger" colspan="3">
                        {{ number_format(max((float) $available, 0), 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="amount-box">
            <span>مبلغ سند الصرف</span>
            <strong>{{ number_format((float) $customerRefundVoucher->amount, 2) }}</strong>
        </div>

        <div class="section">
            <div class="section-title">ملخص السند</div>

            <div class="summary-wrapper">
                <table class="summary-table">
                    <tr class="summary-final">
                        <td>مبلغ السند</td>
                        <td class="ltr">
                            {{ number_format((float) $customerRefundVoucher->amount, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label-cell">الحالة</td>
                        <td>
                            @if($customerRefundVoucher->status === 'draft')
                                مسودة
                            @elseif($customerRefundVoucher->status === 'posted')
                                مرحل
                            @elseif($customerRefundVoucher->status === 'cancelled')
                                ملغى
                            @else
                                {{ $customerRefundVoucher->status }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($customerRefundVoucher->notes)
            <div class="section">
                <div class="section-title">ملاحظات</div>
                <div class="note-box">
                    {{ $customerRefundVoucher->notes }}
                </div>
            </div>
        @endif

        @if($customerRefundVoucher->status === 'cancelled')
            <div class="section">
                <div class="section-title">سبب الإلغاء</div>
                <div class="note-box">
                    {{ $customerRefundVoucher->cancel_reason ?? 'لم يتم تحديد سبب.' }}
                </div>
            </div>
        @endif

        <table class="signatures">
            <tr>
                <td>
                    توقيع المستلم:
                    <br>
                    <span class="signature-line"></span>
                </td>

                <td>
                    المحاسب:
                    <br>
                    <span class="signature-line"></span>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            تم إصدار هذا السند من نظام وازن ERP — إدارة متوازنة لأعمالك
        </div>

    </div>

</div>

<script>
    window.onload = function () {
        // window.print();
    };
</script>

</body>
</html>