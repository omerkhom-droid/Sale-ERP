<x-app-layout>

<div class="container-fluid py-4 wazin-debit-notes" dir="rtl">

    {{-- عنوان الصفحة --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الإشعارات المدينة</h3>

            <p class="page-subtitle mb-0">
                إدارة الإشعارات المدينة المرتبطة بفواتير البيع ومتابعة حالتها.
            </p>
        </div>

        @can('sales_debit_notes.create')
            <a href="{{ route('sales-debit-notes.create') }}"
               class="btn btn-primary">
                + إشعار مدين جديد
            </a>
        @endcan
    </div>

    @include('sales-debit-notes._alerts')

    @if(session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- قائمة الإشعارات --}}
    <div class="card shadow-sm wazin-card">

        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة الإشعارات المدينة</h5>
                <small>
                    عرض الإشعارات حسب الرقم والتاريخ والفاتورة والعميل والحالة
                </small>
            </div>

            <span class="records-count">
                {{ number_format($notes->total()) }} سجل
            </span>
        </div>

        <div class="card-body">

            {{-- البحث --}}
            <form method="GET"
                  action="{{ route('sales-debit-notes.index') }}"
                  class="search-toolbar mb-4">

                <div class="search-field">
                    <label for="debit-search">بحث:</label>

                    <input id="debit-search"
                           type="search"
                           name="q"
                           class="form-control"
                           value="{{ request('q') }}"
                           maxlength="100"
                           placeholder="رقم الإشعار أو الفاتورة أو اسم العميل">
                </div>

                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">
                        بحث
                    </button>

                    @if(request()->filled('q'))
                        <a href="{{ route('sales-debit-notes.index') }}"
                           class="btn btn-outline-secondary">
                            مسح البحث
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover
                              text-center align-middle w-100"
                       id="salesDebitNotesTable">

                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">رقم الإشعار</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">فاتورة البيع</th>
                            <th scope="col">العميل</th>
                            <th scope="col">إجمالي الإشعار</th>
                            <th scope="col">الحالة</th>
                            <th scope="col">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($notes as $note)
                            @php
                                $statusLabel = match ($note->status) {
                                    'posted' => 'مرحّل',
                                    'cancelled' => 'ملغى',
                                    'draft' => 'مسودة',
                                    default => $note->status,
                                };

                                $statusClass = match ($note->status) {
                                    'posted' => 'status-posted',
                                    'cancelled' => 'status-cancelled',
                                    default => 'status-draft',
                                };
                            @endphp

                            <tr>
                                <td>
                                    {{ $notes->firstItem() + $loop->index }}
                                </td>

                                <td class="document-number">
                                    <bdi>{{ $note->note_no }}</bdi>
                                </td>

                                <td class="date-cell">
                                    <bdi>
                                        {{ $note->note_date?->format('Y-m-d') ?? '—' }}
                                    </bdi>
                                </td>

                                <td class="document-number">
                                    <bdi>
                                        {{ $note->salesInvoice?->invoice_no ?? '—' }}
                                    </bdi>
                                </td>

                                <td class="customer-cell">
                                    {{ $note->salesInvoice?->customer_name ?: '—' }}
                                </td>

                                <td class="amount-cell">
                                    <bdi>
                                        {{ number_format((float) $note->total_amount, 2) }}
                                    </bdi>
                                </td>

                                <td>
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <td>
                                    <div class="row-actions">

                                        @can('sales_debit_notes.view')
                                            <a href="{{ route('sales-debit-notes.show', $note) }}"
                                               class="btn btn-sm btn-primary">
                                                عرض
                                            </a>
                                        @endcan

                                        @can('sales_debit_notes.print')
                                            <a href="{{ route('sales-debit-notes.print', $note) }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="btn btn-sm btn-print">
                                                طباعة
                                            </a>
                                        @endcan

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <strong>
                                            {{ request()->filled('q')
                                                ? 'لا توجد إشعارات مطابقة للبحث.'
                                                : 'لا توجد إشعارات مدينة حتى الآن.' }}
                                        </strong>

                                        <span>
                                            {{ request()->filled('q')
                                                ? 'جرّب رقمًا آخر أو اسم العميل.'
                                                : 'يمكنك إضافة إشعار جديد من أعلى الصفحة.' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ترقيم الصفحات --}}
            <div class="table-footer">

                <div class="table-info">
                    إظهار {{ $notes->firstItem() ?? 0 }}
                    إلى {{ $notes->lastItem() ?? 0 }}
                    من أصل {{ number_format($notes->total()) }} سجل
                </div>

                @if($notes->hasPages())
                    <nav class="wazin-pagination"
                         aria-label="صفحات الإشعارات المدينة">

                        @if($notes->onFirstPage())
                            <span class="page-control is-disabled"
                                  aria-disabled="true">
                                السابق
                            </span>
                        @else
                            <a class="page-control"
                               href="{{ $notes->previousPageUrl() }}"
                               rel="prev">
                                السابق
                            </a>
                        @endif

                        <span class="page-current" aria-current="page">
                            {{ $notes->currentPage() }}
                            / {{ $notes->lastPage() }}
                        </span>

                        @if($notes->hasMorePages())
                            <a class="page-control"
                               href="{{ $notes->nextPageUrl() }}"
                               rel="next">
                                التالي
                            </a>
                        @else
                            <span class="page-control is-disabled"
                                  aria-disabled="true">
                                التالي
                            </span>
                        @endif

                    </nav>
                @endif

            </div>

        </div>
    </div>

</div>

<style>
    .wazin-debit-notes .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, .16);
    }

    .wazin-debit-notes .page-title {
        color: #fff;
        font-weight: 900;
    }

    .wazin-debit-notes .page-subtitle {
        color: #CFEFF3;
        font-weight: 600;
        line-height: 1.8;
    }

    .wazin-debit-notes .wazin-card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
    }

    .wazin-debit-notes .wazin-card-header {
        background: #fff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .wazin-debit-notes .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-debit-notes .wazin-card-header small {
        color: #64748B;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-debit-notes .card-body {
        padding: 22px;
    }

    .wazin-debit-notes .records-count {
        background: #EEF4FF;
        color: #2454B8;
        border-radius: 999px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .wazin-debit-notes .search-toolbar,
    .wazin-debit-notes .search-field,
    .wazin-debit-notes .search-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wazin-debit-notes .search-toolbar {
        flex-wrap: wrap;
    }

    .wazin-debit-notes .search-field {
        flex: 1;
        max-width: 580px;
    }

    .wazin-debit-notes .search-field label {
        margin: 0;
        color: #071633;
        font-weight: 900;
        white-space: nowrap;
    }

    .wazin-debit-notes .search-field input {
        min-width: 0;
        height: 46px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 8px 14px;
        color: #111827;
        font-weight: 600;
        background: #fff;
    }

    .wazin-debit-notes .search-field input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .wazin-debit-notes .btn {
        font-weight: 800;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .wazin-debit-notes .btn-primary {
        background: #2F6BFF;
        border-color: #2F6BFF;
        color: #fff;
    }

    .wazin-debit-notes .btn-primary:hover {
        background: #2559D9;
        border-color: #2559D9;
    }

    .wazin-debit-notes .btn-print {
        background: #071633;
        border-color: #071633;
        color: #fff;
    }

    .wazin-debit-notes .btn-print:hover {
        background: #15294D;
        border-color: #15294D;
        color: #fff;
    }

    .wazin-debit-notes .btn-sm {
        padding: 7px 13px;
        border-radius: 11px;
        font-size: 13px;
    }

    .wazin-debit-notes #salesDebitNotesTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        padding: 15px 12px;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    .wazin-debit-notes #salesDebitNotesTable tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        font-weight: 600;
        border-color: #E5E7EB;
    }

    .wazin-debit-notes .document-number {
        min-width: 160px;
        max-width: 260px;
        overflow-wrap: anywhere;
        color: #071633;
        font-weight: 800 !important;
    }

    .wazin-debit-notes .date-cell,
    .wazin-debit-notes .amount-cell {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .wazin-debit-notes .amount-cell {
        color: #071633;
        font-weight: 900 !important;
    }

    .wazin-debit-notes .customer-cell {
        min-width: 180px;
        text-align: right;
    }

    .wazin-debit-notes .row-actions {
        display: flex;
        justify-content: center;
        gap: 7px;
        white-space: nowrap;
    }

    .wazin-debit-notes .status-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .wazin-debit-notes .status-draft {
        background: #FFF4D6;
        color: #805400;
    }

    .wazin-debit-notes .status-posted {
        background: #DCFCE7;
        color: #166534;
    }

    .wazin-debit-notes .status-cancelled {
        background: #FEE2E2;
        color: #991B1B;
    }

    .wazin-debit-notes .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-debit-notes .empty-state {
        padding: 35px 16px;
        color: #64748B;
        line-height: 1.9;
    }

    .wazin-debit-notes .empty-state strong {
        display: block;
        color: #071633;
        margin-bottom: 5px;
    }

    .wazin-debit-notes .empty-state span {
        font-size: 13px;
    }

    .wazin-debit-notes .table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 18px;
    }

    .wazin-debit-notes .table-info {
        color: #64748B;
        font-weight: 700;
        font-size: 13px;
    }

    .wazin-debit-notes .wazin-pagination {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .wazin-debit-notes .page-control,
    .wazin-debit-notes .page-current {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
    }

    .wazin-debit-notes .page-control {
        background: #fff;
        color: #071633;
    }

    .wazin-debit-notes a.page-control:hover {
        background: #2F6BFF;
        border-color: #2F6BFF;
        color: #fff;
    }

    .wazin-debit-notes .page-current {
        background: #2F6BFF;
        border-color: #2F6BFF;
        color: #fff;
    }

    .wazin-debit-notes .page-control.is-disabled {
        background: #F1F5F9;
        color: #64748B;
        cursor: not-allowed;
    }

    @media (max-width: 767px) {
        .wazin-debit-notes .page-header-card {
            flex-direction: column;
            align-items: stretch;
            padding: 20px;
        }

        .wazin-debit-notes .page-header-card .btn {
            width: 100%;
        }

        .wazin-debit-notes .card-body {
            padding: 14px;
        }

        .wazin-debit-notes .search-field {
            flex-basis: 100%;
            max-width: none;
        }

        .wazin-debit-notes .search-actions {
            width: 100%;
        }

        .wazin-debit-notes .search-actions .btn {
            flex: 1;
        }

        .wazin-debit-notes .table-footer {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
    }
</style>

</x-app-layout>