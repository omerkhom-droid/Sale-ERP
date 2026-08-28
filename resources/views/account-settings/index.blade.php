<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">إعدادات الحسابات المحاسبية</h3>
            <p class="page-subtitle mb-0">
                ربط الحسابات الافتراضية التي سيستخدمها النظام في القيود المحاسبية التلقائية.
            </p>
        </div>
    </div>


    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>يرجى مراجعة البيانات التالية:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form action="{{ route('account-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow-sm wazin-card">

            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">الحسابات الافتراضية</h5>
                    <small>
                        هذه الحسابات يتم استخدامها تلقائياً في فواتير البيع والشراء والسداد والقيود.
                    </small>
                </div>

                @can('account_settings.update')
                    <button type="submit" class="btn btn-primary">
                        حفظ الإعدادات
                    </button>
                @endcan
            </div>

            <div class="card-body">

                <div class="settings-note mb-4">
                    <div class="settings-note-icon">⚙️</div>
                    <div>
                        <strong>تنبيه محاسبي</strong>
                        <p class="mb-0">
                            تأكد من اختيار حسابات نهائية وليست حسابات تجميعية حتى يتم إنشاء القيود التلقائية بشكل صحيح.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0 wazin-table">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th width="220">الإعداد</th>
                                <th>الوصف</th>
                                <th width="440">الحساب المرتبط</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($settings as $index => $setting)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <span class="setting-key">
                                            {{ $setting->setting_key }}
                                        </span>
                                    </td>

                                    <td class="setting-description">
                                        {{ $setting->description }}
                                    </td>

                                    <td>
                                        <select name="settings[{{ $setting->setting_key }}]"
                                                class="form-select text-center">
                                            <option value="">-- اختر الحساب --</option>

                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    @selected(old('settings.' . $setting->setting_key, $setting->account_id) == $account->id)>
                                                    {{ $account->account_code }} - {{ $account->account_name_ar }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>

            @can('account_settings.update')
                <div class="card-footer save-footer">
                    <button type="submit" class="btn btn-primary">
                        حفظ الإعدادات
                    </button>
                </div>
            @endcan

        </div>

    </form>

</div>


<style>
    .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
    }

    .page-title {
        color: #fff;
        font-weight: 900;
    }

    .page-subtitle {
        color: #CFEFF3;
        font-weight: 600;
        line-height: 1.8;
    }

    .wazin-card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
    }

    .wazin-card-header {
        background: #fff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-card .card-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .settings-note {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-right: 5px solid #2F6BFF;
        border-radius: 18px;
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .settings-note-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex: 0 0 auto;
    }

    .settings-note strong {
        color: #071633;
        font-weight: 900;
        display: block;
        margin-bottom: 4px;
    }

    .settings-note p {
        color: #64748B;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-table {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
    }

    .wazin-table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    .wazin-table tbody td {
        vertical-align: middle;
        font-weight: 600;
        background: #fff;
    }

    .setting-key {
        direction: ltr;
        display: inline-block;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        border: 1px solid rgba(47, 107, 255, 0.18);
        border-radius: 999px;
        padding: 7px 12px;
        font-weight: 900;
        font-size: 12px;
    }

    .setting-description {
        color: #071633;
        font-weight: 800;
        line-height: 1.8;
    }

    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 700;
        background-color: #fff;
    }

    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .save-footer {
        background: #fff;
        border-top: 1px solid #E5E7EB;
        padding: 16px 20px;
        display: flex;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .wazin-card-header .btn,
        .save-footer .btn {
            width: 100%;
        }

        .settings-note {
            flex-direction: column;
        }

        .save-footer {
            justify-content: stretch;
        }
    }
</style>

</x-app-layout>