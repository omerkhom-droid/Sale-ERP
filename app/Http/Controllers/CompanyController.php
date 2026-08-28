<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    private function actorUserType(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'user';
        }

        if (! empty($user->user_type)) {
            return $user->user_type;
        }

        /*
        |--------------------------------------------------------------------------
        | توافق مؤقت مع النظام القديم
        |--------------------------------------------------------------------------
        */
        if (
            (bool) ($user->is_super_admin ?? false)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || $user->hasRole('Super Admin')
        ) {
            return 'master';
        }

        if ((bool) ($user->can_access_all_branches ?? false)) {
            return 'company_admin';
        }

        return 'user';
    }

    private function actorCanManageCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function assertSystemCompanyManager(): void
    {
        abort_unless(
            $this->actorCanManageCompanies(),
            403,
            'هذه الصفحة مخصصة لإدارة النظام فقط.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('companies.view'), 403);

        /*
        |--------------------------------------------------------------------------
        | صفحة الشركات للنظام فقط
        |--------------------------------------------------------------------------
        | company_owner / company_admin لا يدخلون هنا.
        | لهم صفحة مستقلة: company-settings.
        */
        $this->assertSystemCompanyManager();

        return view('companies.index');
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('companies.view'), 403);

        $this->assertSystemCompanyManager();

        if (! $request->ajax()) {
            abort(404);
        }

        $actor = auth()->user();

        $query = Company::query()
            ->withCount([
                'branches',
                'users',
            ])
            ->latest();

        return datatables()->of($query)
            ->addIndexColumn()

            ->addColumn('name', function ($company) {
                return e($company->name_ar ?: $company->name_en ?: ('شركة رقم ' . $company->id));
            })

            ->addColumn('code', function ($company) {
                return e($company->code ?? '-');
            })

            ->addColumn('contact', function ($company) {
                $items = [];

                if ($company->phone) {
                    $items[] = '<div><strong>الجوال:</strong> ' . e($company->phone) . '</div>';
                }

                if ($company->email) {
                    $items[] = '<div><strong>البريد:</strong> ' . e($company->email) . '</div>';
                }

                return $items ? implode('', $items) : '<span class="text-muted">-</span>';
            })

            ->addColumn('tax_info', function ($company) {
                $items = [];

                if ($company->tax_number) {
                    $items[] = '<div><strong>الرقم الضريبي:</strong> ' . e($company->tax_number) . '</div>';
                }

                if ($company->commercial_registration) {
                    $items[] = '<div><strong>السجل:</strong> ' . e($company->commercial_registration) . '</div>';
                }

                return $items ? implode('', $items) : '<span class="text-muted">-</span>';
            })

            ->addColumn('branches_count_badge', function ($company) {
                return '<span class="badge bg-primary">' . (int) $company->branches_count . '</span>';
            })

            ->addColumn('users_count_badge', function ($company) {
                return '<span class="badge bg-info">' . (int) $company->users_count . '</span>';
            })

            ->addColumn('status_badge', function ($company) {
                return $company->is_active
                    ? '<span class="badge bg-success">نشطة</span>'
                    : '<span class="badge bg-danger">موقوفة</span>';
            })

            ->addColumn('actions', function ($company) use ($actor) {
                if (! $actor || ! $this->actorCanManageCompanies()) {
                    return '-';
                }

                $buttons = '';

                if ($actor->can('companies.edit')) {
                    $editUrl = route('companies.edit', $company);
                    $updateUrl = route('companies.update', $company);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-info"
                                onclick="openEditCompanyModal(\'' . $editUrl . '\', \'' . $updateUrl . '\')">
                            تعديل
                        </button>
                    ';
                }

                if ($actor->can('companies.delete')) {
                    $deleteUrl = route('companies.destroy', $company);

                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                onclick="deleteCompany(\'' . $deleteUrl . '\')">
                            حذف
                        </button>
                    ';
                }

                return $buttons ?: '-';
            })

            ->rawColumns([
                'contact',
                'tax_info',
                'branches_count_badge',
                'users_count_badge',
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('companies.create'), 403);

        $this->assertSystemCompanyManager();

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:companies,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_registration' => ['nullable', 'string', 'max:80'],

            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        Company::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الشركة بنجاح.',
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'تم إنشاء الشركة بنجاح.');
    }

    public function edit(Company $company)
    {
        abort_unless(auth()->user()?->can('companies.edit'), 403);

        /*
        |--------------------------------------------------------------------------
        | تعديل الشركات من هذه الصفحة للنظام فقط
        |--------------------------------------------------------------------------
        | company_owner / company_admin يستخدمون company-settings.
        */
        $this->assertSystemCompanyManager();

        return response()->json([
            'id' => $company->id,
            'code' => $company->code,
            'name_ar' => $company->name_ar,
            'name_en' => $company->name_en,
            'email' => $company->email,
            'phone' => $company->phone,
            'tax_number' => $company->tax_number,
            'commercial_registration' => $company->commercial_registration,
            'city' => $company->city,
            'address' => $company->address,
            'logo' => $company->logo,
            'logo_url' => $company->logo ? asset('storage/' . $company->logo) : null,
            'is_active' => (bool) $company->is_active,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        abort_unless(auth()->user()?->can('companies.edit'), 403);

        /*
        |--------------------------------------------------------------------------
        | تعديل الشركات من هذه الصفحة للنظام فقط
        |--------------------------------------------------------------------------
        | company_owner / company_admin يستخدمون company-settings.
        */
        $this->assertSystemCompanyManager();

        $data = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('companies', 'code')->ignore($company->id),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_registration' => ['nullable', 'string', 'max:80'],

            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $data['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الشركة بنجاح.',
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'تم تحديث الشركة بنجاح.');
    }

    public function destroy(Request $request, Company $company)
    {
        abort_unless(auth()->user()?->can('companies.delete'), 403);

        $this->assertSystemCompanyManager();

        if ($company->branches()->exists() || $company->users()->exists()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الشركة لوجود فروع أو مستخدمين مرتبطين بها.',
                ], 422);
            }

            return redirect()
                ->route('companies.index')
                ->with('error', 'لا يمكن حذف الشركة لوجود فروع أو مستخدمين مرتبطين بها.');
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشركة بنجاح.',
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'تم حذف الشركة بنجاح.');
    }
}