<?php

namespace App\Http\Controllers;

use App\Models\LicenseSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    private function assertSupportUser(): void
    {
        abort_unless(
            in_array(auth()->user()?->user_type, ['master', 'system_admin'], true),
            403,
            'هذه الصفحة مخصصة لإدارة النظام فقط.'
        );
    }

    public function expired()
    {
        $license = LicenseSetting::current();

        return view('licenses.expired', compact('license'));
    }

    public function index()
    {
        $this->assertSupportUser();

        $license = LicenseSetting::current();

        return view('licenses.index', compact('license'));
    }

    public function update(Request $request)
    {
        $this->assertSupportUser();

        $data = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],
            'license_key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('license_settings', 'license_key')->ignore(optional(LicenseSetting::current())->id),
            ],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['trial', 'active', 'expired', 'suspended'])],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        LicenseSetting::query()->updateOrCreate(
            ['id' => optional(LicenseSetting::current())->id ?? 1],
            $data
        );

        return redirect()
            ->route('license.index')
            ->with('success', 'تم تحديث بيانات الترخيص بنجاح.');
    }
}