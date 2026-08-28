<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">تقرير مبيعات التصنيفات POS</h3>
            <p class="page-subtitle mb-0">
                ملخص مبيعات كل تصنيف حسب الكمية، عدد الطلبات، والبيع النهائي.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" onclick="window.print()" class="btn btn-light fw-bold">
                طباعة
            </button>

            <a href="{{ route('pos.index') }}" class="btn btn-secondary fw-bold">
                رجوع
            </a>
        </div>
    </div>

    <div class="card shadow-sm wazin-card mb-4 no-print">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">فلاتر التقرير</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('pos.reports.categories') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date"
                               name="from_date"
                               class="form-control"
                               value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date"
                               name="to_date"
                               class="form-control"
                               value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">كل الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">التصنيف</label>
                        <select name="category_id" class="form-select">
                            <option value="">كل التصنيفات</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">بحث</label>
                        <input type="text"
                               name="q"
                               class="form-control"
                               placeholder="تصنيف / صنف / SKU"
                               value="{{ request('q') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">ترتيب حسب</label>
                        <select name="sort" class="form-select">
                            <option value="total_sales" @selected($sort === 'total_sales')>قيمة المبيعات</option>
                            <option value="total_quantity" @selected($sort === 'total_quantity')>الكمية</option>
                            <option value="orders_count" @selected($sort === 'orders_count')>عدد الطلبات</option>
                            <option value="products_count" @selected($sort === 'products_count')>عدد الأصناف</option>
                        </select>
                    </div>

                    <div class="col-md-12 d-flex gap-2">
                        <button class="btn btn-primary px-4">
                            عرض التقرير
                        </button>

                        <a href="{{ route('pos.reports.categories') }}" class="btn btn-outline-secondary px-4">
                            تصفية
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-2">
            <div class="summary-card">
                <span>عدد التصنيفات</span>
                <strong>{{ number_format($rows->count()) }}</strong>
            </div>
        </div>

        <div class="col-md-2">
            <div class="summary-card">
                <span>عدد الأصناف</span>
                <strong>{{ number_format((int) $totals['products_count']) }}</strong>
            </div>
        </div>

        <div class="col-md-2">
            <div class="summary-card">
                <span>إجمالي الكمية</span>
                <strong>{{ number_format((float) $totals['quantity'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-2">
            <div class="summary-card">
                <span>الضريبة</span>
                <strong>{{ number_format((float) $totals['vat'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-2">
            <div class="summary-card highlight">
                <span>صافي المبيعات</span>
                <strong>{{ number_format((float) $totals['sales'], 2) }}</strong>
            </div>
        </div>

        <div class="col-md-2">
            <div class="summary-card">
                <span>عدد الطلبات</span>
                <strong>{{ number_format((int) $totals['orders_count']) }}</strong>
            </div>
        </div>

    </div>

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">التصنيفات</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التصنيف</th>
                        <th>عدد الأصناف</th>
                        <th>عدد الطلبات</th>
                        <th>الكمية</th>
                        <th>قبل الخصم</th>
                        <th>الخصم</th>
                        <th>الضريبة</th>
                        <th>صافي المبيعات</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td class="fw-bold text-start">
                                {{ $row->category_name ?? 'بدون تصنيف' }}
                            </td>

                            <td>{{ number_format((int) $row->products_count) }}</td>
                            <td>{{ number_format((int) $row->orders_count) }}</td>
                            <td>{{ number_format((float) $row->total_quantity, 2) }}</td>
                            <td>{{ number_format((float) $row->gross_sales, 2) }}</td>
                            <td>{{ number_format((float) $row->total_discount, 2) }}</td>
                            <td>{{ number_format((float) $row->total_vat, 2) }}</td>
                            <td class="fw-bold">{{ number_format((float) $row->total_sales, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-muted fw-bold">
                                لا توجد بيانات حسب الفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">الإجمالي</th>
                        <th>{{ number_format((int) $totals['products_count']) }}</th>
                        <th>{{ number_format((int) $totals['orders_count']) }}</th>
                        <th>{{ number_format((float) $totals['quantity'], 2) }}</th>
                        <th>{{ number_format((float) $totals['gross_sales'], 2) }}</th>
                        <th>{{ number_format((float) $totals['discount'], 2) }}</th>
                        <th>{{ number_format((float) $totals['vat'], 2) }}</th>
                        <th>{{ number_format((float) $totals['sales'], 2) }}</th>
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
        font-weight: 700;
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

    .summary-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        min-height: 86px;
    }

    .summary-card span {
        display: block;
        color: #64748B;
        font-weight: 800;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .summary-card strong {
        color: #071633;
        font-weight: 900;
        font-size: 18px;
    }

    .summary-card.highlight {
        border-color: #2F6BFF;
        background: #EFF6FF;
    }

    .form-label {
        font-weight: 900;
        color: #071633;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        min-height: 42px;
        font-weight: 800;
    }

    .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    .btn-primary {
        background: #2F6BFF;
        border-color: #2F6BFF;
    }

    .table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    .table td {
        font-weight: 700;
        vertical-align: middle;
    }

    .table tfoot th {
        background: #F8FAFC;
        color: #071633;
        font-weight: 900;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .card,
        .summary-card {
            box-shadow: none !important;
        }

        .table thead th {
            background: #eee !important;
            color: #000 !important;
        }
    }
</style>

</x-app-layout>