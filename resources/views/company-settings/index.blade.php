<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">إعدادات الشركة</h3>
            <p class="page-subtitle mb-0">
                تعديل بيانات الشركة الأساسية والشعار المستخدم في الفواتير والتقارير.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ route('company-settings.update') }}"
          enctype="multipart/form-data"
          class="card shadow-sm wazin-card">

        @csrf
        @method('PUT')

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات الشركة</h5>
                <small>هذه البيانات تظهر في الطباعة والتقارير حسب الحاجة</small>
            </div>
        </div>

        <div class="card-body">

            <div class="form-section-title">البيانات الأساسية</div>

            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <label class="form-label">اسم الشركة عربي <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name_ar"
                           class="form-control"
                           value="{{ old('name_ar', $company->name_ar) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم الشركة إنجليزي</label>
                    <input type="text"
                           name="name_en"
                           class="form-control"
                           value="{{ old('name_en', $company->name_en) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $company->email) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الجوال / الهاتف</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           value="{{ old('phone', $company->phone) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">المدينة</label>
                    <input type="text"
                           name="city"
                           class="form-control"
                           value="{{ old('city', $company->city) }}">
                </div>

                <div class="col-md-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address"
                              class="form-control"
                              rows="3">{{ old('address', $company->address) }}</textarea>
                </div>

            </div>


            <div class="form-section-title">البيانات الضريبية والتجارية</div>

            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <label class="form-label">الرقم الضريبي</label>
                    <input type="text"
                           name="tax_number"
                           class="form-control"
                           value="{{ old('tax_number', $company->tax_number) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">السجل التجاري</label>
                    <input type="text"
                           name="commercial_registration"
                           class="form-control"
                           value="{{ old('commercial_registration', $company->commercial_registration) }}">
                </div>

            </div>


            <div class="form-section-title">شعار الشركة</div>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">رفع شعار جديد</label>
                    <input type="file"
                           name="logo"
                           id="company_logo"
                           class="form-control"
                           accept="image/*">

                    <small class="text-muted d-block mt-2">
                        الصيغ المسموحة: JPG, PNG, WEBP — الحد الأقصى 2MB.
                    </small>

                    @if($company->logo)
                        <div class="form-check mt-3">
                            <input type="hidden" name="remove_logo" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="remove_logo"
                                   id="remove_logo"
                                   value="1">
                            <label class="form-check-label" for="remove_logo">
                                حذف الشعار الحالي
                            </label>
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">معاينة الشعار</label>

                    <div class="logo-preview-box">
                        <img src="{{ $company->logo ? asset('storage/' . $company->logo) : '' }}"
                             id="company_logo_preview"
                             class="{{ $company->logo ? '' : 'd-none' }}"
                             alt="Logo">

                        <span id="company_logo_empty"
                              class="text-muted {{ $company->logo ? 'd-none' : '' }}">
                            لا يوجد شعار
                        </span>
                    </div>
                </div>

            </div>

        </div>

        <div class="card-footer bg-white border-0 d-flex justify-content-end">
            @can('company_settings.edit')
                <button type="submit" class="btn btn-primary">
                    حفظ التعديلات
                </button>
            @endcan
        </div>

    </form>

</div>


<style>
    .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
    }

    .page-title {
        color: #fff;
        font-weight: 900;
    }

    .page-subtitle {
        color: #CFEFF3;
        font-weight: 600;
        line-height: 1.8;
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

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
    }

    .form-section-title {
        color: #071633;
        font-weight: 900;
        margin: 0 0 14px;
        padding: 10px 14px;
        background: #fff;
        border-right: 5px solid #2F6BFF;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .form-label {
        color: #071633;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .form-control {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 600;
    }

    textarea.form-control {
        min-height: 90px;
    }

    .form-control:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .logo-preview-box {
        min-height: 135px;
        background: #fff;
        border: 1px dashed #CBD5E1;
        border-radius: 18px;
        padding: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logo-preview-box img {
        max-height: 110px;
        max-width: 240px;
        object-fit: contain;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 22px;
    }
</style>


@push('scripts')
<script>
    $(function () {
        $('#company_logo').on('change', function () {
            let file = this.files && this.files[0] ? this.files[0] : null;

            if (! file) {
                return;
            }

            $('#remove_logo').prop('checked', false);

            let reader = new FileReader();

            reader.onload = function (e) {
                $('#company_logo_preview')
                    .attr('src', e.target.result)
                    .removeClass('d-none');

                $('#company_logo_empty').addClass('d-none');
            };

            reader.readAsDataURL(file);
        });

        $('#remove_logo').on('change', function () {
            if ($(this).is(':checked')) {
                $('#company_logo').val('');
                $('#company_logo_preview').attr('src', '').addClass('d-none');
                $('#company_logo_empty')
                    .removeClass('d-none')
                    .text('سيتم حذف الشعار عند الحفظ');
            }
        });
    });
</script>
@endpush

</x-app-layout>