<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">دليل الحسابات</h3>
            <p class="page-subtitle mb-0">
                إدارة شجرة الحسابات المحاسبية الرئيسية والفرعية حسب نوع الحساب وطبيعته.
            </p>
        </div>

        @can('accounts.create')
            <button type="button" id="addRootAccount" class="btn btn-primary">
                + حساب رئيسي
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Tree Card --}}
    <div class="card shadow-sm wazin-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">شجرة الحسابات</h5>
                <small>عرض الحسابات بشكل هرمي حسب الحساب الرئيسي والفرعي</small>
            </div>
        </div>

        <div class="card-body">

            <div class="account-tree">

                @forelse($accounts as $account)

                    @include('accounts.partials.account-node', [
                        'account' => $account
                    ])

                @empty

                    <div class="empty-state">
                        <div class="empty-icon">📘</div>
                        <h5>لا توجد حسابات حالياً</h5>
                        <p>ابدأ بإضافة حساب رئيسي لبناء دليل الحسابات.</p>

                        @can('accounts.create')
                            <button type="button" id="emptyAddRootAccount" class="btn btn-primary">
                                + إضافة حساب رئيسي
                            </button>
                        @endcan
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</div>


{{-- Modal --}}
@canany(['accounts.create', 'accounts.edit'])
<div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <form id="account_form" class="modal-content">
            @csrf

            <input type="hidden" name="account_id" id="account_id">
            <input type="hidden" name="parent_id" id="parent_id">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="AccountModalLabel">
                        إضافة حساب
                    </h5>
                    <small>أدخل بيانات الحساب وحدد نوعه وطبيعته</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="form_errors"></div>

                <div class="form-section-title">بيانات الحساب</div>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">رقم الحساب <span class="text-danger">*</span></label>
                        <input type="text"
                               name="account_code"
                               id="account_code"
                               class="form-control"
                               readonly
                               placeholder="يتولد تلقائيًا">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الحساب عربي <span class="text-danger">*</span></label>
                        <input type="text" name="account_name_ar" id="account_name_ar" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">اسم الحساب إنجليزي</label>
                        <input type="text" name="account_name_en" id="account_name_en" class="form-control">
                    </div>

                </div>


                <div class="form-section-title">التصنيف المحاسبي</div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">نوع الحساب <span class="text-danger">*</span></label>
                        <select name="account_type" id="account_type" class="form-select" required>
                            <option value="">اختر</option>
                            <option value="asset">أصول</option>
                            <option value="liability">التزامات</option>
                            <option value="equity">حقوق ملكية</option>
                            <option value="revenue">إيرادات</option>
                            <option value="expense">مصروفات</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">طبيعة الحساب <span class="text-danger">*</span></label>
                        <select name="normal_balance" id="normal_balance" class="form-select" required>
                            <option value="">اختر</option>
                            <option value="debit">مدين</option>
                            <option value="credit">دائن</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">نوع الإدخال <span class="text-danger">*</span></label>
                        <select name="is_group" id="is_group" class="form-select" required>
                            <option value="1">حساب تجميعي</option>
                            <option value="0">حساب نهائي</option>
                        </select>
                    </div>

                    <div class="col-md-4">
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

    .account-tree {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /*
     | تنسيق عام حتى يمسك شكل account-node الحالي
    */
    .account-tree .account-node,
    .account-tree .account-item,
    .account-tree .account-card,
    .account-tree > div:not(.empty-state) {
        border-radius: 18px;
    }

    .account-tree .card,
    .account-tree .account-card {
        border: 1px solid #E5E7EB !important;
        border-radius: 18px !important;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
        overflow: hidden;
    }

    .account-tree .card-header,
    .account-tree .account-header {
        background: #F8FAFC !important;
        border-bottom: 1px solid #E5E7EB !important;
        color: #071633 !important;
        font-weight: 900;
        padding: 14px 16px;
    }

    .account-tree .card-body,
    .account-tree .account-body {
        background: #fff;
        padding: 14px 16px;
    }

    .account-tree .account-children,
    .account-tree .children,
    .account-tree ul {
        margin-top: 12px;
        margin-right: 24px;
        padding-right: 18px;
        border-right: 3px solid rgba(47, 107, 255, 0.18);
    }

    .account-tree .badge {
        border-radius: 999px;
        padding: 7px 10px;
        font-weight: 900;
    }

    .account-tree .btn {
        border-radius: 12px;
        font-weight: 900;
        padding: 7px 12px;
    }

    .account-tree .btn-primary,
    .account-tree .btn-info,
    .account-tree .btn-warning {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    .account-tree .btn-primary:hover,
    .account-tree .btn-info:hover,
    .account-tree .btn-warning:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .account-tree .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
    }

    .account-tree .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
    }

    .account-tree .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .empty-state {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 22px;
        padding: 44px 20px;
        text-align: center;
    }

    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 24px;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin-bottom: 16px;
    }

    .empty-state h5 {
        color: #071633;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #64748B;
        font-weight: 700;
        margin-bottom: 18px;
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

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .account-tree .account-children,
        .account-tree .children,
        .account-tree ul {
            margin-right: 10px;
            padding-right: 12px;
        }
    }
