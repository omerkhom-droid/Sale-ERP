<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wazin ERP') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    @stack('styles')

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
            --wazin-danger-hover: #CC2F3D;
            --wazin-shadow: 0 10px 30px rgba(7, 22, 51, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: var(--wazin-bg);
            color: var(--wazin-text);
            overflow-x: hidden;
            font-family: "Figtree", "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 14px;
        }

        body {
            direction: rtl;
            text-align: right;
        }

        a {
            text-decoration: none;
        }

        .app-wrapper {
            min-height: 100vh;
            background: var(--wazin-bg);
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 260px;
            height: 100vh;
            background: var(--wazin-navy);
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            border-left: 1px solid rgba(255, 255, 255, 0.08);
        }

        .app-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .app-sidebar::-webkit-scrollbar-track {
            background: var(--wazin-navy);
        }

        .app-sidebar::-webkit-scrollbar-thumb {
            background: rgba(207, 239, 243, 0.25);
            border-radius: 20px;
        }

        .app-content {
            margin-right: 260px;
            padding: 24px;
            min-height: 100vh;
            background: var(--wazin-bg);
        }

        .page-title {
            color: var(--wazin-navy);
            font-weight: 800;
            margin-bottom: 18px;
        }

        .page-card,
        .card {
            border: 1px solid var(--wazin-border);
            border-radius: 16px;
            background: var(--wazin-card);
            box-shadow: var(--wazin-shadow);
            overflow: hidden;
        }

        .page-card .card-header,
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--wazin-border);
            font-weight: 700;
        }

        .card-header.bg-primary,
        .bg-primary {
            background: var(--wazin-blue) !important;
            color: #fff !important;
        }

        .card-header.bg-dark,
        .bg-dark {
            background: var(--wazin-navy) !important;
            color: #fff !important;
        }

        .card-header.bg-light,
        .bg-light {
            background: #F8FAFC !important;
        }

        .btn {
            border-radius: 12px;
            font-weight: 700;
            padding: 8px 14px;
        }

        .btn-primary {
            background: var(--wazin-blue) !important;
            border-color: var(--wazin-blue) !important;
            color: #fff !important;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--wazin-blue-hover) !important;
            border-color: var(--wazin-blue-hover) !important;
        }

        .btn-dark {
            background: var(--wazin-navy) !important;
            border-color: var(--wazin-navy) !important;
            color: #fff !important;
        }

        .btn-danger {
            background: var(--wazin-danger) !important;
            border-color: var(--wazin-danger) !important;
            color: #fff !important;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background: var(--wazin-danger-hover) !important;
            border-color: var(--wazin-danger-hover) !important;
        }

        .btn-secondary {
            background: #64748B !important;
            border-color: #64748B !important;
            color: #fff !important;
        }

        .btn-info {
            background: var(--wazin-cyan) !important;
            border-color: var(--wazin-cyan) !important;
            color: var(--wazin-navy) !important;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border-color: var(--wazin-border);
            min-height: 42px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--wazin-blue);
            box-shadow: 0 0 0 0.2rem rgba(47, 107, 255, 0.14);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 800;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .table-dark {
            --bs-table-bg: var(--wazin-navy);
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
        }

        .table-light {
            --bs-table-bg: #F8FAFC;
        }

        .badge {
            border-radius: 999px;
            padding: 7px 10px;
            font-weight: 700;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border-radius: 10px;
            border: 1px solid var(--wazin-border);
            padding: 6px 10px;
            margin: 0 6px;
        }

        .dataTables_wrapper .page-link {
            color: var(--wazin-navy);
            border-radius: 10px;
            margin: 0 2px;
        }

        .dataTables_wrapper .page-item.active .page-link {
            background: var(--wazin-blue);
            border-color: var(--wazin-blue);
            color: #fff;
        }

        .modal-content {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(7, 22, 51, 0.22);
        }

        .modal-header {
            background: var(--wazin-navy);
            color: #fff;
            border-bottom: 0;
        }

        .modal-title {
            font-weight: 800;
        }

        .btn-close {
            margin: 0;
        }

        .alert {
            border-radius: 14px;
            border: 0;
        }

        #branchTable th,
        #branchTable td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }


        /* ================================
           Select2 Wazin Style
        ================================ */

        .select2-container {
            width: 100% !important;
            direction: rtl;
            text-align: right;
        }

        .select2-container--default .select2-selection--single {
            height: 42px !important;
            min-height: 42px !important;
            border: 1px solid var(--wazin-border) !important;
            border-radius: 12px !important;
            background-color: #fff !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: none !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--wazin-blue) !important;
            box-shadow: 0 0 0 0.2rem rgba(47, 107, 255, 0.14) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--wazin-text) !important;
            font-weight: 700 !important;
            line-height: 42px !important;
            padding-right: 14px !important;
            padding-left: 34px !important;
            text-align: right !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #8EA0B8 !important;
            font-weight: 700 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            left: 10px !important;
            right: auto !important;
            top: 0 !important;
        }

        /* داخل جدول الأصناف */
        #itemsTable .select2-container--default .select2-selection--single {
            height: 40px !important;
            min-height: 40px !important;
            border-radius: 12px !important;
        }

        #itemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
        }

        #itemsTable .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        /* Dropdown */
        .select2-dropdown {
            border: 1px solid var(--wazin-border) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            direction: rtl !important;
            text-align: right !important;
            z-index: 9999 !important;
        }

        .select2-search--dropdown {
            padding: 8px !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--wazin-border) !important;
            border-radius: 10px !important;
            min-height: 38px !important;
            padding: 6px 10px !important;
            direction: rtl !important;
            text-align: right !important;
            outline: none !important;
        }

        .select2-results__option {
            padding: 9px 12px !important;
            font-weight: 700 !important;
            text-align: right !important;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--wazin-blue) !important;
            color: #fff !important;
        }
        @media (max-width: 991.98px) {
            .app-sidebar {
                width: 230px;
            }

            .app-content {
                margin-right: 230px;
                padding: 18px;
            }
        }

        @media (max-width: 767.98px) {
            .app-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }

            .app-content {
                margin-right: 0;
                padding: 14px;
            }
        }

        @media print {
            body {
                background: #fff !important;
            }

            .app-sidebar,
            .no-print,
            nav,
            aside,
            header,
            .navbar {
                display: none !important;
            }

            .app-content {
                margin-right: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .card,
            .page-card {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="app-wrapper">

        @include('layouts.sidebar')

        <main class="app-content">
            @include('partials.license-warning')
            {{ $slot }}
        </main>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {{-- Select2 لازم يكون بعد jQuery --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
</body>
</html>