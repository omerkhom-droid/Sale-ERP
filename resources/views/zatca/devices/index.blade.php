<x-app-layout>
    @php
        $environment = config('services.zatca.environment', 'developer');

        $environmentLabel = match ($environment) {
            'production' => 'الإنتاج الحقيقي',
            'simulation' => 'المحاكاة',
            default => 'بوابة المطور',
        };
    @endphp

    <style>
        .zatca-page {
            padding: 28px;
            color: #10203b;
        }

        .zatca-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .zatca-header h4 {
            margin: 0 0 6px;
            font-weight: 700;
        }

        .zatca-muted {
            color: #64748b;
            font-size: 13px;
        }

        .zatca-notice {
            padding: 16px 20px;
            margin-bottom: 18px;
            border: 1px solid transparent;
            border-radius: 14px;
        }

        .zatca-notice-success {
            color: #166534;
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .zatca-notice-error {
            color: #991b1b;
            background: #fef2f2;
            border-color: #fecaca;
        }

        .zatca-notice-info {
            color: #075985;
            background: #f0f9ff;
            border-color: #bae6fd;
        }

        .zatca-notice p {
            margin: 6px 0 0;
            font-size: 13px;
        }

        .zatca-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
        }

        .zatca-table-wrap {
            overflow-x: auto;
        }

        .zatca-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            text-align: right;
        }

        .zatca-table th {
            padding: 16px;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            border-bottom: 1px solid #e2e8f0;
        }

        .zatca-table td {
            padding: 18px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
            font-size: 14px;
        }

        .zatca-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .zatca-device-name {
            font-weight: 700;
            min-width: 120px;
        }

        .zatca-serial {
            display: inline-block;
            direction: ltr;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            overflow-wrap: anywhere;
            max-width: 240px;
        }

        .zatca-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .zatca-badge-gray {
            color: #475569;
            background: #f1f5f9;
        }

        .zatca-badge-green {
            color: #166534;
            background: #dcfce7;
        }

        .zatca-badge-blue {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .zatca-badge-amber {
            color: #92400e;
            background: #fef3c7;
        }

        .zatca-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 175px;
        }

        .zatca-actions form {
            margin: 0;
        }

        .zatca-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.5;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s ease;
        }

        .zatca-button-primary {
            color: #fff;
            background: #3165f5;
        }

        .zatca-button-primary:hover {
            color: #fff;
            background: #2453d6;
        }

        .zatca-button-secondary {
            color: #334155;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }

        .zatca-button-secondary:hover {
            background: #e2e8f0;
        }

        .zatca-button-danger {
            color: #b91c1c;
            background: #fef2f2;
            border-color: #fecaca;
        }

        .zatca-button-danger:hover {
            background: #fee2e2;
        }

        .zatca-button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .zatca-button:focus-visible,
        .zatca-close:focus-visible {
            outline: 3px solid #93c5fd;
            outline-offset: 3px;
        }

        .zatca-action-note {
            flex-basis: 100%;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
        }

        .zatca-empty {
            padding: 40px !important;
            text-align: center;
            color: #64748b;
        }

        #zatca-otp-dialog {
            width: min(460px, calc(100vw - 32px));
            max-height: calc(100vh - 40px);
            margin: auto;
            padding: 0;
            border: 0;
            border-radius: 20px;
            background: #fff;
            color: #10203b;
            overflow-y: auto;
            box-shadow: 0 24px 80px rgb(15 23 42 / 25%);
        }

        #zatca-otp-dialog::backdrop {
            background: rgb(15 23 42 / 55%);
        }

        .zatca-dialog-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .zatca-dialog-header h5 {
            margin: 0 0 8px;
            font-size: 19px;
            font-weight: 700;
        }

        .zatca-dialog-header p {
            margin: 0;
        }

        .zatca-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            font-size: 24px;
            cursor: pointer;
        }

        .zatca-close:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .zatca-dialog-body {
            padding: 24px;
        }

        .zatca-otp-label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
        }

        #zatca-otp {
            box-sizing: border-box;
            width: 100%;
            height: 62px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            color: #10203b;
            text-align: center;
            direction: ltr;
            font-size: 28px;
            letter-spacing: 8px;
        }

        #zatca-otp:focus {
            outline: 0;
            border-color: #3165f5;
            box-shadow: 0 0 0 3px rgb(49 101 245 / 12%);
        }

        .zatca-otp-help {
            margin: 12px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.8;
        }

        .zatca-dialog-footer {
            display: flex;
            gap: 10px;
            padding: 18px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .zatca-dialog-footer .zatca-button-primary {
            flex: 1;
        }

        @media (max-width: 640px) {
            .zatca-page {
                padding: 16px;
            }

            .zatca-header {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .zatca-table th,
            .zatca-table td {
                padding: 14px 12px;
            }
        }
    </style>

    <div class="zatca-page" dir="rtl">

        <div class="zatca-header">
            <div>
                <h4>أجهزة ZATCA</h4>
                <div class="zatca-muted">
                    إدارة شهادات الربط والإرسال التلقائي
                </div>
            </div>

            <a href="{{ route('zatca.devices.create') }}"
               class="zatca-button zatca-button-primary">
                إضافة جهاز
            </a>
        </div>

        @if(session('success'))
            <div class="zatca-notice zatca-notice-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="zatca-notice zatca-notice-error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="zatca-notice zatca-notice-error" role="alert">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="zatca-notice zatca-notice-info">
            <strong>البيئة الحالية: {{ $environmentLabel }}</strong>

            @if($environment === 'developer')
                <p>
                    هذه البيئة لاختبارات الامتثال.
                    تفعيل الإرسال يتطلب إعداد المحاكاة أو الإنتاج.
                </p>
            @elseif($environment === 'simulation')
                <p>
                    الطلبات تُرسل إلى بيئة المحاكاة للاختبار.
                </p>
            @endif
        </div>

        <div class="zatca-card">
            <div class="zatca-table-wrap">
                <table class="zatca-table">
                    <thead>
                        <tr>
                            <th scope="col">الجهاز</th>
                            <th scope="col">الرقم التسلسلي</th>
                            <th scope="col">البيئة</th>
                            <th scope="col">نوع الفواتير</th>
                            <th scope="col">الحالة</th>
                            <th scope="col">ICV</th>
                            <th scope="col">الإرسال</th>
                            <th scope="col">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($devices as $device)
                            @php
                                $environmentMatches =
                                    $device->environment === $environment;

                                $canEnableSubmission =
                                    $device->status === 'production'
                                    && $environmentMatches
                                    && in_array(
                                        $environment,
                                        ['simulation', 'production'],
                                        true
                                    )
                                    && filled($device->production_certificate)
                                    && filled($device->production_secret)
                                    && filled($device->private_key);
                            @endphp

                            <tr>
                                <td class="zatca-device-name">
                                    {{ $device->device_name }}
                                </td>

                                <td>
                                    <span class="zatca-serial">
                                        {{ $device->egs_serial_number }}
                                    </span>
                                </td>

                                <td>
                                    {{ match($device->environment) {
                                        'production' => 'إنتاج',
                                        'simulation' => 'محاكاة',
                                        'developer' => 'مطور',
                                        default => $device->environment,
                                    } }}
                                </td>

                                <td>
                                    {{ $device->invoice_type }}
                                </td>

                                <td>
                                    @switch($device->status)
                                        @case('production')
                                            <span class="zatca-badge zatca-badge-green">
                                                شهادة الإرسال جاهزة
                                            </span>
                                            @break

                                        @case('compliance')
                                            <span class="zatca-badge zatca-badge-blue">
                                                شهادة الامتثال جاهزة
                                            </span>
                                            @break

                                        @case('csr_generated')
                                            <span class="zatca-badge zatca-badge-amber">
                                                بانتظار رمز OTP
                                            </span>
                                            @break

                                        @default
                                            <span class="zatca-badge zatca-badge-gray">
                                                جديد
                                            </span>
                                    @endswitch
                                </td>

                                <td>
                                    {{ $device->invoice_counter ?? 0 }}
                                </td>

                                <td>
                                    @if($device->auto_submit)
                                        <span class="zatca-badge zatca-badge-green">
                                            مفعل
                                        </span>
                                    @else
                                        <span class="zatca-badge zatca-badge-gray">
                                            متوقف
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="zatca-actions">

                                        @if($device->status === 'new')
                                            <form
                                                method="POST"
                                                action="{{ route('zatca.devices.generate-csr', $device) }}"
                                                class="js-zatca-action"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="zatca-button zatca-button-primary"
                                                    data-loading-text="جارٍ الإنشاء…"
                                                >
                                                    إنشاء طلب الشهادة
                                                </button>
                                            </form>

                                        @elseif($device->status === 'csr_generated')
                                            <button
                                                type="button"
                                                class="zatca-button zatca-button-primary js-open-otp"
                                                data-url="{{ route('zatca.devices.compliance', $device) }}"
                                                data-device="{{ $device->device_name }}"
                                                @disabled(!$environmentMatches)
                                            >
                                                إدخال رمز التحقق
                                            </button>

                                        @elseif($device->status === 'compliance')
                                            <a href="{{ route('zatca.devices.compliance-tests', $device) }}"
                                               class="btn btn-primary btn-sm">
                                                اختبارات الامتثال
                                            </a>
                                        @endif

                                        @if(
                                            $device->auto_submit
                                            || $device->status === 'production'
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route('zatca.devices.toggle-auto-submit', $device) }}"
                                                class="js-zatca-action"
                                                data-confirm="{{ $device->auto_submit
                                                    ? 'هل تريد إيقاف الإرسال التلقائي؟'
                                                    : 'هل تريد تفعيل الإرسال التلقائي في بيئة '.$environmentLabel.'؟' }}"
                                            >
                                                @csrf

                                                <input
                                                    type="hidden"
                                                    name="enabled"
                                                    value="{{ $device->auto_submit ? 0 : 1 }}"
                                                >

                                                @if($device->auto_submit)
                                                    <button
                                                        type="submit"
                                                        class="zatca-button zatca-button-danger"
                                                        data-loading-text="جارٍ الإيقاف…"
                                                    >
                                                        إيقاف الإرسال
                                                    </button>
                                                @else
                                                    <button
                                                        type="submit"
                                                        class="zatca-button zatca-button-primary"
                                                        data-loading-text="جارٍ التفعيل…"
                                                        @disabled(!$canEnableSubmission)
                                                    >
                                                        تفعيل الإرسال
                                                    </button>
                                                @endif
                                            </form>
                                        @endif

                                        @if(!$environmentMatches)
                                            <span class="zatca-action-note">
                                                بيئة الجهاز تختلف عن البيئة الحالية.
                                            </span>
                                        @elseif(
                                            $device->status === 'production'
                                            && !$device->auto_submit
                                            && !$canEnableSubmission
                                        )
                                            <span class="zatca-action-note">
                                                تفعيل الإرسال يتطلب بيئة محاكاة أو إنتاج
                                                وبيانات شهادة مكتملة.
                                            </span>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="zatca-empty">
                                    لا توجد أجهزة ZATCA.
                                    أضف جهازًا لبدء إعداد الربط.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- نافذة واحدة لإدخال OTP، خارج الجدول --}}
    <dialog
        id="zatca-otp-dialog"
        dir="rtl"
        aria-labelledby="zatca-otp-title"
    >
        <div class="zatca-dialog-header">
            <div>
                <h5 id="zatca-otp-title">طلب شهادة الامتثال</h5>

                <p class="zatca-muted">
                    الجهاز: <span id="zatca-otp-device"></span>
                </p>
            </div>

            <button
                id="zatca-otp-close"
                type="button"
                class="zatca-close"
                aria-label="إغلاق"
            >
                &times;
            </button>
        </div>

        <form id="zatca-otp-form" method="POST">
            @csrf

            <div class="zatca-dialog-body">
                <div class="zatca-notice zatca-notice-info">
                    البيئة: <strong>{{ $environmentLabel }}</strong>
                </div>

                <label for="zatca-otp" class="zatca-otp-label">
                    رمز التحقق OTP
                </label>

                <input
                    id="zatca-otp"
                    name="otp"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    pattern="[0-9]{6}"
                    minlength="6"
                    maxlength="6"
                    placeholder="000000"
                    title="أدخل رمز التحقق المكون من 6 أرقام"
                    aria-describedby="zatca-otp-help"
                    required
                >

                <p id="zatca-otp-help" class="zatca-otp-help">
                    أدخل الرمز الصادر من منصة فاتورة للبيئة المحددة،
                    ثم اضغط «طلب الشهادة».
                </p>

                <div
                    id="zatca-otp-status"
                    class="zatca-otp-help"
                    role="status"
                    aria-live="polite"
                ></div>
            </div>

            <div class="zatca-dialog-footer">
                <button
                    id="zatca-otp-submit"
                    type="submit"
                    class="zatca-button zatca-button-primary"
                >
                    طلب الشهادة
                </button>

                <button
                    id="zatca-otp-cancel"
                    type="button"
                    class="zatca-button zatca-button-secondary"
                >
                    إلغاء
                </button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('zatca-otp-dialog');
            const form = document.getElementById('zatca-otp-form');
            const input = document.getElementById('zatca-otp');
            const submit = document.getElementById('zatca-otp-submit');
            const cancel = document.getElementById('zatca-otp-cancel');
            const close = document.getElementById('zatca-otp-close');
            const status = document.getElementById('zatca-otp-status');
            const deviceName = document.getElementById('zatca-otp-device');

            let sending = false;

            function resetOtpForm() {
                sending = false;
                form.reset();
                form.removeAttribute('aria-busy');
                input.readOnly = false;
                submit.disabled = false;
                cancel.disabled = false;
                close.disabled = false;
                submit.textContent = 'طلب الشهادة';
                status.textContent = '';
            }

            function closeOtpDialog() {
                if (!sending) {
                    dialog.close();
                }
            }

            document.querySelectorAll('.js-open-otp').forEach(button => {
                button.addEventListener('click', () => {
                    resetOtpForm();

                    form.action = button.dataset.url;
                    deviceName.textContent = button.dataset.device;

                    dialog.showModal();
                    input.focus();
                });
            });

            // تحويل الأرقام العربية والفارسية إلى الإنجليزية.
            input.addEventListener('input', () => {
                input.value = input.value
                    .replace(/[٠-٩]/g, digit =>
                        String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit))
                    )
                    .replace(/[۰-۹]/g, digit =>
                        String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
                    );
            });

            cancel.addEventListener('click', closeOtpDialog);
            close.addEventListener('click', closeOtpDialog);

            dialog.addEventListener('cancel', event => {
                if (sending) {
                    event.preventDefault();
                }
            });

            dialog.addEventListener('close', resetOtpForm);

            form.addEventListener('submit', event => {
                if (sending) {
                    event.preventDefault();
                    return;
                }

                if (!form.reportValidity()) {
                    event.preventDefault();
                    return;
                }

                sending = true;
                form.setAttribute('aria-busy', 'true');

                input.readOnly = true;
                submit.disabled = true;
                cancel.disabled = true;
                close.disabled = true;

                submit.textContent = 'جارٍ طلب الشهادة…';
                status.textContent = 'ننتظر رد زاتكا، يرجى عدم تكرار الطلب.';
            });

            const actionForms = document.querySelectorAll('.js-zatca-action');

            actionForms.forEach(actionForm => {
                const button = actionForm.querySelector('button[type="submit"]');

                button.dataset.originalText = button.textContent.trim();
                button.dataset.originalDisabled = button.disabled ? '1' : '0';

                actionForm.addEventListener('submit', event => {
                    if (actionForm.dataset.sending === '1') {
                        event.preventDefault();
                        return;
                    }

                    const message = actionForm.dataset.confirm;

                    if (message && !window.confirm(message)) {
                        event.preventDefault();
                        return;
                    }

                    actionForm.dataset.sending = '1';
                    button.disabled = true;
                    button.textContent =
                        button.dataset.loadingText || 'جارٍ التنفيذ…';
                });
            });

            // استعادة الأزرار عند الرجوع للصفحة من سجل المتصفح.
            window.addEventListener('pageshow', () => {
                resetOtpForm();

                if (dialog.open) {
                    dialog.close();
                }

                actionForms.forEach(actionForm => {
                    const button = actionForm.querySelector('button[type="submit"]');

                    delete actionForm.dataset.sending;
                    button.disabled = button.dataset.originalDisabled === '1';
                    button.textContent = button.dataset.originalText;
                });
            });
        })();
    </script>
</x-app-layout>