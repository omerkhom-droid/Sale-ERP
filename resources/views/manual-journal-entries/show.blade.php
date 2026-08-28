<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">عرض قيد يومية</h3>
            <p class="page-subtitle mb-0">
                مراجعة بيانات القيد اليدوي والسطور والإجماليات وحالة الترحيل أو الإلغاء.
            </p>
        </div>
        <div class="d-flex justify-content-center gap-1 flex-wrap">
            <a href="{{ route('manual-journal-entries.print', $manualJournalEntry) }}"
               target="_blank"
               class="btn btn-primary">
                طباعة
            </a>
            <a href="{{ route('manual-journal-entries.index') }}" class="btn btn-secondary">
                رجوع
            </a>
        </div>
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


    {{-- Document Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">بيانات المستند</h5>
                <small>البيانات الأساسية للقيد وحالته الحالية</small>
            </div>

            <div>
                @if($manualJournalEntry->status === 'draft')
                    <span class="badge bg-secondary">مسودة</span>
                @elseif($manualJournalEntry->status === 'posted')
                    <span class="badge bg-success">مرحل</span>
                @elseif($manualJournalEntry->status === 'cancelled')
                    <span class="badge bg-danger">ملغى</span>
                @else
                    <span class="badge bg-light text-dark">{{ $manualJournalEntry->status }}</span>
                @endif
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">رقم القيد</label>
                    <div class="readonly-box ltr-cell">
                        {{ $manualJournalEntry->manual_no }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ القيد</label>
                    <div class="readonly-box ltr-cell">
                        {{ optional($manualJournalEntry->manual_date)->format('Y-m-d') }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الفرع الافتراضي</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->branch->branch_name_ar
                            ?? $manualJournalEntry->branch->branch_name
                            ?? $manualJournalEntry->branch->name
                            ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">عدد السطور</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->lines->count() }}
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->notes ?? '-' }}
                    </div>
                </div>

            </div>

        </div>

    </div>


    {{-- Lines --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">سطور القيد</h5>
                <small>تفاصيل الأطراف والحسابات ومراكز التكلفة والمبالغ</small>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle wazin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th style="width: 160px;">الفرع</th>
                            <th style="width: 180px;">مركز التكلفة</th>
                            <th style="width: 135px;">نوع السطر</th>
                            <th style="min-width: 280px;">الحساب / العميل / المورد</th>
                            <th style="width: 130px;">مدين</th>
                            <th style="width: 130px;">دائن</th>
                            <th style="min-width: 220px;">البيان</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($manualJournalEntry->lines as $line)
                            <tr>
                                <td class="fw-bold">{{ $loop->iteration }}</td>

                                <td>
                                    {{ $line->branch->branch_name_ar
                                        ?? $line->branch->branch_name
                                        ?? $line->branch->name
                                        ?? '-' }}
                                </td>

                                <td>
                                    @if($line->costCenter)
                                        {{ $line->costCenter->code ? $line->costCenter->code . ' - ' : '' }}
                                        {{ $line->costCenter->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if($line->line_type === 'account')
                                        <span class="line-type-badge">حساب</span>
                                    @elseif($line->line_type === 'customer')
                                        <span class="line-type-badge">عميل</span>
                                    @elseif($line->line_type === 'supplier')
                                        <span class="line-type-badge">مورد</span>
                                    @else
                                        <span class="line-type-badge">{{ $line->line_type }}</span>
                                    @endif
                                </td>

                                <td class="text-start">
                                    @if($line->line_type === 'account')
                                        <span class="target-name">
                                            {{ $line->account->account_code ?? $line->account->code ?? '' }}
                                            -
                                            {{ $line->account->account_name_ar
                                                ?? $line->account->account_name
                                                ?? $line->account->name
                                                ?? '-' }}
                                        </span>
                                    @elseif($line->line_type === 'customer')
                                        <span class="target-name">
                                            {{ $line->customer->customer_code ?? $line->customer->code ?? '' }}
                                            -
                                            {{ $line->customer->customer_name
                                                ?? $line->customer->customer_name_ar
                                                ?? $line->customer->name
                                                ?? '-' }}
                                        </span>
                                    @elseif($line->line_type === 'supplier')
                                        <span class="target-name">
                                            {{ $line->supplier->supplier_code ?? $line->supplier->code ?? '' }}
                                            -
                                            {{ $line->supplier->supplier_name
                                                ?? $line->supplier->supplier_name_ar
                                                ?? $line->supplier->name
                                                ?? '-' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="amount-cell text-success">
                                    {{ number_format((float) $line->debit, 2) }}
                                </td>

                                <td class="amount-cell text-danger">
                                    {{ number_format((float) $line->credit, 2) }}
                                </td>

                                <td class="text-start">
                                    {{ $line->description ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        لا توجد سطور.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @php
                        $totalDebit = round((float) $manualJournalEntry->lines->sum('debit'), 2);
                        $totalCredit = round((float) $manualJournalEntry->lines->sum('credit'), 2);
                        $difference = round($totalDebit - $totalCredit, 2);
                    @endphp

                    <tfoot>
                        <tr>
                            <td colspan="5">الإجمالي</td>

                            <td class="amount-cell text-success">
                                {{ number_format($totalDebit, 2) }}
                            </td>

                            <td class="amount-cell text-danger">
                                {{ number_format($totalCredit, 2) }}
                            </td>

                            <td>
                                الفرق:
                                <span class="amount-cell {{ $difference == 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($difference, 2) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>


            @if($manualJournalEntry->status === 'draft')
                <div class="journal-status-note warning-note mt-4">
                    <strong>تنبيه:</strong>
                    هذا القيد ما زال مسودة، ولن يظهر في التقارير المحاسبية إلا بعد الترحيل.
                </div>
            @elseif($manualJournalEntry->status === 'posted')
                <div class="journal-status-note success-note mt-4">
                    <strong>تم الترحيل:</strong>
                    تم إنشاء القيد المحاسبي في دفتر القيود.
                </div>
            @elseif($manualJournalEntry->status === 'cancelled')
                <div class="journal-status-note danger-note mt-4">
                    <strong>تم الإلغاء:</strong>
                    تم إلغاء هذا القيد وعكس أثره المحاسبي.
                </div>
            @endif

        </div>

    </div>


    {{-- System Info --}}
    <div class="card shadow-sm wazin-card mb-4">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">معلومات النظام</h5>
                <small>بيانات الإنشاء والترحيل والإلغاء</small>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">أنشئ بواسطة</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->creator->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">رحل بواسطة</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->poster->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ألغي بواسطة</label>
                    <div class="readonly-box">
                        {{ $manualJournalEntry->canceller->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">تاريخ الإنشاء</label>
                    <div class="readonly-box ltr-cell">
                        {{ optional($manualJournalEntry->created_at)->format('Y-m-d H:i') }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">تاريخ الترحيل</label>
                    <div class="readonly-box ltr-cell">
                        {{ optional($manualJournalEntry->posted_at)->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">تاريخ الإلغاء</label>
                    <div class="readonly-box ltr-cell">
                        {{ optional($manualJournalEntry->cancelled_at)->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>

                @if($manualJournalEntry->cancellation_reason)
                    <div class="col-md-12">
                        <label class="form-label">سبب الإلغاء</label>
                        <div class="readonly-box">
                            {{ $manualJournalEntry->cancellation_reason }}
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>


    {{-- Actions --}}
    <div class="save-actions mb-5">

        @if($manualJournalEntry->status === 'draft')
            @can('manual_journal_entries.post')
                <form method="POST" action="{{ route('manual-journal-entries.post', $manualJournalEntry) }}">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        ترحيل القيد
                    </button>
                </form>
            @endcan
        @endif

        @if($manualJournalEntry->status === 'posted')
            @can('manual_journal_entries.cancel')
                <form method="POST"
                      action="{{ route('manual-journal-entries.cancel', $manualJournalEntry) }}"
                      class="cancel-form">
                    @csrf

                    <input type="text"
                           name="cancellation_reason"
                           class="form-control"
                           placeholder="سبب الإلغاء">

                    <button type="submit" class="btn btn-danger">
                        إلغاء القيد
                    </button>
                </form>
            @endcan
        @endif

        <a href="{{ route('manual-journal-entries.index') }}" class="btn btn-secondary">
            رجوع
        </a>

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

    .readonly-box {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        background: #fff;
        padding: 10px 14px;
        color: #071633;
        font-weight: 800;
        line-height: 1.7;
    }

    .ltr-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
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

    .wazin-table tfoot td {
        background: #F1F5F9 !important;
        color: #071633;
        font-weight: 900;
        border-top: 2px solid #E5E7EB;
        vertical-align: middle;
    }

    .amount-cell {
        direction: ltr;
        text-align: center;
        font-weight: 900;
        display: table-cell;
    }

    span.amount-cell {
        display: inline-block;
    }

    .target-name {
        color: #071633;
        font-weight: 900;
        line-height: 1.8;
    }

    .line-type-badge {
        display: inline-block;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        border: 1px solid rgba(47, 107, 255, 0.18);
        border-radius: 999px;
        padding: 7px 12px;
        font-weight: 900;
        font-size: 12px;
    }

    .journal-status-note {
        border-radius: 16px;
        padding: 14px 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .warning-note {
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FED7AA;
    }

    .success-note {
        background: #F0FDF4;
        color: #15803D;
        border: 1px solid #BBF7D0;
    }

    .danger-note {
        background: #FEF2F2;
        color: #B91C1C;
        border: 1px solid #FECACA;
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
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-success {
        background: #16A34A !important;
        border-color: #16A34A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .form-control {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
    }

    .form-control:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .save-actions {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        box-shadow: 0 12px 32px rgba(7, 22, 51, 0.08);
        position: sticky;
        bottom: 18px;
        z-index: 20;
    }

    .cancel-form {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cancel-form .form-control {
        width: 280px;
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

        .save-actions {
            flex-direction: column;
            align-items: stretch;
            position: static;
        }

        .save-actions .btn,
        .save-actions form {
            width: 100%;
        }

        .cancel-form {
            flex-direction: column;
            align-items: stretch;
        }

        .cancel-form .form-control {
            width: 100%;
        }
    }
</style>

</x-app-layout>