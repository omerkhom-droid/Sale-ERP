<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'branch.access' => \App\Http\Middleware\EnsureBranchAccess::class,

            'license.valid' => \App\Http\Middleware\EnsureLicenseIsValid::class,
            
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
       $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد لديك صلاحية لتنفيذ هذه العملية.',
                ], 403);
            }

            abort(403, 'لا توجد لديك صلاحية لتنفيذ هذه العملية.');
        });
    })->create();
