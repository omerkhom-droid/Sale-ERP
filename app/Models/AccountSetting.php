<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AccountSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'account_id',
        'description',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public static function getAccountId(string $key): int
    {
        $setting = self::where('setting_key', $key)->first();

        if (!$setting || !$setting->account_id) {
            throw ValidationException::withMessages([
                'account_settings' => 'لم يتم تحديد الحساب المحاسبي للإعداد: ' . $key,
            ]);
        }

        return (int) $setting->account_id;
    }
}