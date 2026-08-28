<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\LicenseSetting;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function actorCanAccessBranchesPage(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
        ], true);
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function assertCanAccessBranchesPage(): void
    {
        abort_unless(
            $this->actorCanAccessBranchesPage(),
            403,
            'هذه الصفحة غير متاحة لهذا النوع من المستخدمين.'
        );
    }

    private function assertCompanyLinkedUser(): void
    {
        $user = auth()->user();

        if ($this->actorCanSeeAllCompanies()) {
            return;
        }

        abort_unless(
            ! empty($user?->company_id),
            403,
            'المستخدم غير مرتبط بشركة.'
        );
    }

    private function applyBranchScope($query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->actorCanSeeAllCompanies()) {
            return $query;
        }

        return $query->where('company_id', $user->company_id ?? 0);
    }

    private function assertBranchAllowed(Branch $branch): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($this->actorCanSeeAllCompanies()) {
            return;
        }

        abort_unless(
            (int) $branch->company_id === (int) $user->company_id,
            403,
            'لا تملك صلاحية الوصول لهذا الفرع.'
        );
    }

    private function availableCompanies()
    {
        $user = auth()->user();

        if ($this->actorCanSeeAllCompanies()) {
            return Company::query()
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->get();
        }

        return Company::query()
            ->where('id', $user->company_id ?? 0)
            ->where('is_active', true)
            ->get();
    }

    private function resolveCompanyId(?int $requestedCompanyId = null): int
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($this->actorCanSeeAllCompanies()) {
            abort_unless(
                ! empty($requestedCompanyId),
                422,
                'يجب اختيار الشركة.'
            );

            $companyExists = Company::query()
                ->where('id', $requestedCompanyId)
                ->where('is_active', true)
                ->exists();

            abort_unless(
                $companyExists,
                422,
                'الشركة غير صحيحة أو غير نشطة.'
            );

            return (int) $requestedCompanyId;
        }

        $this->assertCompanyLinkedUser();

        return (int) $user->company_id;
    }


    private function assertCanCreateBranchByLicense(): void
    {
        $license = LicenseSetting::current();

        abort_unless(
            $license,
            403,
            'لا توجد بيانات ترخيص للنظام.'
        );

        abort_if(
            $license->hasReachedBranchesLimit(),
            403,
            'لا يمكن إنشاء فرع جديد، تم الوصول إلى الحد الأقصى للفروع في الترخيص.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('branches.view'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من صفحة الفروع حتى لو لديهم branches.view بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertCompanyLinkedUser();

        $companies = $this->availableCompanies();

        return view('branches.index', compact('companies'));
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('branches.view'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من جلب بيانات الفروع حتى لو لديهم branches.view بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertCompanyLinkedUser();

        $user = auth()->user();

        $branches = $this->applyBranchScope(
            Branch::query()->with('company')
        )->latest();

        return DataTables::of($branches)
            ->addIndexColumn()

            ->addColumn('company_name', function ($branch) {
                return e(optional($branch->company)->name_ar ?? '-');
            })

            ->addColumn('status', function ($branch) {
                return $branch->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('edit', function ($branch) use ($user) {
                if (
                    ! $user
                    || ! $user->can('branches.edit')
                    || ! $this->actorCanAccessBranchesPage()
                ) {
                    return '<span class="text-muted">-</span>';
                }

                $this->assertBranchAllowed($branch);

                return '
                    <button type="button"
                            class="btn btn-sm btn-primary editBtn"
                            data-id="' . (int) $branch->id . '">
                        تعديل
                    </button>
                ';
            })

            ->addColumn('delete', function ($branch) use ($user) {
                if (
                    ! $user
                    || ! $user->can('branches.delete')
                    || ! $this->actorCanAccessBranchesPage()
                ) {
                    return '<span class="text-muted">-</span>';
                }

                $this->assertBranchAllowed($branch);

                return '
                    <button type="button"
                            class="btn btn-sm btn-danger deleteBtn"
                            data-id="' . (int) $branch->id . '">
                        حذف
                    </button>
                ';
            })

            ->rawColumns(['status', 'edit', 'delete'])

            ->make(true);
    }

    public function store(StoreBranchRequest $request)
    {
        abort_unless(auth()->user()?->can('branches.create'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من إنشاء الفروع حتى لو لديهم branches.create بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertCanCreateBranchByLicense();

        $data = $request->validated();

        $data['company_id'] = $this->resolveCompanyId(
            isset($data['company_id']) ? (int) $data['company_id'] : null
        );

        Branch::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الفرع بنجاح',
        ]);
    }

    public function edit(Branch $branch)
    {
        abort_unless(auth()->user()?->can('branches.edit'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من تعديل الفروع حتى لو لديهم branches.edit بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertBranchAllowed($branch);

        return response()->json($branch);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        abort_unless(auth()->user()?->can('branches.edit'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من تعديل الفروع حتى لو لديهم branches.edit بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertBranchAllowed($branch);

        $data = $request->validated();

        $data['company_id'] = $this->resolveCompanyId(
            isset($data['company_id']) ? (int) $data['company_id'] : (int) $branch->company_id
        );

        $branch->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل الفرع بنجاح',
        ]);
    }

    public function destroy(Branch $branch)
    {
        abort_unless(auth()->user()?->can('branches.delete'), 403);

        /*
        |--------------------------------------------------------------------------
        | منع branch_admin / user من حذف الفروع حتى لو لديهم branches.delete بالغلط
        |--------------------------------------------------------------------------
        */
        $this->assertCanAccessBranchesPage();
        $this->assertBranchAllowed($branch);

        $branch->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الفرع بنجاح',
        ]);
    }
}