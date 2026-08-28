<?php

namespace App\Http\Controllers;

use App\Support\BranchScope;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Http\Requests\UpdateAccountSettingRequest;
use Illuminate\Support\Facades\DB;

class AccountSettingController extends Controller
{
    public function index()
    {
        $settings = AccountSetting::with('account')
            ->orderBy('id')
            ->get();

        $accounts = Account::where('is_active', 1)
            ->where('is_group', 0)
            ->orderBy('account_code')
            ->get();

        return view('account-settings.index', compact('settings', 'accounts'));
    }

    public function update(UpdateAccountSettingRequest $request)
    {
        DB::transaction(function () use ($request) {

            foreach ($request->settings as $settingKey => $accountId) {
                AccountSetting::where('setting_key', $settingKey)
                    ->update([
                        'account_id' => $accountId ?: null,
                    ]);
            }

        });

        return redirect()
            ->route('account-settings.index')
            ->with('success', 'تم حفظ إعدادات الحسابات المحاسبية بنجاح');
    }
}