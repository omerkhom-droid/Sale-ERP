<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">تصنيفات الأصناف</h3>
            <p class="page-subtitle mb-0">
                إدارة التصنيفات الرئيسية والفرعية لتنظيم المنتجات داخل النظام.
            </p>
        </div>

        @can('categories.create')
            <button type="button" id="addRootCategory" class="btn btn-primary">
                + تصنيف رئيسي
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Tree Card --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">شجرة التصنيفات</h5>
                <small>عرض التصنيفات بشكل هرمي حسب التصنيف الرئيسي والفرعي</small>
            </div>
        </div>

        <div class="card-body">

            <div class="category-tree">

                @forelse($categories as $category)

                    @include('categories.partials.category-node', [
                        'category' => $category
                    ])

                @empty

                    <div class="empty-state">
                        <div class="empty-icon">🗂️</div>
                        <h5>لا توجد تصنيفات</h5>
                        <p>ابدأ بإضافة تصنيف رئيسي لتنظيم المنتجات.</p>

                        @can('categories.create')
                            <button type="button" class="btn btn-primary" id="emptyAddCategory">
                                + إضافة تصنيف رئيسي
                            </button>
                        @endcan
                    </div>

                @endforelse

            </div>

        </div>
    </div>

</div>

@include('categories.modal')


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

    .category-tree {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /*
     | هذه التنسيقات عامة حتى تمسك شكل الـ partial الحالي
     | حتى لو كان فيه card / buttons / badges
    */
    .category-tree .category-node,
    .category-tree .category-item,
    .category-tree .category-card,
    .category-tree > div:not(.empty-state) {
        border-radius: 18px;
    }

    .category-tree .card,
    .category-tree .category-card {
        border: 1px solid #E5E7EB !important;
        border-radius: 18px !important;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.05);
        overflow: hidden;
    }

    .category-tree .card-header,
    .category-tree .category-header {
        background: #F8FAFC !important;
        border-bottom: 1px solid #E5E7EB !important;
        color: #071633 !important;
        font-weight: 900;
        padding: 14px 16px;
    }

    .category-tree .card-body,
    .category-tree .category-body {
        background: #fff;
        padding: 14px 16px;
    }

    .category-tree .category-children,
    .category-tree .children,
    .category-tree ul {
        margin-top: 12px;
        margin-right: 24px;
        padding-right: 18px;
        border-right: 3px solid rgba(47, 107, 255, 0.18);
    }

    .category-tree .badge {
        border-radius: 999px;
        padding: 7px 10px;
        font-weight: 900;
    }

    .category-tree .btn {
        border-radius: 12px;
        font-weight: 900;
        padding: 7px 12px;
    }

    .category-tree .btn-primary,
    .category-tree .btn-info {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    .category-tree .btn-primary:hover,
    .category-tree .btn-info:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .category-tree .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
    }

    .category-tree .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
    }

    .category-tree .btn-danger:hover {
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
        background: #071633 !important;
        color: #fff;
        border-bottom: 0;
        padding: 18px 22px;
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

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .category-tree .category-children,
        .category-tree .children,
        .category-tree ul {
            margin-right: 10px;
            padding-right: 12px;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    $('#addRootCategory, #emptyAddCategory').on('click', function () {
        resetCategoryForm();

        $('#parent_id').val('');
        $('#CategoryModalLabel').text('إضافة تصنيف رئيسي');
        $('#action').text('حفظ');

        $('#categoryModal').modal('show');
    });


    $(document).on('click', '.addChildCategory', function () {
        resetCategoryForm();

        let parentId = $(this).data('id');

        $('#parent_id').val(parentId);
        $('#CategoryModalLabel').text('إضافة تصنيف فرعي');
        $('#action').text('حفظ');

        $('#categoryModal').modal('show');
    });


    $(document).on('click', '.editCategory', function () {
        let id = $(this).data('id');

        $.ajax({
            url: "{{ url('/categories') }}/" + id + "/edit",
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (data) {
                resetCategoryForm();

                $('#category_id').val(data.id);
                $('#parent_id').val(data.parent_id);
                $('#category_name').val(data.category_name);
                $('#description').val(data.description);
                $('#is_active').val(data.is_active ? 1 : 0);

                $('#CategoryModalLabel').text('تعديل تصنيف');
                $('#action').text('تحديث');

                $('#categoryModal').modal('show');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert('لا توجد لديك صلاحية لتعديل التصنيفات.', 'danger');
                    return;
                }

                showPageAlert('تعذر جلب بيانات التصنيف.', 'danger');
            }
        });
    });


    $('#category_form').on('submit', function (e) {
        e.preventDefault();

        let categoryId = $('#category_id').val();

        let url = categoryId
            ? "{{ url('/categories') }}/" + categoryId
            : "{{ route('categories.store') }}";

        let method = categoryId ? 'PUT' : 'POST';

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
                $('#categoryModal').modal('hide');

                showSuccess(response.message ?? 'تم حفظ التصنيف بنجاح.');

                setTimeout(function () {
                    location.reload();
                }, 900);
            },
            error: function (xhr) {
                $('#action').prop('disabled', false).text(categoryId ? 'تحديث' : 'حفظ');

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


    $(document).on('click', '.deleteCategory', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف التصنيف',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/categories') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        showSuccess(response.message ?? 'تم حذف التصنيف بنجاح.');

                        setTimeout(function () {
                            location.reload();
                        }, 900);
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'لا يمكن الحذف',
                            text: xhr.responseJSON?.message ?? 'حدث خطأ أثناء الحذف'
                        });
                    }
                });
            }
        });
    });


    function resetCategoryForm() {
        $('#category_form')[0].reset();
        $('#category_id').val('');
        $('#parent_id').val('');
        $('#form_errors').html('');
        $('#is_active').val(1);
        $('#action').prop('disabled', false).text('حفظ');
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