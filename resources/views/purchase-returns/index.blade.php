<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">مردودات المشتريات</h3>
            <p class="page-subtitle mb-0">
                إدارة مردودات المشتريات ومتابعة فاتورة الشراء والمورد والمستودع وحالة المردود.
            </p>
        </div>

        @can('purchase_returns.create')
            <a href="{{ route('purchase-returns.create') }}" class="btn btn-primary">
                + مردود مشتريات جديد
            </a>
        @endcan
    </div>


    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif


    {{-- Table --}}
    <div class="card shadow-sm wazin-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة مردودات المشتريات</h5>
                <small>عرض المردودات حسب الرقم وفاتورة الشراء والمورد والمبالغ وحالة الترحيل</small>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="purchaseReturnsTable"
                       class="table table-bordered table-striped table-hover text-center align-middle w-100">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم المردود</th>
                            <th>فاتورة الشراء</th>
                            <th>المورد</th>
                            <th>المستودع</th>
                            <th>تاريخ المردود</th>
                            <th>الإجمالي قبل الضريبة</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

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
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    #purchaseReturnsTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    #purchaseReturnsTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #purchaseReturnsTable td:nth-child(2),
    #purchaseReturnsTable td:nth-child(3),
    #purchaseReturnsTable td:nth-child(6),
    #purchaseReturnsTable td:nth-child(7),
    #purchaseReturnsTable td:nth-child(8),
    #purchaseReturnsTable td:nth-child(9) {
        direction: ltr;
        font-weight: 900;
    }

    #purchaseReturnsTable td:nth-child(4),
    #purchaseReturnsTable td:nth-child(5) {
        text-align: right;
        min-width: 180px;
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

    .btn-info,
    .btn-warning {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
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

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    /* DataTables Wazin Style */
    #purchaseReturnsTable_wrapper {
        direction: rtl;
    }

    #purchaseReturnsTable_wrapper .dataTables_length,
    #purchaseReturnsTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #purchaseReturnsTable_wrapper .dataTables_length label,
    #purchaseReturnsTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #purchaseReturnsTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #purchaseReturnsTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #purchaseReturnsTable_wrapper .dataTables_filter input {
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

    #purchaseReturnsTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #purchaseReturnsTable_wrapper .dataTables_length select {
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

    #purchaseReturnsTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #purchaseReturnsTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button {
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

    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button.current,
    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #purchaseReturnsTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #purchaseReturnsTable_wrapper::after {
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

        #purchaseReturnsTable_wrapper .dataTables_filter,
        #purchaseReturnsTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #purchaseReturnsTable_wrapper .dataTables_length label,
        #purchaseReturnsTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #purchaseReturnsTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #purchaseReturnsTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(function () {

    $('#purchaseReturnsTable').DataTable({

        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,

        ajax: {
            url: "{{ route('purchase-returns.fetch') }}",
            type: "GET"
        },

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
                data: 'return_no',
                name: 'return_no'
            },
            {
                data: 'invoice_no',
                name: 'invoice.invoice_no',
                orderable: false
            },
            {
                data: 'supplier_name',
                name: 'supplier.supplier_name',
                orderable: false
            },
            {
                data: 'warehouse_name',
                name: 'warehouse.warehouse_name',
                orderable: false
            },
            {
                data: 'return_date',
                name: 'return_date',
                render: function (data) {
                    if (!data) {
                        return '-';
                    }

                    return String(data).substring(0, 10);
                }
            },
            {
                data: 'subtotal',
                name: 'subtotal',
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'vat_amount',
                name: 'vat_amount',
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'total_amount',
                name: 'total_amount',
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'status_badge',
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
        ],

        order: [[5, 'desc']]
    });

});
</script>
@endpush

</x-app-layout>