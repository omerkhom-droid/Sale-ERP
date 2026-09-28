<div class="row mb-3">
    <div class="col-md-6"><p>الرقم: <strong>{{ $note->note_no }}</strong></p><p>التاريخ: {{ $note->note_date->format('Y-m-d') }}</p><p>الفاتورة الأصلية: {{ $note->salesInvoice?->invoice_no }}</p></div>
    <div class="col-md-6"><p>العميل: {{ $note->salesInvoice?->customer_name }}</p><p>الرقم الضريبي للعميل: {{ $note->salesInvoice?->customer_tax_number ?: '—' }}</p><p>الحالة: {{ ['draft'=>'مسودة','posted'=>'مرحّل','cancelled'=>'ملغى'][$note->status] ?? $note->status }}</p></div>
</div>
<p>سبب الإشعار: {{ $note->reason }}</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>الصنف</th><th>نوع الزيادة</th><th>الكمية</th><th>سعر الزيادة</th><th>الصافي</th><th>الضريبة</th><th>الإجمالي</th></tr></thead><tbody>
@foreach($note->items as $item)<tr><td>{{ $item->product_name }} / {{ $item->unit_name }}</td><td>{{ $item->adjustment_type === 'price' ? 'فرق سعر' : 'زيادة كمية' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,2) }}</td><td>{{ number_format($item->net_amount,2) }}</td><td>{{ number_format($item->vat_amount,2) }}</td><td>{{ number_format($item->line_total,2) }}</td></tr>@endforeach
</tbody></table></div>
<p>الصافي: {{ number_format($note->subtotal,2) }} — الضريبة: {{ number_format($note->vat_amount,2) }}</p>
<h5>إجمالي الإشعار: {{ number_format($note->total_amount,2) }}</h5>
@if($note->cancel_reason)<p>سبب الإلغاء: {{ $note->cancel_reason }}</p>@endif
