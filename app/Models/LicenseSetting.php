<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LicenseSetting extends Model
{
    protected $fillable = [
        'client_name',
        'license_key',
        'starts_at',
        'expires_at',
        'status',
        'max_users',
        'max_branches',
        'last_check_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'last_check_at' => 'datetime',
        'max_users' => 'integer',
        'max_branches' => 'integer',
    ];

    public static function current(): ?self
    {
        return self::query()->first();
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && now()->startOfDay()->greaterThan($this->expires_at)) {
            return true;
        }

        return false;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isUsable(): bool
    {
        if ($this->isSuspended()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return in_array($this->status, ['trial', 'active'], true);
    }

    public function remainingDays(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->expires_at, false);
    }

    public function billableUsersCount(): int
    {
        return User::query()
            ->whereNotIn('user_type', ['master', 'system_admin'])
            ->where('is_active', true)
            ->count();
    }

    public function branchesCount(): int
    {
        return Branch::query()
            ->where('is_active', true)
            ->count();
    }

    public function hasReachedUsersLimit(): bool
    {
        if (empty($this->max_users)) {
            return false;
        }

        return $this->billableUsersCount() >= (int) $this->max_users;
    }

    public function hasReachedBranchesLimit(): bool
    {
        if (empty($this->max_branches)) {
            return false;
        }

        return $this->branchesCount() >= (int) $this->max_branches;
    }

    public function remainingUsers(): ?int
    {
        if (empty($this->max_users)) {
            return null;
        }

        return max(0, (int) $this->max_users - $this->billableUsersCount());
    }

    public function remainingBranches(): ?int
    {
        if (empty($this->max_branches)) {
            return null;
        }

        return max(0, (int) $this->max_branches - $this->branchesCount());
    }
}