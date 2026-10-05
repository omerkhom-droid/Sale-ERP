<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditLogService
{
    public function log(
        string $action,
        string $module,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?int $branchId = null
    ): void {
        try {
            $user = auth()->user();

            $resolvedBranchId = $branchId
                ?? $this->resolveBranchId($model)
                ?? $user?->branch_id;

            $resolvedCompanyId = $companyId
                ?? $this->resolveCompanyId($model)
                ?? $this->companyIdFromBranch($resolvedBranchId)
                ?? $user?->company_id;

            AuditLog::create([
                'user_id' => $user?->id,
                'company_id' => $resolvedCompanyId,
                'branch_id' => $resolvedBranchId,

                'action' => $action,
                'module' => $module,

                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),

                'description' => $description,

                'old_values' => $oldValues,
                'new_values' => $newValues,

                'ip_address' => request()?->ip(),
                'method' => request()?->method(),
                'url' => request()?->fullUrl(),
                'user_agent' => request()?->userAgent(),

                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function resolveBranchId(?Model $model): ?int
    {
        if (! $model) {
            return null;
        }

        if (isset($model->branch_id)) {
            return (int) $model->branch_id;
        }

        return null;
    }

    private function resolveCompanyId(?Model $model): ?int
    {
        if (! $model) {
            return null;
        }

        if (isset($model->company_id)) {
            return (int) $model->company_id;
        }

        return null;
    }

    private function companyIdFromBranch(?int $branchId): ?int
    {
        if (! $branchId) {
            return null;
        }

        return Branch::query()
            ->whereKey($branchId)
            ->value('company_id');
    }
}