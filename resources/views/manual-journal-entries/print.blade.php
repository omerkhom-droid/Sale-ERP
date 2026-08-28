<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة قيد يومية - {{ $manualJournalEntry->manual_no }}</title>

    <style>
        :root {
            --text: #111827;
            --muted: #6B7280;
            --border: #D1D5DB;
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

        .text-start {
            text-align: right;
        }

        .ltr {
            direction: ltr;
            text-align: center;
            font-weight: 900;
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

        .line-type-badge {
            display: inline-block;
            background: #EFF6FF;
            color: var(--blue);
            border: 1px solid #BFDBFE;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 900;
            font-size: 11px;
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
    $totalDebit = round((float) $manualJournalEntry->lines->sum('debit'), 2);
    $totalCredit = round((float) $manualJournalEntry->lines->sum('credit'), 2);
    $difference = round($totalDebit - $totalCredit, 2);
@endphp

@if($manualJournalEntry->status === 'draft')
    <div class="watermark">مسودة</div>
@endif

@if($manualJournalEntry->status === 'cancelled')
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
                'document' => $manualJournalEntry,
                'branch' => $manualJournalEntry->branch ?? auth()->user()?->branch,
                'company' => $manualJournalEntry->branch?->company ?? auth()->user()?->company,
                'documentTitle' => 'قيد يومية',
                'documentTitleEn' => 'Manual Journal Entry',
                'documentNumber' => $manualJournalEntry->manual_no,
                'documentStatus' => $manualJournalEntry->status,
            ])
        </div>
    </div>

    <div class="invoice-body">

        <div class="section">
            <div class="section-title">بيانات القيد</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">رقم القيد</td>
                    <td class="value-cell ltr">{{ $manualJournalEntry->manual_no }}</td>

                    <td class="label-cell">تاريخ القيد</td>
                    <td class="value-cell ltr">{{ optional($manualJournalEntry->manual_date)->format('Y-m-d') }}</td>
                </tr>

                <tr>
                    <td class="label-cell">الفرع الافتراضي</td>
                    <td class="value-cell">
                        {{ $manualJournalEntry->branch?->branch_name
                            ?? $manualJournalEntry->branch?->branch_name_ar
                            ?? $manualJournalEntry->branch?->name
                            ?? '-' }}
                    </td>

                    <td class="label-cell">عدد السطور</td>
                    <td class="value-cell ltr">{{ $manualJournalEntry->lines->count() }}</td>
                </tr>

                <tr>
                    <td class="label-cell">الحالة</td>
                    <td class="value-cell">
                        @if($manualJournalEntry->status === 'draft')
                            مسودة
                        @elseif($manualJournalEntry->status === 'posted')
                            مرحل
                        @elseif($manualJournalEntry->status === 'cancelled')
                            ملغى
                        @else
                            {{ $manualJournalEntry->status }}
                        @endif
                    </td>

                    <td class="label-cell">أنشئ بواسطة</td>
                    <td class="value-cell">
                        {{ $manualJournalEntry->creator?->name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">ملاحظات</td>
                    <td class="value-cell" colspan="3">
                        {{ $manualJournalEntry->notes ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">سطور القيد</div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الفرع</th>
                        <th>مركز التكلفة</th>
                        <th>نوع السطر</th>
                        <th>الحساب / العميل / المورد</th>
                        <th>مدين</th>
                        <th>دائن</th>
                        <th>البيان</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($manualJournalEntry->lines as $line)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>

                            <td>
                                {{ $line->branch?->branch_name
                                    ?? $line->branch?->branch_name_ar
                                    ?? $line->branch?->name
                                    ?? '-' }}
                            </td>

                            <td>
                                @if($line->costCenter)
                                    {{ $line->costCenter->code ? $line->costCenter->code . ' - ' : '' }}
                                    {{ $line->costCenter->name }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-center">
                                @if($line->line_type === 'account')
                                    <span class="line-type-badge">حساب</span>
                                @elseif($line->line_type === 'customer')
                                    <span class="line-type-badge">عميل</span>
                                @elseif($line->line_type === 'supplier')
                                    <span class="line-type-badge">مورد</span>
                                @else
                                    <span class="line-type-badge">{{ $line->line_type }}</span>
                                @endif
                            </td>

                            <td class="text-start">
                                @if($line->line_type === 'account')
                                    {{ $line->account?->account_code ?? $line->account?->code ?? '' }}
                                    -
                                    {{ $line->account?->account_name_ar
                                        ?? $line->account?->account_name
                                        ?? $line->account?->name
                                        ?? '-' }}
                                @elseif($line->line_type === 'customer')
                                    {{ $line->customer?->customer_code ?? $line->customer?->code ?? '' }}
                                    -
                                    {{ $line->customer?->customer_name
                                        ?? $line->customer?->customer_name_ar
                                        ?? $line->customer?->name
                                        ?? '-' }}
                                @elseif($line->line_type === 'supplier')
                                    {{ $line->supplier?->supplier_code ?? $line->supplier?->code ?? '' }}
                                    -
                                    {{ $line->supplier?->supplier_name
                                        ?? $line->supplier?->supplier_name_ar
                                        ?? $line->supplier?->name
                                        ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="ltr amount-success">
                                {{ number_format((float) $line->debit, 2) }}
                            </td>

                            <td class="ltr amount-danger">
                                {{ number_format((float) $line->credit, 2) }}
                            </td>

                            <td class="text-start">
                                {{ $line->description ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                لا توجد سطور.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="5" class="label-cell">الإجمالي</td>

                        <td class="ltr amount-success">
                            {{ number_format($totalDebit, 2) }}
                        </td>

                        <td class="ltr amount-danger">
                            {{ number_format($totalCredit, 2) }}
                        </td>

                        <td class="ltr {{ $difference == 0 ? 'amount-success' : 'amount-danger' }}">
                            الفرق: {{ number_format($difference, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="section">
            <div class="section-title">معلومات النظام</div>

            <table class="info-table">
                <tr>
                    <td class="label-cell">أنشئ بواسطة</td>
                    <td class="value-cell">{{ $manualJournalEntry->creator?->name ?? '-' }}</td>

                    <td class="label-cell">تاريخ الإنشاء</td>
                    <td class="value-cell ltr">{{ optional($manualJournalEntry->created_at)->format('Y-m-d H:i') }}</td>
                </tr>

                <tr>
                    <td class="label-cell">رحل بواسطة</td>
                    <td class="value-cell">{{ $manualJournalEntry->poster?->name ?? '-' }}</td>

                    <td class="label-cell">تاريخ الترحيل</td>
                    <td class="value-cell ltr">{{ optional($manualJournalEntry->posted_at)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>

                <tr>
                    <td class="label-cell">ألغي بواسطة</td>
                    <td class="value-cell">{{ $manualJournalEntry->canceller?->name ?? '-' }}</td>

                    <td class="label-cell">تاريخ الإلغاء</td>
                    <td class="value-cell ltr">{{ optional($manualJournalEntry->cancelled_at)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>

                @if($manualJournalEntry->cancellation_reason)
                    <tr>
                        <td class="label-cell">سبب الإلغاء</td>
                        <td class="value-cell" colspan="3">
                            {{ $manualJournalEntry->cancellation_reason }}
                        </td>
                    </tr>
                @endif
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
                    المراجع:
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
            تم إصدار هذا القيد من نظام وازن ERP — إدارة متوازنة لأعمالك
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