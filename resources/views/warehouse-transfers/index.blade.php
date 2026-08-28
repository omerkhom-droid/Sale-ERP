<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">التحويل بين المستودعات</h3>
            <p class="page-subtitle mb-0">
                إنشاء ومراجعة وترحيل تحويلات البضاعة بين المستودعات.
            </p>
        </div>

        @can('warehouse_transfers.create')
            <a href="{{ route('warehouse-transfers.create') }}" class="btn btn-light fw-bold">
                + تحويل جديد
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success fw-bold">{{ session('success') }}</div>
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
            <h5 class="mb-0 fw-bold">قائمة التحويلات</h5>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>رقم التحويل</th>
                        <th>التاريخ</th>
                        <th>من مستودع</th>
                        <th>إلى مستودع</th>
                        <th>الحالة</th>
                        <th>أنشئ بواسطة</th>
                        <th width="220">الإجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $transfer->transfer_no }}</td>
                            <td>{{ optional($transfer->transfer_date)->format('Y-m-d') }}</td>
                            <td>{{ $transfer->fromWarehouse?->warehouse_name ?? '-' }}</td>
                            <td>{{ $transfer->toWarehouse?->warehouse_name ?? '-' }}</td>
                            <td>
                                @if($transfer->status === 'draft')
                                    <span class="badge bg-warning text-dark">مسودة</span>
                                @elseif($transfer->status === 'posted')
                                    <span class="badge bg-success">مرحل</span>
                                @elseif($transfer->status === 'cancelled')
                                    <span class="badge bg-danger">ملغي</span>
                                @else
                                    <span class="badge bg-secondary">{{ $transfer->status }}</span>
                                @endif
                            </td>
                            <td>{{ $transfer->creator?->name ?? '-' }}</td>
                            <td>
                                @can('warehouse_transfers.view')
                                    <a href="{{ route('warehouse-transfers.show', $transfer) }}"
                                       class="btn btn-sm btn-primary">
                                        عرض
                                    </a>
                                @endcan

                                @can('warehouse_transfers.print')
                                    <a href="{{ route('warehouse-transfers.print', $transfer) }}"
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
                                لا توجد تحويلات حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $transfers->links() }}
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