<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4 no-print">
        <div>
            <h3 class="page-title mb-1">مطابقة المخزون مع الحسابات</h3>
            <p class="page-subtitle mb-0">
                مقارنة قيمة المخزون الحالية مع رصيد حساب المخزون في دفتر الأستاذ.
            </p>
        </div>

        <button type="button" onclick="window.print()" class="btn btn-light fw-bold">
            طباعة
        </button>
    </div>

    @if($configError)
        <div class="alert alert-danger fw-bold">
            {{ $configError }}
        </div>
    @endif

    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">الفلاتر</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('reports.inventory-accounting-reconciliation.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-6">
                        <label class="form-label fw-bold">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">كل الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $branchId === (int) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            عرض التقرير
                        </button>

                        <a href="{{ route('reports.inventory-accounting-reconciliation.index') }}" class="btn btn-secondary">
                            مسح الفلاتر
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="print-header d-none d-print-block mb-4">
        <h3>مطابقة المخزون مع الحسابات</h3>
        <p>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-card">
                <span>قيمة المخزون من النظام</span>
                <strong>{{ number_format((float) $inventoryValue, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <span>رصيد حساب المخزون</span>
                <strong>{{ number_format((float) $ledgerBalance, 2) }}</strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card {{ abs((float) $difference) <= 0.01 ? 'matched' : 'different' }}">
                <span>الفرق</span>
                <strong>{{ number_format((float) $difference, 2) }}</strong>
            </div>
        </div>

    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">المطابقة حسب الفرع</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>الفرع</th>
                        <th>قيمة المخزون</th>
                        <th>رصيد حساب المخزون</th>
                        <th>الفرق</th>
                        <th>الحالة</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td class="fw-bold">
                                {{ $row['branch_name'] ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ number_format((float) $row['inventory_value'], 2) }}
                            </td>

                            <td class="text-center">
                                {{ number_format((float) $row['ledger_balance'], 2) }}
                            </td>

                            <td class="text-center fw-bold {{ $row['status'] === 'matched' ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $row['difference'], 2) }}
                            </td>

                            <td class="text-center">
                                @if($row['status'] === 'matched')
                                    <span class="badge bg-success">مطابق</span>
                                @else
                                    <span class="badge bg-danger">يوجد فرق</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted fw-bold py-4">
                                لا توجد بيانات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="2" class="text-end">الإجمالي</th>
                        <th class="text-center">{{ number_format((float) $inventoryValue, 2) }}</th>
                        <th class="text-center">{{ number_format((float) $ledgerBalance, 2) }}</th>
                        <th class="text-center">{{ number_format((float) $difference, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
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

    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .summary-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .summary-card strong {
        color: #0F172A;
        font-weight: 900;
        font-size: 22px;
    }

    .summary-card.matched {
        border-color: #22C55E;
    }

    .summary-card.different {
        border-color: #EF4444;
    }

    .form-select {
        border-radius: 12px;
        min-height: 42px;
        font-weight: 700;
    }

    .btn {
        border-radius: 12px;
        font-weight: 800;
        padding: 9px 16px;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-weight: 700;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
        }

        .table th {
            background: #f1f5f9 !important;
            color: #000 !important;
        }
    }
</style>

</x-app-layout>