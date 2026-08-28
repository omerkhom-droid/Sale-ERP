<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وازن ERP | نظام إدارة المبيعات والمخزون والمحاسبة</title>

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
            --wazin-success: #16A34A;
            --wazin-warning: #F59E0B;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: var(--wazin-bg);
            color: var(--wazin-text);
        }

        a {
            text-decoration: none;
        }

        .container {
            width: min(1180px, calc(100% - 40px));
            margin: 0 auto;
        }

        .landing-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(47, 107, 255, 0.22), transparent 34%),
                radial-gradient(circle at bottom left, rgba(207, 239, 243, 0.28), transparent 38%),
                linear-gradient(135deg, #071633 0%, #0A1730 52%, #101F3C 100%);
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .landing-page::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(47, 107, 255, 0.12);
            left: -180px;
            top: 180px;
            filter: blur(2px);
        }

        .topbar {
            min-height: 86px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 5;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 17px;
            background: var(--wazin-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 900;
            box-shadow: 0 14px 36px rgba(47, 107, 255, 0.36);
        }

        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
        }

        .brand small {
            display: block;
            margin-top: 4px;
            color: var(--wazin-muted);
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 900;
            transition: all .18s ease-in-out;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--wazin-blue);
            color: #fff;
            box-shadow: 0 14px 34px rgba(47, 107, 255, 0.30);
        }

        .btn-primary:hover {
            background: var(--wazin-blue-hover);
            transform: translateY(-2px);
        }

        .btn-outline {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .hero {
            padding: 80px 0 70px;
            position: relative;
            z-index: 2;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 44px;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(207, 239, 243, 0.12);
            border: 1px solid rgba(207, 239, 243, 0.18);
            color: var(--wazin-cyan);
            border-radius: 999px;
            padding: 9px 15px;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .hero h2 {
            margin: 0 0 20px;
            font-size: 50px;
            line-height: 1.32;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .hero h2 span {
            color: var(--wazin-cyan);
        }

        .hero p {
            margin: 0;
            color: #D8E3F3;
            font-size: 17px;
            line-height: 2;
            font-weight: 600;
            max-width: 690px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .hero-note {
            margin-top: 22px;
            display: flex;
            gap: 10px;
            align-items: center;
            color: var(--wazin-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .hero-panel {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 30px;
            padding: 24px;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.26);
            backdrop-filter: blur(12px);
            position: relative;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            background: rgba(47, 107, 255, 0.25);
            border-radius: 50%;
            top: -40px;
            left: -40px;
            filter: blur(4px);
        }

        .panel-inner {
            position: relative;
            z-index: 2;
            background: #fff;
            color: var(--wazin-text);
            border-radius: 24px;
            overflow: hidden;
        }

        .panel-header {
            background: var(--wazin-navy);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
        }

        .panel-dots {
            display: flex;
            gap: 6px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.38);
        }

        .panel-body {
            padding: 20px;
        }

        .metric-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }

        .metric-card {
            background: #F8FAFC;
            border: 1px solid var(--wazin-border);
            border-radius: 18px;
            padding: 16px;
        }

        .metric-card small {
            display: block;
            color: #64748B;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .metric-card strong {
            color: var(--wazin-navy);
            font-size: 21px;
            font-weight: 900;
        }

        .progress-card {
            margin-top: 14px;
            background: #F8FAFC;
            border: 1px solid var(--wazin-border);
            border-radius: 18px;
            padding: 16px;
        }

        .progress-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 900;
            color: var(--wazin-navy);
        }

        .progress-bar {
            height: 10px;
            border-radius: 999px;
            background: #E5E7EB;
            overflow: hidden;
        }

        .progress-fill {
            width: 100%;
            height: 100%;
            background: var(--wazin-blue);
            border-radius: 999px;
        }

        .mini-list {
            margin-top: 16px;
            display: grid;
            gap: 10px;
        }

        .mini-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border: 1px solid var(--wazin-border);
            border-radius: 16px;
            background: #fff;
            font-size: 13px;
            font-weight: 800;
            color: #475569;
        }

        .mini-badge {
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.10);
            color: var(--wazin-success);
            font-size: 12px;
            font-weight: 900;
        }

        .section {
            padding: 70px 0;
            background: var(--wazin-bg);
            color: var(--wazin-text);
        }

        .section-header {
            text-align: center;
            max-width: 760px;
            margin: 0 auto 36px;
        }

        .section-header h3 {
            margin: 0 0 12px;
            color: var(--wazin-navy);
            font-size: 34px;
            font-weight: 900;
        }

        .section-header p {
            margin: 0;
            color: #64748B;
            line-height: 2;
            font-weight: 600;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            background: #fff;
            border: 1px solid var(--wazin-border);
            border-radius: 24px;
            padding: 24px;
            min-height: 205px;
            box-shadow: 0 14px 36px rgba(7, 22, 51, 0.08);
            transition: all .18s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 44px rgba(47, 107, 255, 0.14);
            border-color: rgba(47, 107, 255, 0.35);
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(47, 107, 255, 0.10);
            font-size: 25px;
            margin-bottom: 16px;
        }

        .feature-card h4 {
            margin: 0 0 10px;
            color: var(--wazin-navy);
            font-size: 18px;
            font-weight: 900;
        }

        .feature-card p {
            margin: 0;
            color: #64748B;
            line-height: 1.8;
            font-size: 14px;
            font-weight: 600;
        }

        .modules-section {
            background: #fff;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .module-card {
            border: 1px solid var(--wazin-border);
            border-radius: 22px;
            padding: 22px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(7, 22, 51, 0.06);
        }

        .module-card h4 {
            margin: 0 0 14px;
            color: var(--wazin-navy);
            font-size: 18px;
            font-weight: 900;
        }

        .module-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .module-card li {
            padding: 9px 0;
            color: #475569;
            font-size: 14px;
            font-weight: 700;
            border-bottom: 1px dashed #E5E7EB;
        }

        .module-card li:last-child {
            border-bottom: 0;
        }

        .cta-section {
            background: var(--wazin-navy);
            color: #fff;
            padding: 64px 0;
        }

        .cta-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            background:
                radial-gradient(circle at top left, rgba(47, 107, 255, 0.24), transparent 34%),
                var(--wazin-navy-2);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 28px;
            padding: 34px;
        }

        .cta-box h3 {
            margin: 0 0 10px;
            font-size: 30px;
            font-weight: 900;
        }

        .cta-box p {
            margin: 0;
            color: var(--wazin-muted);
            font-weight: 700;
            line-height: 1.8;
        }

        .footer {
            background: #061126;
            color: var(--wazin-muted);
            padding: 24px 0;
            font-size: 13px;
            font-weight: 700;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero h2 {
                font-size: 38px;
            }

            .features-grid,
            .modules-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cta-box {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .container {
                width: min(100% - 24px, 1180px);
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
                padding: 18px 0;
            }

            .top-actions {
                justify-content: stretch;
            }

            .top-actions .btn {
                flex: 1;
            }

            .hero {
                padding: 48px 0 52px;
            }

            .hero h2 {
                font-size: 31px;
            }

            .hero p {
                font-size: 15px;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .features-grid,
            .modules-grid,
            .metric-row {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 52px 0;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="landing-page">

    <header class="container topbar">
        <div class="brand">
            <div class="brand-mark">W</div>
            <div>
                <h1>وازن ERP</h1>
                <small>Wazin Tech</small>
            </div>
        </div>

        <div class="top-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    الدخول للوحة التحكم
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        تسجيل الدخول
                    </a>
                @endif
            @endauth
        </div>
    </header>

    <main class="container hero">
        <div class="hero-grid">

            <section>
                <div class="hero-badge">🚀 نظام ERP عربي متكامل</div>

                <h2>
                    إدارة متوازنة
                    <br>
                    <span>للمبيعات والمخزون والمحاسبة</span>
                </h2>

                <p>
                    وازن ERP يساعدك على إدارة الفواتير، المشتريات، العملاء، الموردين،
                    المخزون، القيود المحاسبية، والتقارير المالية من مكان واحد بواجهة عربية واضحة.
                </p>

                <div class="hero-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            فتح النظام
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                دخول المستخدم
                            </a>
                        @endif
                    @endauth

                    <a href="#features" class="btn btn-outline">
                        استعراض المزايا
                    </a>
                </div>

                <div class="hero-note">
                    🔐 نظام داخلي قابل للتشغيل بصلاحيات وأدوار متعددة حسب المستخدم.
                </div>
            </section>

            <section class="hero-panel">
                <div class="panel-inner">
                    <div class="panel-header">
                        <strong>لوحة وازن ERP</strong>
                        <div class="panel-dots">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>

                    <div class="panel-body">
                        <div class="metric-row">
                            <div class="metric-card">
                                <small>المبيعات</small>
                                <strong>فواتير وسندات</strong>
                            </div>

                            <div class="metric-card">
                                <small>المخزون</small>
                                <strong>حركة دقيقة</strong>
                            </div>
                        </div>

                        <div class="metric-row">
                            <div class="metric-card">
                                <small>المحاسبة</small>
                                <strong>قيود وتقارير</strong>
                            </div>

                            <div class="metric-card">
                                <small>الصلاحيات</small>
                                <strong>تحكم كامل</strong>
                            </div>
                        </div>

                        <div class="progress-card">
                            <div class="progress-title">
                                <span>حالة النظام</span>
                                <span>جاهز للتجربة التشغيلية</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="mini-list">
                            <div class="mini-item">
                                <span>الطباعة الموحدة</span>
                                <span class="mini-badge">مفعلة</span>
                            </div>
                            <div class="mini-item">
                                <span>الأدوار والصلاحيات</span>
                                <span class="mini-badge">مفعلة</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

</div>

<section class="section" id="features">
    <div class="container">

        <div class="section-header">
            <h3>مزايا وازن ERP</h3>
            <p>
                نظام مصمم لإدارة العمليات اليومية بشكل مرتب، مع ربط المبيعات والمشتريات والمخزون بالمحاسبة.
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🧾</div>
                <h4>الفواتير والسندات</h4>
                <p>إدارة فواتير البيع والشراء وسندات القبض والصرف والمردودات من مكان واحد.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <h4>المخزون والمنتجات</h4>
                <p>متابعة الأصناف، الوحدات، المستودعات، الأرصدة، وحركة المخزون بدقة.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📒</div>
                <h4>المحاسبة</h4>
                <p>دليل حسابات، قيود يومية، مراكز تكلفة، أرصدة افتتاحية، وترحيل محاسبي.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h4>التقارير</h4>
                <p>تقارير مالية وتشغيلية تساعد الإدارة على متابعة الأداء واتخاذ القرار.</p>
            </div>
        </div>

    </div>
</section>

<section class="section modules-section">
    <div class="container">

        <div class="section-header">
            <h3>وحدات النظام</h3>
            <p>
                أهم الوحدات المتوفرة في وازن ERP لتجهيز النظام للتشغيل الفعلي.
            </p>
        </div>

        <div class="modules-grid">
            <div class="module-card">
                <h4>المبيعات</h4>
                <ul>
                    <li>عروض الأسعار</li>
                    <li>فواتير المبيعات</li>
                    <li>مردودات المبيعات</li>
                    <li>سندات قبض العملاء</li>
                    <li>كشف حساب العميل</li>
                </ul>
            </div>

            <div class="module-card">
                <h4>المشتريات</h4>
                <ul>
                    <li>فواتير المشتريات</li>
                    <li>مردودات المشتريات</li>
                    <li>سندات صرف الموردين</li>
                    <li>كشف حساب المورد</li>
                    <li>أرصدة الموردين</li>
                </ul>
            </div>

            <div class="module-card">
                <h4>المحاسبة والتقارير</h4>
                <ul>
                    <li>القيود اليومية</li>
                    <li>دفتر الأستاذ العام</li>
                    <li>ميزان المراجعة</li>
                    <li>قائمة الدخل</li>
                    <li>التدفقات النقدية</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <div>
                <h3>ابدأ إدارة أعمالك من لوحة واحدة</h3>
                <p>
                    سجّل الدخول للوصول إلى لوحة التحكم وإدارة العمليات اليومية والتقارير.
                </p>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    فتح لوحة التحكم
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        تسجيل الدخول
                    </a>
                @endif
            @endauth
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container footer-content">
        <div>© {{ date('Y') }} Wazin Tech — جميع الحقوق محفوظة</div>
        <div>وازن ERP | نظام إدارة المبيعات والمخزون والمحاسبة</div>
    </div>
</footer>

</body>
</html>