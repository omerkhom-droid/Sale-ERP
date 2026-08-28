<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;

    public const USER_LEVELS = [
        'master' => 100,
        'system_admin' => 90,
        'company_owner' => 70,
        'company_admin' => 60,
        'branch_admin' => 40,
        'user' => 10,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'branch_id',
        'user_type',
        'level',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function isMaster(): bool
    {
        return $this->user_type === 'master';
    }

    public function isSystemAdmin(): bool
    {
        return $this->user_type === 'system_admin';
    }

    public function isCompanyOwner(): bool
    {
        return $this->user_type === 'company_owner';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->user_type === 'company_admin';
    }

    public function isBranchAdmin(): bool
    {
        return $this->user_type === 'branch_admin';
    }

    public function isNormalUser(): bool
    {
        return $this->user_type === 'user';
    }

    public function canSeeAllSystem(): bool
    {
        return $this->isMaster();
    }

    public function canSeeAllCompaniesForSupport(): bool
    {
        return in_array($this->user_type, [
            'master',
            'system_admin',
        ], true);
    }

    public function canSeeAllCompanyBranches(): bool
    {
        return in_array($this->user_type, [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
        ], true);
    }

    public function isCompanyScoped(): bool
    {
        return in_array($this->user_type, [
            'company_owner',
            'company_admin',
        ], true);
    }

    public function isBranchScoped(): bool
    {
        return in_array($this->user_type, [
            'branch_admin',
            'user',
        ], true);
    }

    public function canManageUser(User $targetUser): bool
    {
        if ((int) $this->id === (int) $targetUser->id) {
            return false;
        }

        return (int) $this->level > (int) $targetUser->level;
    }

    public function canCreateUserType(string $userType): bool
    {
        return (int) $this->level > self::levelForType($userType);
    }

    public static function levelForType(string $userType): int
    {
        return self::USER_LEVELS[$userType] ?? 10;
    }
}