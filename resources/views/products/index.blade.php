<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4" >
        <div>
            <h3 class="page-title mb-1">إدارة الأصناف</h3>
            <p class="page-subtitle mb-0">
                إدارة بيانات المنتجات وربطها بالتصنيفات والعلامات التجارية والوحدات والأسعار.
            </p>
        </div>
        <div class="header-actions">
            @if(in_array(auth()->user()->user_type, ['master', 'system_admin'], true))
                <a href="{{ route('products.csv.index') }}" class="btn btn-success">
                    استيراد / تصدير CSV
                </a>
            @endif

            @can('products.create')
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    + إضافة صنف
                </a>
            @endcan
        </div>
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة الأصناف</h5>
                <small>عرض وتحديث بيانات الأصناف المسجلة في النظام</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="productsTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th width="60">#</th>
                            <th width="120">SKU</th>
                            <th>اسم الصنف</th>
                            <th width="180">التصنيف</th>
                            <th width="160">العلامة</th>
                            <th width="130">الوحدة</th>
                            <th width="120">سعر البيع</th>
                            <th width="100">الحالة</th>
                            <th width="160">العمليات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

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

    #productsTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #productsTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #productsTable td:nth-child(2) {
        direction: ltr;
        font-weight: 900;
        color: #071633;
    }

    #productsTable td:nth-child(7) {
        font-weight: 900;
        color: #071633;
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

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .btn-info,
    .btn-warning {
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
    #productsTable_wrapper {
        direction: rtl;
    }

    #productsTable_wrapper .dataTables_length,
    #productsTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #productsTable_wrapper .dataTables_length label,
    #productsTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #productsTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #productsTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #productsTable_wrapper .dataTables_filter input {
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

    #productsTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #productsTable_wrapper .dataTables_length select {
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

    #productsTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #productsTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #productsTable_wrapper .dataTables_paginate .paginate_button {
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

    #productsTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #productsTable_wrapper .dataTables_paginate .paginate_button.current,
    #productsTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #productsTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #productsTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #productsTable_wrapper::after {
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

        #productsTable_wrapper .dataTables_filter,
        #productsTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #productsTable_wrapper .dataTables_length label,
        #productsTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #productsTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #productsTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    const productsTable = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('products.fetch') }}",
        order: [[1, 'asc']],
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
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'sku',
                name: 'sku'
            },
            {
                data: 'product_name_ar',
                name: 'product_name_ar'
            },
            {
                data: 'category_name',
                name: 'category_name'
            },
            {
                data: 'brand_name',
                name: 'brand_name'
            },
            {
                data: 'default_unit',
                name: 'default_unit',
                orderable: false,
                searchable: false
            },
            {
                data: 'sale_price',
                name: 'sale_price',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ]
    });


    $(document).on('click', '.deleteBtn', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'هل تريد حذف الصنف؟',
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
                url: "{{ url('/products') }}/" + id,
                method: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    productsTable.ajax.reload(null, false);

                    showSuccess(response.message ?? 'تم حذف الصنف بنجاح.');
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        showError('لا توجد لديك صلاحية لحذف الأصناف.');
                        return;
                    }

                    showError(xhr.responseJSON?.message ?? 'حدث خطأ أثناء حذف الصنف.');
                }
            });
        });
    });


    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم',
                text: message,
                timer: 1400,
                showConfirmButton: false
            });
        } else {
            showPageAlert(message, 'success');
        }
    }


    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: message
            });
        } else {
            showPageAlert(message, 'danger');
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