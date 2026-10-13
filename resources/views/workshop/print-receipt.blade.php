<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كرت استلام سيارة - {{ $copy === 'customer' ? 'نسخة العميل' : 'نسخة الورشة' }} - {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f4f9; color: #152238; font: 13px Tahoma, Arial, sans-serif; }
        .tools { max-width: 900px; margin: 0 auto 12px; text-align: left; }
        .tools button { border: 0; border-radius: 7px; background: #2864ed; color: #fff; padding: 9px 22px; cursor: pointer; font: inherit; }
        .page { max-width: 900px; min-height: 930px; margin: auto; padding: 30px; background: white; border: 1px solid #d7dee9; border-radius: 10px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 25px; padding-bottom: 19px; border-bottom: 2px solid #2864ed; }
        .brand { font-weight: bold; font-size: 19px; }
        .muted { color: #59667b; font-size: 11px; margin-top: 5px; line-height: 1.8; }
        .document { text-align: left; }
        .document h1 { font-size: 21px; margin: 0 0 8px; }
        .number { direction: ltr; unicode-bidi: isolate; font-weight: bold; }
        .section { margin-top: 23px; break-inside: avoid; }
        .section h2 { background: #edf3ff; color: #173666; padding: 9px 12px; margin: 0 0 10px; border-radius: 5px; font-size: 14px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 20px; }
        .field { padding: 10px 2px; border-bottom: 1px solid #e2e7ef; min-height: 39px; overflow-wrap: anywhere; }
        .label { color: #526175; margin-left: 8px; }
        .text { border: 1px solid #d7dee9; border-radius: 5px; min-height: 68px; padding: 12px; line-height: 1.9; white-space: pre-wrap; overflow-wrap: anywhere; }
        .empty-lines { min-height: 77px; background: repeating-linear-gradient(white, white 31px, #dfe5ee 32px); }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-top: 26px; }
        .signature { border: 1px solid #d7dee9; border-radius: 5px; padding: 12px; min-height: 95px; }
        .signature strong { display: block; }
        .signature span { display: block; margin-top: 18px; color: #657286; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #d7dee9; text-align: center; color: #657286; font-size: 10px; }
        @page { size: A4; margin: 12mm; }
        @media print { body { background: #fff; padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } .tools { display: none; } .page { border: 0; border-radius: 0; padding: 0; min-height: auto; max-width: none; } }
    </style>
</head>
<body>
    <div class="tools"><button type="button" onclick="window.print()">طباعة {{ $copy === 'customer' ? 'نسخة العميل' : 'نسخة الورشة' }}</button></div>
    <main class="page">
        <header class="head">
            <div>
                <div class="brand">{{ $order->branch?->company?->display_name ?? 'الورشة' }}</div>
                <div class="muted">{{ $order->branch?->branch_name }}<br>{{ $order->branch?->phone }} @if($order->branch?->company?->phone) · {{ $order->branch->company->phone }} @endif</div>
            </div>
            <div class="document">
                <h1>كرت استلام سيارة</h1>
                <div class="muted"><strong>{{ $copy === 'customer' ? 'نسخة العميل' : 'نسخة الورشة' }}</strong></div>
                <div>أمر العمل: <span class="number">{{ $order->order_number }}</span></div>
                <div class="muted">تاريخ ووقت الاستلام: {{ $order->created_at?->format('Y/m/d H:i') }}</div>
            </div>
        </header>

        <section class="section">
            <h2>بيانات العميل</h2>
            <div class="grid">
                <div class="field"><span class="label">اسم العميل:</span>{{ ($order->customer_name ?? $order->vehicle?->owner_name ?? $order->vehicle?->customer?->customer_name) ?? '—' }}</div>
                <div class="field"><span class="label">رقم الجوال:</span>{{ ($order->customer_mobile ?? $order->vehicle?->owner_mobile ?? $order->vehicle?->customer?->phone) ?? '—' }}</div>
            </div>
        </section>

        <section class="section">
            <h2>بيانات السيارة</h2>
            <div class="grid">
                <div class="field"><span class="label">الشركة والطراز:</span>{{ $order->vehicle?->make }} {{ $order->vehicle?->model }}</div>
                <div class="field"><span class="label">رقم اللوحة:</span>{{ $order->vehicle?->plate_number ?? '—' }}</div>
                <div class="field"><span class="label">رقم الهيكل:</span>{{ $order->vehicle?->vin ?? '—' }}</div>
                <div class="field"><span class="label">سنة الصنع واللون:</span>{{ $order->vehicle?->model_year ?? '—' }} / {{ $order->vehicle?->color ?? '—' }}</div>
                <div class="field"><span class="label">قراءة العداد:</span>{{ $order->odometer !== null ? number_format($order->odometer) : '—' }}</div>
                <div class="field"><span class="label">الفرع المستلم:</span>{{ $order->branch?->branch_name ?? '—' }}</div>
                <div class="field"><span class="label">الوقود عند الاستلام:</span>{{ $order->fuel_percent !== null ? $order->fuel_percent.'%' : 'لم يسجل' }}</div>
                <div class="field"><span class="label">عدد المفاتيح:</span>{{ $order->key_count ?? 'لم يسجل' }}</div>
            </div>
        </section>

        <section class="section">
            <h2>الشكوى عند الاستلام</h2>
            <div class="text">{{ $order->customer_complaint }}</div>
        </section>

        <section class="section">
            <h2>حالة السيارة وملاحظات الاستلام</h2>
            <div class="text">{{ $order->condition_notes ?: 'لم تسجل ملاحظات حالة السيارة.' }}</div>
            <p><strong>الموجودات المسلمة مع السيارة:</strong></p>
            <div class="text">{{ $order->belongings ?: 'لم تسجل موجودات.' }}</div>
            <p class="muted">تُراجع البيانات المدونة عند الاستلام قبل توقيع الطرفين.</p>
        </section>

        <div class="signatures">
            <div class="signature"><strong>توقيع العميل / مُسلّم السيارة</strong><span>الاسم: .................................... &nbsp; التوقيع: ....................................</span></div>
            <div class="signature"><strong>توقيع مستلم السيارة</strong><span>الاسم: {{ $order->receiver?->name ?? '....................................' }} &nbsp; التوقيع: ....................................</span></div>
        </div>
        @if($copy === 'customer')
            <div class="footer">احتفظ بهذه النسخة، واذكر رقم أمر العمل عند الاستفسار عن السيارة أو مراجعة الفرع. هذه البطاقة لإثبات الاستلام وليست عرض سعر أو فاتورة.</div>
        @else
            <div class="footer">نسخة الورشة لحفظ بيانات استلام السيارة وتوقيعات الطرفين ضمن ملف أمر العمل.</div>
        @endif
    </main>
</body>
</html>
