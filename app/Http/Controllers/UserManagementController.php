<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\LicenseSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    private const ROLE_LEVELS = [
        'Master' => 100,
        'System Admin' => 90,
        'Company Owner' => 70,
        'Company Admin' => 60,
        'Branch Admin' => 40,
        'Employee' => 10,
    ];
    
    private const USER_LEVELS = [
        'master' => 100,
        'system_admin' => 90,
        'company_owner' => 70,
        'company_admin' => 60,
        'branch_admin' => 40,
        'user' => 10,
    ];

    private const USER_LABELS = [
        'master' => 'مدير النظام الرئيسي',
        'system_admin' => 'مشرف النظام',
        'company_owner' => 'مالك الشركة',
        'company_admin' => 'مدير الشركة',
        'branch_admin' => 'مدير فرع',
        'user' => 'موظف',
    ];

    private function actor(): User
    {
        $actor = auth()->user();

        abort_unless($actor, 403);

        return $actor;
    }

    private function actorUserType(): string
    {
        return $this->actor()->user_type ?? 'user';
    }

    private function actorLevel(): int
    {
        return (int) ($this->actor()->level ?? self::USER_LEVELS[$this->actorUserType()] ?? 10);
    }

    private function levelForType(string $userType): int
    {
        return self::USER_LEVELS[$userType] ?? 10;
    }

    private function actorCanAccessUsersPage(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
            'branch_admin',
        ], true);
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function actorIsCompanyScoped(): bool
    {
        return in_array($this->actorUserType(), [
            'company_owner',
            'company_admin',
        ], true);
    }

    private function actorIsBranchScoped(): bool
    {
        return $this->actorUserType() === 'branch_admin';
    }

    private function assertCanAccessUsersPage(): void
    {
        abort_unless(
            $this->actorCanAccessUsersPage(),
            403,
            'هذه الصفحة غير متاحة لهذا النوع من المستخدمين.'
        );
    }

    private function availableUserTypesForActor(): array
    {
        $actorType = $this->actorUserType();
        $actorLevel = $this->actorLevel();

        $types = collect(self::USER_LEVELS)
            ->filter(fn ($level, $type) => $level < $actorLevel)
            ->keys()
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin من إنشاء أي نوع غير موظف عادي
        |--------------------------------------------------------------------------
        */
        if ($actorType === 'branch_admin') {
            return ['user'];
        }

        /*
        |--------------------------------------------------------------------------
        | company_admin لا ينشئ company_admin أو company_owner
        |--------------------------------------------------------------------------
        */
        if ($actorType === 'company_admin') {
            return ['branch_admin', 'user'];
        }

        /*
        |--------------------------------------------------------------------------
        | company_owner ينشئ داخل شركته فقط
        |--------------------------------------------------------------------------
        */
        if ($actorType === 'company_owner') {
            return ['company_admin', 'branch_admin', 'user'];
        }

        return $types;
    }

    private function assertCanUseUserType(string $userType): void
    {
        abort_unless(
            in_array($userType, $this->availableUserTypesForActor(), true),
            403,
            'لا تملك صلاحية إنشاء أو تعيين هذا النوع من المستخدمين.'
        );
    }

    private function canManageUserRecord(User $targetUser): bool
    {
        $actor = $this->actor();

        if ((int) $actor->id === (int) $targetUser->id) {
            return false;
        }

        if ((int) $actor->level <= (int) $targetUser->level) {
            return false;
        }

        if ($this->actorCanSeeAllCompanies()) {
            return true;
        }

        if ($this->actorIsCompanyScoped()) {
            return (int) $targetUser->company_id === (int) $actor->company_id;
        }

        if ($this->actorIsBranchScoped()) {
            return (int) $targetUser->company_id === (int) $actor->company_id
                && (int) $targetUser->branch_id === (int) $actor->branch_id
                && $targetUser->user_type === 'user';
        }

        return false;
    }

    private function assertCanManageUser(User $targetUser): void
    {
        abort_unless(
            $this->canManageUserRecord($targetUser),
            403,
            'لا تملك صلاحية إدارة هذا المستخدم.'
        );
    }

    private function applyUserScope($query)
    {
        $actor = $this->actor();

        /*
        |--------------------------------------------------------------------------
        | منع المستخدم العادي من إدارة المستخدمين نهائياً
        |--------------------------------------------------------------------------
        */
        if (! $this->actorCanAccessUsersPage()) {
            return $query->whereRaw('1 = 0');
        }

        /*
        |--------------------------------------------------------------------------
        | المستخدم لا يرى من هم أعلى منه أو بنفس مستواه
        |--------------------------------------------------------------------------
        */
        $query->where('level', '<', (int) $actor->level);

        /*
        |--------------------------------------------------------------------------
        | master / system_admin
        |--------------------------------------------------------------------------
        */
        if ($this->actorCanSeeAllCompanies()) {
            return $query;
        }

        /*
        |--------------------------------------------------------------------------
        | company_owner / company_admin
        |--------------------------------------------------------------------------
        */
        if ($this->actorIsCompanyScoped()) {
            return $query->where('company_id', $actor->company_id ?? 0);
        }

        /*
        |--------------------------------------------------------------------------
        | branch_admin
        |--------------------------------------------------------------------------
        */
        if ($this->actorIsBranchScoped()) {
            return $query
                ->where('company_id', $actor->company_id ?? 0)
                ->where('branch_id', $actor->branch_id ?? 0)
                ->where('user_type', 'user');
        }

        return $query->whereRaw('1 = 0');
    }

    private function availableCompanies()
    {
        $actor = $this->actor();

        if ($this->actorCanSeeAllCompanies()) {
            return Company::query()
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->get();
        }

        return Company::query()
            ->where('id', $actor->company_id ?? 0)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();
    }

    private function availableBranches(?int $companyId = null)
    {
        $actor = $this->actor();

        $query = Branch::query()
            ->where('is_active', true)
            ->orderBy('branch_name');

        if ($this->actorCanSeeAllCompanies()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->get();
        }

        if ($this->actorIsCompanyScoped()) {
            return $query
                ->where('company_id', $actor->company_id ?? 0)
                ->get();
        }

        if ($this->actorIsBranchScoped()) {
            return $query
                ->where('id', $actor->branch_id ?? 0)
                ->where('company_id', $actor->company_id ?? 0)
                ->get();
        }

        return collect();
    }

    private function resolveCompanyId(?int $requestedCompanyId, string $targetUserType): ?int
    {
        $actor = $this->actor();

        /*
        |--------------------------------------------------------------------------
        | مستخدمو النظام لا يتبعون شركة
        |--------------------------------------------------------------------------
        */
        if (in_array($targetUserType, ['master', 'system_admin'], true)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | master / system_admin يختارون الشركة
        |--------------------------------------------------------------------------
        */
        if ($this->actorCanSeeAllCompanies()) {
            abort_unless($requestedCompanyId, 422, 'يجب اختيار الشركة.');

            $companyExists = Company::query()
                ->where('id', $requestedCompanyId)
                ->where('is_active', true)
                ->exists();

            abort_unless($companyExists, 422, 'الشركة غير صحيحة أو غير نشطة.');

            return (int) $requestedCompanyId;
        }

        /*
        |--------------------------------------------------------------------------
        | باقي الإدارة داخل شركتهم فقط
        |--------------------------------------------------------------------------
        */
        abort_unless($actor->company_id, 403, 'المستخدم غير مرتبط بشركة.');

        return (int) $actor->company_id;
    }

    private function resolveBranchId(?int $requestedBranchId, ?int $companyId, string $targetUserType): ?int
    {
        /*
        |--------------------------------------------------------------------------
        | هذه الأنواع ليست ملزمة بفرع
        |--------------------------------------------------------------------------
        */
        if (in_array($targetUserType, [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
        ], true)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | branch_admin و user يجب ربطهم بفرع
        |--------------------------------------------------------------------------
        */
        abort_unless($requestedBranchId, 422, 'يجب اختيار الفرع.');

        $actor = $this->actor();

        if ($this->actorIsBranchScoped()) {
            abort_unless(
                (int) $requestedBranchId === (int) $actor->branch_id,
                403,
                'لا تملك صلاحية ربط مستخدم بفرع آخر.'
            );
        }

        $branchExists = Branch::query()
            ->where('id', $requestedBranchId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->exists();

        abort_unless($branchExists, 422, 'الفرع غير صحيح أو لا يتبع الشركة المحددة.');

        return (int) $requestedBranchId;
    }


    private function userTypeBadge(User $user): string
    {
        $type = $user->user_type ?? 'user';

        $class = match ($type) {
            'master' => 'bg-dark',
            'system_admin' => 'bg-danger',
            'company_owner' => 'bg-primary',
            'company_admin' => 'bg-info',
            'branch_admin' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };

        return '<span class="badge ' . $class . '">' . e(self::USER_LABELS[$type] ?? $type) . '</span>';
    }

    private function roleLevel(string $roleName): int
    {
        return self::ROLE_LEVELS[$roleName] ?? 10;
    }

    private function availableRolesForActor()
    {
        $actorLevel = $this->actorLevel();

        return Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($actorLevel) {
                foreach (self::ROLE_LEVELS as $roleName => $level) {
                    if ($level < $actorLevel) {
                        $query->orWhere('name', $roleName);
                    }
                }

                $query->orWhereNotIn('name', array_keys(self::ROLE_LEVELS));
            })
            ->orderBy('name')
            ->get();
    }

    private function sanitizeRoles(array $roles, string $targetUserType): array
    {
        $roles = collect($roles)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $actorLevel = $this->actorLevel();
        $targetLevel = $this->levelForType($targetUserType);

        foreach ($roles as $roleName) {
            $roleLevel = $this->roleLevel($roleName);

            abort_unless(
                $roleLevel < $actorLevel,
                403,
                'لا تملك صلاحية تعيين دور أعلى من مستواك أو مساوي له.'
            );

            abort_unless(
                $roleLevel <= $targetLevel,
                403,
                'لا يمكن تعيين دور أعلى من نوع المستخدم المحدد.'
            );
        }

        return $roles;
    }

    private function assertCanCreateUserByLicense(string $targetUserType): void
    {
        if (in_array($targetUserType, ['master', 'system_admin'], true)) {
            return;
        }

        $license = LicenseSetting::current();

        abort_unless(
            $license,
            403,
            'لا توجد بيانات ترخيص للنظام.'
        );

        abort_if(
            $license->hasReachedUsersLimit(),
            403,
            'لا يمكن إنشاء مستخدم جديد، تم الوصول إلى الحد الأقصى للمستخدمين في الترخيص.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('users.view'), 403);

        $this->assertCanAccessUsersPage();

        $users = $this->applyUserScope(
            User::query()->with(['company', 'branch', 'roles'])
        )
            ->latest()
            ->paginate(20);

        $companies = $this->availableCompanies();
        $branches = $this->availableBranches();

        $roles = $this->availableRolesForActor();

        $userTypes = $this->availableUserTypesForActor();
        $userTypeLabels = self::USER_LABELS;

        return view('users.index', compact(
            'users',
            'companies',
            'branches',
            'roles',
            'userTypes',
            'userTypeLabels'
        ));
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('users.view'), 403);

        $this->assertCanAccessUsersPage();

        if (! $request->ajax()) {
            abort(404);
        }

        $actor = auth()->user();

        $query = $this->applyUserScope(
            User::query()->with(['company', 'branch', 'roles'])
        )->latest();

        return datatables()->of($query)
            ->addIndexColumn()

            ->addColumn('company_name', function ($user) {
                return optional($user->company)->name_ar
                    ?? optional($user->company)->name_en
                    ?? '-';
            })

            ->addColumn('branch_name', function ($user) {
                return optional($user->branch)->branch_name ?? '-';
            })

            ->addColumn('user_type_badge', function ($user) {
                return $this->userTypeBadge($user);
            })

            ->addColumn('roles_names', function ($user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">بدون دور</span>';
                }

                return $user->roles
                    ->map(fn ($role) => '<span class="badge bg-primary mb-1">' . e($role->name) . '</span>')
                    ->implode(' ');
            })

            ->addColumn('status_badge', function ($user) {
                return $user->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">موقوف</span>';
            })

            ->addColumn('actions', function ($user) use ($actor) {
                if (! $actor || ! $this->canManageUserRecord($user)) {
                    return '-';
                }

                $buttons = '';

                if ($actor->can('users.edit')) {
                    $editUrl = route('users.edit', $user);
                    $updateUrl = route('users.update', $user);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-info"
                                onclick="openEditUserModal(\'' . $editUrl . '\', \'' . $updateUrl . '\')">
                            تعديل
                        </button>
                    ';
                }

                if ($actor->can('users.delete')) {
                    $deleteUrl = route('users.destroy', $user);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                onclick="deleteUser(\'' . $deleteUrl . '\')">
                            حذف
                        </button>
                    ';
                }

                return $buttons ?: '-';
            })

            ->rawColumns([
                'user_type_badge',
                'roles_names',
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function branchesByCompany(Company $company)
    {
        abort_unless(auth()->user()?->can('users.view'), 403);

        $this->assertCanAccessUsersPage();

        if (! $this->actorCanSeeAllCompanies()) {
            abort_unless(
                (int) $company->id === (int) $this->actor()->company_id,
                403,
                'لا تملك صلاحية عرض فروع هذه الشركة.'
            );
        }

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('branch_name')
            ->get(['id', 'branch_name']);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('users.create'), 403);

        $this->assertCanAccessUsersPage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            'user_type' => [
                'required',
                'string',
                Rule::in(array_keys(self::USER_LEVELS)),
            ],

            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],

            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],

            'is_active' => ['nullable', 'boolean'],
        ]);

        $targetUserType = $data['user_type'];

        $this->assertCanCreateUserByLicense($targetUserType);
        $this->assertCanUseUserType($targetUserType);

        $companyId = $this->resolveCompanyId(
            isset($data['company_id']) ? (int) $data['company_id'] : null,
            $targetUserType
        );

        $branchId = $this->resolveBranchId(
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            $companyId,
            $targetUserType
        );

        $roles = $this->sanitizeRoles($data['roles'] ?? [], $targetUserType);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => $targetUserType,
            'level' => $this->levelForType($targetUserType),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles($roles);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المستخدم بنجاح.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        $this->assertCanAccessUsersPage();
        $this->assertCanManageUser($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'level' => (int) $user->level,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'is_active' => (bool) $user->is_active,
            'roles' => $user->roles()->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        $this->assertCanAccessUsersPage();
        $this->assertCanManageUser($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => ['nullable', 'string', 'min:6', 'confirmed'],

            'user_type' => [
                'required',
                'string',
                Rule::in(array_keys(self::USER_LEVELS)),
            ],

            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],

            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],

            'is_active' => ['nullable', 'boolean'],
        ]);

        $targetUserType = $data['user_type'];

        $this->assertCanUseUserType($targetUserType);

        $companyId = $this->resolveCompanyId(
            isset($data['company_id']) ? (int) $data['company_id'] : null,
            $targetUserType
        );

        $branchId = $this->resolveBranchId(
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            $companyId,
            $targetUserType
        );

        $roles = $this->sanitizeRoles($data['roles'] ?? [], $targetUserType);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $targetUserType,
            'level' => $this->levelForType($targetUserType),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        $user->syncRoles($roles);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المستخدم بنجاح.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless(auth()->user()?->can('users.delete'), 403);

        $this->assertCanAccessUsersPage();
        $this->assertCanManageUser($user);

        $user->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم بنجاح.');
    }
}