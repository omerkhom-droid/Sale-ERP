<x-app-layout>
    <div class="container py-5" dir="rtl">
        <div class="row justify-content-center">
            <div class="col-md-7">

                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">

                        <div class="mb-4" style="font-size: 64px;">
                            🔒
                        </div>

                        <h3 class="fw-bold text-danger mb-3">
                            انتهى الاشتراك أو الترخيص غير فعال
                        </h3>

                        <p class="text-muted mb-4">
                            لا يمكن استخدام النظام حاليًا بسبب انتهاء الاشتراك أو إيقاف الترخيص.
                            يرجى التواصل مع مزود النظام لتجديد الاشتراك.
                        </p>

                        @if($license)
                            <div class="alert alert-light border text-center" dir="rtl">
                                <div><strong>اسم العميل:</strong> {{ $license->client_name ?? '-' }}</div>
                                <div><strong>حالة الترخيص:</strong> {{ $license->status }}</div>
                                <div><strong>تاريخ الانتهاء:</strong> {{ optional($license->expires_at)->format('Y-m-d') ?? '-' }}</div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4">
                                تسجيل الخروج
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>