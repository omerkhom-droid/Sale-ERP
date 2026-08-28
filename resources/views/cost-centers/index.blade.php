<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">مراكز التكلفة</h3>
            <p class="page-subtitle mb-0">
                إدارة مراكز التكلفة وربطها بالحركات والقيود لتتبع المصروفات والإيرادات حسب المركز.
            </p>
        </div>

        @can('cost_centers.create')
            <button type="button" id="add_button" class="btn btn-primary" onclick="openCreateModal()">
                + إضافة مركز تكلفة
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة مراكز التكلفة</h5>
                <small>عرض وتحديث مراكز التكلفة الرئيسية والفرعية</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="costCentersTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>تابع لـ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>


{{-- Modal --}}
@canany(['cost_centers.create', 'cost_centers.edit'])
<div class="modal fade" id="costCenterModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <form id="costCenterForm" class="modal-content">
            @csrf

            <input type="hidden" id="cost_center_id">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="modalTitle">
                        إضافة مركز تكلفة
                    </h5>
                    <small>أدخل بيانات مركز التكلفة وحدد المركز الرئيسي إن وجد</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="formErrors" class="alert alert-danger d-none"></div>

                <div class="form-section-title">بيانات مركز التكلفة</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">الكود</label>
                        <input type="text" name="code" id="code" class="form-control" placeholder="اختياري">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">اسم مركز التكلفة <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>

                </div>


                <div class="form-section-title">التصنيف والحالة</div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">تابع لـ</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">رئيسي</option>

                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}">
                                    {{ $parent->code ? $parent->code . ' - ' : '' }}{{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="is_active" id="is_active" class="form-select">
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
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
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
    }

    #costCentersTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #costCentersTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #costCentersTable td:nth-child(2) {
        direction: ltr;
        font-weight: 900;
        color: #071633;
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
        background-color: #fff;
    }

    textarea.form-control {
        min-height: 90px;
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

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .btn-warning,
    .btn-info {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 10px;
        font-weight: 900;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    /* DataTables Wazin Style */
    #costCentersTable_wrapper {
        direction: rtl;
    }

    #costCentersTable_wrapper .dataTables_length,
    #costCentersTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #costCentersTable_wrapper .dataTables_length label,
    #costCentersTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #costCentersTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #costCentersTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #costCentersTable_wrapper .dataTables_filter input {
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

    #costCentersTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #costCentersTable_wrapper .dataTables_length select {
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

    #costCentersTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #costCentersTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #costCentersTable_wrapper .dataTables_paginate .paginate_button {
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

    #costCentersTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #costCentersTable_wrapper .dataTables_paginate .paginate_button.current,
    #costCentersTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #costCentersTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #costCentersTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #costCentersTable_wrapper::after {
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

        #costCentersTable_wrapper .dataTables_filter,
        #costCentersTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #costCentersTable_wrapper .dataTables_length label,
        #costCentersTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #costCentersTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #costCentersTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
    let costCentersTable;
    let modal;

    document.addEventListener('DOMContentLoaded', function () {
        modal = new bootstrap.Modal(document.getElementById('costCenterModal'));

        costCentersTable = $('#costCentersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('cost-centers.fetch') }}",
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
                {data: 'code', name: 'code', defaultContent: '-'},
                {data: 'name', name: 'name'},
                {data: 'parent_name', name: 'parent_name', orderable: false, searchable: false},
                {data: 'status', name: 'status', orderable: false, searchable: false},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[1, 'asc']]
        });


        $('#costCenterForm').on('submit', function (e) {
            e.preventDefault();

            let id = $('#cost_center_id').val();

            let url = id
                ? "{{ url('/cost-centers') }}/" + id
                : "{{ route('cost-centers.store') }}";

            let method = id ? 'PUT' : 'POST';

            $('#action').prop('disabled', true).text('جاري الحفظ...');
            $('#formErrors').addClass('d-none').html('');

            $.ajax({
                url: url,
                method: method,
                data: $(this).serialize(),
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    modal.hide();

                    resetCostCenterForm();

                    costCentersTable.ajax.reload(null, false);

                    showSuccess(response.message ?? 'تم حفظ مركز التكلفة بنجاح.');

                    $('#action').prop('disabled', false).text('حفظ');
                },
                error: function (xhr) {
                    $('#action').prop('disabled', false).text(id ? 'تحديث' : 'حفظ');

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showValidationErrors(xhr.responseJSON.errors);
                        return;
                    }

                    if (xhr.status === 403) {
                        showFormError('لا توجد لديك صلاحية لتنفيذ هذه العملية.');
                        return;
                    }

                    showFormError(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الحفظ.');
                }
            });
        });


        $(document).on('click', '.edit-btn', function () {
            $('#modalTitle').text('تعديل مركز تكلفة');

            $('#cost_center_id').val($(this).data('id'));
            $('#code').val($(this).data('code') ?? '');
            $('#name').val($(this).data('name') ?? '');
            $('#parent_id').val($(this).data('parent-id') ?? '');
            $('#is_active').val($(this).data('is-active') ? '1' : '0');
            $('#notes').val($(this).data('notes') ?? '');

            $('#formErrors').addClass('d-none').html('');
            $('#action').text('تحديث').prop('disabled', false);

            modal.show();
        });


        $(document).on('click', '.delete-btn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'حذف مركز التكلفة؟',
                text: 'لا يمكن حذف مركز التكلفة إذا كان مرتبطاً بحركات أو قيود.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#E63B4A',
                cancelButtonColor: '#64748B'
            }).then((result) => {
                if (! result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ url('/cost-centers') }}/" + id,
                    method: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        costCentersTable.ajax.reload(null, false);
                        showSuccess(response.message ?? 'تم حذف مركز التكلفة بنجاح.');
                    },
                    error: function (xhr) {
                        let message = xhr.responseJSON?.message ?? 'تعذر حذف مركز التكلفة.';

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'تنبيه',
                                text: message
                            });
                        } else {
                            showPageAlert(message, 'danger');
                        }
                    }
                });
            });
        });
    });


    window.openCreateModal = function () {
        resetCostCenterForm();

        $('#modalTitle').text('إضافة مركز تكلفة');
        $('#action').text('حفظ').prop('disabled', false);

        modal.show();
    };


    function resetCostCenterForm() {
        $('#costCenterForm')[0].reset();
        $('#cost_center_id').val('');
        $('#code').val('');
        $('#name').val('');
        $('#parent_id').val('');
        $('#is_active').val('1');
        $('#notes').val('');
        $('#formErrors').addClass('d-none').html('');
    }


    function showValidationErrors(errors) {
        let html = '<strong>يرجى مراجعة البيانات التالية:</strong><ul class="mb-0 mt-2">';

        $.each(errors, function (key, value) {
            html += `<li>${value[0]}</li>`;
        });

        html += '</ul>';

        $('#formErrors').removeClass('d-none').html(html);
    }


    function showFormError(message) {
        $('#formErrors').removeClass('d-none').html(message);
    }


    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم',
                text: message,
                timer: 1200,
                showConfirmButton: false
            });
        } else {
            showPageAlert(message, 'success');
        }
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
</script>
@endpush

</x-app-layout>