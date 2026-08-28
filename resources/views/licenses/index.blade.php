<x-app-layout>
    <div class="container py-4" dir="rtl">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">إعدادات الترخيص</h3>
                <p class="text-muted mb-0">إدارة اشتراك النسخة الحالية</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>يرجى مراجعة الأخطاء التالية:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <form method="POST" action="{{ route('license.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">اسم العميل</label>
                            <input type="text"
                                   name="client_name"
                                   class="form-control"
                                   value="{{ old('client_name', $license?->client_name) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">مفتاح الترخيص</label>
                            <input type="text"
                                   name="license_key"
                                   class="form-control"
                                   value="{{ old('license_key', $license?->license_key) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاريخ البداية</label>
                            <input type="date"
                                   name="starts_at"
                                   class="form-control"
                                   value="{{ old('starts_at', optional($license?->starts_at)->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاريخ الانتهاء</label>
                            <input type="date"
                                   name="expires_at"
                                   class="form-control"
                                   value="{{ old('expires_at', optional($license?->expires_at)->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select" required>
                                @foreach(['trial' => 'تجريبي', 'active' => 'نشط', 'expired' => 'منتهي', 'suspended' => 'موقوف'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $license?->status ?? 'trial') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">أقصى عدد مستخدمين</label>
                            <input type="number"
                                   name="max_users"
                                   min="1"
                                   class="form-control"
                                   value="{{ old('max_users', $license?->max_users) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">أقصى عدد فروع</label>
                            <input type="number"
                                   name="max_branches"
                                   min="1"
                                   class="form-control"
                                   value="{{ old('max_branches', $license?->max_branches) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes"
                                      rows="4"
                                      class="form-control">{{ old('notes', $license?->notes) }}</textarea>
                        </div>

                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            حفظ الترخيص
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</x-app-layout>