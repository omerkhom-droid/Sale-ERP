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

    $currentLicense = LicenseSetting::current();

    $data = $request->validate([
        'client_name' => ['nullable', 'string', 'max:255'],

        'license_key' => [
            'nullable',
            'string',
            'max:255',
            Rule::unique('license_settings', 'license_key')
                ->ignore($currentLicense?->id),
        ],

        'starts_at' => ['nullable', 'date'],

        'expires_at' => [
            'nullable',
            'date',
            'after_or_equal:starts_at',
        ],

        'status' => [
            'required',
            Rule::in([
                'trial',
                'active',
                'expired',
                'suspended',
            ]),
        ],

        'max_users' => [
            'nullable',
            'integer',
            'min:1',
        ],

        'max_branches' => [
            'nullable',
            'integer',
            'min:1',
        ],

        'notes' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $oldValues = $currentLicense ? [
        'client_name' => $currentLicense->client_name,
        'license_key' => $currentLicense->license_key,
        'starts_at' => $currentLicense->starts_at,
        'expires_at' => $currentLicense->expires_at,
        'status' => $currentLicense->status,
        'max_users' => $currentLicense->max_users,
        'max_branches' => $currentLicense->max_branches,
        'notes' => $currentLicense->notes,
    ] : null;

    $license = LicenseSetting::query()->updateOrCreate(
        [
            'id' => $currentLicense?->id ?? 1,
        ],
        $data
    );

    app(\App\Services\AuditLogService::class)->log(
        action: $currentLicense ? 'update' : 'create',
        module: 'License',

        description: $currentLicense
            ? 'تم تحديث إعدادات الترخيص'
            : 'تم إنشاء إعدادات الترخيص',

        model: $license,

        oldValues: $oldValues,

        newValues: [
            'client_name' => $license->client_name,
            'license_key' => $license->license_key,
            'starts_at' => $license->starts_at,
            'expires_at' => $license->expires_at,
            'status' => $license->status,
            'max_users' => $license->max_users,
            'max_branches' => $license->max_branches,
            'notes' => $license->notes,
        ]
    );

    return redirect()
        ->route('license.index')
        ->with(
            'success',
            'تم تحديث بيانات الترخيص بنجاح.'
        );
}
}