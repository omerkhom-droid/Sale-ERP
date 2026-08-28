<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchScope
{
    public static function user()
    {
        return Auth::user();
    }

    public static function canAccessAllBranches(): bool
    {
        $user = self::user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'canAccessAllBranches')) {
            return $user->canAccessAllBranches();
        }

        return false;
    }

    public static function userBranchId(): ?int
    {
        $user = self::user();

        return $user && $user->branch_id
            ? (int) $user->branch_id
            : null;
    }

    public static function allowedBranchIds(): array
    {
        $user = self::user();

        if (! $user) {
            return [];
        }

        if (method_exists($user, 'allowedBranchIds')) {
            return $user->allowedBranchIds();
        }

        return $user->branch_id ? [(int) $user->branch_id] : [];
    }

    public static function apply($query, string $column = 'branch_id', ?string $table = null)
    {
        if (self::canAccessAllBranches()) {
            return $query;
        }

        $branchId = self::userBranchId();

        if (! $branchId) {
            return $query->whereRaw('1 = 0');
        }

        $qualifiedColumn = $table
            ? $table . '.' . $column
            : $column;

        return $query->where($qualifiedColumn, $branchId);
    }

    public static function applyToBranches($query)
    {
        if (self::canAccessAllBranches()) {
            return $query;
        }

        $branchId = self::userBranchId();

        if (! $branchId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('id', $branchId);
    }

    public static function selectedBranchId(Request $request, string $key = 'branch_id'): ?int
    {
        $requestedBranchId = $request->filled($key)
            ? (int) $request->input($key)
            : null;

        if (self::canAccessAllBranches()) {
            return $requestedBranchId;
        }

        $userBranchId = self::userBranchId();

        if (! $userBranchId) {
            abort(403, 'لم يتم ربط المستخدم بأي فرع.');
        }

        if ($requestedBranchId && $requestedBranchId !== $userBranchId) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا الفرع.');
        }

        return $userBranchId;
    }

    public static function forceBranchId(?int $requestedBranchId = null): ?int
    {
        if (self::canAccessAllBranches()) {
            return $requestedBranchId;
        }

        $userBranchId = self::userBranchId();

        if (! $userBranchId) {
            abort(403, 'لم يتم ربط المستخدم بأي فرع.');
        }

        if ($requestedBranchId && $requestedBranchId !== $userBranchId) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا الفرع.');
        }

        return $userBranchId;
    }
}