<x-app-layout>

@php
    $operating = $report['operating'] ?? collect();
    $investing = $report['investing'] ?? collect();
    $financing = $report['financing'] ?? collect();

    if (is_array($operating)) {
        $operating = collect($operating);
    }

    if (is_array($investing)) {
        $investing = collect($investing);
    }

    if (is_array($financing)) {
        $financing = collect($financing);
    }

    $openingBalance = (float) ($report['opening_balance'] ?? 0);
    $operatingNet = (float) ($report['operating_net'] ?? 0);
    $investingNet = (float) ($report['investing_net'] ?? 0);
    $financingNet = (float) ($report['financing_net'] ?? 0);
    $netCashFlow = (float) ($report['net_cash_flow'] ?? 0);
    $closingBalance = (float) ($report['closing_balance'] ?? 0);

    $totalCashIn = (float) ($report['total_cash_in'] ?? 0);
    $totalCashOut = (float) ($report['total_cash_out'] ?? 0);

    $periodFrom = request('date_from', $dateFrom ?? now()->startOfYear()->toDateString());
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
            <h3 class="page-title mb-1">قائمة التدفقات النقدية</h3>
            <p class="page-subtitle mb-0">
                عرض حركة النقدية والبنك خلال الفترة، مع تصنيف التدفقات إلى تشغيلية واستثمارية وتمويلية.
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
                <h5 class="mb-0 fw-bold">فلاتر قائمة التدفقات النقدية</h5>
                <small>حدد الفترة والفرع ومركز التكلفة لعرض التدفقات النقدية المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('cash-flow-reports.index') }}">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ $periodFrom }}">
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
                    <a href="{{ route('cash-flow-reports.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>
                </div>

            </form>

        </div>

    </div>


    {{-- Print Header --}}
    <div class="print-header text-center mb-4">
        <h3 class="fw-bold mb-1">قائمة التدفقات النقدية</h3>
        <div>الفترة: {{ $periodFrom }} إلى {{ $periodTo }}</div>
        <div>الفرع: {{ $selectedBranchName }}</div>
        <div>مركز التكلفة: {{ $selectedCostCenterName }}</div>
    </div>


    {{-- Filter Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات التقرير</h5>
                <small>الفترة والفلاتر المستخدمة في احتساب قائمة التدفقات النقدية</small>
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


    {{-- Main Summary --}}
    <div class="summary-grid mb-4">

        <div class="summary-card opening-card">
            <span>رصيد أول الفترة</span>
            <strong>{{ number_format($openingBalance, 2) }}</strong>
        </div>

        <div class="summary-card cash-in-card">
            <span>إجمالي الداخل</span>
            <strong>{{ number_format($totalCashIn, 2) }}</strong>
        </div>

        <div class="summary-card cash-out-card">
            <span>إجمالي الخارج</span>
            <strong>{{ number_format($totalCashOut, 2) }}</strong>
        </div>

        <div class="summary-card closing-card">
            <span>رصيد آخر الفترة</span>
            <strong>{{ number_format($closingBalance, 2) }}</strong>
        </div>

    </div>


    {{-- Cash Flow Summary --}}
    <div class="card shadow-sm wazin-card report-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">ملخص التدفقات النقدية</h5>
                <small>صافي التدفق النقدي حسب نوع النشاط</small>
            </div>

            <span class="result-count {{ $netCashFlow >= 0 ? 'positive-count' : 'negative-count' }}">
                {{ $netCashFlow >= 0 ? 'زيادة' : 'نقص' }}
                {{ number_format(abs($netCashFlow), 2) }}
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                    <thead>
                        <tr>
                            <th>البند</th>
                            <th>صافي التدفق</th>
                            <th>النتيجة</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="statement-cell">صافي التدفقات من الأنشطة التشغيلية</td>
                            <td class="amount-cell fw-bold {{ $operatingNet >= 0 ? 'success-amount' : 'danger-amount' }}">
                                {{ number_format(abs($operatingNet), 2) }}
                            </td>
                            <td>
                                @if($operatingNet >= 0)
                                    <span class="badge bg-success">داخل</span>
                                @else
                                    <span class="badge bg-danger">خارج</span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="statement-cell">صافي التدفقات من الأنشطة الاستثمارية</td>
                            <td class="amount-cell fw-bold {{ $investingNet >= 0 ? 'success-amount' : 'danger-amount' }}">
                                {{ number_format(abs($investingNet), 2) }}
                            </td>
                            <td>
                                @if($investingNet >= 0)
                                    <span class="badge bg-success">داخل</span>
                                @else
                                    <span class="badge bg-danger">خارج</span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="statement-cell">صافي التدفقات من الأنشطة التمويلية</td>
                            <td class="amount-cell fw-bold {{ $financingNet >= 0 ? 'success-amount' : 'danger-amount' }}">
                                {{ number_format(abs($financingNet), 2) }}
                            </td>
                            <td>
                                @if($financingNet >= 0)
                                    <span class="badge bg-success">داخل</span>
                                @else
                                    <span class="badge bg-danger">خارج</span>
                                @endif
                            </td>
                        </tr>

                        <tr class="table-total-row">
                            <td class="statement-cell">صافي التغير في النقدية والبنك</td>
                            <td class="amount-cell fw-bold {{ $netCashFlow >= 0 ? 'success-amount' : 'danger-amount' }}">
                                {{ number_format(abs($netCashFlow), 2) }}
                            </td>
                            <td>
                                @if($netCashFlow >= 0)
                                    زيادة
                                @else
                                    نقص
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="statement-cell">رصيد النقدية والبنك أول الفترة</td>
                            <td class="amount-cell fw-bold">
                                {{ number_format($openingBalance, 2) }}
                            </td>
                            <td>-</td>
                        </tr>

                        <tr class="table-total-row">
                            <td class="statement-cell">رصيد النقدية والبنك آخر الفترة</td>
                            <td class="amount-cell fw-bold">
                                {{ number_format($closingBalance, 2) }}
                            </td>
                            <td>-</td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- Details Sections --}}
    <div class="cash-flow-sections">

        @include('cash-flow-reports.partials.section', [
            'title' => 'الأنشطة التشغيلية',
            'rows' => $operating,
            'net' => $operatingNet,
        ])

        @include('cash-flow-reports.partials.section', [
            'title' => 'الأنشطة الاستثمارية',
            'rows' => $investing,
            'net' => $investingNet,
        ])

        @include('cash-flow-reports.partials.section', [
            'title' => 'الأنشطة التمويلية',
            'rows' => $financing,
            'net' => $financingNet,
        ])

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

    .info-grid,
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

    .opening-card {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
    }

    .opening-card strong {
        color: #2F6BFF;
    }

    .cash-in-card {
        border-right: 5px solid #16A34A;
        background: rgba(22, 163, 74, 0.05);
    }

    .cash-in-card strong,
    .success-amount {
        color: #16A34A !important;
    }

    .cash-out-card {
        border-right: 5px solid #E63B4A;
        background: rgba(230, 59, 74, 0.05);
    }

    .cash-out-card strong,
    .danger-amount {
        color: #E63B4A !important;
    }

    .closing-card {
        border-right: 5px solid #071633;
        background: rgba(7, 22, 51, 0.04);
    }

    .closing-card strong {
        color: #071633;
    }

    .result-count {
        direction: ltr;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        font-size: 13px;
    }

    .positive-count {
        background: rgba(22, 163, 74, 0.10);
        color: #16A34A;
    }

    .negative-count {
        background: rgba(230, 59, 74, 0.10);
        color: #E63B4A;
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

    .wazin-table tfoot td,
    .table-total-row td {
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

    .statement-cell {
        text-align: right !important;
        min-width: 280px;
        line-height: 1.8;
        font-weight: 900;
    }

    .cash-flow-sections > .card,
    .cash-flow-sections .card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04) !important;
        margin-bottom: 18px;
    }

    .cash-flow-sections .card-header {
        background: #fff !important;
        color: #071633 !important;
        border-bottom: 1px solid #E5E7EB;
        padding: 16px 18px;
        font-weight: 900;
    }

    .cash-flow-sections .card-header h6,
    .cash-flow-sections .card-header h5 {
        color: #071633 !important;
        font-weight: 900;
        margin: 0;
    }

    .cash-flow-sections table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    .cash-flow-sections table td {
        vertical-align: middle;
        font-weight: 600;
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
            size: A4 portrait;
            margin: 9mm;
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

        .wazin-card,
        .cash-flow-sections .card {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            margin-bottom: 8px !important;
        }

        .wazin-card-header,
        .cash-flow-sections .card-header {
            background: #f2f2f2 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            padding: 8px !important;
        }

        .wazin-card-header h5,
        .wazin-card-header small,
        .cash-flow-sections .card-header h5,
        .cash-flow-sections .card-header h6 {
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
        .wazin-table tfoot td,
        .cash-flow-sections table thead th {
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