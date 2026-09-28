<x-app-layout>
<div class="container py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h3>الإشعارات المدينة</h3><p class="text-muted mb-0">زيادات مرتبطة بفواتير البيع</p></div>
        @can('sales_debit_notes.create')<a class="btn btn-primary" href="{{ route('sales-debit-notes.create') }}">إشعار مدين جديد</a>@endcan
    </div>
    @include('sales-debit-notes._alerts')
    <form method="GET" class="d-flex gap-2 mb-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="رقم الإشعار أو الفاتورة أو اسم العميل" aria-label="بحث"><button class="btn btn-outline-primary">بحث</button></form>
    <div class="card"><div class="table-responsive"><table class="table table-bordered align-middle mb-0">
        <thead><tr><th>الإشعار</th><th>التاريخ</th><th>الفاتورة الأصلية</th><th>العميل</th><th>الإجمالي</th><th>الحالة</th><th>الإجراء</th></tr></thead>
        <tbody>@forelse($notes as $note)
            <tr><td>{{ $note->note_no }}</td><td>{{ $note->note_date->format('Y-m-d') }}</td><td>{{ $note->salesInvoice?->invoice_no }}</td><td>{{ $note->salesInvoice?->customer_name }}</td><td>{{ number_format($note->total_amount, 2) }}</td><td>{{ ['draft'=>'مسودة','posted'=>'مرحّل','cancelled'=>'ملغى'][$note->status] ?? $note->status }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('sales-debit-notes.show', $note) }}">عرض</a></td></tr>
        @empty<tr><td colspan="7" class="text-center">لا توجد إشعارات.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $notes->links() }}</div>
</div>
</x-app-layout>
