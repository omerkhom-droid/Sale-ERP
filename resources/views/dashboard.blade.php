<x-app-layout>

@php
    $user = auth()->user();

    /*
        الأفضل نعتمد dashboardMode القادم من DashboardController.
        وإذا لم يصل لأي سبب، نحدد الوضع من user_type.
    */
    $mode = $dashboardMode ?? null;

    if (! $mode) {
        $adminTypes = [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
            'branch_admin',
        ];

        $mode = $user && in_array($user->user_type, $adminTypes, true)
            ? 'admin'
            : 'shortcuts';
    }
@endphp

@if($mode === 'admin')
    @include('dashboard.admin')
@else
    @include('dashboard.employee')
@endif
</x-app-layout>