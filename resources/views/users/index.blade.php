<x-app-layout>

@php
    $authUser = auth()->user();
    $authType = $authUser->user_type ?? 'user';

    $canChooseCompany = in_array($authType, ['master', 'system_admin'], true);
    $isBranchFixed = $authType === 'branch_admin';

    $currentCompany = $companies->first();
    $currentBranch = $branches->first();

    $userTypes = $userTypes ?? ['user'];

    $userTypeLabels = $userTypeLabels ?? [
        'master' => 'مدير النظام الرئيسي',
        'system_admin' => 'مشرف النظام',
        'company_owner' => 'مالك الشركة',
        'company_admin' => 'مدير الشركة',
        'branch_admin' => 'مدير فرع',
        'user' => 'موظف',
    ];
@endphp

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">المستخدمون</h3>
            <p class="page-subtitle mb-0">
                إدارة مستخدمي النظام وربطهم بالشركات والفروع والأدوار.
            </p>
        </div>

        @can('users.create')
            @if($authType !== 'user')
                <button type="button" id="add_button" class="btn btn-primary">
                    + مستخدم جديد
                </button>
            @endif
        @endcan
    </div>

    <div id="alert_action"></div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة المستخدمين</h5>
                <small>عرض وتحديث بيانات المستخدمين المسجلين في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle w-100" id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الشركة</th>
                            <th>الفرع</th>
                            <th>نوع المستخدم</th>
                            <th>الأدوار</th>
                            <th>الحالة</th>
                            <th width="180">الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>


