<x-app-layout>

@php
    $assets = $report['assets'] ?? collect();
    $liabilities = $report['liabilities'] ?? collect();
    $equity = $report['equity'] ?? collect();

    if (is_array($assets)) {
        $assets = collect($assets);
    }

    if (is_array($liabilities)) {
        $liabilities = collect($liabilities);
    }

    if (is_array($equity)) {
        $equity = collect($equity);
    }

    $totalAssets = (float) ($report['total_assets'] ?? 0);
    $totalLiabilities = (float) ($report['total_liabilities'] ?? 0);
    $totalEquity = (float) ($report['total_equity'] ?? 0);
    $totalLiabilitiesAndEquity = (float) ($report['total_liabilities_and_equity'] ?? 0);
    $difference = (float) ($report['difference'] ?? 0);
    $netIncome = (float) ($report['net_income'] ?? 0);

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

    $isBalanced = round($difference, 2) == 0;
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">الميزانية العمومية</h3>
            <p class="page-subtitle mb-0">
                عرض المركز المالي حتى تاريخ محدد مع مقارنة إجمالي الأصول بإجمالي الالتزامات وحقوق الملكية.
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
                <h5 class="mb-0 fw-bold">فلاتر الميزانية العمومية</h5>
                <small>حدد تاريخ التقرير والفرع ومركز التكلفة لعرض الميزانية المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('balance-sheet-reports.index') }}">

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
                    <a href="{{ route('balance-sheet-reports.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>
                </div>

            </form>

        </div>

    </div>


    {{-- Print Header --}}
    <div class="print-header text-center mb-4">
        <h3 class="fw-bold mb-1">الميزانية العمومية</h3>
        <div>حتى تاريخ: {{ $dateToValue }}</div>
        <div>الفرع: {{ $selectedBranchName }}</div>
        <div>مركز التكلفة: {{ $selectedCostCenterName }}</div>
    </div>


    {{-- Filter Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات التقرير</h5>
                <small>الفترة والفلاتر المستخدمة في احتساب الميزانية العمومية</small>
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
                    <span>حالة الميزانية</span>

                    @if($isBalanced)
                        <strong class="success-amount">متوازنة</strong>
                    @else
                        <strong class="danger-amount">غير متوازنة</strong>
                    @endif
                </div>

            </div>

        </div>

    </div>


    {{-- Summary --}}
    <div class="summary-grid mb-4">

        <div class="summary-card assets-card">
            <span>إجمالي الأصول</span>
            <strong>{{ number_format($totalAssets, 2) }}</strong>
        </div>

        <div class="summary-card liabilities-card">
            <span>إجمالي الالتزامات</span>
            <strong>{{ number_format($totalLiabilities, 2) }}</strong>
        </div>

        <div class="summary-card equity-card">
            <span>إجمالي حقوق الملكية</span>
            <strong>{{ number_format($totalEquity, 2) }}</strong>
        </div>

        <div class="summary-card difference-card {{ $isBalanced ? 'balanced-card' : 'unbalanced-card' }}">
            <span>الفرق</span>
            <strong>{{ number_format(abs($difference), 2) }}</strong>

            @if($isBalanced)
                <small class="balance-success">متوازنة</small>
            @else
                <small class="balance-danger">غير متوازنة</small>
            @endif
        </div>

    </div>


    @if(!$isBalanced)
        <div class="alert alert-warning wazin-alert">
            يوجد فرق بين إجمالي الأصول وإجمالي الالتزامات وحقوق الملكية:
            <strong>{{ number_format(abs($difference), 2) }}</strong>
        </div>
    @endif


    {{-- Main Balance Sheet --}}
    <div class="row g-4">

        {{-- Assets --}}
        <div class="col-lg-6">

            <div class="card shadow-sm wazin-card report-card mb-4">

                <div class="card-header wazin-card-header assets-header">
                    <div>
                        <h5 class="mb-0 fw-bold">الأصول</h5>
                        <small>أرصدة حسابات الأصول حتى تاريخ التقرير</small>
                    </div>

                    <span class="result-count assets-count">
                        {{ number_format($totalAssets, 2) }}
                    </span>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>كود الحساب</th>
                                    <th>اسم الحساب</th>
                                    <th>الرصيد</th>
                                    <th class="no-print">دفتر الأستاذ</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($assets as $index => $row)
                                    <tr>
                                        <td class="fw-bold">{{ $index + 1 }}</td>

                                        <td class="amount-cell">
                                            {{ $row['account_code'] ?: '-' }}
                                        </td>

                                        <td class="account-cell">
                                            {{ $row['account_name'] ?? '-' }}
                                        </td>

                                        <td class="amount-cell fw-bold assets-amount">
                                            {{ number_format(abs((float) ($row['balance'] ?? 0)), 2) }}

                                            @if(($row['balance'] ?? 0) < 0)
                                                <small class="danger-amount">عكسي</small>
                                            @endif
                                        </td>

                                        <td class="no-print">
                                            @if(!empty($row['account_id']) && \Illuminate\Support\Facades\Route::has('account-ledger-reports.index'))
                                                <a href="{{ route('account-ledger-reports.index', [
                                                    'account_id' => $row['account_id'],
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
                                        <td colspan="5">
                                            <div class="empty-state">
                                                لا توجد أرصدة أصول حسب الفلاتر المحددة.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="3">إجمالي الأصول</td>
                                    <td class="amount-cell assets-amount">
                                        {{ number_format($totalAssets, 2) }}
                                    </td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>


        {{-- Liabilities and Equity --}}
        <div class="col-lg-6">

            {{-- Liabilities --}}
            <div class="card shadow-sm wazin-card report-card mb-4">

                <div class="card-header wazin-card-header liabilities-header">
                    <div>
                        <h5 class="mb-0 fw-bold">الالتزامات</h5>
                        <small>أرصدة حسابات الالتزامات حتى تاريخ التقرير</small>
                    </div>

                    <span class="result-count liabilities-count">
                        {{ number_format($totalLiabilities, 2) }}
                    </span>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>كود الحساب</th>
                                    <th>اسم الحساب</th>
                                    <th>الرصيد</th>
                                    <th class="no-print">دفتر الأستاذ</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($liabilities as $index => $row)
                                    <tr>
                                        <td class="fw-bold">{{ $index + 1 }}</td>

                                        <td class="amount-cell">
                                            {{ $row['account_code'] ?: '-' }}
                                        </td>

                                        <td class="account-cell">
                                            {{ $row['account_name'] ?? '-' }}
                                        </td>

                                        <td class="amount-cell fw-bold liabilities-amount">
                                            {{ number_format(abs((float) ($row['balance'] ?? 0)), 2) }}

                                            @if(($row['balance'] ?? 0) < 0)
                                                <small class="danger-amount">عكسي</small>
                                            @endif
                                        </td>

                                        <td class="no-print">
                                            @if(!empty($row['account_id']) && \Illuminate\Support\Facades\Route::has('account-ledger-reports.index'))
                                                <a href="{{ route('account-ledger-reports.index', [
                                                    'account_id' => $row['account_id'],
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
                                        <td colspan="5">
                                            <div class="empty-state">
                                                لا توجد أرصدة التزامات حسب الفلاتر المحددة.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="3">إجمالي الالتزامات</td>
                                    <td class="amount-cell liabilities-amount">
                                        {{ number_format($totalLiabilities, 2) }}
                                    </td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>


            {{-- Equity --}}
            <div class="card shadow-sm wazin-card report-card mb-4">

                <div class="card-header wazin-card-header equity-header">
                    <div>
                        <h5 class="mb-0 fw-bold">حقوق الملكية</h5>
                        <small>أرصدة حسابات حقوق الملكية وصافي ربح أو خسارة الفترة</small>
                    </div>

                    <span class="result-count equity-count">
                        {{ number_format($totalEquity, 2) }}
                    </span>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>كود الحساب</th>
                                    <th>اسم الحساب</th>
                                    <th>الرصيد</th>
                                    <th class="no-print">دفتر الأستاذ</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($equity as $index => $row)
                                    <tr>
                                        <td class="fw-bold">{{ $index + 1 }}</td>

                                        <td class="amount-cell">
                                            {{ $row['account_code'] ?: '-' }}
                                        </td>

                                        <td class="account-cell">
                                            {{ $row['account_name'] ?? '-' }}

                                            @if(!empty($row['is_net_income']))
                                                <small class="auto-note">(محسوب تلقائيًا)</small>
                                            @endif
                                        </td>

                                        <td class="amount-cell fw-bold equity-amount">
                                            {{ number_format(abs((float) ($row['balance'] ?? 0)), 2) }}

                                            @if(($row['balance'] ?? 0) < 0)
                                                <small class="danger-amount">مدين</small>
                                            @else
                                                <small class="success-amount">دائن</small>
                                            @endif
                                        </td>

                                        <td class="no-print">
                                            @if(empty($row['is_net_income']) && !empty($row['account_id']) && \Illuminate\Support\Facades\Route::has('account-ledger-reports.index'))
                                                <a href="{{ route('account-ledger-reports.index', [
                                                    'account_id' => $row['account_id'],
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
                                        <td colspan="5">
                                            <div class="empty-state">
                                                لا توجد أرصدة حقوق ملكية حسب الفلاتر المحددة.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="3">إجمالي حقوق الملكية</td>
                                    <td class="amount-cell equity-amount">
                                        {{ number_format($totalEquity, 2) }}
                                    </td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Final Summary --}}
    <div class="card shadow-sm wazin-card final-summary-card mt-2">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">ملخص الميزانية</h5>
                <small>مقارنة الأصول مع الالتزامات وحقوق الملكية</small>
            </div>
        </div>

        <div class="card-body">

            <div class="final-summary-table">

                <div class="final-row">
                    <span>إجمالي الأصول</span>
                    <strong class="assets-amount">{{ number_format($totalAssets, 2) }}</strong>
                </div>

                <div class="final-row">
                    <span>إجمالي الالتزامات</span>
                    <strong class="liabilities-amount">{{ number_format($totalLiabilities, 2) }}</strong>
                </div>

                <div class="final-row">
                    <span>إجمالي حقوق الملكية</span>
                    <strong class="equity-amount">{{ number_format($totalEquity, 2) }}</strong>
                </div>

                <div class="final-row">
                    <span>إجمالي الالتزامات وحقوق الملكية</span>
                    <strong>{{ number_format($totalLiabilitiesAndEquity, 2) }}</strong>
                </div>

                <div class="final-row final-balance-row">
                    <span>الفرق</span>

                    <strong class="{{ $isBalanced ? 'success-amount' : 'danger-amount' }}">
                        {{ number_format(abs($difference), 2) }}

                        @if($isBalanced)
                            <span class="badge bg-success">متوازنة</span>
                        @else
                            <span class="badge bg-danger">غير متوازنة</span>
                        @endif
                    </strong>
                </div>

                <div class="final-row">
                    <span>صافي ربح / خسارة الفترة غير المرحل</span>

                    <strong class="{{ $netIncome > 0 ? 'success-amount' : ($netIncome < 0 ? 'danger-amount' : '') }}">
                        {{ number_format(abs($netIncome), 2) }}

                        @if($netIncome > 0)
                            <span class="badge bg-success">ربح</span>
                        @elseif($netIncome < 0)
                            <span class="badge bg-danger">خسارة</span>
                        @else
                            <span class="badge bg-secondary">صفر</span>
                        @endif
                    </strong>
                </div>

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
        grid-template-columns: repeat(4, minmax(0, 1fr));
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
        font-size: 24px;
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

    .assets-card,
    .assets-header {
        border-right: 5px solid #2F6BFF;
    }

    .liabilities-card,
    .liabilities-header {
        border-right: 5px solid #E63B4A;
    }

    .equity-card,
    .equity-header {
        border-right: 5px solid #16A34A;
    }

    .difference-card {
        border-right: 5px solid #64748B;
    }

    .balanced-card {
        background: rgba(22, 163, 74, 0.05);
        border-right-color: #16A34A;
    }

    .unbalanced-card {
        background: rgba(230, 59, 74, 0.05);
        border-right-color: #E63B4A;
    }

    .assets-card strong,
    .assets-amount {
        color: #2F6BFF !important;
    }

    .liabilities-card strong,
    .liabilities-amount,
    .danger-amount,
    .balance-danger {
        color: #E63B4A !important;
    }

    .equity-card strong,
    .equity-amount,
    .success-amount,
    .balance-success {
        color: #16A34A !important;
    }

    .result-count {
        direction: ltr;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        font-size: 13px;
    }

    .assets-count {
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
    }

    .liabilities-count {
        background: rgba(230, 59, 74, 0.10);
        color: #E63B4A;
    }

    .equity-count {
        background: rgba(22, 163, 74, 0.10);
        color: #16A34A;
    }

    .wazin-alert {
        border-radius: 16px;
        border: 1px solid rgba(245, 158, 11, 0.35);
        font-weight: 800;
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
        min-width: 210px;
        line-height: 1.8;
        font-weight: 900;
    }

    .auto-note {
        display: block;
        color: #64748B;
        font-size: 11px;
        font-weight: 700;
        margin-top: 3px;
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

    .final-summary-card {
        margin-bottom: 20px;
    }

    .final-summary-table {
        max-width: 760px;
        margin-right: auto;
        margin-left: auto;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        overflow: hidden;
    }

    .final-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 18px;
        border-bottom: 1px solid #E5E7EB;
        font-weight: 900;
    }

    .final-row:last-child {
        border-bottom: 0;
    }

    .final-row span {
        color: #071633;
    }

    .final-row strong {
        direction: ltr;
        color: #071633;
        font-size: 18px;
        font-weight: 900;
    }

    .final-balance-row {
        background: #F8FAFC;
    }

    .badge {
        border-radius: 999px;
        padding: 7px 11px;
        font-weight: 900;
        margin-right: 6px;
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

        .final-row {
            flex-direction: column;
            align-items: flex-start;
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px !important;
        }

        .info-box,
        .summary-card,
        .final-summary-table {
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
            margin-bottom: 8px !important;
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

        .final-row {
            border-color: #000 !important;
            padding: 7px !important;
        }

        .final-row span,
        .final-row strong {
            color: #000 !important;
            font-size: 12px !important;
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