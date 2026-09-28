<!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>إشعار مدين {{ $note->note_no }}</title>
<style>body{font-family:Tahoma,Arial,sans-serif;margin:30px;color:#172338}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{border:1px solid #ccc;padding:9px;text-align:right}h3{border-bottom:2px solid;padding-bottom:14px}@media print{button{display:none}}@page{size:A4;margin:15mm}</style>
</head><body><button onclick="window.print()">طباعة</button><h3>إشعار مدين — نسخة داخلية</h3>
@include('sales-debit-notes._details')
</body></html>
