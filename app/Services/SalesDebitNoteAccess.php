<?php

namespace App\Services;

use App\Models\Branch;

class SalesDebitNoteAccess
{
    public function branchIds(): ?array
    {
        $user = auth()->user();
        if (!$user) { return []; }
        if (in_array($user->user_type, ['master', 'system_admin'], true)) { return null; }
        if (in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
            return $user->company_id ? Branch::where('company_id', $user->company_id)->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
        }
        return $user->branch_id ? [(int) $user->branch_id] : [];
    }

    public function scope($query)
    {
        $ids = $this->branchIds();
        return $ids === null ? $query : $query->whereIn('branch_id', $ids);
    }

    public function authorize(string $action, $document = null): void
    {
        abort_unless(auth()->user()?->can('sales_debit_notes.' . $action), 403);
        if ($document !== null) {
            $ids = $this->branchIds();
            abort_unless($ids === null || in_array((int) $document->branch_id, $ids, true), 403);
        }
    }
}
