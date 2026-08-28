<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    private const ROLE_LEVELS = [
        'Master' => 100,
        'System Admin' => 90,
        'Company Owner' => 70,
        'Company Admin' => 60,
        'Branch Admin' => 40,
        'Employee' => 10,
    ];

    private function actor()
    {
        $user = auth()->user();

        abort_unless($user, 403);

        return $user;
    }

    private function actorUserType(): string
    {
        return $this->actor()->user_type ?? 'user';
    }

    private function actorLevel(): int
    {
        return (int) ($this->actor()->level ?? 10);
    }

    private function actorCanAccessRolesPage(): bool
    {
        return $this->actor()->can('roles.view');
    }

    private function assertCanAccessRolesPage(): void
    {
        abort_unless(
            $this->actorCanAccessRolesPage(),
            403,
            'لا تملك صلاحية عرض صفحة الأدوار والصلاحيات.'
        );
    }

    private function actorIsMaster(): bool
    {
        return $this->actorUserType() === 'master';
    }

    private function roleLevel(Role|string $role): int
    {
        $roleName = $role instanceof Role ? $role->name : $role;

        return self::ROLE_LEVELS[$roleName] ?? 10;
    }

    private function availablePermissions()
    {
        if ($this->actorIsMaster()) {
            return Permission::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get();
        }

        $actorPermissionNames = $this->actor()
            ->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        return Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $actorPermissionNames)
            ->orderBy('name')
            ->get();
    }

    private function availablePermissionNames(): array
    {
        return $this->availablePermissions()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    private function sanitizePermissions(array $permissions): array
    {
        $permissions = collect($permissions)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $availablePermissionNames = $this->availablePermissionNames();

        foreach ($permissions as $permission) {
            abort_unless(
                in_array($permission, $availablePermissionNames, true),
                403,
                'لا تملك صلاحية منح واحدة أو أكثر من الصلاحيات المحددة.'
            );
        }

        return $permissions;
    }

    private function visibleRolePermissionNames(Role $role): array
    {
        $availablePermissionNames = $this->availablePermissionNames();

        return $role->permissions()
            ->whereIn('permissions.name', $availablePermissionNames)
            ->pluck('permissions.name')
            ->toArray();
    }

    private function lockedRolePermissionNames(Role $role): array
    {
        if ($this->actorIsMaster()) {
            return [];
        }

        $availablePermissionNames = $this->availablePermissionNames();

        return $role->permissions()
            ->whereNotIn('permissions.name', $availablePermissionNames)
            ->pluck('permissions.name')
            ->toArray();
    }

    private function roleHasPermissionsHigherThanActor(Role $role): bool
    {
        return count($this->lockedRolePermissionNames($role)) > 0;
    }

    private function canManageRole(Role $role): bool
    {
        if ($this->actorLevel() <= $this->roleLevel($role)) {
            return false;
        }

        if ($this->actorIsMaster()) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | غير master لا يدير دورًا يحتوي صلاحيات لا يملكها
        |--------------------------------------------------------------------------
        */
        return ! $this->roleHasPermissionsHigherThanActor($role);
    }

    private function assertCanManageRole(Role $role): void
    {
        abort_unless(
            $this->canManageRole($role),
            403,
            'لا تملك صلاحية إدارة هذا الدور.'
        );
    }

    private function applyRoleScope($query)
    {
        /*
        |--------------------------------------------------------------------------
        | المستخدم يرى فقط الأدوار الأقل من مستواه
        |--------------------------------------------------------------------------
        */
        $query->where('guard_name', 'web')
            ->where(function ($query) {
                foreach (self::ROLE_LEVELS as $roleName => $level) {
                    if ($level < $this->actorLevel()) {
                        $query->orWhere('name', $roleName);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | الأدوار المخصصة غير المعرفة في ROLE_LEVELS نعتبرها مستوى موظف
                |--------------------------------------------------------------------------
                */
                $query->orWhereNotIn('name', array_keys(self::ROLE_LEVELS));
            });

        /*
        |--------------------------------------------------------------------------
        | غير master لا يرى دورًا يحتوي صلاحيات أعلى منه
        |--------------------------------------------------------------------------
        */
        if (! $this->actorIsMaster()) {
            $availablePermissionNames = $this->availablePermissionNames();

            $query->whereDoesntHave('permissions', function ($permissionQuery) use ($availablePermissionNames) {
                $permissionQuery->whereNotIn('permissions.name', $availablePermissionNames);
            });
        }

        return $query;
    }

    private function syncRolePermissionsSafely(Role $role, array $selectedPermissions): void
    {
        $selectedPermissions = $this->sanitizePermissions($selectedPermissions);

        $lockedPermissions = $this->lockedRolePermissionNames($role);

        $finalPermissions = collect($lockedPermissions)
            ->merge($selectedPermissions)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $role->syncPermissions($finalPermissions);
    }

    public function index()
    {
        $this->assertCanAccessRolesPage();

        $roles = $this->applyRoleScope(
            Role::query()
                ->withCount('users')
                ->with('permissions')
        )
            ->orderBy('name')
            ->paginate(20);

        $permissions = $this->availablePermissions()
            ->groupBy(function ($permission) {
                return Str::before($permission->name, '.') ?: 'general';
            });

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function fetch(Request $request)
    {
        $this->assertCanAccessRolesPage();

        if (! $request->ajax()) {
            abort(404);
        }

        $actor = $this->actor();

        $query = $this->applyRoleScope(
            Role::query()
                ->withCount('users')
                ->with('permissions')
        )->orderBy('name');

        return datatables()->of($query)
            ->addIndexColumn()

            ->addColumn('permissions_count_badge', function ($role) {
                return '<span class="badge bg-primary">' . (int) $role->permissions->count() . '</span>';
            })

            ->addColumn('users_count_badge', function ($role) {
                return '<span class="badge bg-secondary">' . (int) $role->users_count . '</span>';
            })

            ->addColumn('level_badge', function ($role) {
                return '<span class="badge bg-dark">' . $this->roleLevel($role) . '</span>';
            })

            ->addColumn('actions', function ($role) use ($actor) {
                if (! $this->canManageRole($role)) {
                    return '-';
                }

                $buttons = '';

                if ($actor->can('roles.edit')) {
                    $editUrl = route('roles.edit', $role);
                    $updateUrl = route('roles.update', $role);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-info"
                                onclick="openEditRoleModal(\'' . $editUrl . '\', \'' . $updateUrl . '\')">
                            تعديل
                        </button>
                    ';
                }

                if (
                    $actor->can('roles.delete')
                    && (int) $role->users_count === 0
                    && ! in_array($role->name, array_keys(self::ROLE_LEVELS), true)
                ) {
                    $deleteUrl = route('roles.destroy', $role);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                onclick="deleteRole(\'' . $deleteUrl . '\')">
                            حذف
                        </button>
                    ';
                }

                return $buttons ?: '-';
            })

            ->rawColumns([
                'permissions_count_badge',
                'users_count_badge',
                'level_badge',
                'actions',
            ])

            ->make(true);
    }

    public function store(Request $request)
    {
        abort_unless($this->actor()->can('roles.create'), 403);

        $this->assertCanAccessRolesPage();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:roles,name',
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | لا يمكن إنشاء دور بمستوى مساوي أو أعلى من المستخدم
        |--------------------------------------------------------------------------
        */
        abort_unless(
            $this->actorLevel() > $this->roleLevel($data['name']),
            403,
            'لا تملك صلاحية إنشاء دور بهذا المستوى.'
        );

        $permissions = $this->sanitizePermissions($data['permissions'] ?? []);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدور بنجاح.',
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function edit(Role $role)
    {
        abort_unless($this->actor()->can('roles.edit'), 403);

        $this->assertCanAccessRolesPage();
        $this->assertCanManageRole($role);

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'level' => $this->roleLevel($role),
            'is_system_role' => in_array($role->name, array_keys(self::ROLE_LEVELS), true),
            'permissions' => $this->visibleRolePermissionNames($role),
            'hidden_permissions_count' => count($this->lockedRolePermissionNames($role)),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($this->actor()->can('roles.edit'), 403);

        $this->assertCanAccessRolesPage();
        $this->assertCanManageRole($role);

        $isSystemRole = in_array($role->name, array_keys(self::ROLE_LEVELS), true);

        $data = $request->validate([
            'name' => [
                $isSystemRole ? 'nullable' : 'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | الأدوار الأساسية لا نغير أسماءها حتى لا تنكسر قواعد النظام
        |--------------------------------------------------------------------------
        */
        if (! $isSystemRole) {
            abort_unless(
                $this->actorLevel() > $this->roleLevel($data['name']),
                403,
                'لا تملك صلاحية رفع الدور إلى هذا المستوى.'
            );

            $role->update([
                'name' => $data['name'],
            ]);
        }

        $permissions = $this->sanitizePermissions($data['permissions'] ?? []);

        $this->syncRolePermissionsSafely($role, $permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح.',
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'تم تحديث الدور بنجاح.');
    }

    public function destroy(Request $request, Role $role)
    {
        abort_unless($this->actor()->can('roles.delete'), 403);

        $this->assertCanAccessRolesPage();
        $this->assertCanManageRole($role);

        if (in_array($role->name, array_keys(self::ROLE_LEVELS), true)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الأدوار الأساسية للنظام.',
                ], 422);
            }

            return redirect()
                ->route('roles.index')
                ->with('error', 'لا يمكن حذف الأدوار الأساسية للنظام.');
        }

        if ($role->users()->count() > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف دور مرتبط بمستخدمين.',
                ], 422);
            }

            return redirect()
                ->route('roles.index')
                ->with('error', 'لا يمكن حذف دور مرتبط بمستخدمين.');
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الدور بنجاح.',
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'تم حذف الدور بنجاح.');
    }
}