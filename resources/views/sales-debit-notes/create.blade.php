<x-app-layout>
<div class="container py-4" dir="rtl">
    <div class="d-flex justify-content-between mb-4"><h3>إنشاء إشعار مدين</h3><a class="btn btn-secondary" href="{{ route('sales-debit-notes.index') }}">رجوع</a></div>
    @include('sales-debit-notes._alerts')
    @if(!$invoice)
        <p>اختر الفاتورة الأصلية للعميل المسجل.</p>
        <form method="GET" class="d-flex gap-2 mb-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="ابحث برقم الفاتورة أو اسم العميل" aria-label="بحث الفواتير"><button class="btn btn-primary">بحث</button></form>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>الفاتورة</th><th>العميل</th><th>التاريخ</th><th></th></tr></thead><tbody>
        @forelse($invoices as $choice)<tr><td>{{ $choice->invoice_no }}</td><td>{{ $choice->customer_name }}</td><td>{{ $choice->invoice_date->format('Y-m-d') }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('sales-debit-notes.create', ['invoice_id'=>$choice->id]) }}">اختيار</a></td></tr>
        @empty<tr><td colspan="4">لا توجد فواتير مطابقة.</td></tr>@endforelse
        </tbody></table></div>{{ $invoices->links() }}
    @else
        <div class="alert alert-info">الفاتورة: <strong>{{ $invoice->invoice_no }}</strong> — العميل: {{ $invoice->customer_name }}</div>
        <form method="POST" action="{{ route('sales-debit-notes.store') }}">
            @csrf
            <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="row mb-3"><div class="col-md-4"><label class="form-label" for="note-date">تاريخ الإشعار</label><input id="note-date" class="form-control" type="date" name="note_date" value="{{ old('note_date', now()->format('Y-m-d')) }}" min="{{ $invoice->invoice_date->format('Y-m-d') }}" required></div></div>
            <label class="form-label" for="reason">سبب الزيادة</label><textarea id="reason" class="form-control mb-3" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea>
            <p class="text-muted">لزيادة الكمية: أدخل الكمية الإضافية وسعر وحدتها. لتصحيح السعر: أدخل الكمية التي يشملها التصحيح وفرق السعر للوحدة فقط. اترك كمية البند صفرًا لاستبعاده.</p>
            <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>الصنف / الوحدة</th><th>الكمية الأصلية</th><th>نوع الزيادة</th><th>الكمية</th><th>سعر الوحدة / فرق السعر</th><th>الضريبة</th></tr></thead><tbody>
            @foreach($invoice->items as $index => $item)
                <tr><td>{{ $item->product_name }} / {{ $item->unit_name }}<input type="hidden" name="items[{{ $index }}][sales_invoice_item_id]" value="{{ $item->id }}"></td><td>{{ $item->quantity }}</td>
                <td><select class="form-select" name="items[{{ $index }}][adjustment_type]"><option value="quantity" @selected(old("items.$index.adjustment_type") !== 'price')>زيادة كمية</option><option value="price" @selected(old("items.$index.adjustment_type") === 'price')>فرق سعر فقط</option></select></td>
                <td><input aria-label="الكمية" class="form-control" type="number" min="0" max="1000000" step="0.001" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity", 0) }}" required></td>
                <td><input aria-label="سعر الزيادة" class="form-control" type="number" min="0" max="100000000" step="0.01" name="items[{{ $index }}][unit_price]" value="{{ old("items.$index.unit_price", $item->unit_price) }}" required></td><td>{{ $item->vat_rate }}%</td></tr>
            @endforeach
            </tbody></table></div>
            <button class="btn btn-secondary" name="save_action" value="draft">حفظ مسودة</button>
            @can('sales_debit_notes.post')<button class="btn btn-primary" name="save_action" value="post">حفظ وترحيل</button>@endcan
        </form>
    @endif
</div>
</x-app-layout>
