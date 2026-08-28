@php
    $authUser = auth()->user();
    $license = \App\Models\LicenseSetting::current();

    $remainingDays = $license?->remainingDays();

    if (! is_null($remainingDays)) {
        $remainingDays = (int) ceil($remainingDays);
    }

    $shouldShowLicenseWarning =
        $authUser
        && $license
        && ! in_array($authUser->user_type ?? 'user', ['master', 'system_admin'], true)
        && in_array($license->status, ['trial', 'active'], true)
        && ! is_null($remainingDays)
        && $remainingDays >= 0
        && $remainingDays <= 7;
@endphp

@if($shouldShowLicenseWarning)
    @if($remainingDays <= 3)
        <div class="alert alert-danger mb-4 license-warning-alert" dir="rtl">
            <strong>تنبيه مهم:</strong>
            متبقي على انتهاء اشتراك النظام
            <strong>{{ $remainingDays }}</strong>
            يوم.
            يرجى التواصل لتجديد الاشتراك قبل توقف النظام.
        </div>
    @else
        <div class="alert alert-warning mb-4 license-warning-alert" dir="rtl">
            <strong>تنبيه:</strong>
            متبقي على انتهاء اشتراك النظام
            <strong>{{ $remainingDays }}</strong>
            أيام.
        </div>
    @endif
@endif

@if($shouldShowLicenseWarning && $remainingDays <= 3)
    @push('scripts')
        <script>
            $(function () {
                if (typeof Swal === 'undefined') {
                    return;
                }

                let warningKey = 'license_warning_shown_{{ optional($license->expires_at)->format('Ymd') }}';

                if (! sessionStorage.getItem(warningKey)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'قرب انتهاء الاشتراك',
                        html: 'متبقي على انتهاء اشتراك النظام <strong>{{ $remainingDays }}</strong> يوم. يرجى التواصل لتجديد الاشتراك.',
                        confirmButtonText: 'حسنًا',
                        didOpen: function (popup) {
                            popup.setAttribute('dir', 'rtl');
                        }
                    });

                    sessionStorage.setItem(warningKey, '1');
                }
            });
        </script>
    @endpush
@endif