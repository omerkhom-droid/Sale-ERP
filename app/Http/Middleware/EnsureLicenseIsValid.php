<?php

namespace App\Http\Middleware;

use App\Models\LicenseSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | مسارات مسموحة حتى لو الترخيص منتهي
        |--------------------------------------------------------------------------
        */
        if (
            $request->routeIs('license.expired')
            || $request->routeIs('license.index')
            || $request->routeIs('license.update')
            || $request->routeIs('logout')
            || $request->routeIs('profile.*')
        ) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | الدعم الفني ومدير النظام يستطيعان الدخول لمعالجة الترخيص
        |--------------------------------------------------------------------------
        */
        if (in_array($user->user_type, ['master', 'system_admin'], true)) {
            return $next($request);
        }

        $license = LicenseSetting::current();

        if (! $license || ! $license->isUsable()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهى الاشتراك أو الترخيص غير فعال.',
                ], 403);
            }

            return redirect()->route('license.expired');
        }

        return $next($request);
    }
}