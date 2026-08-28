<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">العملاء</h3>
            <p class="page-subtitle mb-0">
                إدارة بيانات العملاء، المعلومات الضريبية، العنوان، والرصيد الافتتاحي.
            </p>
        </div>

        @can('customers.create')
            <button type="button" id="add_button" class="btn btn-primary">
                + عميل جديد
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة العملاء</h5>
                <small>عرض وتحديث بيانات العملاء المسجلين في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="customersTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الكود</th>
                            <th>اسم العميل</th>
                            <th>النوع</th>
                            <th>الجوال</th>
                            <th>الرقم الضريبي</th>
                            <th>المدينة</th>
                            <th>الرصيد</th>
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
@canany(['customers.create', 'customers.edit'])
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <form id="customer_form" class="modal-content">
            @csrf

            <input type="hidden" name="customer_id" id="customer_id">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="CustomerModalLabel">
                        إضافة عميل
                    </h5>
                    <small>أدخل بيانات العميل ومعلومات العنوان والرصيد الافتتاحي</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="form_errors"></div>

                <div class="form-section-title">البيانات الأساسية</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-3">
                        <label class="form-label">كود العميل <span class="text-danger">*</span></label>
                        <input type="text" name="customer_code" id="customer_code" class="form-control" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">نوع العميل <span class="text-danger">*</span></label>
                        <select name="customer_type" id="customer_type" class="form-select" required>
                            <option value="company">شركة</option>
                            <option value="individual">فرد</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الجوال</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text" name="tax_number" id="tax_number" class="form-control" maxlength="15">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">السجل التجاري</label>
                        <input type="text" name="commercial_register" id="commercial_register" class="form-control" maxlength="20">
                    </div>

                </div>


                <div class="form-section-title">العنوان الوطني</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-2">
                        <label class="form-label">الدولة</label>
                        <input type="text" name="country_code" id="country_code" class="form-control" value="SA">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المنطقة</label>
                        <input type="text" name="state" id="state" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المدينة</label>
                        <input type="text" name="city" id="city" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الحي</label>
                        <input type="text" name="district" id="district" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الشارع</label>
                        <input type="text" name="street_name" id="street_name" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">رقم المبنى</label>
                        <input type="text" name="building_number" id="building_number" class="form-control" maxlength="10">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">الرقم الإضافي</label>
                        <input type="text" name="additional_number" id="additional_number" class="form-control" maxlength="10">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">الرمز البريدي</label>
                        <input type="text" name="postal_code" id="postal_code" class="form-control" maxlength="10">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                    </div>

                </div>


                <div class="form-section-title">الحالة</div>

                <div class="row g-3">

<!--                     <div class="col-md-4">
                        <label class="form-label">الرصيد الافتتاحي <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control" value="0" required>
                    </div>
 -->
                    <input type="hidden" name="opening_balance" id="opening_balance" class="form-control" value="0">
                    <input type="hidden" name="balance_type" id="balance_type" class="form-control" value="">
