<x-app-layout>
@include('sales-debit-notes._styles')
<div class="container-fluid py-4 wazin-note" dir="rtl">
    <div class="wn-hero"><div><h3>إنشاء إشعار مدين</h3><p>تسجيل زيادة مرتبطة بفاتورة بيع سابقة.</p></div><a class="btn wn-back" href="{{ route('sales-debit-notes.index') }}">رجوع</a></div>
    @include('sales-debit-notes._alerts')
    @if(!$invoice)
        <section class="wn-card"><div class="wn-heading"><div><h5>اختيار الفاتورة الأصلية</h5><small>اختر فاتورة مرحلة مرتبطة بعميل مسجل.</small></div></div><div class="wn-body">
        <form method="GET" class="wn-search mb-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="ابحث برقم الفاتورة أو اسم العميل" aria-label="بحث الفواتير"><button class="btn btn-primary">بحث</button></form>
        <div class="wn-table-wrap"><table class="wn-table"><thead><tr><th>الفاتورة</th><th>العميل</th><th>التاريخ</th><th></th></tr></thead><tbody>
        @forelse($invoices as $choice)<tr><td>{{ $choice->invoice_no }}</td><td>{{ $choice->customer_name }}</td><td>{{ $choice->invoice_date->format('Y-m-d') }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('sales-debit-notes.create', ['invoice_id'=>$choice->id]) }}">اختيار</a></td></tr>
        @empty<tr><td colspan="4">لا توجد فواتير مطابقة.</td></tr>@endforelse
        </tbody></table></div>
        @if($invoices->hasPages())
            <nav class="wn-pager" aria-label="صفحات الفواتير">
                @if($invoices->previousPageUrl())<a href="{{ $invoices->previousPageUrl() }}">السابق</a>@endif
                <span class="wn-page">{{ $invoices->currentPage() }} / {{ $invoices->lastPage() }}</span>
                @if($invoices->nextPageUrl())<a href="{{ $invoices->nextPageUrl() }}">التالي</a>@endif
            </nav>
        @endif
        </div></section>
    @else
        <section class="wn-card">
            <div class="wn-heading"><h5>الفاتورة الأصلية</h5><a class="btn btn-sm btn-outline-secondary" href="{{ route('sales-debit-notes.create') }}">تغيير الفاتورة</a></div>
            <div class="wn-body wn-grid">
                <div class="wn-field"><span>رقم الفاتورة</span><strong><bdi>{{ $invoice->invoice_no }}</bdi></strong></div>
                <div class="wn-field"><span>العميل</span><strong>{{ $invoice->customer_name }}</strong></div>
                <div class="wn-field"><span>تاريخ الفاتورة</span><strong><bdi>{{ $invoice->invoice_date->format('Y-m-d') }}</bdi></strong></div>
            </div>
        </section>
        <form method="POST" action="{{ route('sales-debit-notes.store') }}">
            @csrf
            <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
            <section class="wn-card"><div class="wn-heading"><div><h5>بيانات الإشعار</h5><small>حدد التاريخ وسبب الزيادة</small></div></div><div class="wn-body wn-form-grid"><div><label class="form-label" for="note-date">تاريخ الإشعار</label><input id="note-date" class="form-control" type="date" name="note_date" value="{{ old('note_date', now()->format('Y-m-d')) }}" min="{{ $invoice->invoice_date->format('Y-m-d') }}" required></div>
            <div><label class="form-label" for="reason">سبب الزيادة</label><textarea id="reason" class="form-control mb-3" name="reason" rows="3" maxlength="2000" placeholder="وضح سبب الزيادة" required>{{ old('reason') }}</textarea></div></div></section>
            <section class="wn-card"><div class="wn-heading"><div><h5>أصناف الإشعار</h5><small>الكميات الإضافية وفروق الأسعار</small></div></div><div class="wn-body"><p class="wn-help">لزيادة الكمية: أدخل الكمية الإضافية وسعر وحدتها. لتصحيح السعر: أدخل الكمية التي يشملها التصحيح وفرق السعر للوحدة فقط. اترك كمية البند صفرًا لاستبعاده.</p></div>
            <div class="wn-table-wrap"><table class="wn-table wn-entry-table"><thead><tr><th>الصنف / الوحدة</th><th>الكمية الأصلية</th><th>نوع الزيادة</th><th>الكمية</th><th>سعر الوحدة / فرق السعر</th><th>الضريبة</th></tr></thead><tbody>
            @foreach($invoice->items as $index => $item)
                <tr><td>{{ $item->product_name }} / {{ $item->unit_name }}<input type="hidden" name="items[{{ $index }}][sales_invoice_item_id]" value="{{ $item->id }}"></td><td>{{ $item->quantity }}</td>
                <td><select class="form-select" name="items[{{ $index }}][adjustment_type]"><option value="quantity" @selected(old("items.$index.adjustment_type") !== 'price')>زيادة كمية</option><option value="price" @selected(old("items.$index.adjustment_type") === 'price')>فرق سعر فقط</option></select></td>
                <td><input aria-label="الكمية" class="form-control" type="number" min="0" max="1000000" step="0.001" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity", 0) }}" required></td>
                <td><input aria-label="سعر الزيادة" class="form-control" type="number" min="0" max="100000000" step="0.01" name="items[{{ $index }}][unit_price]" value="{{ old("items.$index.unit_price", $item->unit_price) }}" required></td><td>{{ $item->vat_rate }}%</td></tr>
            @endforeach
            </tbody></table></div></section>
            <div class="wn-card"><div class="wn-body wn-footer"><span class="wn-muted">المسودة لا تؤثر على المديونية أو المخزون حتى ترحيلها.</span><div class="wn-actions">
            <button class="btn btn-outline-secondary" name="save_action" value="draft">حفظ مسودة</button>
            @can('sales_debit_notes.post')<button class="btn btn-primary" name="save_action" value="post">حفظ وترحيل</button>@endcan
            </div></div></div>
        </form>
    @endif
</div>
</x-app-layout>
