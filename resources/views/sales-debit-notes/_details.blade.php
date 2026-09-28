@include('sales-debit-notes._styles')
@php
    $label = match($note->status) {'draft'=>'مسودة','posted'=>'مرحّل','cancelled'=>'ملغى',default=>$note->status};
    $badge = match($note->status) {'posted'=>'wn-posted','cancelled'=>'wn-cancelled',default=>'wn-draft'};
@endphp
<div class="wazin-note">
    <section class="wn-card wn-keep">
        <div class="wn-heading"><h5>بيانات الإشعار</h5><span class="wn-badge {{ $badge }}">{{ $label }}</span></div>
        <div class="wn-body wn-grid">
            <div class="wn-field"><span>رقم الإشعار</span><strong><bdi>{{ $note->note_no }}</bdi></strong></div>
            <div class="wn-field"><span>تاريخ الإشعار</span><strong><bdi>{{ $note->note_date?->format('Y-m-d') ?? '—' }}</bdi></strong></div>
            <div class="wn-field"><span>الفاتورة الأصلية</span><strong><bdi>{{ $note->salesInvoice?->invoice_no ?? '—' }}</bdi></strong></div>
            <div class="wn-field"><span>العميل</span><strong>{{ $note->salesInvoice?->customer_name ?: '—' }}</strong></div>
            <div class="wn-field"><span>الرقم الضريبي للعميل</span><strong><bdi>{{ $note->salesInvoice?->customer_tax_number ?: '—' }}</bdi></strong></div>
            <div class="wn-field"><span>سبب الإشعار</span><p class="wn-text">{{ $note->reason ?: '—' }}</p></div>
        </div>
    </section>
    <section class="wn-card">
        <div class="wn-heading"><div><h5>أصناف الإشعار</h5><small>تفاصيل الكميات والأسعار والضريبة</small></div></div>
        <div class="wn-table-wrap"><table class="wn-table">
            <thead><tr><th>الصنف / الوحدة</th><th>نوع الزيادة</th><th>الكمية</th><th>سعر الزيادة</th><th>الصافي</th><th>الضريبة</th><th>الإجمالي</th></tr></thead>
            <tbody>@forelse($note->items as $item)
                <tr><td class="wn-product">{{ $item->product_name }}<small>{{ $item->unit_name }}</small></td>
                    <td>{{ $item->adjustment_type === 'price' ? 'فرق سعر' : 'زيادة كمية' }}</td>
                    <td class="wn-number"><bdi>{{ number_format((float)$item->quantity,3) }}</bdi></td>
                    <td class="wn-number"><bdi>{{ number_format((float)$item->unit_price,2) }}</bdi></td>
                    <td class="wn-number"><bdi>{{ number_format((float)$item->net_amount,2) }}</bdi></td>
                    <td class="wn-number"><bdi>{{ number_format((float)$item->vat_amount,2) }}</bdi></td>
                    <td class="wn-number"><strong><bdi>{{ number_format((float)$item->line_total,2) }}</bdi></strong></td>
                </tr>
            @empty<tr><td colspan="7"><div class="wn-empty">لا توجد أصناف في هذا الإشعار.</div></td></tr>@endforelse</tbody>
        </table></div>
    </section>
    <div class="wn-card wn-total">
        <div class="wn-total-row"><span>الصافي قبل الضريبة</span><strong><bdi>{{ number_format((float)$note->subtotal,2) }}</bdi></strong></div>
        <div class="wn-total-row"><span>ضريبة القيمة المضافة</span><strong><bdi>{{ number_format((float)$note->vat_amount,2) }}</bdi></strong></div>
        <div class="wn-total-row"><span>إجمالي الإشعار</span><bdi>{{ number_format((float)$note->total_amount,2) }}</bdi></div>
    </div>
    @if($note->cancel_reason)
        <section class="wn-card wn-cancel-box wn-keep"><div class="wn-heading"><h5>سبب الإلغاء</h5></div><div class="wn-body"><p class="wn-text">{{ $note->cancel_reason }}</p></div></section>
    @endif
</div>