</style>


@push('scripts')
<script>
$(function () {
    
    $('#addRootAccount, #emptyAddRootAccount').on('click', function () {
        resetAccountForm();

        $('#parent_id').val('');
        $('#account_code').val('');
        $('#AccountModalLabel').text('إضافة حساب رئيسي');

        $('#accountModal').modal('show');
    });

    $('#account_type').on('change', function () {
        let parentId = $('#parent_id').val();

        if (parentId) {
            return;
        }

        let accountType = $(this).val();

        if (!accountType) {
            $('#account_code').val('');
            return;
        }

        generateAccountCode(null, accountType);
    });


    $(document).on('click', '.addChildAccount', function () {
        resetAccountForm();

        let parentId = $(this).data('id');
        let parentType = $(this).data('type');
        let parentBalance = $(this).data('balance');

        $('#parent_id').val(parentId);
        $('#account_type').val(parentType);
        $('#normal_balance').val(parentBalance);

        generateAccountCode(parentId, parentType);

        $('#AccountModalLabel').text('إضافة حساب فرعي');

        $('#accountModal').modal('show');
    });


    $(document).on('click', '.editAccount', function () {
        let id = $(this).data('id');

        $.ajax({
            url: "{{ url('/accounts') }}/" + id + "/edit",
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (data) {
                resetAccountForm();

                $('#account_id').val(data.id);
                $('#parent_id').val(data.parent_id);
                $('#account_code').val(data.account_code);
                $('#account_name_ar').val(data.account_name_ar);
                $('#account_name_en').val(data.account_name_en);
                $('#account_type').val(data.account_type);
                $('#normal_balance').val(data.normal_balance);
                $('#is_group').val(data.is_group ? 1 : 0);
                $('#is_active').val(data.is_active ? 1 : 0);

                $('#AccountModalLabel').text('تعديل حساب');
                $('#action').text('تحديث').prop('disabled', false);

                $('#accountModal').modal('show');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert('لا توجد لديك صلاحية لتعديل الحسابات.', 'danger');
                    return;
                }

                showPageAlert('تعذر جلب بيانات الحساب.', 'danger');
            }
        });
    });


    $('#account_form').on('submit', function (e) {
        e.preventDefault();

        let accountId = $('#account_id').val();

        let url = accountId
            ? "{{ url('/accounts') }}/" + accountId
            : "{{ route('accounts.store') }}";

        let method = accountId ? 'PUT' : 'POST';

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
                showSuccess(response.message ?? 'تم حفظ الحساب بنجاح.');

                setTimeout(function () {
                    location.reload();
                }, 700);
            },
            error: function (xhr) {
                $('#action').prop('disabled', false).text(accountId ? 'تحديث' : 'حفظ');

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


    $(document).on('click', '.deleteAccount', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'حذف الحساب؟',
            text: 'لا يمكن حذف الحساب إذا كان مرتبطاً بحسابات فرعية أو حركات محاسبية.',
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
                url: "{{ url('/accounts') }}/" + id,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    showSuccess(response.message ?? 'تم حذف الحساب بنجاح.');

                    setTimeout(function () {
                        location.reload();
                    }, 700);
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        showPageAlert('لا توجد لديك صلاحية لحذف الحسابات.', 'danger');
                        return;
                    }

                    showPageAlert(xhr.responseJSON?.message ?? 'لا يمكن حذف الحساب.', 'danger');
                }
            });
        });
    });


    function resetAccountForm() {
        $('#account_form')[0].reset();
        $('#account_id').val('');
        $('#parent_id').val('');
        $('#form_errors').html('');
        $('#is_group').val('0');
        $('#is_active').val('1');
        $('#action').text('حفظ').prop('disabled', false);
    }

    function generateAccountCode(parentId = null, accountType = null) {
        $('#account_code').val('جاري التوليد...');

        $.ajax({
            url: "{{ route('accounts.generate-code') }}",
            type: 'GET',
            data: {
                parent_id: parentId,
                account_type: accountType
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                $('#account_code').val(response.account_code);
            },
            error: function () {
                $('#account_code').val('');
                showFormError('تعذر توليد رقم الحساب تلقائيًا.');
            }
        });
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