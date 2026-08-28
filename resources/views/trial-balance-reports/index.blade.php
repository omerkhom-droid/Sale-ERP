<x-app-layout>

@php
    $rows = $report['rows'] ?? collect();

    if (is_array($rows)) {
        $rows = collect($rows);
    }

    $totalOpeningDebit = (float) ($report['total_opening_debit'] ?? 0);
    $totalOpeningCredit = (float) ($report['total_opening_credit'] ?? 0);

    $totalPeriodDebit = (float) ($report['total_period_debit'] ?? 0);
    $totalPeriodCredit = (float) ($report['total_period_credit'] ?? 0);

    $totalClosingDebit = (float) ($report['total_closing_debit'] ?? 0);
    $totalClosingCredit = (float) ($report['total_closing_credit'] ?? 0);

    $periodDifference = round($totalPeriodDebit - $totalPeriodCredit, 2);
    $closingDifference = round($totalClosingDebit - $totalClosingCredit, 2);

    $periodFrom = request('date_from') ?: 'البداية';
    $periodTo = request('date_to', $dateTo ?? date('Y-m-d'));

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
            <h3 class="page-title mb-1">ميزان المراجعة</h3>
            <p class="page-subtitle mb-0">
                مراجعة أرصدة الحسابات وحركة الفترة مع التأكد من توازن المدين والدائن.
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
                <h5 class="mb-0 fw-bold">فلاتر ميزان المراجعة</h5>
                <small>حدد الفترة والفرع ومركز التكلفة لعرض ميزان المراجعة المطلوب</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('trial-balance-reports.index') }}">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ $periodTo }}">
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
                    <a href="{{ route('trial-balance-reports.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>
                </div>

            </form>

        </div>

    </div>


    {{-- Print Header --}}
    <div class="print-header text-center mb-4">
        <h3 class="fw-bold mb-1">ميزان المراجعة</h3>
        <div>الفترة: {{ $periodFrom }} إلى {{ $periodTo }}</div>
        <div>الفرع: {{ $selectedBranchName }}</div>
        <div>مركز التكلفة: {{ $selectedCostCenterName }}</div>
    </div>


    {{-- Filter Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات التقرير</h5>
                <small>الفترة والفلاتر المستخدمة في احتساب ميزان المراجعة</small>
            </div>
        </div>

        <div class="card-body">

            <div class="info-grid">

                <div class="info-box">
                    <span>من تاريخ</span>
                    <strong class="ltr-text">{{ $periodFrom }}</strong>
                </div>

                <div class="info-box">
                    <span>إلى تاريخ</span>
                    <strong class="ltr-text">{{ $periodTo }}</strong>
                </div>

                <div class="info-box">
                    <span>الفرع</span>
                    <strong>{{ $selectedBranchName }}</strong>
                </div>

                <div class="info-box">
                    <span>مركز التكلفة</span>
                    <strong>{{ $selectedCostCenterName }}</strong>
                </div>

            </div>

        </div>

    </div>


    {{-- Summary --}}
    <div class="summary-grid mb-4">

        <div class="summary-card movement-card">
            <span>حركة الفترة</span>

            <div class="summary-pair">
                <small>مدين</small>
                <strong class="debit-amount">{{ number_format($totalPeriodDebit, 2) }}</strong>
            </div>

            <div class="summary-pair">
                <small>دائن</small>
                <strong class="credit-amount">{{ number_format($totalPeriodCredit, 2) }}</strong>
            </div>

            <div class="balance-status mt-2">
                @if($periodDifference == 0)
                    <span class="badge bg-success">متوازن</span>
                @else
                    <span class="badge bg-danger">
                        فرق {{ number_format(abs($periodDifference), 2) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="summary-card closing-card">
            <span>الرصيد النهائي</span>

            <div class="summary-pair">
                <small>مدين</small>
                <strong class="debit-amount">{{ number_format($totalClosingDebit, 2) }}</strong>
            </div>

            <div class="summary-pair">
                <small>دائن</small>
                <strong class="credit-amount">{{ number_format($totalClosingCredit, 2) }}</strong>
            </div>

            <div class="balance-status mt-2">
                @if($closingDifference == 0)
                    <span class="badge bg-success">متوازن</span>
                @else
                    <span class="badge bg-danger">
                        فرق {{ number_format(abs($closingDifference), 2) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="summary-card count-card">
            <span>عدد الحسابات</span>
            <strong class="count-number">{{ $rows->count() }}</strong>
            <small class="count-note">الحسابات التي لها حركة أو رصيد</small>
        </div>

    </div>


    {{-- Trial Balance Table --}}
    <div class="card shadow-sm wazin-card report-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">تفاصيل ميزان المراجعة</h5>
                <small>الرصيد الافتتاحي وحركة الفترة والرصيد النهائي لكل حساب</small>
            </div>

            <span class="result-count no-print">
                عدد الحسابات: {{ $rows->count() }}
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th rowspan="2">#</th>
                            <th rowspan="2">كود الحساب</th>
                            <th rowspan="2">اسم الحساب</th>
                            <th rowspan="2">نوع الحساب</th>
                            <th colspan="2">الرصيد الافتتاحي</th>
                            <th colspan="2">حركة الفترة</th>
                            <th colspan="2">الرصيد النهائي</th>
                            <th rowspan="2" class="no-print">دفتر الأستاذ</th>
                        </tr>

                        <tr>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>مدين</th>
                            <th>دائن</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $index => $row)
                            @php
                                $accountId = $row['account_id'] ?? null;
                            @endphp

                            <tr>
                                <td class="fw-bold">{{ $index + 1 }}</td>

                                <td class="amount-cell">
                                    {{ $row['account_code'] ?: '-' }}
                                </td>

                                <td class="account-cell">
                                    {{ $row['account_name'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['account_type'] ?? '-' }}
                                </td>

                                <td class="amount-cell debit-amount">
                                    {{ number_format((float) ($row['opening_debit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell credit-amount">
                                    {{ number_format((float) ($row['opening_credit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell debit-amount">
                                    {{ number_format((float) ($row['period_debit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell credit-amount">
                                    {{ number_format((float) ($row['period_credit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell debit-amount fw-bold">
                                    {{ number_format((float) ($row['closing_debit'] ?? 0), 2) }}
                                </td>

                                <td class="amount-cell credit-amount fw-bold">
                                    {{ number_format((float) ($row['closing_credit'] ?? 0), 2) }}
                                </td>

                                <td class="no-print">
                                    @if($accountId && \Illuminate\Support\Facades\Route::has('account-ledger-reports.index'))
                                        <a href="{{ route('account-ledger-reports.index', [
                                            'account_id' => $accountId,
                                            'date_from' => request('date_from'),
                                            'date_to' => $periodTo,
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
                                <td colspan="11">
                                    <div class="empty-state">
                                        لا توجد بيانات حسب الفلاتر المحددة.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="4">الإجمالي</td>

                            <td class="amount-cell debit-amount">
                                {{ number_format($totalOpeningDebit, 2) }}
                            </td>

                            <td class="amount-cell credit-amount">
                                {{ number_format($totalOpeningCredit, 2) }}
                            </td>

                            <td class="amount-cell debit-amount">
                                {{ number_format($totalPeriodDebit, 2) }}
                            </td>

                            <td class="amount-cell credit-amount">
                                {{ number_format($totalPeriodCredit, 2) }}
                            </td>

                            <td class="amount-cell debit-amount">
                                {{ number_format($totalClosingDebit, 2) }}
                            </td>

                            <td class="amount-cell credit-amount">
                                {{ number_format($totalClosingCredit, 2) }}
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
    .summary-card > span {
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

    .summary-pair {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #F8FAFC;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 9px 12px;
        margin-bottom: 8px;
    }

    .summary-pair small {
        color: #64748B;
        font-weight: 900;
    }

    .summary-pair strong {
        direction: ltr;
        font-weight: 900;
    }

    .movement-card {
        border-right: 5px solid #2F6BFF;
    }

    .closing-card {
        border-right: 5px solid #16A34A;
    }

    .count-card {
        border-right: 5px solid #64748B;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .count-number {
        display: block;
        direction: ltr;
        color: #071633;
        font-size: 38px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .count-note {
        color: #64748B;
        font-weight: 800;
        line-height: 1.7;
    }

    .debit-amount {
        color: #E63B4A !important;
    }

    .credit-amount {
        color: #16A34A !important;
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

    .account-cell {
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
        .summary-card span,
        .summary-pair small,
        .count-note {
            color: #000 !important;
            font-size: 10px;
        }

        .info-box strong,
        .summary-pair strong,
        .count-number {
            color: #000 !important;
            font-size: 13px;
        }

        .summary-pair {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            background: #fff !important;
            padding: 4px !important;
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
            font-size: 9.5px;
            width: 100% !important;
        }

        table th,
        table td {
            border: 1px solid #000 !important;
            padding: 3px !important;
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