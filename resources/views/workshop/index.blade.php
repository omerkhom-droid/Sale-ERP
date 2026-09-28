<x-app-layout>
<div class="container-fluid py-4" dir="rtl">
    <div class="page-header-card mb-4">
        <div><h3 class="page-title mb-1">أوامر عمل الورشة</h3><p class="page-subtitle mb-0">متابعة السيارات ومراحل الإصلاح والقطع والخدمات.</p></div>
        @can('workshop.create')<a href="{{ route('workshop.create') }}" class="btn btn-primary">+ أمر عمل جديد</a>@endcan
    </div>
    <div class="card shadow-sm wazin-card"><div class="card-body">
        <form method="GET" action="{{ route('workshop.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-lg-3"><label class="form-label">بحث</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="الأمر، اللوحة، العميل"></div>
            <div class="col-lg-2"><label class="form-label">المرحلة</label><select name="status" class="form-select"><option value="">كل المراحل</option>@foreach(\App\Models\WorkshopOrder::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-2"><label class="form-label">الفرع</label><select name="branch_id" class="form-select"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->branch_name }}</option>@endforeach</select></div>
            <div class="col-lg-2"><label class="form-label">من تاريخ</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-lg-2"><label class="form-label">إلى تاريخ</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-lg-3"><label class="form-label">المستندات</label><select name="document" class="form-select"><option value="">الكل</option><option value="with_quote" @selected(request('document') === 'with_quote')>مع عرض سعر</option><option value="without_quote" @selected(request('document') === 'without_quote')>دون عرض سعر</option><option value="with_invoice" @selected(request('document') === 'with_invoice')>مع فاتورة</option><option value="without_invoice" @selected(request('document') === 'without_invoice')>دون فاتورة</option></select></div>
            <div class="col-lg-3"><button class="btn btn-primary">تصفية</button> <a href="{{ route('workshop.index') }}" class="btn btn-outline-secondary">مسح</a></div>
        </form>
        <div class="mb-2 text-muted">النتائج: {{ number_format($orders->total()) }}</div>
        <div class="table-responsive"><table class="table table-striped align-middle text-center"><thead><tr><th>الرقم</th><th>السيارة</th><th>العميل</th><th>الفرع</th><th>المرحلة</th><th>عرض السعر</th><th>الفاتورة</th><th>التاريخ</th><th></th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr><td>{{ $order->order_number }}</td><td>{{ $order->vehicle?->make }} {{ $order->vehicle?->model }} / {{ $order->vehicle?->plate_number }}</td><td>{{ $order->vehicle?->customer?->customer_name }}</td><td>{{ $order->branch?->branch_name }}</td><td><span class="badge bg-info text-dark">{{ \App\Models\WorkshopOrder::STATUSES[$order->status] ?? $order->status }}</span></td><td>{{ $order->quotation?->quotation_no ?? '—' }}</td><td>{{ $order->sales_invoice_id || $order->quotation?->converted_sales_invoice_id ? 'موجودة' : '—' }}</td><td>{{ $order->created_at?->format('Y-m-d') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('workshop.show', $order) }}">تفاصيل</a></td></tr>
            @empty<tr><td colspan="9" class="text-muted py-4">لا توجد أوامر عمل مطابقة.</td></tr>@endforelse
        </tbody></table></div>{{ $orders->links() }}
    </div></div>
</div>
</x-app-layout>
