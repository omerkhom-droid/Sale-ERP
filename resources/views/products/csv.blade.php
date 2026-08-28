<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">استيراد وتصدير المنتجات CSV</h3>
            <p class="page-subtitle mb-0">
                تصدير المنتجات لتعديل الأسعار خارجيًا في Excel ثم إعادة استيرادها لتحديث المنتجات والوحدات والأسعار والباركود.
            </p>
        </div>

        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            رجوع
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('import_summary'))
        @php($summary = session('import_summary'))

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">ملخص الاستيراد</h5>

                <div class="row g-3">
                    <div class="col-md-2">منتجات جديدة: <strong>{{ $summary['created_products'] }}</strong></div>
                    <div class="col-md-2">منتجات محدثة: <strong>{{ $summary['updated_products'] }}</strong></div>
                    <div class="col-md-2">وحدات جديدة: <strong>{{ $summary['created_units'] }}</strong></div>
                    <div class="col-md-2">وحدات محدثة: <strong>{{ $summary['updated_units'] }}</strong></div>
                    <div class="col-md-2">باركودات: <strong>{{ $summary['barcodes'] }}</strong></div>
                </div>

                @if(!empty($summary['errors']))
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>ملاحظات:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($summary['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>يوجد أخطاء:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">تصدير المنتجات الحالية</h5>
                </div>

                <div class="card-body">
                    <p class="text-muted fw-bold">
                        صدّر المنتجات الحالية، عدّل الأسعار أو الباركود في Excel، ثم احفظ الملف بصيغة CSV UTF-8.
                    </p>

                    <a href="{{ route('products.csv.export') }}" class="btn btn-primary">
                        تصدير المنتجات الحالية
                    </a>

                    <a href="{{ route('products.csv.template') }}" class="btn btn-outline-secondary">
                        تحميل قالب CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">استيراد المنتجات من CSV</h5>
                </div>

                <div class="card-body">
                    <form method="POST"
                          action="{{ route('products.csv.import') }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">ملف CSV</label>
                            <input type="file"
                                   name="file"
                                   class="form-control"
                                   accept=".csv,.txt"
                                   required>
                        </div>

                        <div class="alert alert-info">
                            يتم التحديث حسب <strong>SKU + unit_name</strong>.
                            إذا كان المنتج موجودًا يتم تحديثه، وإذا لم يكن موجودًا يتم إنشاؤه.
                        </div>

                        <button type="submit" class="btn btn-success">
                            استيراد وتحديث المنتجات
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

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

    .btn-primary,
    .btn-success,
    .btn-secondary,
    .btn-outline-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }
</style>

</x-app-layout>