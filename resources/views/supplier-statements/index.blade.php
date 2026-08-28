<x-app-layout>

@php
    $periodFrom = request('date_from') ?: 'البداية';
    $periodTo = request('date_to', $dateTo ?? date('Y-m-d'));
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">كشف حساب مورد</h3>
            <p class="page-subtitle mb-0">
                عرض حركة حساب المورد من فواتير الشراء وسندات الصرف والمردودات والقيود مع الرصيد الافتتاحي والنهائي.
            </p>
        </div>

        @if($statement)
            <button type="button" onclick="window.print()" class="btn btn-dark">
                طباعة الكشف
            </button>
        @endif
    </div>


    {{-- Filters --}}
    <div class="card shadow-sm wazin-card mb-4 no-print">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">فلاتر كشف الحساب</h5>
                <small>اختر المورد وحدد الفترة والفرع ومركز التكلفة لعرض الحركات المطلوبة</small>
            </div>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('supplier-statements.index') }}">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">المورد <span class="text-danger">*</span></label>

                        <select name="supplier_id" class="form-select" required>
                            <option value="">اختر المورد</option>

                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name
                                        ?? $supplier->name
                                        ?? 'مورد رقم ' . $supplier->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ request('date_to', $dateTo ?? date('Y-m-d')) }}">
                    </div>

                    <div class="col-md-2">
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

                    <div class="col-md-2">
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

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            عرض
                        </button>
                    </div>

                </div>

                <div class="filter-actions mt-3">
                    <a href="{{ route('supplier-statements.index') }}" class="btn btn-outline-secondary">
                        إعادة تعيين
                    </a>

                    @if($statement)
                        <button type="button" onclick="window.print()" class="btn btn-dark">
                            طباعة
                        </button>
                    @endif
                </div>

            </form>

        </div>

    </div>


    @if($statement)

        @php
            $supplier = $statement['supplier'];
            $movements = $statement['movements'] ?? collect();

            if (is_array($movements)) {
                $movements = collect($movements);
            }

            $openingBalance = (float) ($statement['opening_balance'] ?? 0);
            $totalDebit = (float) ($statement['total_debit'] ?? 0);
            $totalCredit = (float) ($statement['total_credit'] ?? 0);
            $closingBalance = (float) ($statement['closing_balance'] ?? $statement['balance'] ?? 0);

            $supplierName = $supplier->supplier_name
                ?? $supplier->name
                ?? 'مورد رقم ' . $supplier->id;

            $supplierPhone = $supplier->mobile
                ?? $supplier->phone
                ?? '-';

            $supplierTax = $supplier->tax_registration_number
                ?? $supplier->tax_number
                ?? $supplier->vat_number
                ?? '-';

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

            $typeLabels = [
                'purchase_invoice' => 'فاتورة شراء',
                'purchase_invoice_payment' => 'دفع مباشر لفاتورة شراء',
                'supplier_payment_voucher' => 'سند صرف مورد',
                'purchase_return' => 'مردود مشتريات',

                'opening_balance' => 'رصيد افتتاحي',
                'manual_journal_entry' => 'قيد يومية يدوي',

                'general_receipt_voucher' => 'سند قبض عام',
                'general_payment_voucher' => 'سند صرف عام',

                'journal_entry' => 'قيد يومية',
            ];
        @endphp


        {{-- Print Header --}}
        <div class="print-header text-center mb-4">
            <h3 class="fw-bold mb-1">كشف حساب مورد</h3>
            <div>المورد: {{ $supplierName }}</div>
            <div>الفترة: {{ $periodFrom }} إلى {{ $periodTo }}</div>
            <div>الفرع: {{ $selectedBranchName }}</div>
            <div>مركز التكلفة: {{ $selectedCostCenterName }}</div>
        </div>


        {{-- Supplier Info --}}
        <div class="card shadow-sm wazin-card mb-4">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات المورد</h5>
                    <small>معلومات المورد والفلاتر المستخدمة في كشف الحساب</small>
                </div>
            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info-box">
                        <span>اسم المورد</span>
                        <strong>{{ $supplierName }}</strong>
                    </div>

                    <div class="info-box">
                        <span>الجوال</span>
                        <strong class="ltr-text">{{ $supplierPhone }}</strong>
                    </div>

                    <div class="info-box">
                        <span>الرقم الضريبي</span>
                        <strong class="ltr-text">{{ $supplierTax }}</strong>
                    </div>

                    <div class="info-box">
                        <span>الفلاتر</span>
                        <strong>
                            {{ $selectedBranchName }}
                            /
                            {{ $selectedCostCenterName }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        {{-- Summary --}}
        <div class="summary-grid mb-4">

            <div class="summary-card opening-card">
                <span>الرصيد الافتتاحي</span>
                <strong>
                    {{ number_format(abs($openingBalance), 2) }}
                </strong>

                @if($openingBalance > 0)
                    <small class="balance-credit">دائن</small>
                @elseif($openingBalance < 0)
                    <small class="balance-debit">مدين</small>
                @else
                    <small class="balance-neutral">متوازن</small>
                @endif
            </div>

            <div class="summary-card debit-card">
                <span>إجمالي المدين</span>
                <strong>{{ number_format($totalDebit, 2) }}</strong>
            </div>

            <div class="summary-card credit-card">
                <span>إجمالي الدائن</span>
                <strong>{{ number_format($totalCredit, 2) }}</strong>
            </div>

            <div class="summary-card closing-card">
                <span>الرصيد النهائي</span>
                <strong class="{{ $closingBalance > 0 ? 'credit-amount' : ($closingBalance < 0 ? 'debit-amount' : '') }}">
                    {{ number_format(abs($closingBalance), 2) }}
                </strong>

                @if($closingBalance > 0)
                    <small class="balance-credit">دائن</small>
                @elseif($closingBalance < 0)
                    <small class="balance-debit">مدين</small>
                @else
                    <small class="balance-neutral">متوازن</small>
                @endif
            </div>

        </div>


        {{-- Movements --}}
        <div class="card shadow-sm wazin-card report-card">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">حركات حساب المورد</h5>
                    <small>تفاصيل الحركة المالية للمورد حسب الفترة والفلاتر المحددة</small>
                </div>

                <span class="result-count no-print">
                    عدد الحركات: {{ $movements->count() }}
                </span>
            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 wazin-table">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>نوع المستند</th>
                                <th>رقم المستند</th>
                                <th>البيان</th>
                                <th>مدين</th>
                                <th>دائن</th>
                                <th>الرصيد</th>
                                <th>طبيعة الرصيد</th>
                                <th class="no-print">الإجراءات</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($movements as $index => $movement)

                                @php
                                    $type = $movement['type']
                                        ?? $movement['document_type']
                                        ?? 'journal_entry';

                                    $typeLabel = $movement['document_type_label']
                                        ?? ($typeLabels[$type] ?? $type);

                                    $balance = (float) ($movement['balance'] ?? 0);
                                    $referenceId = $movement['reference_id'] ?? null;

                                    $showRoute = null;

                                    if ($type === 'purchase_invoice' && $referenceId && \Illuminate\Support\Facades\Route::has('purchase-invoices.show')) {
                                        $showRoute = route('purchase-invoices.show', $referenceId);
                                    } elseif ($type === 'supplier_payment_voucher' && $referenceId && \Illuminate\Support\Facades\Route::has('supplier-payment-vouchers.show')) {
                                        $showRoute = route('supplier-payment-vouchers.show', $referenceId);
                                    } elseif ($type === 'purchase_return' && $referenceId && \Illuminate\Support\Facades\Route::has('purchase-returns.show')) {
                                        $showRoute = route('purchase-returns.show', $referenceId);
                                    } elseif ($type === 'manual_journal_entry' && $referenceId && \Illuminate\Support\Facades\Route::has('manual-journal-entries.show')) {
                                        $showRoute = route('manual-journal-entries.show', $referenceId);
                                    } elseif ($type === 'general_receipt_voucher' && $referenceId && \Illuminate\Support\Facades\Route::has('general-receipt-vouchers.show')) {
                                        $showRoute = route('general-receipt-vouchers.show', $referenceId);
                                    } elseif ($type === 'general_payment_voucher' && $referenceId && \Illuminate\Support\Facades\Route::has('general-payment-vouchers.show')) {
                                        $showRoute = route('general-payment-vouchers.show', $referenceId);
                                    }
                                @endphp

                                <tr>
                                    <td class="fw-bold">{{ $index + 1 }}</td>

                                    <td class="amount-cell">
                                        {{ $movement['date'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $typeLabel }}
                                    </td>

                                    <td class="amount-cell">
                                        {{ $movement['document_no'] ?? '-' }}
                                    </td>

                                    <td class="description-cell">
                                        {{ $movement['description'] ?? '-' }}
                                    </td>

                                    <td class="amount-cell debit-amount">
                                        {{ number_format((float) ($movement['debit'] ?? 0), 2) }}
                                    </td>

                                    <td class="amount-cell credit-amount">
                                        {{ number_format((float) ($movement['credit'] ?? 0), 2) }}
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
                                        @if($showRoute)
                                            <a href="{{ $showRoute }}" class="btn btn-primary btn-sm">
                                                عرض
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            لا توجد حركات لهذا المورد حسب الفلاتر المحددة.
                                        </div>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="5">الإجمالي</td>

                                <td class="amount-cell debit-amount">
                                    {{ number_format($totalDebit, 2) }}
                                </td>

                                <td class="amount-cell credit-amount">
                                    {{ number_format($totalCredit, 2) }}
                                </td>

                                <td class="amount-cell {{ $closingBalance > 0 ? 'credit-amount' : ($closingBalance < 0 ? 'debit-amount' : '') }}">
                                    {{ number_format(abs($closingBalance), 2) }}
                                </td>

                                <td>
                                    @if($closingBalance > 0)
                                        دائن
                                    @elseif($closingBalance < 0)
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

    @else

        <div class="empty-page-card">
            <div class="empty-icon">📄</div>
            <h5>لم يتم عرض كشف الحساب بعد</h5>
            <p>اختر موردًا ثم اضغط عرض لعرض كشف الحساب.</p>
        </div>

    @endif

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

    .summary-card small {
        display: inline-block;
        border-radius: 999px;
        padding: 5px 11px;
        font-weight: 900;
        font-size: 12px;
    }

    .opening-card {
        border-right: 5px solid #64748B;
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

    .closing-card {
        border-right: 5px solid #2F6BFF;
        background: rgba(47, 107, 255, 0.05);
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

    .description-cell {
        text-align: right !important;
        min-width: 260px;
        line-height: 1.8;
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

    .empty-page-card {
        background: #fff;
        border: 1px dashed #CBD5E1;
        border-radius: 24px;
        padding: 50px 20px;
        text-align: center;
        color: #64748B;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .empty-page-card h5 {
        color: #071633;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .empty-page-card p {
        margin: 0;
        font-weight: 700;
    }

    .empty-icon {
        font-size: 42px;
        margin-bottom: 12px;
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