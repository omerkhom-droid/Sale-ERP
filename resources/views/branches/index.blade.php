<x-app-layout>

@php
    $actorType = auth()->user()->user_type ?? 'user';

    $canChooseCompany = in_array($actorType, ['master', 'system_admin'], true);

    $currentCompany = $companies->first();
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الفروع</h3>
            <p class="page-subtitle mb-0">
                إدارة بيانات الفروع وربط كل فرع بالشركة التابعة له.
            </p>
        </div>

        @can('branches.create')
            @if(in_array($actorType, ['master', 'system_admin', 'company_owner', 'company_admin'], true))
                <button type="button" id="add_button" class="btn btn-primary">
                    + إضافة فرع
                </button>
            @endif
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة الفروع</h5>
                <small>عرض وتحديث بيانات الفروع المسجلة في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="branchTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الشركة</th>
                            <th>اسم الفرع</th>
                            <th>المدينة</th>
                            <th>الرقم الضريبي</th>
                            <th>النسبة</th>
                            <th>الحالة</th>
                            <th>تعديل</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>


{{-- Modal --}}
@canany(['branches.create', 'branches.edit'])
<div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="branch_form" class="modal-content">
            @csrf

            <input type="hidden" name="branch_id" id="branch_id">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="BranchModalLabel">إضافة فرع</h5>
                    <small>أدخل بيانات الفرع بدقة لاستخدامها في الفواتير والتقارير</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="form_errors"></div>

                <div class="form-section-title">البيانات الأساسية</div>

                <div class="row g-3 mb-4">

                    @if($canChooseCompany)
                        <div class="col-md-4">
                            <label class="form-label">الشركة <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-select" required>
                                <option value="">اختر الشركة</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">
                                        {{ $company->name_ar ?: $company->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label">الشركة</label>
                            <input type="text"
                                   id="company_name_readonly"
                                   class="form-control"
                                   value="{{ $currentCompany?->name_ar ?: $currentCompany?->name_en }}"
                                   readonly>

                            <input type="hidden"
                                   name="company_id"
                                   id="company_id_hidden"
                                   value="{{ $currentCompany?->id }}">
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label">اسم الفرع <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" id="branch_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">النسبة <span class="text-danger">*</span></label>
                        <input type="number" name="preceatage" id="preceatage" class="form-control"
                               min="0" max="100" step="0.01" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي <span class="text-danger">*</span></label>
                        <input type="text" name="tax_registration_number"
                               id="tax_registration_number"
                               class="form-control" maxlength="15" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">نوع الترخيص <span class="text-danger">*</span></label>
                        <input type="text" name="license_type" id="license_type"
                               value="CRN" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم الترخيص <span class="text-danger">*</span></label>
                        <input type="text" name="license_number" id="license_number"
                               class="form-control" maxlength="10" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="phone" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="is_active" id="is_active" class="form-select">
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>

                </div>


                <div class="form-section-title">العنوان الوطني</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">كود الدولة <span class="text-danger">*</span></label>
                        <input type="text" name="country_code" id="country_code" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">المنطقة <span class="text-danger">*</span></label>
                        <input type="text" name="state" id="state" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">المدينة <span class="text-danger">*</span></label>
                        <input type="text" name="city" id="city" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الحي <span class="text-danger">*</span></label>
                        <input type="text" name="neighborhood" id="neighborhood" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الشارع <span class="text-danger">*</span></label>
                        <input type="text" name="street_name" id="street_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الشارع الإضافي <span class="text-danger">*</span></label>
                        <input type="text" name="additional_street_name"
                               id="additional_street_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم المبنى <span class="text-danger">*</span></label>
                        <input type="text" name="building_number" id="building_number"
                               class="form-control" maxlength="5" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرقم الإضافي <span class="text-danger">*</span></label>
                        <input type="text" name="secondary_number" id="secondary_number"
                               class="form-control" maxlength="4" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرمز البريدي <span class="text-danger">*</span></label>
                        <input type="text" name="postal_zone" id="postal_zone"
                               class="form-control" maxlength="5" required>
                    </div>

                </div>


                <div class="form-section-title">ملاحظات</div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">عنوان الفرع / تفاصيل إضافية</label>
                        <textarea name="details" id="details" class="form-control" rows="3"></textarea>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    إغلاق
                </button>

                <button type="submit" id="action" class="btn btn-primary">
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

    #branchTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #branchTable tbody td {
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

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
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

    #branchTable_wrapper {
        direction: rtl;
    }

    #branchTable_wrapper .dataTables_length,
    #branchTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #branchTable_wrapper .dataTables_length label,
    #branchTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #branchTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #branchTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #branchTable_wrapper .dataTables_filter input {
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

    #branchTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #branchTable_wrapper .dataTables_length select {
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

    #branchTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #branchTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #branchTable_wrapper .dataTables_paginate .paginate_button {
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

    #branchTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #branchTable_wrapper .dataTables_paginate .paginate_button.current,
    #branchTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #branchTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #branchTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #branchTable_wrapper::after {
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

        #branchTable_wrapper .dataTables_filter,
        #branchTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #branchTable_wrapper .dataTables_length label,
        #branchTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #branchTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #branchTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    const canChooseCompany = @json($canChooseCompany);
    const defaultCompanyId = @json($currentCompany?->id);

    const table = $('#branchTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('branches.fetch') }}",
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
            },
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'company_name', name: 'company.name_ar', orderable: false},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'city', name: 'city'},
            {data: 'tax_registration_number', name: 'tax_registration_number'},
            {data: 'preceatage', name: 'preceatage'},
            {data: 'status', name: 'status', orderable: false, searchable: false},
            {data: 'edit', name: 'edit', orderable: false, searchable: false},
            {data: 'delete', name: 'delete', orderable: false, searchable: false},
        ],
        order: [[2, 'asc']]
    });


    $('#add_button').on('click', function () {
        resetBranchForm();

        $('#BranchModalLabel').text('إضافة فرع');
        $('#action').text('حفظ').prop('disabled', false);

        if (canChooseCompany) {
            $('#company_id').val('');
        } else {
            $('#company_id_hidden').val(defaultCompanyId);
        }

        $('#branchModal').modal('show');
    });


    $('#branch_form').on('submit', function (e) {
        e.preventDefault();

        let branchId = $('#branch_id').val();

        let url = branchId
            ? "{{ url('/branches') }}/" + branchId
            : "{{ route('branches.store') }}";

        let method = branchId ? 'PUT' : 'POST';

        $('#action').prop('disabled', true).text('جاري الحفظ...');
        $('#form_errors').html('');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                $('#branchModal').modal('hide');
                resetBranchForm();

                table.ajax.reload(null, false);

                showPageAlert(response.message ?? 'تم حفظ بيانات الفرع بنجاح.', 'success');

                $('#action').prop('disabled', false).text('حفظ');
            },
            error: function (xhr) {
                $('#action').prop('disabled', false).text(branchId ? 'تحديث' : 'حفظ');

                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors);
                    return;
                }

                if (xhr.status === 403) {
                    showFormError(xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لتنفيذ هذه العملية.');
                    return;
                }

                showFormError(xhr.responseJSON?.message ?? 'حدث خطأ غير متوقع أثناء الحفظ.');
            }
        });
    });


    $(document).on('click', '.editBtn', function () {
        let id = $(this).data('id');

        $('#form_errors').html('');
        $('#action').text('تحديث').prop('disabled', false);

        $.ajax({
            url: "{{ url('/branches') }}/" + id + "/edit",
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (data) {
                $('#branch_id').val(data.id);

                if (canChooseCompany) {
                    $('#company_id').val(data.company_id);
                } else {
                    $('#company_id_hidden').val(defaultCompanyId);
                }

                $('#branch_name').val(data.branch_name);
                $('#preceatage').val(data.preceatage);
                $('#tax_registration_number').val(data.tax_registration_number);
                $('#license_type').val(data.license_type ?? 'CRN');
                $('#license_number').val(data.license_number);
                $('#country_code').val(data.country_code);
                $('#state').val(data.state);
                $('#city').val(data.city);
                $('#neighborhood').val(data.neighborhood);
                $('#street_name').val(data.street_name);
                $('#additional_street_name').val(data.additional_street_name);
                $('#building_number').val(data.building_number);
                $('#secondary_number').val(data.secondary_number);
                $('#postal_zone').val(data.postal_zone);
                $('#phone').val(data.phone);
                $('#details').val(data.details);
                $('#is_active').val(data.is_active ? '1' : '0');

                $('#BranchModalLabel').text('تعديل فرع');
                $('#branchModal').modal('show');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert(xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لتعديل الفروع.', 'error');
                    return;
                }

                showPageAlert('تعذر جلب بيانات الفرع.', 'error');
            }
        });
    });


    $(document).on('click', '.deleteBtn', function () {
        let id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'تأكيد الحذف',
            text: 'هل أنت متأكد من حذف الفرع؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545',
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            }
        }).then(function (result) {
            if (! result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ url('/branches') }}/" + id,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    showPageAlert(response.message ?? 'تم حذف الفرع بنجاح.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        showPageAlert(xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لحذف الفروع.', 'error');
                        return;
                    }

                    showPageAlert(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الحذف.', 'error');
                }
            });
        });
    });


    function resetBranchForm() {
        $('#branch_form')[0].reset();
        $('#branch_id').val('');
        $('#license_type').val('CRN');
        $('#is_active').val('1');
        $('#form_errors').html('');

        if (canChooseCompany) {
            $('#company_id').val('');
        } else {
            $('#company_id_hidden').val(defaultCompanyId);
        }
    }


    function showValidationErrors(errors) {
        let html = '<div class="text-start" dir="rtl"><strong>يرجى مراجعة البيانات التالية:</strong><ul class="mb-0 mt-2">';

        $.each(errors, function (key, value) {
            html += `<li>${value[0]}</li>`;
        });

        html += '</ul></div>';

        showPageAlert(html, 'error');
    }


    function showFormError(message) {
        showPageAlert(message, 'error');
    }


    function showPageAlert(message, type = 'success') {
        let icon = type === 'error' ? 'error' : type;

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

});
</script>
@endpush

</x-app-layout>