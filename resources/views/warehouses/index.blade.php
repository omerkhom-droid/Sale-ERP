<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">المستودعات</h3>
            <p class="page-subtitle mb-0">
                إدارة المستودعات وربط كل مستودع بالفرع التابع له مع تحديد الكود والموقع والحالة.
            </p>
        </div>

        @can('warehouses.create')
            <button type="button" id="add_button" class="btn btn-primary">
                + إضافة مستودع
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة المستودعات</h5>
                <small>عرض وتحديث بيانات المستودعات المسجلة في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="warehouseTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الفرع</th>
                            <th>كود المستودع</th>
                            <th>اسم المستودع</th>
                            <th>الموقع</th>
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
@canany(['warehouses.create', 'warehouses.edit'])
<div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <form id="warehouse_form" class="modal-content">
            @csrf

            <input type="hidden" name="warehouse_id" id="warehouse_id">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="WarehouseModalLabel">
                        إضافة مستودع
                    </h5>
                    <small>أدخل بيانات المستودع وربطه بالفرع المناسب</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="form_errors"></div>

                <div class="form-section-title">بيانات المستودع</div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">الفرع <span class="text-danger">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-select" required>
                            <option value="">اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كود المستودع <span class="text-danger">*</span></label>
                        <input type="text" name="warehouse_code" id="warehouse_code" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم المستودع <span class="text-danger">*</span></label>
                        <input type="text" name="warehouse_name" id="warehouse_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="is_active" id="is_active" class="form-select" required>
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">الموقع</label>
                        <textarea name="location" id="location" class="form-control" rows="3"></textarea>
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

    #warehouseTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #warehouseTable tbody td {
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

    /* DataTables Wazin Style */
    #warehouseTable_wrapper {
        direction: rtl;
    }

    #warehouseTable_wrapper .dataTables_length,
    #warehouseTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #warehouseTable_wrapper .dataTables_length label,
    #warehouseTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #warehouseTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #warehouseTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #warehouseTable_wrapper .dataTables_filter input {
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

    #warehouseTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #warehouseTable_wrapper .dataTables_length select {
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

    #warehouseTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #warehouseTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #warehouseTable_wrapper .dataTables_paginate .paginate_button {
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

    #warehouseTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #warehouseTable_wrapper .dataTables_paginate .paginate_button.current,
    #warehouseTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #warehouseTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #warehouseTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #warehouseTable_wrapper::after {
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

        #warehouseTable_wrapper .dataTables_filter,
        #warehouseTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #warehouseTable_wrapper .dataTables_length label,
        #warehouseTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #warehouseTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #warehouseTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    const table = $('#warehouseTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('warehouses.fetch') }}",
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
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'warehouse_code', name: 'warehouse_code'},
            {data: 'warehouse_name', name: 'warehouse_name'},
            {data: 'location', name: 'location'},
            {data: 'status', name: 'status', orderable: false, searchable: false},
            {data: 'edit', name: 'edit', orderable: false, searchable: false},
            {data: 'delete', name: 'delete', orderable: false, searchable: false},
        ],
        order: [[2, 'asc']]
    });


    $('#add_button').on('click', function () {
        resetWarehouseForm();

        $('#WarehouseModalLabel').text('إضافة مستودع');
        $('#action').text('حفظ').prop('disabled', false);

        $('#warehouseModal').modal('show');
    });


    $('#warehouse_form').on('submit', function (e) {
        e.preventDefault();

        let warehouseId = $('#warehouse_id').val();

        let url = warehouseId
            ? "{{ url('/warehouses') }}/" + warehouseId
            : "{{ route('warehouses.store') }}";

        let method = warehouseId ? 'PUT' : 'POST';

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
                $('#warehouseModal').modal('hide');
                resetWarehouseForm();

                table.ajax.reload(null, false);

                showPageAlert(response.message ?? 'تم حفظ بيانات المستودع بنجاح.', 'success');

                $('#action').prop('disabled', false).text('حفظ');
            },
            error: function (xhr) {
                $('#action').prop('disabled', false).text(warehouseId ? 'تحديث' : 'حفظ');

                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors);
                    return;
                }

                if (xhr.status === 403) {
                    showFormError('لا توجد لديك صلاحية لتنفيذ هذه العملية.');
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
            url: "{{ url('/warehouses') }}/" + id + "/edit",
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (data) {
                $('#warehouse_id').val(data.id);
                $('#branch_id').val(data.branch_id);
                $('#warehouse_code').val(data.warehouse_code);
                $('#warehouse_name').val(data.warehouse_name);
                $('#location').val(data.location);
                $('#is_active').val(data.is_active ? 1 : 0);

                $('#WarehouseModalLabel').text('تعديل مستودع');
                $('#warehouseModal').modal('show');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert('لا توجد لديك صلاحية لتعديل المستودعات.', 'danger');
                    return;
                }

                showPageAlert('تعذر جلب بيانات المستودع.', 'danger');
            }
        });
    });


    $(document).on('click', '.deleteBtn', function () {
        if (!confirm('هل أنت متأكد من حذف المستودع؟')) {
            return;
        }

        let id = $(this).data('id');

        $.ajax({
            url: "{{ url('/warehouses') }}/" + id,
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                table.ajax.reload(null, false);
                showPageAlert(response.message ?? 'تم حذف المستودع بنجاح.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert('لا توجد لديك صلاحية لحذف المستودعات.', 'danger');
                    return;
                }

                showPageAlert(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الحذف.', 'danger');
            }
        });
    });


    function resetWarehouseForm() {
        $('#warehouse_form')[0].reset();
        $('#warehouse_id').val('');
        $('#branch_id').val('');
        $('#is_active').val('1');
        $('#form_errors').html('');
    }


    function showValidationErrors(errors) {
        let html = '<div class="alert alert-danger"><strong>يرجى مراجعة البيانات التالية:</strong><ul class="mb-0 mt-2">';

        $.each(errors, function (key, value) {
            html += `<li>${value[0]}</li>`;
        });

        html += '</ul></div>';

        $('#form_errors').html(html);
    }


    function showFormError(message) {
        $('#form_errors').html(`
            <div class="alert alert-danger">
                ${message}
            </div>
        `);
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

});
</script>
@endpush

</x-app-layout>