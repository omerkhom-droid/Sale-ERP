<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">إعدادات POS</h3>
            <p class="page-subtitle mb-0">
                إعدادات نقطة البيع حسب الفرع.
            </p>
        </div>

        <div>
            <a href="{{ route('pos.index') }}" class="btn btn-light fw-bold">
                رجوع إلى POS
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fw-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($branches->isEmpty())
        <div class="alert alert-warning fw-bold">
            لا توجد فروع متاحة لهذا المستخدم.
        </div>
    @else

        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <h5 class="mb-0 fw-bold">اختيار الفرع</h5>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('pos.settings.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">الفرع</label>
                            <select name="branch_id" class="form-select" onchange="this.form.submit()">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                            @selected((int) $selectedBranch?->id === (int) $branch->id)>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('pos.settings.save') }}">
            @csrf

            <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">

            <div class="card shadow-sm wazin-card">
                <div class="card-header wazin-card-header">
                    <h5 class="mb-0 fw-bold">
                        إعدادات فرع: {{ $selectedBranch->branch_name }}
                    </h5>
                </div>

                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger fw-bold">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">المستودع الافتراضي</label>
                            <select name="default_warehouse_id" class="form-select">
                                <option value="">بدون مستودع افتراضي</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}"
                                            @selected((string) old('default_warehouse_id', $setting->default_warehouse_id) === (string) $warehouse->id)>
                                        {{ $warehouse->warehouse_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted fw-bold">
                                يستخدم تلقائيًا عند فتح وردية POS.
                            </small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">العميل النقدي الافتراضي</label>
                            <select name="default_customer_id" class="form-select">
                                <option value="">عميل نقدي بدون تحديد</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                            @selected((string) old('default_customer_id', $setting->default_customer_id) === (string) $customer->id)>
                                        {{ $customer->customer_name ?? $customer->name ?? ('عميل #' . $customer->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نسبة الضريبة %</label>
                            <input type="number"
                                   name="tax_rate"
                                   class="form-control"
                                   step="0.01"
                                   min="0"
                                   max="100"
                                   value="{{ old('tax_rate', $setting->tax_rate ?? 15) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">عدد نسخ الإيصال</label>
                            <input type="number"
                                   name="receipt_copies"
                                   class="form-control"
                                   min="1"
                                   max="5"
                                   value="{{ old('receipt_copies', $setting->receipt_copies ?? 1) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">عنوان الإيصال</label>
                            <input type="text"
                                   name="receipt_title"
                                   class="form-control"
                                   value="{{ old('receipt_title', $setting->receipt_title) }}"
                                   placeholder="مثال: Wazin Restaurant POS">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">خيارات العرض والطباعة</label>

                            <input type="hidden" name="auto_print_receipt" value="0">
                            <input type="hidden" name="show_product_images" value="0">

                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="auto_print_receipt"
                                       id="auto_print_receipt"
                                       value="1"
                                       @checked(old('auto_print_receipt', $setting->auto_print_receipt ?? true))>

                                <label class="form-check-label fw-bold" for="auto_print_receipt">
                                    طباعة تلقائية بعد الدفع
                                </label>
                            </div>

                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="show_product_images"
                                       id="show_product_images"
                                       value="1"
                                       @checked(old('show_product_images', $setting->show_product_images ?? true))>

                                <label class="form-check-label fw-bold" for="show_product_images">
                                    إظهار صور الأصناف في POS
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">نص أسفل الإيصال</label>
                            <textarea name="receipt_footer"
                                      class="form-control"
                                      rows="3"
                                      placeholder="مثال: شكرًا لزيارتكم">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                        </div>

                    </div>

                </div>

                <div class="card-footer bg-white d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-5">
                        حفظ الإعدادات
                    </button>

                    <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary px-4">
                        إلغاء
                    </a>
                </div>

            </div>
        </form>

    @endif

</div>

<style>
    .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
    }

    .page-title {
        color: #fff;
        font-weight: 900;
    }

    .page-subtitle {
        color: #CFEFF3;
        font-weight: 700;
    }

    .wazin-card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
    }

    .wazin-card-header {
        background: #fff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 20px;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .form-label {
        font-weight: 900;
        color: #071633;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        min-height: 42px;
        font-weight: 800;
    }

    .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF;
        border-color: #2F6BFF;
    }
</style>

</x-app-layout>