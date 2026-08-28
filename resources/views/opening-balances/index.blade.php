<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الأرصدة الافتتاحية</h3>
            <p class="page-subtitle mb-0">
                إدارة مستندات الأرصدة الافتتاحية للحسابات وترحيلها إلى القيود المحاسبية.
            </p>
        </div>

        @can('opening_balances.create')
            <a href="{{ route('opening-balances.create') }}" class="btn btn-primary">
                + إضافة رصيد افتتاحي
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
                <h5 class="mb-0 fw-bold">قائمة الأرصدة الافتتاحية</h5>
                <small>عرض المستندات المسجلة وحالتها وعدد السطور المرتبطة بها</small>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle wazin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم المستند</th>
                            <th>التاريخ</th>
                            <th>عدد السطور</th>
                            <th>الحالة</th>
                            <th>أنشئ بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($openingBalances as $index => $openingBalance)
                            <tr>
                                <td class="fw-bold">
                                    {{ $openingBalances->firstItem() + $index }}
                                </td>

                                <td>
                                    <span class="document-no">
                                        {{ $openingBalance->opening_no }}
                                    </span>
                                </td>

                                <td class="ltr-cell">
                                    {{ optional($openingBalance->opening_date)->format('Y-m-d') }}
                                </td>

                                <td>
                                    <span class="lines-count">
                                        {{ $openingBalance->lines_count }}
                                    </span>
                                </td>

                                <td>
                                    @if($openingBalance->status === 'draft')
                                        <span class="badge bg-secondary">مسودة</span>
                                    @elseif($openingBalance->status === 'posted')
                                        <span class="badge bg-success">مرحل</span>
                                    @elseif($openingBalance->status === 'cancelled')
                                        <span class="badge bg-danger">ملغي</span>
                                    @else
                                        <span class="badge bg-light text-dark">
                                            {{ $openingBalance->status }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $openingBalance->creator?->name ?? '-' }}
                                </td>

                                <td>
                                    @can('opening_balances.view')
                                        <a href="{{ route('opening-balances.show', $openingBalance) }}"
                                           class="btn btn-sm btn-info">
                                            عرض
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon">📘</div>
                                        <h5>لا توجد أرصدة افتتاحية حتى الآن</h5>
                                        <p>ابدأ بإضافة مستند رصيد افتتاحي جديد.</p>

                                        @can('opening_balances.create')
                                            <a href="{{ route('opening-balances.create') }}" class="btn btn-primary">
                                                + إضافة رصيد افتتاحي
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($openingBalances->hasPages())
                <div class="pagination-wrapper mt-4">
                    {{ $openingBalances->links() }}
                </div>
            @endif

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

    .wazin-card .card-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .wazin-table {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 0;
    }

    .wazin-table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    .wazin-table tbody td {
        vertical-align: middle;
        font-weight: 600;
        background: #fff;
    }

    .document-no {
        direction: ltr;
        display: inline-block;
        color: #071633;
        background: rgba(47, 107, 255, 0.10);
        border: 1px solid rgba(47, 107, 255, 0.18);
        border-radius: 999px;
        padding: 7px 12px;
        font-weight: 900;
    }

    .lines-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 32px;
        border-radius: 999px;
        background: #F1F5F9;
        color: #071633;
        font-weight: 900;
    }

    .ltr-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
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

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
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

    .pagination-wrapper {
        display: flex;
        justify-content: flex-end;
    }

    .pagination {
        gap: 6px;
        margin-bottom: 0;
    }

    .page-link {
        border-radius: 12px !important;
        border: 1px solid #E5E7EB;
        color: #071633;
        font-weight: 900;
        min-width: 38px;
        text-align: center;
    }

    .page-link:hover {
        background: #2F6BFF;
        border-color: #2F6BFF;
        color: #fff;
    }

    .page-item.active .page-link {
        background: #2F6BFF;
        border-color: #2F6BFF;
        color: #fff;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    .page-item.disabled .page-link {
        background: #F1F5F9;
        color: #94A3B8;
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .pagination-wrapper {
            justify-content: center;
        }
    }
</style>

</x-app-layout>