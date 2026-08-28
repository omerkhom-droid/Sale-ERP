<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (in_array($user->user_type, [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
        ], true)) {
            return $next($request);
        }

        if (in_array($user->user_type, ['branch_admin', 'user'], true)) {
            if (! $user->branch_id) {
                abort(403, 'المستخدم غير مرتبط بفرع.');
            }

            return $next($request);
        }

        abort(403);
    }
}