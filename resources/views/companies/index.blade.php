<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الشركات</h3>
            <p class="page-subtitle mb-0">
                إدارة الشركات وربطها بالفروع والمستخدمين داخل النظام.
            </p>
        </div>

        @can('companies.create')
            <button type="button" id="add_button" class="btn btn-primary">
                + شركة جديدة
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif


    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة الشركات</h5>
                <small>عرض وتحديث بيانات الشركات المسجلة في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle w-100" id="companiesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الكود</th>
                            <th>اسم الشركة</th>
                            <th>بيانات التواصل</th>
                            <th>البيانات الضريبية</th>
                            <th>الفروع</th>
                            <th>المستخدمون</th>
                            <th>الحالة</th>
                            <th width="180">الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>


{{-- Modal --}}
@canany(['companies.create', 'companies.edit'])
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <form method="POST" id="companyForm" action="{{ route('companies.store') }}" enctype="multipart/form-data" class="modal-content" >
            @csrf

            <input type="hidden" name="_method" id="companyFormMethod" value="POST">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="companyModalTitle">إضافة شركة</h5>
                    <small>أدخل بيانات الشركة الأساسية والضريبية</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="companyFormErrorBox" class="alert alert-danger d-none mb-3"></div>

                <div class="form-section-title">البيانات الأساسية</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">كود الشركة</label>
                        <input type="text" name="code" id="company_code" class="form-control" placeholder="مثال: MAIN">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الشركة عربي <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="company_name_ar" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الشركة إنجليزي</label>
                        <input type="text" name="name_en" id="company_name_en" class="form-control">
                    </div>

                </div>


                <div class="form-section-title">بيانات التواصل</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="company_email" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم الجوال / الهاتف</label>
                        <input type="text" name="phone" id="company_phone" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">المدينة</label>
                        <input type="text" name="city" id="company_city" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" id="company_address" class="form-control" rows="3"></textarea>
                    </div>

                </div>
                
                <div class="form-section-title">شعار الشركة</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">الشعار</label>
                        <input type="file" name="logo" id="company_logo" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-2">
                            الصيغ المسموحة: JPG, PNG, WEBP — الحد الأقصى 2MB.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">معاينة الشعار</label>

                        <div class="logo-preview-box">
                            <img src="" id="company_logo_preview" alt="Logo" class="d-none">
                            <span id="company_logo_empty" class="text-muted">لا يوجد شعار</span>
                        </div>

                        <div class="form-check mt-2 d-none" id="remove_logo_box">
                            <input type="hidden" name="remove_logo" value="0">
                            <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                            <label class="form-check-label" for="remove_logo">
                                حذف الشعار الحالي
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-section-title">البيانات الضريبية والتجارية</div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text" name="tax_number" id="company_tax_number" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">السجل التجاري</label>
                        <input type="text" name="commercial_registration" id="company_commercial_registration" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <div class="permission-switch-card">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="company_is_active" value="1" checked>
                            <label class="form-check-label" for="company_is_active">
                                <strong>نشطة</strong>
                                <small>السماح باستخدام الشركة وربطها بالفروع والمستخدمين</small>
                            </label>
                        </div>
                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    إغلاق
                </button>

                <button type="submit" class="btn btn-primary">
                    حفظ
                </button>
            </div>

        </form>
    </div>
