<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    private function actorUserType(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'user';
        }

        return $user->user_type ?? 'user';
    }

    private function actorCanManageOwnCompanySettings(): bool
    {
        return in_array($this->actorUserType(), [
            'company_owner',
        ], true);
    }

    private function company(): Company
    {
        $user = auth()->user();

        abort_unless($user, 403);
        abort_unless($this->actorCanManageOwnCompanySettings(), 403, 'لا تملك صلاحية تعديل إعدادات الشركة.');
        abort_unless($user->company_id, 403, 'المستخدم غير مرتبط بشركة.');

        $company = Company::findOrFail($user->company_id);

        abort_unless($company->is_active, 403, 'الشركة موقوفة.');

        return $company;
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('company_settings.view'), 403);

        $company = $this->company();

        return view('company-settings.index', compact('company'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->can('company_settings.edit'), 403);

        $company = $this->company();

        $data = $request->validate([
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
        ]);

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

        return redirect()
            ->route('company-settings.index')
            ->with('success', 'تم تحديث إعدادات الشركة بنجاح.');
    }
}