<x-app-layout>
<style>
    .print-header {
        display: none;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        body {
            background: #fff !important;
            color: #000 !important;
            font-size: 11px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        nav,
        aside,
        header,
        .navbar,
        .sidebar,
        .app-sidebar,
        .main-sidebar,
        .no-print,
        .btn,
        form,
        .alert {
            display: none !important;
        }

        .app-content,
        .content-wrapper,
        .main-content,
        .page-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #111827;
        }

        .print-header .brand {
            font-size: 18px;
            font-weight: 900;
            color: #071633;
        }

        .print-header .title {
            font-size: 15px;
            font-weight: 900;
            margin-top: 4px;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #111827 !important;
            margin-bottom: 8px !important;
            page-break-inside: avoid;
        }

        .card-header {
            background: #071633 !important;
            color: #fff !important;
            padding: 6px 8px !important;
            font-size: 12px !important;
            font-weight: 900 !important;
        }

        .card-body {
            padding: 8px !important;
        }

        .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .col-md-3 {
            width: 25% !important;
            flex: 0 0 25% !important;
            margin-bottom: 6px !important;
        }

        .col-md-4 {
            width: 33.333% !important;
            flex: 0 0 33.333% !important;
        }

        .col-md-12 {
            width: 100% !important;
            flex: 0 0 100% !important;
        }

        label {
            font-size: 10px !important;
            margin-bottom: 2px !important;
        }

        .border.rounded.p-2.bg-light {
            padding: 5px !important;
            min-height: 27px !important;
            border: 1px solid #CBD5E1 !important;
            background: #F8FAFC !important;
            font-size: 11px !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10.5px !important;
        }

        table th,
        table td {
            border: 1px solid #111827 !important;
            padding: 5px !important;
            vertical-align: middle !important;
        }

        thead.table-dark,
        .table-dark {
            background: #071633 !important;
            color: #fff !important;
        }

        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: #F8FAFC !important;
        }

        .badge {
            border: 1px solid #111827 !important;
            color: #000 !important;
            background: #fff !important;
            padding: 3px 7px !important;
            font-size: 10px !important;
        }

        .text-danger,
        .text-success {
            color: #000 !important;
        }

        h4 {
            display: none !important;
        }
    }
