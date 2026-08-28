<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form id="productForm"
          action="{{ route('products.store') }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf

        {{-- Page Header --}}
        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">إضافة صنف جديد</h3>
                <p class="page-subtitle mb-0">
                    إدخال بيانات الصنف الأساسية، الوحدات، الأسعار، والصور الخاصة بالمنتج.
                </p>
            </div>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                رجوع
            </a>
        </div>

        <div id="formErrors"></div>

        {{-- Basic Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">البيانات الأساسية</h5>
                    <small>بيانات الصنف والتصنيف والعلامة التجارية وحالة التتبع</small>
                </div>
            </div>

            <div class="card-body">
                @include('products.partials.basic-info')
            </div>
        </div>


        {{-- Units & Prices --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">الوحدات والأسعار</h5>
                    <small>تحديد وحدات البيع والشراء والتحويلات والأسعار</small>
                </div>
            </div>

            <div class="card-body">
                @include('products.partials.units')
            </div>
        </div>


        {{-- Images --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">صور الصنف</h5>
                    <small>إضافة صور المنتج للاستخدام الداخلي أو التقارير لاحقًا</small>
                </div>
            </div>

            <div class="card-body">
                @include('products.partials.images')
            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" id="btnSave" class="btn btn-primary btn-lg">
                حفظ الصنف
            </button>
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
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
    }

    .wazin-card .card-body {
        background: #F8FAFC;
        padding: 22px;
    }

    /*
     | تنسيق عام للحقول داخل partials
     | حتى لو كانت الملفات الداخلية تحتوي form-control / form-select / table
    */
    #productForm .form-label {
        color: #071633;
        font-weight: 900;
        margin-bottom: 7px;
    }

    #productForm .form-control,
    #productForm .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 600;
        background-color: #fff;
    }

    #productForm textarea.form-control {
        min-height: 90px;
    }

    #productForm .form-control:focus,
    #productForm .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #productForm small,
    #productForm .text-muted {
        color: #64748B !important;
        font-weight: 700;
    }

    #productForm .table {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 0;
    }

    #productForm .table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        text-align: center;
    }

    #productForm .table tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    #productForm .btn {
        border-radius: 14px;
        font-weight: 900;
    }

    #productForm .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        padding: 10px 18px;
    }

    #productForm .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    #productForm .btn-success {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #productForm .btn-success:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    #productForm .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
    }

    #productForm .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    #productForm .btn-warning,
    #productForm .btn-info {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
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

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .save-actions {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        box-shadow: 0 12px 32px rgba(7, 22, 51, 0.08);
        position: sticky;
        bottom: 18px;
        z-index: 20;
    }

    .save-actions .btn {
        min-width: 140px;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    @media (max-width: 767px) {
        .page-header-card {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn {
            width: 100%;
        }

        .save-actions {
            flex-direction: column;
            position: static;
        }

        .save-actions .btn {
            width: 100%;
        }
    }
</style>

@include('products.partials.scripts')

</x-app-layout>