@canany(['users.create', 'users.edit'])
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <form method="POST" id="userForm" action="{{ route('users.store') }}" class="modal-content">
            @csrf

            <input type="hidden" name="_method" id="userFormMethod" value="POST">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="userModalTitle">إضافة مستخدم</h5>
                    <small>أدخل بيانات المستخدم وحدد الشركة والفرع والدور المناسب</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="userFormErrorBox" class="alert alert-danger d-none mb-3"></div>

                <div class="form-section-title">بيانات المستخدم</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="user_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="user_email" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" id="user_password" class="form-control">
                        <small class="text-muted d-block mt-2">
                            مطلوبة عند إنشاء مستخدم جديد، واتركها فارغة عند التعديل.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirmation" id="user_password_confirmation" class="form-control">
                    </div>

                </div>


                <div class="form-section-title">نوع المستخدم والارتباط</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">نوع المستخدم <span class="text-danger">*</span></label>

                        <select name="user_type" id="user_type" class="form-select" required>
                            @foreach($userTypes as $type)
                                <option value="{{ $type }}">
                                    {{ $userTypeLabels[$type] ?? $type }}
                                </option>
                            @endforeach
                        </select>

                        <small class="text-muted d-block mt-2">
                            نوع المستخدم يحدد نطاق البيانات، والأدوار تحدد العمليات.
                        </small>
                    </div>


                    <div class="col-md-4" id="user_company_box">
                        <label class="form-label">الشركة</label>

                        @if($canChooseCompany)
                            <select name="company_id" id="user_company_id" class="form-select">
                                <option value="">اختر الشركة</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">
                                        {{ $company->name_ar ?: $company->name_en }}
                                    </option>
                                @endforeach
                            </select>

                            <small class="text-muted d-block mt-2">
                                مطلوبة لمستخدمي الشركات والفروع.
                            </small>
                        @else
                            <input type="hidden"
                                   name="company_id"
                                   id="user_company_id"
                                   value="{{ $currentCompany?->id }}">

                            <div class="form-control bg-light">
                                {{ $currentCompany?->name_ar ?: $currentCompany?->name_en ?: 'شركة المستخدم الحالي' }}
                            </div>

                            <small class="text-muted d-block mt-2">
                                يتم ربط المستخدم بنفس شركتك تلقائيًا.
                            </small>
                        @endif
                    </div>


                    <div class="col-md-4" id="user_branch_box">
                        <label class="form-label">الفرع</label>

                        @if($isBranchFixed)
                            <input type="hidden"
                                   name="branch_id"
                                   id="user_branch_id"
                                   value="{{ $authUser->branch_id }}">

                            <div class="form-control bg-light">
                                {{ $currentBranch?->branch_name ?: 'فرع المستخدم الحالي' }}
                            </div>

                            <small class="text-muted d-block mt-2">
                                مدير الفرع ينشئ مستخدمين داخل نفس فرعه فقط.
                            </small>
                        @else
                            <select name="branch_id" id="user_branch_id" class="form-select">
                                <option value="">بدون فرع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->branch_name ?? ('فرع رقم ' . $branch->id) }}
                                    </option>
                                @endforeach
                            </select>

                            <small class="text-muted d-block mt-2">
                                مطلوب فقط مع مدير الفرع والموظف.
                            </small>
                        @endif
                    </div>

                </div>


                <div class="form-section-title">الأدوار والحالة</div>

                <div class="row g-3">

                    <div class="col-md-8">
                        <label class="form-label">الأدوار</label>

                        <select name="roles[]" id="user_roles" class="form-select" multiple>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>

                        <small class="text-muted d-block mt-2">
                            يمكن اختيار أكثر من دور. الأدوار تحدد الصلاحيات التشغيلية داخل النظام.
                        </small>
                    </div>

                    <div class="col-md-4">
                        <div class="permission-switch-card">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="user_is_active" value="1" checked>
                            <label class="form-check-label" for="user_is_active">
                                <strong>نشط</strong>
                                <small>السماح للمستخدم بالدخول للنظام</small>
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

    #usersTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #usersTable tbody td {
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

    .form-select[multiple] {
        min-height: 115px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .permission-switch-card {
        min-height: 115px;
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

    #usersTable_wrapper {
        direction: rtl;
    }

    #usersTable_wrapper .dataTables_length,
    #usersTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #usersTable_wrapper .dataTables_length label,
    #usersTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #usersTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #usersTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #usersTable_wrapper .dataTables_filter input {
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

    #usersTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #usersTable_wrapper .dataTables_length select {
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

    #usersTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #usersTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #usersTable_wrapper .dataTables_paginate .paginate_button {
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

    #usersTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #usersTable_wrapper .dataTables_paginate .paginate_button.current,
    #usersTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #usersTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #usersTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #usersTable_wrapper::after {
        content: "";
        display: block;
        clear: both;
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        #usersTable_wrapper .dataTables_filter,
        #usersTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #usersTable_wrapper .dataTables_length label,
        #usersTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #usersTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #usersTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
    let usersTable;

    const CAN_CHOOSE_COMPANY = @json($canChooseCompany);
    const IS_BRANCH_FIXED = @json($isBranchFixed);
    const DEFAULT_COMPANY_ID = @json($currentCompany?->id);
    const DEFAULT_BRANCH_ID = @json($currentBranch?->id);
    const USER_TYPE_LABELS = @json($userTypeLabels);

    const COMPANY_LINKED_TYPES = [
        'company_owner',
        'company_admin',
        'branch_admin',
        'user',
    ];

    const BRANCH_SCOPED_TYPES = [
        'branch_admin',
        'user',
    ];

    $(function () {

        usersTable = $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('users.fetch') }}",
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
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'company_name', name: 'company_name', orderable: false, searchable: false },
                { data: 'branch_name', name: 'branch_name', orderable: false, searchable: false },
                { data: 'user_type_badge', name: 'user_type_badge', orderable: false, searchable: false },
                { data: 'roles_names', name: 'roles_names', orderable: false, searchable: false },
                { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[1, 'asc']]
        });


        $('#add_button').on('click', function () {
            openCreateUserModal();
        });


        $('#user_type').on('change', function () {
            syncScopeFields();
        });


        $('#user_company_id').on('change', function () {
            if (! CAN_CHOOSE_COMPANY || IS_BRANCH_FIXED) {
                syncScopeFields();
                return;
            }

            let companyId = $(this).val();

            loadCompanyBranches(companyId, null);

            syncScopeFields();
        });


        $('#userForm').on('submit', function (e) {
            e.preventDefault();

            let form = $('#userForm')[0];
            let formData = new FormData(form);

            $.ajax({
                url: $('#userForm').attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                beforeSend: function () {
                    $('#userForm button[type="submit"]')
                        .prop('disabled', true)
                        .text('جاري الحفظ...');

                    hideUserFormError();
                },
                success: function (response) {
                    $('#userForm button[type="submit"]')
                        .prop('disabled', false)
                        .text('حفظ');

                    let modal = bootstrap.Modal.getInstance($('#userModal')[0]);

                    if (modal) {
                        modal.hide();
                    }

                    $('#userForm')[0].reset();

                    usersTable.ajax.reload(null, false);

                    showPageAlert(response.message ?? 'تم حفظ المستخدم بنجاح.', 'success');
                },
                error: function (xhr) {
                    $('#userForm button[type="submit"]')
                        .prop('disabled', false)
                        .text($('#userFormMethod').val() === 'PUT' ? 'تحديث' : 'حفظ');

                    showUserFormError(getAjaxErrorMessage(xhr, 'حدث خطأ أثناء الحفظ.'));
                }
            });
        });

    });


    function openCreateUserModal() {
        hideUserFormError();

        $('#userModalTitle').text('إضافة مستخدم');

        $('#userForm').attr('action', "{{ route('users.store') }}");
        $('#userFormMethod').val('POST');

        $('#user_name').val('');
        $('#user_email').val('');
        $('#user_password').val('');
        $('#user_password_confirmation').val('');

        let userTypeSelect = $('#user_type');

        if (userTypeSelect.find('option[value="user"]').length) {
            userTypeSelect.val('user');
        } else {
            userTypeSelect.prop('selectedIndex', 0);
        }

        if (CAN_CHOOSE_COMPANY) {
            $('#user_company_id').val('');
            resetBranchOptions(null);
        } else {
            $('#user_company_id').val(DEFAULT_COMPANY_ID);
        }

        if (IS_BRANCH_FIXED) {
            $('#user_branch_id').val(DEFAULT_BRANCH_ID);
        } else {
            $('#user_branch_id').val('');
        }

        $('#user_roles option').prop('selected', false);
        $('#user_is_active').prop('checked', true);

        syncScopeFields();

        $('#userForm button[type="submit"]')
            .prop('disabled', false)
            .text('حفظ');

        let modal = new bootstrap.Modal($('#userModal')[0]);
        modal.show();
    }


    function openEditUserModal(editUrl, updateUrl) {
        hideUserFormError();

        $.ajax({
            url: editUrl,
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (user) {
                $('#userModalTitle').text('تعديل مستخدم');

                $('#userForm').attr('action', updateUrl);
                $('#userFormMethod').val('PUT');

                $('#user_name').val(user.name ?? '');
                $('#user_email').val(user.email ?? '');
                $('#user_password').val('');
                $('#user_password_confirmation').val('');

                ensureUserTypeOption(user.user_type);
                $('#user_type').val(user.user_type ?? 'user');

                if (CAN_CHOOSE_COMPANY) {
                    $('#user_company_id').val(user.company_id ?? '');

                    if (user.company_id) {
                        loadCompanyBranches(user.company_id, user.branch_id ?? null);
                    } else {
                        resetBranchOptions(null);
                    }
                } else {
                    $('#user_company_id').val(DEFAULT_COMPANY_ID);

                    if (IS_BRANCH_FIXED) {
                        $('#user_branch_id').val(DEFAULT_BRANCH_ID);
                    } else {
                        $('#user_branch_id').val(user.branch_id ?? '');
                    }
                }

                $('#user_roles option').prop('selected', false);

                $('#user_roles option').each(function () {
                    $(this).prop(
                        'selected',
                        Array.isArray(user.roles) && user.roles.includes($(this).val())
                    );
                });

                $('#user_is_active').prop('checked', !!user.is_active);

                syncScopeFields();

                $('#userForm button[type="submit"]')
                    .prop('disabled', false)
                    .text('تحديث');

                let modal = new bootstrap.Modal($('#userModal')[0]);
                modal.show();
            },
            error: function (xhr) {
                showPageAlert(getAjaxErrorMessage(xhr, 'تعذر جلب بيانات المستخدم.'), 'error');
            }
        });
    }


    function syncScopeFields() {
        let selectedType = $('#user_type').val();

        let needsCompany = COMPANY_LINKED_TYPES.includes(selectedType);
        let needsBranch = BRANCH_SCOPED_TYPES.includes(selectedType);

        if (needsCompany) {
            $('#user_company_box').removeClass('d-none');

            if (CAN_CHOOSE_COMPANY) {
                $('#user_company_id')
                    .prop('disabled', false)
                    .prop('required', true);
            }
        } else {
            $('#user_company_box').addClass('d-none');

            if (CAN_CHOOSE_COMPANY) {
                $('#user_company_id')
                    .val('')
                    .prop('required', false);
            }
        }

        if (needsBranch) {
            $('#user_branch_box').removeClass('d-none');

            if (! IS_BRANCH_FIXED) {
                $('#user_branch_id')
                    .prop('disabled', false)
                    .prop('required', true);
            }
        } else {
            $('#user_branch_box').addClass('d-none');

            if (! IS_BRANCH_FIXED) {
                $('#user_branch_id')
                    .val('')
                    .prop('required', false);
            }
        }
    }


    function loadCompanyBranches(companyId, selectedBranchId = null) {
        resetBranchOptions(selectedBranchId);

        if (! companyId) {
            return;
        }

        let url = "{{ route('users.company-branches', ['company' => '__COMPANY__']) }}";
        url = url.replace('__COMPANY__', companyId);

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (branches) {
                resetBranchOptions(null);

                $.each(branches, function (index, branch) {
                    $('#user_branch_id').append(
                        $('<option>', {
                            value: branch.id,
                            text: branch.branch_name
                        })
                    );
                });

                if (selectedBranchId) {
                    $('#user_branch_id').val(selectedBranchId);
                }
            },
            error: function () {
                resetBranchOptions(null);
                showPageAlert('تعذر جلب فروع الشركة.', 'error');
            }
        });
    }


    function resetBranchOptions(selectedBranchId = null) {
        if (IS_BRANCH_FIXED) {
            $('#user_branch_id').val(DEFAULT_BRANCH_ID);
            return;
        }

        $('#user_branch_id').html('<option value="">بدون فرع</option>');

        if (selectedBranchId) {
            $('#user_branch_id').val(selectedBranchId);
        }
    }


    function ensureUserTypeOption(type) {
        if (! type) {
            return;
        }

        if ($('#user_type option[value="' + type + '"]').length) {
            return;
        }

        $('#user_type').append(
            $('<option>', {
                value: type,
                text: USER_TYPE_LABELS[type] ?? type
            })
        );
    }


    function deleteUser(deleteUrl) {
        if (! confirm('هل أنت متأكد من حذف المستخدم؟')) {
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
                usersTable.ajax.reload(null, false);
                showPageAlert(response.message ?? 'تم حذف المستخدم بنجاح.', 'success');
            },
            error: function (xhr) {
                showPageAlert(getAjaxErrorMessage(xhr, 'حدث خطأ أثناء الحذف.'), 'error');
            }
        });
    }


    function hideUserFormError() {
        $('#userFormErrorBox')
            .addClass('d-none')
            .html('');
    }


    function showUserFormError(message) {
        showPageAlert(message, 'error');
    }


    function showPageAlert(message, type = 'success') {
        let icon = type === 'danger' ? 'error' : type;

        let toastTypes = ['success', 'info'];
        let isToast = toastTypes.includes(icon);

        Swal.fire({
            icon: icon,
            html: message,
            toast: isToast,
            position: isToast ? 'top-end' : 'center',
            showConfirmButton: ! isToast,
            confirmButtonText: 'حسنًا',
            timer: isToast ? 3500 : undefined,
            timerProgressBar: isToast,
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        });
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