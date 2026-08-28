<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | وازن ERP</title>

    <style>
        :root {
            --wazin-navy: #071633;
            --wazin-navy-2: #0A1730;
            --wazin-navy-3: #101F3C;
            --wazin-blue: #2F6BFF;
            --wazin-blue-hover: #2559D9;
            --wazin-cyan: #CFEFF3;
            --wazin-bg: #ECEEF2;
            --wazin-card: #FFFFFF;
            --wazin-text: #111827;
            --wazin-muted: #8EA0B8;
            --wazin-border: #E5E7EB;
            --wazin-danger: #E63B4A;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(47, 107, 255, 0.22), transparent 34%),
                radial-gradient(circle at bottom left, rgba(207, 239, 243, 0.20), transparent 36%),
                linear-gradient(135deg, var(--wazin-navy), var(--wazin-navy-2));
            color: var(--wazin-text);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
        }

        .login-brand-side {
            padding: 60px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-brand-side::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(47, 107, 255, 0.18);
            top: -120px;
            right: -120px;
        }

        .login-brand-side::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 1px solid rgba(207, 239, 243, 0.22);
            bottom: 70px;
            left: 70px;
        }

        .brand-content,
        .brand-footer {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 70px;
        }

        .brand-mark {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: var(--wazin-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            box-shadow: 0 14px 36px rgba(47, 107, 255, 0.36);
        }

        .brand-logo h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 900;
        }

        .brand-logo small {
            color: var(--wazin-muted);
            font-weight: 700;
        }

        .brand-title {
            max-width: 620px;
        }

        .brand-title h2 {
            font-size: 42px;
            line-height: 1.4;
            margin: 0 0 18px;
            font-weight: 900;
        }

        .brand-title p {
            color: var(--wazin-cyan);
            line-height: 2;
            font-size: 17px;
            font-weight: 600;
            margin: 0;
        }

        .brand-features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 36px;
            max-width: 620px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 18px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(8px);
            font-weight: 800;
        }

        .brand-footer {
            color: var(--wazin-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .login-form-side {
            background: var(--wazin-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-card {
            width: 100%;
            max-width: 460px;
            background: var(--wazin-card);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 28px 70px rgba(7, 22, 51, 0.18);
            border: 1px solid rgba(229, 231, 235, 0.9);
        }

        .login-card-header {
            margin-bottom: 28px;
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(47, 107, 255, 0.10);
            color: var(--wazin-blue);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .login-card h3 {
            margin: 0 0 8px;
            color: var(--wazin-navy);
            font-size: 28px;
            font-weight: 900;
        }

        .login-card p {
            margin: 0;
            color: #64748B;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.8;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--wazin-navy);
            font-size: 14px;
            font-weight: 900;
        }

        .form-control {
            width: 100%;
            height: 50px;
            border: 1px solid var(--wazin-border);
            border-radius: 16px;
            padding: 0 15px;
            font-size: 15px;
            color: var(--wazin-text);
            outline: none;
            background: #fff;
            transition: all .18s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--wazin-blue);
            box-shadow: 0 0 0 4px rgba(47, 107, 255, 0.10);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--wazin-blue);
            font-weight: 900;
            cursor: pointer;
            padding: 6px 8px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 18px 0 24px;
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748B;
            font-size: 14px;
            font-weight: 700;
        }

        .remember-label input {
            width: 17px;
            height: 17px;
            accent-color: var(--wazin-blue);
        }

        .forgot-link {
            color: var(--wazin-blue);
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
        }

        .forgot-link:hover {
            color: var(--wazin-blue-hover);
        }

        .btn-login {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 16px;
            background: var(--wazin-blue);
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 16px 34px rgba(47, 107, 255, 0.28);
            transition: all .18s ease-in-out;
        }

        .btn-login:hover {
            background: var(--wazin-blue-hover);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 16px;
            padding: 13px 15px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.8;
        }

        .alert-success {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }

        .alert-danger {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }

        .error-text {
            margin-top: 8px;
            color: var(--wazin-danger);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.7;
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            color: #94A3B8;
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 992px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-brand-side {
                display: none;
            }

            .login-form-side {
                min-height: 100vh;
                padding: 24px;
            }

            .login-card {
                padding: 28px 22px;
                border-radius: 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <section class="login-brand-side">
        <div class="brand-content">

            <div class="brand-logo">
                <div class="brand-mark">W</div>
                <div>
                    <h1>وازن ERP</h1>
                    <small>Wazin Tech</small>
                </div>
            </div>

            <div class="brand-title">
                <h2>إدارة متوازنة لأعمالك</h2>
                <p>
                    نظام متكامل لإدارة المبيعات والمشتريات والمخزون والمحاسبة،
                    مصمم ليوفر رؤية واضحة وتحكمًا أفضل في العمليات اليومية.
                </p>

                <div class="brand-features">
                    <div class="feature-item">🧾 الفواتير والسندات</div>
                    <div class="feature-item">📦 المخزون والمنتجات</div>
                    <div class="feature-item">📊 التقارير المالية</div>
                    <div class="feature-item">🔐 صلاحيات المستخدمين</div>
                </div>
            </div>

        </div>

        <div class="brand-footer">
            © {{ date('Y') }} Wazin Tech — نظام إدارة المبيعات والمخزون والمحاسبة
        </div>
    </section>


    <section class="login-form-side">

        <div class="login-card">

            <div class="login-card-header">
                <div class="login-badge">🔐 دخول آمن للنظام</div>

                <h3>تسجيل الدخول</h3>
                <p>
                    أدخل بيانات الدخول للوصول إلى لوحة تحكم وازن ERP.
                </p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="example@email.com"
                    >

                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">كلمة المرور</label>

                    <div class="password-wrapper">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >

                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            إظهار
                        </button>
                    </div>

                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="remember-row">
                    <label for="remember_me" class="remember-label">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>تذكرني</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            نسيت كلمة المرور؟
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn-login">
                    تسجيل الدخول
                </button>
            </form>

            <div class="login-footer">
                تطوير وازن التقنية — Wazin ERP
            </div>

        </div>

    </section>

</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const button = document.querySelector('.password-toggle');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            button.textContent = 'إخفاء';
        } else {
            passwordInput.type = 'password';
            button.textContent = 'إظهار';
        }
    }
</script>

</body>
</html>