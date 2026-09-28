<x-app-layout>
<div class="container-fluid py-4" dir="rtl">
    <div class="page-header-card mb-4"><div><h3 class="page-title mb-1">سيارات العملاء</h3><p class="page-subtitle mb-0">تسجيل السيارات وربطها بالعملاء الحاليين.</p></div><a href="{{ route('workshop.index') }}" class="btn btn-outline-secondary">أوامر العمل</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @can('workshop.create')
    <div class="card shadow-sm wazin-card mb-4"><div class="card-header wazin-card-header"><h5 class="mb-0">تسجيل سيارة</h5></div><div class="card-body">
        <form method="POST" action="{{ route('workshop.vehicles.store') }}" class="row g-3">@csrf
            <div class="col-md-4"><label class="form-label">العميل</label><select name="customer_id" class="form-select" required><option value="">اختر العميل</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->customer_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">رقم اللوحة</label><input name="plate_number" value="{{ old('plate_number') }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">رقم الهيكل VIN</label><input name="vin" value="{{ old('vin') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">الشركة المصنعة</label><input name="make" value="{{ old('make') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">الطراز</label><input name="model" value="{{ old('model') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">سنة الصنع</label><input name="model_year" type="number" value="{{ old('model_year') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">اللون</label><input name="color" value="{{ old('color') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div>
            <div class="col-12"><button class="btn btn-primary">حفظ السيارة</button></div>
        </form>
    </div></div>
    @endcan
    <div class="card shadow-sm wazin-card"><div class="card-body table-responsive"><table class="table table-striped text-center"><thead><tr><th>اللوحة</th><th>الهيكل</th><th>السيارة</th><th>العميل</th></tr></thead><tbody>@forelse($vehicles as $vehicle)<tr><td>{{ $vehicle->plate_number }}</td><td>{{ $vehicle->vin ?: '—' }}</td><td>{{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->model_year }}</td><td>{{ $vehicle->customer?->customer_name }}</td></tr>@empty<tr><td colspan="4">لا توجد سيارات.</td></tr>@endforelse</tbody></table>{{ $vehicles->links() }}</div></div>
</div>
</x-app-layout>
