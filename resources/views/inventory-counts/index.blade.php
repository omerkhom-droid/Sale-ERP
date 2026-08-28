<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الجرد المخزني</h3>
            <p class="page-subtitle mb-0">
                إنشاء ومراجعة وترحيل عمليات الجرد للمستودعات.
            </p>
        </div>

        @can('inventory_counts.create')
            <a href="{{ route('inventory-counts.create') }}" class="btn btn-light fw-bold">
                + إنشاء جرد جديد
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success fw-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger fw-bold">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">قائمة الجرد</h5>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>رقم الجرد</th>
                        <th>التاريخ</th>
                        <th>المستودع</th>
                        <th>النطاق</th>
                        <th>الحالة</th>
                        <th>أنشئ بواسطة</th>
                        <th width="220">الإجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($counts as $count)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $count->count_no }}</td>
                            <td>{{ optional($count->count_date)->format('Y-m-d') }}</td>
                            <td>{{ $count->warehouse?->warehouse_name ?? '-' }}</td>
                            <td>
                                @if($count->scope_type === 'all')
                                    كل المنتجات
                                @elseif($count->scope_type === 'category')
                                    تصنيف
                                @elseif($count->scope_type === 'brand')
                                    براند
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($count->status === 'draft')
                                    <span class="badge bg-warning text-dark">مسودة</span>
                                @elseif($count->status === 'posted')
                                    <span class="badge bg-success">مرحل</span>
                                @elseif($count->status === 'cancelled')
                                    <span class="badge bg-danger">ملغي</span>
                                @else
                                    <span class="badge bg-secondary">{{ $count->status }}</span>
                                @endif
                            </td>
                            <td>{{ $count->creator?->name ?? '-' }}</td>
                            <td>
                                @can('inventory_counts.view')
                                    <a href="{{ route('inventory-counts.show', $count) }}"
                                       class="btn btn-sm btn-primary">
                                        عرض
                                    </a>
                                @endcan

                                @can('inventory_counts.print')
                                    <a href="{{ route('inventory-counts.print', $count) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary">
                                        طباعة
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted fw-bold py-4">
                                لا توجد عمليات جرد حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $counts->links() }}
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

    .table th,
    .table td {
        vertical-align: middle;
        font-weight: 700;
    }

    .btn {
        border-radius: 12px;
        font-weight: 800;
    }
</style>

</x-app-layout>