</style>
<div class="container-fluid py-4" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            الأرصدة الافتتاحية: {{ $openingBalance->opening_no }}
        </h4>

        <a href="{{ route('opening-balances.index') }}" class="btn btn-secondary no-print">
            رجوع
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $totalDebit = (float) $openingBalance->lines->sum('debit');
        $totalCredit = (float) $openingBalance->lines->sum('credit');
        $difference = $totalDebit - $totalCredit;

        $typeLabels = [
            'account' => 'حساب',
            'customer' => 'عميل',
            'supplier' => 'مورد',
        ];

        $defaultBranchName = $openingBalance->branch?->branch_name_ar
            ?? $openingBalance->branch?->branch_name
            ?? $openingBalance->branch?->name
            ?? '-';
    @endphp

    <div class="print-header">
        <div class="brand">وازن ERP</div>
        <div class="title">
            الأرصدة الافتتاحية: {{ $openingBalance->opening_no }}
        </div>
        <div>
            التاريخ: {{ optional($openingBalance->opening_date)->format('Y-m-d') }}
            |
            الفرع: {{ $defaultBranchName }}
        </div>
    </div>
    
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>بيانات المستند</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="fw-bold text-muted">رقم المستند</label>
                    <div class="border rounded p-2 bg-light">
                        {{ $openingBalance->opening_no }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">التاريخ</label>
                    <div class="border rounded p-2 bg-light">
                        {{ optional($openingBalance->opening_date)->format('Y-m-d') }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">الفرع الافتراضي</label>
                    <div class="border rounded p-2 bg-light">
                        {{ $defaultBranchName }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">الحالة</label>
                    <div class="border rounded p-2 bg-light">
                        @if($openingBalance->status === 'draft')
                            <span class="badge bg-secondary">مسودة</span>
                        @elseif($openingBalance->status === 'posted')
                            <span class="badge bg-success">مرحل</span>
                        @elseif($openingBalance->status === 'cancelled')
                            <span class="badge bg-danger">ملغي</span>
                        @else
                            {{ $openingBalance->status }}
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">أنشئ بواسطة</label>
                    <div class="border rounded p-2 bg-light">
                        {{ $openingBalance->creator?->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">رحّل بواسطة</label>
                    <div class="border rounded p-2 bg-light">
                        {{ $openingBalance->poster?->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">تاريخ الترحيل</label>
                    <div class="border rounded p-2 bg-light">
                        {{ optional($openingBalance->posted_at)->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold text-muted">تاريخ الإلغاء</label>
                    <div class="border rounded p-2 bg-light">
                        {{ optional($openingBalance->cancelled_at)->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="fw-bold text-muted">ملاحظات</label>
                    <div class="border rounded p-2 bg-light">
                        {{ $openingBalance->notes ?: '-' }}
                    </div>
                </div>

                @if($openingBalance->status === 'cancelled')
                    <div class="col-md-12">
                        <label class="fw-bold text-muted">سبب الإلغاء</label>
                        <div class="border rounded p-2 bg-light text-danger">
                            {{ $openingBalance->cancellation_reason ?: '-' }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>


    <div class="row g-3 mb-3">

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted">إجمالي المدين</div>
                    <div class="fs-5 fw-bold text-danger">
                        {{ number_format($totalDebit, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted">إجمالي الدائن</div>
                    <div class="fs-5 fw-bold text-success">
                        {{ number_format($totalCredit, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted">الفرق</div>
                    <div class="fs-5 fw-bold">
                        {{ number_format(abs($difference), 2) }}

                        @if($difference > 0)
                            <small class="text-success">سيضاف دائن على أرصدة افتتاحية</small>
                        @elseif($difference < 0)
                            <small class="text-danger">سيضاف مدين على أرصدة افتتاحية</small>
                        @else
                            <small class="text-muted">متوازن</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>


    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>سطور الأرصدة الافتتاحية</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>الفرع</th>
                            <th>مركز التكلفة</th>
                            <th>النوع</th>
                            <th>الحساب / العميل / المورد</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>البيان</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($openingBalance->lines as $index => $line)

                            @php
                                $targetName = '-';

                                if ($line->line_type === 'account') {
                                    $code = $line->account?->account_code ?? $line->account?->code ?? '';
                                    $name = $line->account?->account_name_ar
                                        ?? $line->account?->account_name
                                        ?? $line->account?->name
                                        ?? '-';

                                    $targetName = trim($code . ' - ' . $name, ' -');
                                }

                                if ($line->line_type === 'customer') {
                                    $targetName = $line->customer?->customer_name
                                        ?? $line->customer?->name
                                        ?? '-';
                                }

                                if ($line->line_type === 'supplier') {
                                    $targetName = $line->supplier?->supplier_name
                                        ?? $line->supplier?->name
                                        ?? '-';
                                }

                                $lineBranchName = $line->branch?->branch_name_ar
                                    ?? $line->branch?->branch_name
                                    ?? $line->branch?->name
                                    ?? $defaultBranchName;

                                $costCenterName = '-';

                                if ($line->costCenter) {
                                    $costCenterCode = $line->costCenter->code ?? '';
                                    $costCenterTitle = $line->costCenter->name ?? ('مركز تكلفة رقم ' . $line->costCenter->id);
                                    $costCenterName = trim($costCenterCode . ' - ' . $costCenterTitle, ' -');
                                }
                            @endphp

                            <tr>
                                <td>{{ $index + 1 }}</td>

                                <td>{{ $lineBranchName ?: '-' }}</td>

                                <td>{{ $costCenterName }}</td>

                                <td>{{ $typeLabels[$line->line_type] ?? $line->line_type }}</td>

                                <td class="text-start">{{ $targetName }}</td>

                                <td class="text-end text-danger">
                                    {{ number_format((float) $line->debit, 2) }}
                                </td>

                                <td class="text-end text-success">
                                    {{ number_format((float) $line->credit, 2) }}
                                </td>

                                <td class="text-start">
                                    {{ $line->description ?: '-' }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-muted">
                                    لا توجد سطور.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5">الإجمالي</td>

                            <td class="text-end text-danger">
                                {{ number_format($totalDebit, 2) }}
                            </td>

                            <td class="text-end text-success">
                                {{ number_format($totalCredit, 2) }}
                            </td>

                            <td></td>
                        </tr>
                    </tfoot>
                </table>

            </div>
        </div>
    </div>


    <div class="d-flex gap-2 flex-wrap no-print">

        @if($openingBalance->status === 'draft')
            @can('opening_balances.post')
                <form method="POST"
                      action="{{ route('opening-balances.post', $openingBalance) }}"
                      onsubmit="return confirm('هل أنت متأكد من ترحيل الأرصدة الافتتاحية؟ سيتم إنشاء قيد محاسبي.');">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        ترحيل وإنشاء القيد
                    </button>
                </form>
            @endcan
        @endif

        @if($openingBalance->status === 'posted')
            @can('opening_balances.cancel')
                <form method="POST"
                      action="{{ route('opening-balances.cancel', $openingBalance) }}"
                      onsubmit="return confirm('هل أنت متأكد من إلغاء المستند؟ سيتم عكس القيد المحاسبي.');">
                    @csrf

                    <input type="hidden"
                           name="cancellation_reason"
                           value="إلغاء من شاشة الأرصدة الافتتاحية">

                    <button type="submit" class="btn btn-danger">
                        إلغاء وعكس القيد
                    </button>
                </form>
            @endcan
        @endif

        @can('opening_balances.print')
            <button type="button" onclick="window.print()" class="btn btn-dark">
                طباعة
            </button>
        @endcan

    </div>

</div>

</x-app-layout>