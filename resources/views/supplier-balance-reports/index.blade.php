<x-app-layout>

@php
    $rows = $report['rows'] ?? collect();

    if (is_array($rows)) {
        $rows = collect($rows);
    }

    $totalDebit = (float) ($report['total_debit'] ?? 0);
    $totalCredit = (float) ($report['total_credit'] ?? 0);
    $totalBalance = (float) ($report['total_balance'] ?? 0);

    $dateToValue = request('date_to', $dateTo ?? date('Y-m-d'));

    $selectedBranchName = 'كل الفروع';

    if(request('branch_id')) {
        $selectedBranch = $branches->firstWhere('id', request('branch_id'));

        $selectedBranchName = $selectedBranch->branch_name_ar
            ?? $selectedBranch->branch_name
            ?? $selectedBranch->name
            ?? 'فرع محدد';
    }

    $selectedCostCenterName = 'كل مراكز التكلفة';

    if(request('cost_center_id')) {
        $selectedCostCenter = $costCenters->firstWhere('id', request('cost_center_id'));

        $selectedCostCenterName = $selectedCostCenter
            ? trim((($selectedCostCenter->code ?? '') . ' - ' . ($selectedCostCenter->name ?? '')), ' -')
            : 'مركز تكلفة محدد';
    }
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">تقرير أرصدة الموردين</h3>
            <p class="page-subtitle mb-0">
                عرض إجمالي أرصدة الموردين حتى تاريخ محدد مع إمكانية التصفية حسب الفرع ومركز التكلفة.
            </p>
        </div>

        <button type="button" onclick="window.print()" class="btn btn-dark">
            طباعة التقرير
        </button>
    </div>


    {{-- Filters --}}
    <div class="card shadow-sm wazin-card mb-4 no-print">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">فلاتر التقرير</h5>
                <small>حدد تاريخ التقرير والفرع ومركز التكلفة لعرض أرصدة الموردين المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('supplier-balance-reports.index') }}">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">حتى تاريخ</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ $dateToValue }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">كل الفروع</option>

                            @foreach($branches as $branch)
                                @php
                                    $branchName = $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? ('فرع رقم ' . $branch->id);
                                @endphp

                                <option value="{{ $branch->id }}"
                                    @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branchName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">مركز التكلفة</label>
                        <select name="cost_center_id" class="form-select">
                            <option value="">كل مراكز التكلفة</option>

                            @foreach($costCenters as $costCenter)
                                @php
                                    $costCenterName = trim(
                                        (($costCenter->code ?? '') . ' - ' . ($costCenter->name ?? '')),
                                        ' -'
                                    );
                                @endphp

                                <option value="{{ $costCenter->id }}"
                                    @selected((string) request('cost_center_id') === (string) $costCenter->id)>
                                    {{ $costCenterName ?: ('مركز تكلفة رقم ' . $costCenter->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            عرض
                        </button>

                        <button type="button" onclick="window.print()" class="btn btn-dark w-100">
                            طباعة
                        </button>
                    </div>

                </div>

                <div class="filter-actions mt-3">
                    <a href="{{ route('supplier-balance-reports.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>
                </div>

            </form>

        </div>

    </div>


    {{-- Print Header --}}
    <div class="print-header text-center mb-4">
        <h3 class="fw-bold mb-1">تقرير أرصدة الموردين</h3>
        <div>حتى تاريخ: {{ $dateToValue }}</div>
        <div>الفرع: {{ $selectedBranchName }}</div>
        <div>مركز التكلفة: {{ $selectedCostCenterName }}</div>
    </div>


    {{-- Filter Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات التقرير</h5>
                <small>الفترة والفلاتر المستخدمة في احتساب أرصدة الموردين</small>
            </div>
        </div>

        <div class="card-body">

            <div class="info-grid">

                <div class="info-box">
                    <span>حتى تاريخ</span>
                    <strong class="ltr-text">{{ $dateToValue }}</strong>
                </div>

                <div class="info-box">
                    <span>الفرع</span>
                    <strong>{{ $selectedBranchName }}</strong>
                </div>

                <div class="info-box">
                    <span>مركز التكلفة</span>
                    <strong>{{ $selectedCostCenterName }}</strong>
                </div>

                <div class="info-box">
                    <span>عدد الموردين</span>
                    <strong class="ltr-text">{{ $rows->count() }}</strong>
                </div>

            </div>

        </div>

    </div>


    {{-- Summary --}}
    <div class="summary-grid mb-4">

        <div class="summary-card debit-card">
            <span>إجمالي المدين</span>
            <strong>{{ number_format($totalDebit, 2) }}</strong>
        </div>

        <div class="summary-card credit-card">
            <span>إجمالي الدائن</span>
            <strong>{{ number_format($totalCredit, 2) }}</strong>
        </div>

        <div class="summary-card balance-card">
            <span>صافي أرصدة الموردين</span>
            <strong>
                {{ number_format(abs($totalBalance), 2) }}
            </strong>

            @if($totalBalance > 0)
                <small class="balance-credit">دائن</small>
            @elseif($totalBalance < 0)
                <small class="balance-debit">مدين</small>
            @else
                <small class="balance-neutral">متوازن</small>
            @endif
        </div>

    </div>


    {{-- Balances Table --}}
    <div class="card shadow-sm wazin-card report-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">
                    أرصدة الموردين حتى {{ $dateToValue }}
                </h5>
                <small>قائمة الموردين مع إجمالي المدين والدائن وصافي الرصيد</small>
            </div>

            <span class="result-count no-print">
                عدد الموردين: {{ $rows->count() }}
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المورد</th>
                            <th>الجوال</th>
                            <th>إجمالي المدين</th>
                            <th>إجمالي الدائن</th>
                            <th>الرصيد</th>
                            <th>طبيعة الرصيد</th>
                            <th class="no-print">كشف الحساب</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $index => $row)

                            @php
                                $balance = (float) ($row['balance'] ?? 0);
                                $supplierId = $row['supplier_id'] ?? null;
                            @endphp

                            <tr>
                                <td class="fw-bold">{{ $index + 1 }}</td>

                                <td class="supplier-cell">
                                    {{ $row['supplier_name'] ?? '-' }}
                                </td>

                                <td class="amount-cell">
                                    {{ $row['mobile'] ?? '-' }}
                                </td>

                                <td class="amount-cell debit-amount">
                                    {{ number_format((float) ($row['total_debit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell credit-amount">
                                    {{ number_format((float) ($row['total_credit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell fw-bold {{ $balance > 0 ? 'credit-amount' : ($balance < 0 ? 'debit-amount' : '') }}">
                                    {{ number_format(abs($balance), 2) }}
                                </td>

                                <td>
                                    @if($balance > 0)
                                        <span class="badge bg-success">دائن</span>
                                    @elseif($balance < 0)
                                        <span class="badge bg-danger">مدين</span>
                                    @else
                                        <span class="badge bg-secondary">متوازن</span>
                                    @endif
                                </td>

                                <td class="no-print">
                                    @if($supplierId && \Illuminate\Support\Facades\Route::has('supplier-statements.index'))
                                        <a href="{{ route('supplier-statements.index', [
                                            'supplier_id' => $supplierId,
                                            'date_to' => $dateToValue,
                                            'branch_id' => request('branch_id'),
                                            'cost_center_id' => request('cost_center_id'),
                                        ]) }}" class="btn btn-primary btn-sm">
                                            عرض
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        لا توجد أرصدة موردين حسب الفلاتر المحددة.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3">الإجمالي</td>

                            <td class="amount-cell debit-amount">
                                {{ number_format($totalDebit, 2) }}
                            </td>

                            <td class="amount-cell credit-amount">
                                {{ number_format($totalCredit, 2) }}
                            </td>

                            <td class="amount-cell {{ $totalBalance > 0 ? 'credit-amount' : ($totalBalance < 0 ? 'debit-amount' : '') }}">
                                {{ number_format(abs($totalBalance), 2) }}
                            </td>

                            <td>
                                @if($totalBalance > 0)
                                    دائن
                                @elseif($totalBalance < 0)
                                    مدين
                                @else
                                    متوازن
                                @endif
                            </td>

                            <td class="no-print"></td>
                        </tr>
                    </tfoot>

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
        gap: 14px;
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
        font-weight: 700;
        background-color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .info-box,
    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .info-box span,
    .summary-card span {
        display: block;
        color: #64748B;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .info-box strong {
        display: block;
        color: #071633;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.7;
    }

    .summary-card {
        text-align: center;
    }

    .summary-card strong {
        display: block;
        direction: ltr;
        color: #071633;
        font-size: 26px;
        font-weight: 900;
        margin-bottom: 5px;
    }

    .summary-card small {
        display: inline-block;
        border-radius: 999px;
        padding: 5px 11px;
        font-weight: 900;
        font-size: 12px;
    }

    .debit-card {
        border-right: 5px solid #E63B4A;
        background: rgba(230, 59, 74, 0.05);
    }

    .debit-card strong,
    .debit-amount,
    .balance-debit {
        color: #E63B4A !important;
    }

    .credit-card {
        border-right: 5px solid #16A34A;
        background: rgba(22, 163, 74, 0.05);
    }

    .credit-card strong,
    .credit-amount,
    .balance-credit {
        color: #16A34A !important;
    }

    .balance-card {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
    }

    .balance-card strong {
        color: #2F6BFF;
    }

    .balance-neutral {
        color: #64748B;
    }

    .result-count {
        background: #F1F5F9;
        color: #071633;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        font-size: 13px;
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

    .wazin-table tfoot td {
        background: #EEF2F7 !important;
        color: #071633;
        font-weight: 900;
        border-color: #D7DEE8;
        vertical-align: middle;
    }

    .amount-cell,
    .ltr-text {
        direction: ltr;
        text-align: center;
        font-weight: 900;
        white-space: nowrap;
    }

    .supplier-cell {
        text-align: right !important;
        min-width: 220px;
        line-height: 1.8;
        font-weight: 900;
    }

    .empty-state {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 18px;
        padding: 28px 16px;
        text-align: center;
        color: #64748B;
        font-weight: 800;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .btn-dark,
    .btn-outline-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-outline-secondary {
        border-color: #CBD5E1;
        color: #071633;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        background: #F1F5F9;
        border-color: #CBD5E1;
        color: #071633;
    }

    .btn-sm {
        border-radius: 12px;
        padding: 7px 13px;
        font-weight: 900;
    }

    .print-header {
        display: none;
    }

    @media (max-width: 991px) {
        .info-grid,
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .info-grid,
        .summary-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions .btn {
            width: 100%;
        }
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        .no-print,
        nav,
        aside,
        header,
        .sidebar,
        .navbar,
        .app-sidebar {
            display: none !important;
        }

        .print-header {
            display: block;
        }

        body {
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .app-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .info-grid,
        .summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px !important;
        }

        .info-box,
        .summary-card {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 8px !important;
            background: #fff !important;
        }

        .info-box span,
        .summary-card span {
            color: #000 !important;
            font-size: 10px;
        }

        .info-box strong,
        .summary-card strong {
            color: #000 !important;
            font-size: 13px;
        }

        .wazin-card {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }

        .wazin-card-header {
            background: #f2f2f2 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            padding: 8px !important;
        }

        .wazin-card-header h5,
        .wazin-card-header small {
            color: #000 !important;
        }

        .wazin-card .card-body {
            background: #fff !important;
            padding: 0 !important;
        }

        table {
            font-size: 10px;
            width: 100% !important;
        }

        table th,
        table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
            color: #000 !important;
        }

        .wazin-table thead th,
        .wazin-table tfoot td {
            background: #f2f2f2 !important;
            color: #000 !important;
        }

        .badge {
            border: 1px solid #000;
            color: #000 !important;
            background: #fff !important;
            padding: 3px 6px !important;
        }
    }
</style>

</x-app-layout>