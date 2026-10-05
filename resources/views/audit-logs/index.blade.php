<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">سجل النشاطات</h3>
            <p class="page-subtitle mb-0">
                متابعة العمليات الحساسة داخل النظام.
            </p>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-light fw-bold">
            رجوع
        </a>
    </div>

    <div class="card shadow-sm wazin-card mb-4">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">فلاتر البحث</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('audit-logs.index') }}">
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
                        <label class="form-label">العملية</label>
                        <select name="action" class="form-select">
                            <option value="">كل العمليات</option>
                            @foreach($actions as $key => $label)
                                <option value="{{ $key }}" @selected(request('action') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">المستخدم</label>
                        <select name="user_id" class="form-select">
                            <option value="">كل المستخدمين</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="form-label">بحث</label>
                        <input type="text"
                               name="q"
                               class="form-control"
                               value="{{ request('q') }}"
                               placeholder="وصف / رابط / وحدة">
                    </div>

                    <div class="col-md-12 d-flex gap-2">
                        <button class="btn btn-primary px-4">
                            بحث
                        </button>

                        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary px-4">
                            تصفية
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <h5 class="mb-0 fw-bold">العمليات</h5>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوقت</th>
                        <th>المستخدم</th>
                        <th>الفرع</th>
                        <th>الوحدة</th>
                        <th>العملية</th>
                        <th>الوصف</th>
                        <th>IP</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>

                            <td>
                                {{ optional($log->created_at)->format('Y-m-d H:i:s') }}
                            </td>

                            <td>
                                {{ $log->user?->name ?? 'النظام' }}
                            </td>

                            <td>
                                {{ $log->branch?->branch_name ?? '-' }}
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $log->module }}
                                </span>
                            </td>

                            <td>
                                <span class="badge action-badge action-{{ $log->action }}">
                                    {{ $actions[$log->action] ?? $log->action }}
                                </span>
                            </td>

                            <td class="text-start">
                                <div class="fw-bold">{{ $log->description }}</div>

                                @if($log->model_type && $log->model_id)
                                    <small class="text-muted">
                                        {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                {{ $log->ip_address ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 text-muted fw-bold">
                                لا توجد عمليات مسجلة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
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

    .badge {
        font-weight: 900;
        border-radius: 10px;
        padding: 7px 10px;
    }

    .action-badge {
        background: #64748B;
    }

    .action-post,
    .action-open_shift,
    .action-close_shift {
        background: #16A34A;
    }

    .action-cancel,
    .action-pos_cancel_order {
        background: #DC2626;
    }

    .action-update {
        background: #2563EB;
    }

    .action-create {
        background: #0F766E;
    }

    .action-delete {
        background: #991B1B;
    }
</style>

</x-app-layout>