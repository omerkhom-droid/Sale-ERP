<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">القيود اليومية اليدوية</h3>
            <p class="page-subtitle mb-0">
                إدارة القيود اليدوية ومتابعة حالتها وترحيلها أو إلغائها من خلال قيد عكسي.
            </p>
        </div>

        @can('manual_journal_entries.create')
            <a href="{{ route('manual-journal-entries.create') }}" class="btn btn-primary">
                + قيد يومية جديد
            </a>
        @endcan
    </div>

    <div id="alert_action"></div>

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة القيود اليومية</h5>
                <small>عرض القيود اليدوية حسب الرقم والتاريخ والحالة والإجماليات</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="manualJournalEntriesTable" class="table table-bordered table-striped table-hover text-center align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم القيد</th>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>إجمالي المدين</th>
                            <th>إجمالي الدائن</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
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
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    #manualJournalEntriesTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    #manualJournalEntriesTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #manualJournalEntriesTable td:nth-child(2),
    #manualJournalEntriesTable td:nth-child(3),
    #manualJournalEntriesTable td:nth-child(5),
    #manualJournalEntriesTable td:nth-child(6) {
        direction: ltr;
        font-weight: 900;
    }

    #manualJournalEntriesTable td:nth-child(4) {
        text-align: right;
        min-width: 240px;
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
    #manualJournalEntriesTable_wrapper {
        direction: rtl;
    }

    #manualJournalEntriesTable_wrapper .dataTables_length,
    #manualJournalEntriesTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #manualJournalEntriesTable_wrapper .dataTables_length label,
    #manualJournalEntriesTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #manualJournalEntriesTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #manualJournalEntriesTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #manualJournalEntriesTable_wrapper .dataTables_filter input {
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

    #manualJournalEntriesTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #manualJournalEntriesTable_wrapper .dataTables_length select {
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

    #manualJournalEntriesTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #manualJournalEntriesTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button {
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

    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button.current,
    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #manualJournalEntriesTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #manualJournalEntriesTable_wrapper::after {
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

        #manualJournalEntriesTable_wrapper .dataTables_filter,
        #manualJournalEntriesTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #manualJournalEntriesTable_wrapper .dataTables_length label,
        #manualJournalEntriesTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #manualJournalEntriesTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #manualJournalEntriesTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>


@push('scripts')
<script>
$(document).ready(function () {

    let table = $('#manualJournalEntriesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('manual-journal-entries.fetch') }}",
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
                data: 'manual_no',
                name: 'manual_no'
            },
            {
                data: 'manual_date',
                name: 'manual_date'
            },
            {
                data: 'notes',
                name: 'notes',
                defaultContent: '-'
            },
            {
                data: 'total_debit',
                name: 'total_debit_sum',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'total_credit',
                name: 'total_credit_sum',
                orderable: false,
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
        order: [[2, 'desc']]
    });


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json'
        }
    });


    $(document).on('click', '.cancelBtn', function () {
        let id = $(this).data('id');

        if (typeof Swal === 'undefined') {
            let reason = prompt('سبب الإلغاء - اختياري');

            if (reason === null) {
                return;
            }

            sendCancelRequest(id, reason, table);
            return;
        }

        Swal.fire({
            title: 'إلغاء القيد اليدوي',
            text: 'سيتم إنشاء قيد عكسي وإلغاء القيد الحالي.',
            input: 'text',
            inputLabel: 'سبب الإلغاء',
            inputPlaceholder: 'اختياري',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'تأكيد الإلغاء',
            cancelButtonText: 'رجوع',
            confirmButtonColor: '#E63B4A',
            cancelButtonColor: '#64748B'
        }).then((result) => {
            if (! result.isConfirmed) {
                return;
            }

            sendCancelRequest(id, result.value, table);
        });
    });


    function sendCancelRequest(id, reason, table) {
        let url = "{{ route('manual-journal-entries.cancel', ':id') }}";
        url = url.replace(':id', id);

        $.post(url, {
            cancel_reason: reason
        })
        .done(function (response) {
            showSuccess(response.message ?? 'تم إلغاء القيد بنجاح.');
            table.ajax.reload(null, false);
        })
        .fail(function (xhr) {
            showError(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الإلغاء.');
        });
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