</div>
@endcanany


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
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
    }

    #companiesTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #companiesTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    .modal-header {
        background: #071633;
        color: #fff;
        border-bottom: 0;
        padding: 18px 22px;
    }

    .modal-header small {
        color: #CFEFF3;
        font-weight: 600;
    }

    .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(7, 22, 51, 0.22);
    }

    .modal-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .modal-footer {
        background: #fff;
        border-top: 1px solid #E5E7EB;
        padding: 16px 22px;
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

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 600;
    }

    textarea.form-control {
        min-height: 90px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .permission-switch-card {
        min-height: 96px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .permission-switch-card .form-check-input {
        width: 44px;
        height: 23px;
        margin: 0;
        cursor: pointer;
    }

    .permission-switch-card .form-check-input:checked {
        background-color: #2F6BFF;
        border-color: #2F6BFF;
    }

    .permission-switch-card label {
        cursor: pointer;
    }

    .permission-switch-card strong {
        display: block;
        color: #071633;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .permission-switch-card small {
        display: block;
        color: #64748B;
        font-weight: 700;
        line-height: 1.6;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    #companiesTable_wrapper {
        direction: rtl;
    }

    #companiesTable_wrapper .dataTables_length,
    #companiesTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #companiesTable_wrapper .dataTables_length label,
    #companiesTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #companiesTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #companiesTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #companiesTable_wrapper .dataTables_filter input {
        width: 280px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 8px 14px;
        margin: 0;
        outline: none;
        color: #111827;
        font-weight: 700;
        background: #fff;
        transition: all .18s ease-in-out;
    }

    #companiesTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #companiesTable_wrapper .dataTables_length select {
        width: 90px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 6px 12px;
        margin: 0 8px;
        outline: none;
        color: #111827;
        font-weight: 800;
        background: #fff;
    }

    #companiesTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #companiesTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #companiesTable_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #E5E7EB !important;
        background: #fff !important;
        color: #071633 !important;
        border-radius: 12px !important;
        padding: 8px 14px !important;
        margin: 0 2px !important;
        font-weight: 900;
        cursor: pointer;
        transition: all .18s ease-in-out;
    }

    #companiesTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #companiesTable_wrapper .dataTables_paginate .paginate_button.current,
    #companiesTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #companiesTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #companiesTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #companiesTable_wrapper::after {
        content: "";
        display: block;
        clear: both;
    }

    .logo-preview-box {
        min-height: 120px;
        background: #fff;
        border: 1px dashed #CBD5E1;
        border-radius: 18px;
        padding: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logo-preview-box img {
        max-height: 95px;
        max-width: 220px;
        object-fit: contain;
    }
    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        #companiesTable_wrapper .dataTables_filter,
        #companiesTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #companiesTable_wrapper .dataTables_length label,
        #companiesTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #companiesTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #companiesTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>

@push('scripts')
<script>
    let companiesTable;

    $(function () {

        companiesTable = $('#companiesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('companies.fetch') }}",
            language: {
                processing: 'جاري المعالجة...',
                search: 'بحث:',
                lengthMenu: 'عرض _MENU_ سجل',
                info: 'إظهار _START_ إلى _END_ من أصل _TOTAL_ سجل',
                infoEmpty: 'لا توجد سجلات',
                infoFiltered: '(تمت التصفية من أصل _MAX_ سجل)',
                loadingRecords: 'جاري التحميل...',
                zeroRecords: 'لا توجد بيانات مطابقة',
                emptyTable: 'لا توجد بيانات متاحة',
                paginate: {
                    first: 'الأول',
                    previous: 'السابق',
                    next: 'التالي',
                    last: 'الأخير'
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name_ar' },
                { data: 'contact', name: 'contact', orderable: false, searchable: false },
                { data: 'tax_info', name: 'tax_info', orderable: false, searchable: false },
                { data: 'branches_count_badge', name: 'branches_count', orderable: false, searchable: false },
                { data: 'users_count_badge', name: 'users_count', orderable: false, searchable: false },
                { data: 'status_badge', name: 'is_active', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[2, 'asc']]
        });

        $('#add_button').on('click', function () {
            openCreateCompanyModal();
        });

        $('#company_logo').on('change', function () {
            previewCompanyLogo(this);
        });

        $('#remove_logo').on('change', function () {
            if ($(this).is(':checked')) {
                $('#company_logo_preview').attr('src', '').addClass('d-none');
                $('#company_logo_empty').removeClass('d-none').text('سيتم حذف الشعار عند الحفظ');
                $('#company_logo').val('');
            }
        });

        $('#companyForm').on('submit', function (e) {
            e.preventDefault();

            let form = $('#companyForm')[0];
            let formData = new FormData(form);

            $.ajax({
                url: $('#companyForm').attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                beforeSend: function () {
                    $('#companyForm button[type="submit"]')
                        .prop('disabled', true)
                        .text('جاري الحفظ...');

                    hideCompanyFormError();
                },
                success: function (response) {
                    $('#companyForm button[type="submit"]')
                        .prop('disabled', false)
                        .text('حفظ');

                    let modalElement = $('#companyModal')[0];
                    let modal = bootstrap.Modal.getInstance(modalElement);

                    if (modal) {
                        modal.hide();
                    }

                    $('#companyForm')[0].reset();

                    resetLogoPreview();

                    companiesTable.ajax.reload(null, false);

                    showPageAlert(response.message ?? 'تم حفظ الشركة بنجاح.', 'success');
                },
                error: function (xhr) {
                    $('#companyForm button[type="submit"]')
                        .prop('disabled', false)
                        .text('حفظ');

                    showCompanyFormError(getAjaxErrorMessage(xhr, 'حدث خطأ أثناء الحفظ.'));
                }
            });
        });

    });


    function openCreateCompanyModal() {
        hideCompanyFormError();

        $('#companyModalTitle').text('إضافة شركة');

        $('#companyForm').attr('action', "{{ route('companies.store') }}");
        $('#companyFormMethod').val('POST');

        $('#company_code').val('');
        $('#company_name_ar').val('');
        $('#company_name_en').val('');
        $('#company_email').val('');
        $('#company_phone').val('');
        $('#company_tax_number').val('');
        $('#company_commercial_registration').val('');
        $('#company_city').val('');
        $('#company_address').val('');
        $('#company_is_active').prop('checked', true);

        resetLogoPreview();

        $('#companyForm button[type="submit"]')
            .prop('disabled', false)
            .text('حفظ');

        let modal = new bootstrap.Modal($('#companyModal')[0]);
        modal.show();
    }


    function openEditCompanyModal(editUrl, updateUrl) {
        hideCompanyFormError();

        $.ajax({
            url: editUrl,
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (company) {
                $('#companyModalTitle').text('تعديل شركة');

                $('#companyForm').attr('action', updateUrl);
                $('#companyFormMethod').val('PUT');

                $('#company_code').val(company.code ?? '');
                $('#company_name_ar').val(company.name_ar ?? '');
                $('#company_name_en').val(company.name_en ?? '');
                $('#company_email').val(company.email ?? '');
                $('#company_phone').val(company.phone ?? '');
                $('#company_tax_number').val(company.tax_number ?? '');
                $('#company_commercial_registration').val(company.commercial_registration ?? '');
                $('#company_city').val(company.city ?? '');
                $('#company_address').val(company.address ?? '');
                $('#company_is_active').prop('checked', !!company.is_active);

                $('#company_logo').val('');
                $('#remove_logo').prop('checked', false);

                if (company.logo_url) {
                    $('#company_logo_preview')
                        .attr('src', company.logo_url)
                        .removeClass('d-none');

                    $('#company_logo_empty').addClass('d-none');
                    $('#remove_logo_box').removeClass('d-none');
                } else {
                    resetLogoPreview();
                }

                $('#companyForm button[type="submit"]')
                    .prop('disabled', false)
                    .text('تحديث');

                let modal = new bootstrap.Modal($('#companyModal')[0]);
                modal.show();
            },
            error: function () {
                showPageAlert('تعذر جلب بيانات الشركة.', 'danger');
            }
        });
    }


    function previewCompanyLogo(input) {
        let file = input.files && input.files[0] ? input.files[0] : null;

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
    }


    function resetLogoPreview() {
        $('#company_logo').val('');
        $('#company_logo_preview').attr('src', '').addClass('d-none');
        $('#company_logo_empty').removeClass('d-none').text('لا يوجد شعار');
        $('#remove_logo').prop('checked', false);
        $('#remove_logo_box').addClass('d-none');
    }


    function deleteCompany(deleteUrl) {
        if (! confirm('هل أنت متأكد من حذف الشركة؟')) {
            return;
        }

        $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: "{{ csrf_token() }}"
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                companiesTable.ajax.reload(null, false);
                showPageAlert(response.message ?? 'تم حذف الشركة بنجاح.', 'success');
            },
            error: function (xhr) {
                showPageAlert(getAjaxErrorMessage(xhr, 'حدث خطأ أثناء الحذف.'), 'danger');
            }
        });
    }


    function hideCompanyFormError() {
        $('#companyFormErrorBox')
            .addClass('d-none')
            .html('');
    }


    function showCompanyFormError(message) {
        let errorBox = $('#companyFormErrorBox');

        if (! errorBox.length) {
            alert(message);
            return;
        }

        errorBox
            .html(message)
            .removeClass('d-none');

        $('html, body').animate({
            scrollTop: errorBox.offset().top - 120
        }, 300);
    }


    function showPageAlert(message, type = 'success') {
        $('#alert_action').html(`
            <div class="alert alert-${type} mb-4">
                ${message}
            </div>
        `);

        setTimeout(function () {
            $('#alert_action').html('');
        }, 3500);
    }


    function getAjaxErrorMessage(xhr, defaultMessage) {
        let message = defaultMessage;

        if (xhr.status === 403) {
            message = xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لتنفيذ هذه العملية.';
        } else if (xhr.status === 401) {
            message = 'انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.';
        } else if (xhr.status === 419) {
            message = 'انتهت صلاحية الجلسة، قم بتحديث الصفحة وحاول مرة أخرى.';
        } else if (xhr.status === 422) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            } else {
                message = xhr.responseJSON?.message ?? 'يرجى مراجعة البيانات المدخلة.';
            }
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }

        return message;
    }
</script>
@endpush

</x-app-layout>