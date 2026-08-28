@php
    $code = $code ?? 'خطأ';
    $title = $title ?? 'حدث خطأ غير متوقع';
    $message = $message ?? 'تعذر تنفيذ الطلب في الوقت الحالي.';
    $accent = $accent ?? '#E63B4A';

    $dashboardUrl = \Illuminate\Support\Facades\Route::has('dashboard')
        ? route('dashboard')
        : url('/');

    $loginUrl = \Illuminate\Support\Facades\Route::has('login')
        ? route('login')
        : url('/login');

    $previousUrl = url()->previous() !== url()->current()
        ? url()->previous()
        : $dashboardUrl;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $code }} | {{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --wazin-navy: #071633;
            --wazin-navy-2: #0A1730;
            --wazin-blue: #2F6BFF;
            --wazin-blue-hover: #2559D9;
            --wazin-cyan: #CFEFF3;
            --wazin-bg: #ECEEF2;
            --wazin-card: #FFFFFF;
            --wazin-text: #111827;
            --wazin-muted: #64748B;
            --wazin-border: #E5E7EB;
            --wazin-accent: {{ $accent }};
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(47, 107, 255, .12), transparent 35%),
                linear-gradient(135deg, #ECEEF2, #F8FAFC);
            font-family: Tahoma, Arial, sans-serif;
            color: var(--wazin-text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .error-wrapper {
            width: min(960px, 100%);
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            background: var(--wazin-card);
            border: 1px solid var(--wazin-border);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 24px 65px rgba(7, 22, 51, .14);
        }

        .error-brand {
            background: linear-gradient(135deg, var(--wazin-navy), var(--wazin-navy-2));
            color: #fff;
            padding: 38px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 34px;
            min-height: 430px;
        }

        .brand-title {
            font-size: 26px;
            font-weight: 900;
            margin: 0 0 8px;
            color: #fff;
        }

        .brand-subtitle {
            color: var(--wazin-cyan);
            font-weight: 700;
            line-height: 1.9;
            margin: 0;
        }

        .brand-badge {
            width: 76px;
            height: 76px;
            border-radius: 24px;
            background: var(--wazin-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 900;
            box-shadow: 0 14px 32px rgba(47, 107, 255, .32);
        }

        .brand-footer {
            color: rgba(255,255,255,.72);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.9;
        }

        .error-content {
            padding: 42px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .error-code {
            color: var(--wazin-accent);
            font-size: 86px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 18px;
            letter-spacing: -2px;
        }

        .error-title {
            color: var(--wazin-navy);
            font-size: 28px;
            font-weight: 900;
            margin: 0 0 12px;
        }

        .error-message {
            color: var(--wazin-muted);
            font-size: 15px;
            font-weight: 700;
            line-height: 2;
            margin: 0 0 28px;
            max-width: 560px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 11px 22px;
            border-radius: 15px;
            font-weight: 900;
            text-decoration: none;
            border: 1px solid transparent;
            transition: .2s ease;
        }

        .btn-primary {
            background: var(--wazin-blue);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--wazin-blue-hover);
            color: #fff;
        }

        .btn-outline {
            background: #fff;
            color: var(--wazin-navy);
            border-color: #CBD5E1;
        }

        .btn-outline:hover {
            background: #F1F5F9;
            color: var(--wazin-navy);
        }

        .hint-box {
            margin-top: 26px;
            background: #F8FAFC;
            border: 1px solid var(--wazin-border);
            border-right: 5px solid var(--wazin-accent);
            border-radius: 18px;
            padding: 14px 16px;
            color: var(--wazin-muted);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.9;
        }

        .hint-box strong {
            color: var(--wazin-navy);
        }

        @media (max-width: 820px) {
            .error-wrapper {
                grid-template-columns: 1fr;
            }

            .error-brand {
                min-height: auto;
                padding: 28px 24px;
            }

            .error-content {
                padding: 32px 24px;
            }

            .error-code {
                font-size: 70px;
            }

            .error-title {
                font-size: 23px;
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 14px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <main class="error-wrapper">

        <section class="error-brand">
            <div>
                <div class="brand-badge">و</div>

                <div style="margin-top: 26px;">
                    <h1 class="brand-title">وازن ERP</h1>
                    <p class="brand-subtitle">
                        نظام إدارة المبيعات والمخزون والمحاسبة
                    </p>
                </div>
            </div>

            <div class="brand-footer">
                وازن التقنية — إدارة متوازنة لأعمالك
            </div>
        </section>

        <section class="error-content">
            <div class="error-code">{{ $code }}</div>

            <h2 class="error-title">{{ $title }}</h2>

            <p class="error-message">
                {{ $message }}
            </p>

            <div class="actions">
                @auth
                    <a href="{{ $dashboardUrl }}" class="btn btn-primary">
                        العودة إلى لوحة التحكم
                    </a>
                @else
                    <a href="{{ $loginUrl }}" class="btn btn-primary">
                        تسجيل الدخول
                    </a>
                @endauth

                <a href="{{ $previousUrl }}" class="btn btn-outline">
                    الرجوع للخلف
                </a>
            </div>

            <div class="hint-box">
                <strong>معلومة:</strong>
                إذا تكررت هذه الرسالة، يرجى التواصل مع مدير النظام أو الدعم الفني مع توضيح الصفحة التي كنت تحاول الوصول إليها.
            </div>
        </section>

    </main>

</body>
</html>