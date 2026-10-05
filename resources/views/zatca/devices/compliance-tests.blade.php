<x-app-layout>
<div class="container py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">اختبارات امتثال ZATCA</h4>
            <div class="text-muted">{{ $device->device_name }} — {{ match($device->environment) {
                'production' => 'الإنتاج', 'simulation' => 'المحاكاة', default => 'بوابة المطور'
            } }}</div>
        </div>
        <a href="{{ route('zatca.devices.index') }}" class="btn btn-outline-secondary">الأجهزة</a>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
        @if(session($key))
            <div class="alert alert-{{ $color }}" role="alert">{{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul></div>
    @endif

    @if($problem)
        <div class="alert alert-warning">{{ $problem }}</div>
    @else
        <div class="alert alert-info">
            هذه مستندات لاختبار الامتثال، ولا تسجل مبيعات أو حركات مخزون.
            شغّل الاختبارات بالترتيب. يُتاح طلب شهادة الإرسال بعد نجاحها جميعاً.
        </div>

        <div class="card mb-4"><div class="card-body">
            <h5>1. بيانات البائع لمستندات الاختبار</h5>
            <p>{{ $device->organization_name }} — الرقم الضريبي: <span dir="ltr">{{ $device->organization_identifier }}</span></p>
            <p class="text-muted">أدخل السجل التجاري والعنوان الفعلي للبائع المرتبط بالشهادة.</p>
            <form method="POST" action="{{ route('zatca.devices.compliance-tests.prepare', $device) }}" class="zatca-operation"
                  @if($run) onsubmit="return confirm('سيتم إنشاء مجموعة جديدة ومسح نتائج الاختبارات السابقة من هذه الصفحة. متابعة؟')" @endif>
                @csrf
                <div class="row g-3">
                    @foreach([
                        'registration_number' => ['السجل التجاري', '[0-9]{10}', 10],
                        'street' => ['الشارع', null, 255],
                        'building_number' => ['رقم المبنى', '[0-9]{4}', 4],
                        'district' => ['الحي', null, 255],
                        'city' => ['المدينة', null, 255],
                        'postal_code' => ['الرمز البريدي', '[0-9]{5}', 5],
                    ] as $field => [$label, $pattern, $length])
                        <div class="col-md-4">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" class="form-control" required maxlength="{{ $length }}"
                                   value="{{ old($field, $run['supplier'][$field] ?? '') }}"
                                   @if($pattern) pattern="{{ $pattern }}" inputmode="numeric" dir="ltr" @endif>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-outline-primary mt-3" type="submit">{{ $run ? 'إعادة تجهيز الاختبارات' : 'تجهيز الاختبارات' }}</button>
            </form>
        </div></div>

        <div class="card mb-4"><div class="card-body">
            <h5>2. تشغيل الاختبارات</h5>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>المستند</th><th>النتيجة</th><th>الإجراء</th></tr></thead>
                <tbody>
                @php
                    $previousPassed = true;
                @endphp
                
                @foreach($definitions as $key => $definition)
                    @php
                        $entry = $run['tests'][$key] ?? [];
                        $state = $entry['state'] ?? 'pending';
                        $passed = $state === 'passed';
                        $canRun = $run && $previousPassed && !$passed;
                    @endphp
                    <tr>
                        <td>
                            {{ $definition['label'] }}
                            @if(!empty($entry['messages']))
                                <ul class="small mt-2 mb-0">
                                    @foreach($entry['messages'] as $message)<li>{{ $message }}</li>@endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $passed ? 'bg-success' : (in_array($state, ['failed', 'unknown']) ? 'bg-danger' : 'bg-secondary') }}">
                                {{ match($state) { 'passed' => 'ناجح', 'failed' => 'لم ينجح', 'unknown' => 'لم تصل نتيجة', default => 'لم يُختبر' } }}
                            </span>
                            @if(!empty($entry['http_status']))<small class="d-block" dir="ltr">HTTP {{ $entry['http_status'] }}</small>@endif
                        </td>
                        <td>
                            @if(!$passed)
                                <form method="POST" action="{{ route('zatca.devices.compliance-tests.run', [$device, $key]) }}" class="zatca-operation">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" @disabled(!$canRun)>
                                        {{ $state === 'pending' ? 'تشغيل الاختبار' : 'إعادة المحاولة' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-success">مكتمل</span>
                            @endif
                        </td>
                    </tr>
                    @php
                        $previousPassed = $previousPassed && $passed;
                    @endphp
                @endforeach
                </tbody>
            </table></div>
        </div></div>

        <div class="card"><div class="card-body">
            <h5>3. شهادة الإرسال</h5>
            @if($device->environment === 'developer')
                <p class="mb-0">هذه اختبارات بوابة المطور. لإعداد الإرسال في المحاكاة، يلزم جهاز وشهادة Compliance صادران من المحاكاة.</p>
            @else
                <p>الشهادة ستكون لبيئة {{ $device->environment === 'simulation' ? 'المحاكاة فقط' : 'الإنتاج' }}. لن يُفعّل الإرسال التلقائي بمجرد إصدارها.</p>
                <form method="POST" action="{{ route('zatca.devices.compliance-tests.production', $device) }}" class="zatca-operation"
                      onsubmit="return confirm('طلب شهادة الإرسال لهذا الجهاز؟')">
                    @csrf
                    <button type="submit" class="btn btn-success" @disabled(!$ready)>طلب شهادة الإرسال</button>
                </form>
            @endif
        </div></div>
        <div id="zatca-progress" role="status" aria-live="polite" class="alert alert-info mt-3" hidden>جارٍ تنفيذ الطلب، يرجى الانتظار…</div>
    @endif
</div>
<script>
document.querySelectorAll('form.zatca-operation').forEach(form => {
    form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        document.getElementById('zatca-progress').hidden = false;
        document.querySelectorAll('form.zatca-operation button').forEach(button => { button.disabled = true; });
    });
});
</script>
</x-app-layout>
