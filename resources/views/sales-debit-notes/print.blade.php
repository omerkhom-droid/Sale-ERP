<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إشعار مدين — {{ $note->note_no }}</title>
    @include('sales-debit-notes._styles')
    <style>
        body{margin:0;background:#EEF1F5;font-family:Tahoma,Arial,sans-serif}
        .print-sheet{max-width:1050px;margin:25px auto;padding:30px;background:#fff;box-sizing:border-box}
        .print-toolbar{display:flex;justify-content:flex-end;margin-bottom:18px}
        .print-toolbar button{cursor:pointer;font-family:inherit;border:0}
        .print-caption{text-align:center;color:#64748B;font-size:12px;border-top:1px solid #E5E7EB;padding-top:15px}
        @media screen and (max-width:767px){.print-sheet{margin:0;padding:15px}}
        @media print{body{background:white}.print-sheet{max-width:none;margin:0;padding:0}.print-toolbar{display:none}}
        @page{size:A4;margin:14mm}
    </style>
</head>
<body>
    <main class="print-sheet wazin-note">
        <div class="print-toolbar wn-no-print"><button type="button" class="btn btn-primary" onclick="window.print()">طباعة الإشعار</button></div>
        <header class="wn-hero"><div><h3>إشعار مدين</h3><p>نسخة داخلية</p></div><strong>وازن ERP</strong></header>
        @include('sales-debit-notes._details')
        <footer class="print-caption">وازن ERP — إدارة متوازنة لأعمالك</footer>
    </main>
</body>
</html>
