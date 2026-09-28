<x-app-layout>
<div class="container-fluid py-4" dir="rtl">
    <div class="page-header-card mb-4"><div><h3 class="page-title mb-1">فتح أمر عمل</h3><p class="page-subtitle mb-0">اختر السيارة والفرع، وسجل شكوى العميل عند الاستقبال.</p></div><a href="{{ route('workshop.vehicles') }}" class="btn btn-outline-primary">+ تسجيل سيارة</a></div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card shadow-sm wazin-card"><div class="card-body"><form method="POST" action="{{ route('workshop.store') }}" class="row g-3">@csrf
        <div class="col-md-6"><label class="form-label">الفرع</label><select name="branch_id" class="form-select" required><option value="">اختر الفرع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', auth()->user()->branch_id) == $branch->id)>{{ $branch->branch_name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">السيارة</label><select name="vehicle_id" class="form-select" required><option value="">اختر السيارة</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} — {{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->customer?->customer_name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">قراءة العداد</label><input type="number" min="0" name="odometer" value="{{ old('odometer') }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">شكوى العميل</label><textarea name="customer_complaint" class="form-control" rows="4" required>{{ old('customer_complaint') }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">فتح أمر العمل</button></div>
    </form></div></div>
</div>
</x-app-layout>
