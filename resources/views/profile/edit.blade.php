<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            الملف الشخصي
        </h2>
    </x-slot>

    <div class="container-fluid py-4 profile-page" dir="rtl">

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">الملف الشخصي</h3>
                <p class="page-subtitle mb-0">
                    إدارة بيانات الحساب وكلمة المرور وإعدادات المستخدم الخاصة بك.
                </p>
            </div>

            <div class="profile-user-badge">
                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1, 'UTF-8'), 'UTF-8') }}
            </div>
        </div>


        {{-- Profile Sections --}}
        <div class="profile-grid">

            <div class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h5>بيانات الحساب</h5>
                        <small>تحديث الاسم والبريد الإلكتروني الخاص بالمستخدم</small>
                    </div>
                </div>

                <div class="profile-card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>


            <div class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h5>كلمة المرور</h5>
                        <small>تحديث كلمة المرور للحفاظ على أمان الحساب</small>
                    </div>
                </div>

                <div class="profile-card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

    </div>


    <style>
        .profile-page {
            background: #ECEEF2;
            min-height: calc(100vh - 80px);
        }

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

        .profile-user-badge {
            width: 62px;
            height: 62px;
            border-radius: 20px;
            background: #2F6BFF;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            box-shadow: 0 10px 25px rgba(47, 107, 255, 0.28);
            flex-shrink: 0;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .profile-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(7, 22, 51, 0.05);
        }

        .profile-card.danger-section {
            grid-column: 1 / -1;
        }

        .profile-card-header {
            background: #fff;
            border-bottom: 1px solid #E5E7EB;
            padding: 18px 20px;
            border-right: 5px solid #2F6BFF;
        }

        .profile-card-header h5 {
            margin: 0;
            color: #071633;
            font-weight: 900;
        }

        .profile-card-header small {
            display: block;
            margin-top: 5px;
            color: #8EA0B8;
            font-weight: 700;
            line-height: 1.8;
        }

        .danger-header {
            border-right-color: #E63B4A;
        }

        .profile-card-body {
            padding: 22px;
            background: #F8FAFC;
        }

        .profile-card-body section > header h2,
        .profile-card-body h2 {
            color: #071633 !important;
            font-size: 17px !important;
            font-weight: 900 !important;
            margin-bottom: 6px !important;
        }

        .profile-card-body section > header p,
        .profile-card-body p {
            color: #64748B !important;
            font-weight: 700;
            line-height: 1.8;
        }

        .profile-card-body label {
            color: #071633 !important;
            font-weight: 900 !important;
            margin-bottom: 7px;
        }

        .profile-card-body input[type="text"],
        .profile-card-body input[type="email"],
        .profile-card-body input[type="password"] {
            width: 100%;
            border-radius: 14px !important;
            border: 1px solid #E5E7EB !important;
            min-height: 44px;
            font-weight: 700;
            background-color: #fff !important;
            padding: 10px 12px;
            box-shadow: none !important;
        }

        .profile-card-body input[type="text"]:focus,
        .profile-card-body input[type="email"]:focus,
        .profile-card-body input[type="password"]:focus {
            border-color: #2F6BFF !important;
            box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12) !important;
            outline: none !important;
        }

        .profile-card-body button[type="submit"] {
            background: #2F6BFF !important;
            border: 1px solid #2F6BFF !important;
            color: #fff !important;
            border-radius: 14px !important;
            padding: 10px 20px !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }

        .profile-card-body button[type="submit"]:hover {
            background: #2559D9 !important;
            border-color: #2559D9 !important;
        }

        .danger-section .profile-card-body button[type="submit"] {
            background: #E63B4A !important;
            border-color: #E63B4A !important;
        }

        .danger-section .profile-card-body button[type="submit"]:hover {
            background: #CC2F3D !important;
            border-color: #CC2F3D !important;
        }

        .profile-card-body button[type="button"],
        .profile-card-body a {
            border-radius: 14px !important;
            font-weight: 900 !important;
        }

        .profile-card-body .text-sm,
        .profile-card-body .text-gray-600,
        .profile-card-body .text-gray-500 {
            color: #64748B !important;
        }

        .profile-card-body .text-green-600 {
            color: #16A34A !important;
            font-weight: 900;
        }

        .profile-card-body .text-red-600,
        .profile-card-body .text-red-500 {
            color: #E63B4A !important;
            font-weight: 800;
        }

        @media (max-width: 991px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-card.danger-section {
                grid-column: auto;
            }
        }

        @media (max-width: 767px) {
            .page-header-card {
                flex-direction: column;
                align-items: stretch;
            }

            .profile-user-badge {
                width: 54px;
                height: 54px;
                font-size: 23px;
            }

            .profile-card-body {
                padding: 18px;
            }
        }
    </style>

</x-app-layout>