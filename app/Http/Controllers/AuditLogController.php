<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('audit_logs.view'), 403);

        $query = AuditLog::query()
            ->with(['user', 'company', 'branch'])
            ->latest('created_at');

        $this->applyScope($query);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', 'like', '%' . trim($request->module) . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        if ($request->filled('branch_id')) {
            $this->assertBranchAccess((int) $request->branch_id);
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->orderBy('name')
            ->limit(300)
            ->get();

        $branchesQuery = Branch::query()
            ->orderBy('branch_name');

        $this->applyBranchScope($branchesQuery);

        $branches = $branchesQuery->get();

        $actions = [
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'post' => 'ترحيل',
            'cancel' => 'إلغاء',
            'open_shift' => 'فتح وردية',
            'close_shift' => 'إغلاق وردية',
            'pos_cancel_order' => 'إلغاء طلب POS',
        ];

        return view('audit-logs.index', compact(
            'logs',
            'users',
            'branches',
            'actions'
        ));
    }

    private function applyScope($query): void
    {
        $user = auth()->user();

        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (in_array($user->user_type, ['master', 'system_admin'], true)) {
            return;
        }

        if (in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
            $query->where('company_id', $user->company_id);
            return;
        }

        if (! empty($user->branch_id)) {
            $query->where('branch_id', $user->branch_id);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function applyBranchScope($query): void
    {
        $user = auth()->user();

        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (in_array($user->user_type, ['master', 'system_admin'], true)) {
            return;
        }

        if (in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
            $query->where('company_id', $user->company_id);
            return;
        }

        if (! empty($user->branch_id)) {
            $query->whereKey($user->branch_id);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function assertBranchAccess(int $branchId): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if (in_array($user->user_type, ['master', 'system_admin'], true)) {
            return;
        }

        if (in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
            $allowed = Branch::query()
                ->whereKey($branchId)
                ->where('company_id', $user->company_id)
                ->exists();

            abort_unless($allowed, 403);
            return;
        }

        abort_unless((int) $user->branch_id === $branchId, 403);
    }
}