<!--                     <div class="col-md-12">
                        <label class="form-label">طبيعة الرصيد <span class="text-danger">*</span></label>
                        <select name="balance_type" id="balance_type" class="form-select" required>
                            <option value="debit">مدين</option>
                            <option value="credit">دائن</option>
                        </select>
                    </div> -->

                    <div class="col-md-12">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="is_active" id="is_active" class="form-select" required>
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
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

    #customersTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #customersTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #customersTable td:nth-child(2),
    #customersTable td:nth-child(5),
    #customersTable td:nth-child(6),
    #customersTable td:nth-child(8) {
        direction: ltr;
        font-weight: 900;
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
    #customersTable_wrapper {
        direction: rtl;
    }

    #customersTable_wrapper .dataTables_length,
    #customersTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #customersTable_wrapper .dataTables_length label,
    #customersTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #customersTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #customersTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #customersTable_wrapper .dataTables_filter input {
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

    #customersTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #customersTable_wrapper .dataTables_length select {
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

    #customersTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #customersTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #customersTable_wrapper .dataTables_paginate .paginate_button {
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

    #customersTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #customersTable_wrapper .dataTables_paginate .paginate_button.current,
    #customersTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #customersTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #customersTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #customersTable_wrapper::after {
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

        #customersTable_wrapper .dataTables_filter,
        #customersTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #customersTable_wrapper .dataTables_length label,
        #customersTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #customersTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #customersTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    const table = $('#customersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('customers.fetch') }}",
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
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'customer_code'},
            {data: 'customer_name'},
            {data: 'customer_type_text', orderable: false, searchable: false},
            {data: 'phone'},
            {data: 'tax_number'},
            {data: 'city'},
            {data: 'opening_balance'},
            {data: 'status', orderable: false, searchable: false},
            {data: 'actions', orderable: false, searchable: false},
        ],
        order: [[1, 'asc']]
    });


    $('#add_button').on('click', function () {
        resetCustomerForm();

        $('#CustomerModalLabel').text('إضافة عميل');
        $('#action').text('حفظ').prop('disabled', false);

        $('#customerModal').modal('show');
    });


    $('#customer_form').on('submit', function (e) {
        e.preventDefault();

        let customerId = $('#customer_id').val();

        let url = customerId
            ? "{{ url('/customers') }}/" + customerId
            : "{{ route('customers.store') }}";

        let method = customerId ? 'PUT' : 'POST';

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
                $('#customerModal').modal('hide');

                resetCustomerForm();

                table.ajax.reload(null, false);

                showSuccess(response.message ?? 'تم حفظ العميل بنجاح.');

                $('#action').prop('disabled', false).text('حفظ');
            },
            error: function (xhr) {
                $('#action').prop('disabled', false).text(customerId ? 'تحديث' : 'حفظ');

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
            url: "{{ url('/customers') }}/" + id + "/edit",
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (data) {
                $('#customer_id').val(data.id);
                $('#customer_code').val(data.customer_code);
                $('#customer_name').val(data.customer_name);
                $('#customer_type').val(data.customer_type);
                $('#phone').val(data.phone);
                $('#email').val(data.email);
                $('#tax_number').val(data.tax_number);
                $('#commercial_register').val(data.commercial_register);
                $('#country_code').val(data.country_code ?? 'SA');
                $('#state').val(data.state);
                $('#city').val(data.city);
                $('#district').val(data.district);
                $('#street_name').val(data.street_name);
                $('#building_number').val(data.building_number);
                $('#additional_number').val(data.additional_number);
                $('#postal_code').val(data.postal_code);
                $('#address').val(data.address);
                $('#opening_balance').val(data.opening_balance ?? 0);
                $('#balance_type').val(data.balance_type ?? 'debit');
                $('#is_active').val(data.is_active ? 1 : 0);

                $('#CustomerModalLabel').text('تعديل عميل');
                $('#customerModal').modal('show');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert('لا توجد لديك صلاحية لتعديل العملاء.', 'danger');
                    return;
                }

                showPageAlert('تعذر جلب بيانات العميل.', 'danger');
            }
        });
    });


    $(document).on('click', '.deleteBtn', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'حذف العميل؟',
            text: 'لا يمكن التراجع عن عملية الحذف بعد تنفيذها.',
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
                url: "{{ url('/customers') }}/" + id,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    showSuccess(response.message ?? 'تم حذف العميل بنجاح.');
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        showPageAlert('لا توجد لديك صلاحية لحذف العملاء.', 'danger');
                        return;
                    }

                    showPageAlert(xhr.responseJSON?.message ?? 'حدث خطأ أثناء حذف العميل.', 'danger');
                }
            });
        });
    });


    function resetCustomerForm() {
        $('#customer_form')[0].reset();
        $('#customer_id').val('');
        $('#country_code').val('SA');
        $('#opening_balance').val(0);
        $('#balance_type').val('debit');
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

});
</script>
@endpush

</x-app-layout>