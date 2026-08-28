<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->user_type ?? null) !== 'master') {
            abort(403, 'هذه الصفحة مخصصة لمدير النظام الرئيسي فقط.');
        }

        return $next($request);
    }
}