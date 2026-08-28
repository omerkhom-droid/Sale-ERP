<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة سند صرف مورد - {{ $supplierPaymentVoucher->voucher_no }}</title>

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
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            padding: 9px 22px;
            font-weight: 800;
            cursor: pointer;
            margin: 0 4px;
        }

        .print-actions button:hover {
            background: var(--light);
        }

        .invoice-container {
            width: 100%;
            max-width: 1000px;
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

        .amount-boxes {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .amount-box {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            background: var(--light);
            text-align: center;
        }

        .amount-box span {
            display: block;
            color: var(--muted);
            font-size: 11.5px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .amount-box strong {
            display: block;
            direction: ltr;
            font-size: 20px;
            font-weight: 900;
        }

        .amount-main strong {
            color: var(--success);
        }

        .amount-allocated strong {
            color: var(--blue);
        }

        .amount-unallocated strong {
            color: var(--danger);
        }

        .totals-wrapper {
            display: flex;
            justify-content: flex-start;
            margin-top: 18px;
        }

        .totals-table {
            width: 42%;
            min-width: 360px;
            border: 1px solid var(--border);
        }

        .totals-table td {
            padding: 9px 10px;
        }

        .total-final td {
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

        .amount-primary {
            color: var(--blue);
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
            width: 33.333%;
            font-weight: 900;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            margin-top: 22px;
            min-width: 180px;
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

            .section,
            .amount-boxes {
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
    $formatDate = function ($value) {
        if (empty($value)) {
            return '-';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp


@php
    $printBranch = $supplierPaymentVoucher->branch
        ?? $supplierPaymentVoucher->allocations->first()?->purchaseInvoice?->branch
        ?? auth()->user()?->branch
        ?? null;

    $printCompany = $printBranch?->company
        ?? auth()->user()?->company
        ?? null;
@endphp



@if($supplierPaymentVoucher->status === 'draft')
    <div class="watermark">مسودة</div>
@endif

@if($supplierPaymentVoucher->status === 'cancelled')
    <div class="watermark">ملغى</div>
@endif

<div class="print-actions">
    <button onclick="window.print()">طباعة</button>
    <button onclick="window.close()">إغلاق</button>
</div>

<div class="invoice-container">

    <div class="invoice-top">
        <div class="print-header">
            @include('partials.print-company-header', [
                'document' => $supplierPaymentVoucher,
                'branch' => $printBranch,
                'company' => $printCompany,
                'documentTitle' => 'سند صرف مورد',
                'documentTitleEn' => 'Supplier Payment Voucher',
                'documentNumber' => $supplierPaymentVoucher->voucher_no,
                'documentStatus' => $supplierPaymentVoucher->status,
            ])
        </div>
    </div>

    <div class="invoice-body">

        <div class="section">
            <div class="section-title">بيانات السند</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">رقم السند</td>
                    <td class="value-cell ltr">{{ $supplierPaymentVoucher->voucher_no }}</td>

                    <td class="label-cell">تاريخ السند</td>
                    <td class="value-cell ltr">{{ $formatDate($supplierPaymentVoucher->voucher_date) }}</td>
                </tr>

                <tr>
                    <td class="label-cell">الفرع</td>
                    <td class="value-cell">
                        {{ $printBranch?->branch_name
                            ?? $printBranch?->branch_name_ar
                            ?? $printBranch?->name
                            ?? '-' }}
                    </td>

                    <td class="label-cell">حالة السند</td>
                    <td class="value-cell">
                        @if($supplierPaymentVoucher->status === 'draft')
                            مسودة
                        @elseif($supplierPaymentVoucher->status === 'posted')
                            مرحل
                        @elseif($supplierPaymentVoucher->status === 'cancelled')
                            ملغى
                        @else
                            {{ $supplierPaymentVoucher->status }}
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">مركز التكلفة</td>
                    <td class="value-cell" colspan="3">
                        @if($supplierPaymentVoucher->costCenter ?? false)
                            {{ $supplierPaymentVoucher->costCenter->code ? $supplierPaymentVoucher->costCenter->code . ' - ' : '' }}
                            {{ $supplierPaymentVoucher->costCenter->name }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">بيانات المورد وحساب الدفع</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">المورد</td>
                    <td class="value-cell">
                        {{ $supplierPaymentVoucher->supplier?->supplier_name
                            ?? $supplierPaymentVoucher->supplier?->name
                            ?? '-' }}
                    </td>

                    <td class="label-cell">الرقم الضريبي للمورد</td>
                    <td class="value-cell ltr">
                        {{ $supplierPaymentVoucher->supplier?->tax_registration_number
                            ?? $supplierPaymentVoucher->supplier?->tax_number
                            ?? $supplierPaymentVoucher->supplier?->vat_number
                            ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">جوال المورد</td>
                    <td class="value-cell ltr">
                        {{ $supplierPaymentVoucher->supplier?->mobile
                            ?? $supplierPaymentVoucher->supplier?->phone
                            ?? '-' }}
                    </td>

                    <td class="label-cell">حساب الدفع</td>
                    <td class="value-cell">
                        @if($supplierPaymentVoucher->paymentAccount)
                            {{ $supplierPaymentVoucher->paymentAccount->account_code }}
                            -
                            {{ $supplierPaymentVoucher->paymentAccount->account_name_ar }}
                        @else
                            -
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">البيان</td>
                    <td class="value-cell" colspan="3">
                        {{ $supplierPaymentVoucher->notes ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="amount-boxes">
            <div class="amount-box amount-main">
                <span>مبلغ السند رقمًا</span>
                <strong>{{ number_format((float) $supplierPaymentVoucher->amount, 2) }}</strong>
            </div>

            <div class="amount-box amount-allocated">
                <span>إجمالي التوزيع</span>
                <strong>{{ number_format((float) $supplierPaymentVoucher->allocated_amount, 2) }}</strong>
            </div>

            <div class="amount-box amount-unallocated">
                <span>غير موزع</span>
                <strong>{{ number_format((float) $supplierPaymentVoucher->unallocated_amount, 2) }}</strong>
            </div>
        </div>

        <div class="section">
            <div class="section-title">الفواتير المسددة من هذا السند</div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الفاتورة</th>
                        <th>تاريخ الفاتورة</th>
                        <th>إجمالي الفاتورة</th>
                        <th>المدفوع الحالي</th>
                        <th>المتبقي الحالي</th>
                        <th>المبلغ المسدد بهذا السند</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($supplierPaymentVoucher->allocations as $index => $allocation)
                        @php
                            $invoice = $allocation->purchaseInvoice;
                        @endphp

                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>

                            <td class="ltr">
                                {{ $invoice?->invoice_no ?? '-' }}
                            </td>

                            <td class="ltr">
                                {{ $formatDate($invoice?->invoice_date) }}
                            </td>

                            <td class="ltr">
                                {{ number_format((float) ($invoice?->total_amount ?? 0), 2) }}
                            </td>

                            <td class="ltr amount-success">
                                {{ number_format((float) ($invoice?->paid_amount ?? 0), 2) }}
                            </td>

                            <td class="ltr amount-danger">
                                {{ number_format((float) ($invoice?->remaining_amount ?? 0), 2) }}
                            </td>

                            <td class="ltr amount-primary">
                                {{ number_format((float) $allocation->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                لا توجد توزيعات على فواتير لهذا السند.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($supplierPaymentVoucher->notes)
            <div class="section" style="margin-top: 22px;">
                <div class="section-title">ملاحظات</div>
                <div class="note-box">
                    {{ $supplierPaymentVoucher->notes }}
                </div>
            </div>
        @endif

        @if($supplierPaymentVoucher->status === 'cancelled')
            <div class="section" style="margin-top: 22px;">
                <div class="section-title">سبب الإلغاء</div>
                <div class="note-box">
                    {{ $supplierPaymentVoucher->cancel_reason ?? '-' }}
                </div>
            </div>
        @endif

        <div class="totals-wrapper">
            <table class="totals-table">
                <tr class="total-final">
                    <td>مبلغ السند</td>
                    <td class="ltr">
                        {{ number_format((float) $supplierPaymentVoucher->amount, 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">إجمالي التوزيع</td>
                    <td class="ltr amount-primary">
                        {{ number_format((float) $supplierPaymentVoucher->allocated_amount, 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">غير موزع</td>
                    <td class="ltr amount-danger">
                        {{ number_format((float) $supplierPaymentVoucher->unallocated_amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="signatures">
            <tr>
                <td>
                    المحاسب:
                    <br>
                    <span class="signature-line"></span>
                </td>

                <td>
                    المستلم:
                    <br>
                    <span class="signature-line"></span>
                </td>

                <td>
                    الإدارة:
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