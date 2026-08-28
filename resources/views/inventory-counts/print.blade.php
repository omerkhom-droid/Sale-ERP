<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة الجرد {{ $inventoryCount->count_no }}</title>

    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            color: #111827;
            margin: 24px;
            font-size: 13px;
        }

        .header {
            border-bottom: 3px solid #071633;
            padding-bottom: 14px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .title {
            font-size: 22px;
            font-weight: 900;
            color: #071633;
            margin: 0 0 8px;
        }

        .subtitle {
            font-size: 13px;
            color: #475569;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            color: #fff;
            font-weight: 900;
        }

        .badge-draft {
            background: #F59E0B;
            color: #111827;
        }

        .badge-posted {
            background: #16A34A;
        }

        .badge-cancelled {
            background: #DC2626;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .info-box {
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            padding: 10px;
        }

        .info-box span {
            display: block;
            color: #64748B;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .info-box strong {
            font-weight: 900;
            color: #0F172A;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            background: #071633;
            color: #fff;
            font-weight: 900;
            padding: 8px;
            border: 1px solid #071633;
            text-align: center;
        }

        td {
            border: 1px solid #CBD5E1;
            padding: 7px;
            text-align: center;
            font-weight: 700;
        }

        .text-start {
            text-align: right;
        }

        tfoot td {
            background: #F1F5F9;
            font-weight: 900;
        }

        .notes {
            margin-top: 18px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            padding: 12px;
            min-height: 50px;
        }

        .signatures {
            margin-top: 48px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            text-align: center;
        }

        .signature-box {
            border-top: 1px solid #111827;
            padding-top: 8px;
            font-weight: 900;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 12px;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 16px; text-align:left;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold;">
            طباعة
        </button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">تقرير جرد مخزني</h1>
            <div class="subtitle">رقم الجرد: {{ $inventoryCount->count_no }}</div>
        </div>

        <div>
            @if($inventoryCount->status === 'draft')
                <span class="badge badge-draft">مسودة</span>
            @elseif($inventoryCount->status === 'posted')
                <span class="badge badge-posted">مرحل</span>
            @elseif($inventoryCount->status === 'cancelled')
                <span class="badge badge-cancelled">ملغي</span>
            @else
                <span class="badge">{{ $inventoryCount->status }}</span>
            @endif
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <span>المستودع</span>
            <strong>{{ $inventoryCount->warehouse?->warehouse_name ?? '-' }}</strong>
        </div>

        <div class="info-box">
            <span>تاريخ الجرد</span>
            <strong>{{ optional($inventoryCount->count_date)->format('Y-m-d') }}</strong>
        </div>

        <div class="info-box">
            <span>أنشئ بواسطة</span>
            <strong>{{ $inventoryCount->creator?->name ?? '-' }}</strong>
        </div>

        <div class="info-box">
            <span>تاريخ الترحيل</span>
            <strong>{{ optional($inventoryCount->posted_at)->format('Y-m-d H:i') ?? '-' }}</strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40">#</th>
                <th>الكود</th>
                <th>الصنف</th>
                <th>الوحدة</th>
                <th>كمية النظام</th>
                <th>الكمية الفعلية</th>
                <th>الفرق</th>
                <th>تكلفة الوحدة</th>
                <th>قيمة الفرق</th>
                <th>ملاحظات</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalVarianceQty = 0;
                $totalVarianceCost = 0;
            @endphp

            @foreach($inventoryCount->items as $item)
                @php
                    $totalVarianceQty += (float) $item->variance_quantity;
                    $totalVarianceCost += (float) $item->total_variance_cost;
                @endphp

                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td class="text-start">{{ $item->product?->product_name_ar ?? '-' }}</td>
                    <td>{{ $item->productUnit?->unit?->unit_name ?? '-' }}</td>
                    <td>{{ number_format((float) $item->system_quantity, 3) }}</td>
                    <td>
                        {{ $item->actual_quantity !== null ? number_format((float) $item->actual_quantity, 3) : '-' }}
                    </td>
                    <td>{{ number_format((float) $item->variance_quantity, 3) }}</td>
                    <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td>{{ number_format((float) $item->total_variance_cost, 2) }}</td>
                    <td class="text-start">{{ $item->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="6">الإجمالي</td>
                <td>{{ number_format($totalVarianceQty, 3) }}</td>
                <td></td>
                <td>{{ number_format($totalVarianceCost, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if($inventoryCount->notes)
        <div class="notes">
            <strong>ملاحظات:</strong>
            <br>
            {{ $inventoryCount->notes }}
        </div>
    @endif

    <div class="signatures">
        <div class="signature-box">مسؤول الجرد</div>
        <div class="signature-box">أمين المستودع</div>
        <div class="signature-box">المدير المسؤول</div>
    </div>

</body>
</html>