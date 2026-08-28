@php
    $userName = auth()->user()->name ?? 'مستخدم النظام';

    $shortcutGroups = [
        [
            'title' => 'المبيعات والعملاء',
            'description' => 'عمليات البيع اليومية وخدمة العملاء',
            'class' => 'blue-group',
            'links' => [
                [
                    'title' => 'شاشة POS',
                    'description' => 'نقطة البيع',
                    'route' => 'pos.index',
                    'badge' => 'بيع',
                ],
                [
                    'title' => 'فاتورة بيع جديدة',
                    'description' => 'إنشاء فاتورة بيع للعميل',
                    'route' => 'sales-invoices.create',
                    'badge' => 'بيع',
                ],
                [
                    'title' => 'فواتير البيع',
                    'description' => 'متابعة فواتير البيع',
                    'route' => 'sales-invoices.index',
                    'badge' => 'فواتير',
                ],
                [
                    'title' => 'مرتجع بيع',
                    'description' => 'تسجيل مرتجع من العميل',
                    'route' => 'sales-returns.create',
                    'badge' => 'مرتجع',
                ],
                [
                    'title' => 'العملاء',
                    'description' => 'إدارة بيانات العملاء',
                    'route' => 'customers.index',
                    'badge' => 'عملاء',
                ],
                [
                    'title' => 'سند قبض عميل',
                    'description' => 'تسجيل مبلغ محصل من عميل',
                    'route' => 'customer-receipt-vouchers.create',
                    'badge' => 'قبض',
                ],
            ],
        ],
        [
            'title' => 'المشتريات والموردون',
            'description' => 'عمليات الشراء والتعامل مع الموردين',
            'class' => 'green-group',
            'links' => [
                [
                    'title' => 'فاتورة شراء جديدة',
                    'description' => 'تسجيل فاتورة شراء من مورد',
                    'route' => 'purchase-invoices.create',
                    'badge' => 'شراء',
                ],
                [
                    'title' => 'فواتير الشراء',
                    'description' => 'متابعة فواتير الشراء',
                    'route' => 'purchase-invoices.index',
                    'badge' => 'فواتير',
                ],
                [
                    'title' => 'مرتجع شراء',
                    'description' => 'تسجيل مرتجع إلى المورد',
                    'route' => 'purchase-returns.create',
                    'badge' => 'مرتجع',
                ],
                [
                    'title' => 'الموردون',
                    'description' => 'إدارة بيانات الموردين',
                    'route' => 'suppliers.index',
                    'badge' => 'مورد',
                ],
                [
                    'title' => 'سند صرف مورد',
                    'description' => 'تسجيل مبلغ مدفوع لمورد',
                    'route' => 'supplier-payment-vouchers.create',
                    'badge' => 'صرف',
                ],
            ],
        ],
        [
            'title' => 'المخزون والمنتجات',
            'description' => 'متابعة الأصناف والكميات وحركة المخزون',
            'class' => 'dark-group',
            'links' => [
                [
                    'title' => 'المنتجات',
                    'description' => 'استعراض وإدارة المنتجات',
                    'route' => 'products.index',
                    'badge' => 'منتج',
                ],
                [
                    'title' => 'إضافة منتج',
                    'description' => 'إنشاء منتج جديد',
                    'route' => 'products.create',
                    'badge' => 'جديد',
                ],
                [
                    'title' => 'حركة المخزون',
                    'description' => 'متابعة دخول وخروج المنتجات',
                    'route' => 'inventory-movements.index',
                    'badge' => 'حركة',
                ],
                [
                    'title' => 'المخزون الافتتاحي',
                    'description' => 'تسجيل أو مراجعة المخزون الافتتاحي',
                    'route' => 'opening-stock.index',
                    'badge' => 'مخزون',
                ],
                [
                    'title' => 'المستودعات',
                    'description' => 'إدارة المستودعات',
                    'route' => 'warehouses.index',
                    'badge' => 'مستودع',
                ],
            ],
        ],
        [
            'title' => 'عمليات عامة',
            'description' => 'اختصارات تشغيلية لا تعرض مؤشرات مالية حساسة',
            'class' => 'gray-group',
            'links' => [
                [
                    'title' => 'الفروع',
                    'description' => 'استعراض الفروع',
                    'route' => 'branches.index',
                    'badge' => 'فرع',
                ],
                [
                    'title' => 'التصنيفات',
                    'description' => 'إدارة تصنيفات المنتجات',
                    'route' => 'categories.index',
                    'badge' => 'تصنيف',
                ],
                [
                    'title' => 'الوحدات',
                    'description' => 'إدارة وحدات القياس',
                    'route' => 'units.index',
                    'badge' => 'وحدة',
                ],
                [
                    'title' => 'العلامات التجارية',
                    'description' => 'إدارة العلامات التجارية',
                    'route' => 'brands.index',
                    'badge' => 'علامة',
                ],
            ],
        ],
    ];
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Header --}}
    <div class="employee-header mb-4">
        <div>
            <h3 class="mb-1">لوحة الموظف</h3>
            <p class="mb-0">
                مرحبًا {{ $userName }}، هذه لوحة تشغيلية للوصول السريع إلى مهامك اليومية بدون عرض أرباح أو أرصدة مالية.
            </p>
        </div>

        <div class="employee-avatar">
            {{ mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8') }}
        </div>
    </div>


    {{-- Notice --}}
    <div class="safe-notice mb-4">
        <strong>تنبيه:</strong>
        هذه اللوحة مخصصة للتشغيل اليومي فقط. التقارير المالية والأرباح والأرصدة تظهر في لوحة المدير فقط.
    </div>


    {{-- Shortcut Groups --}}
    <div class="shortcut-groups">

        @foreach($shortcutGroups as $group)
            @php
                $availableLinks = collect($group['links'])->filter(function ($link) {
                    return \Illuminate\Support\Facades\Route::has($link['route']);
                });
            @endphp

            @if($availableLinks->isNotEmpty())
                <div class="shortcut-group {{ $group['class'] }}">

                    <div class="shortcut-group-header">
                        <div>
                            <h5>{{ $group['title'] }}</h5>
                            <small>{{ $group['description'] }}</small>
                        </div>
                    </div>

                    <div class="shortcut-grid">
                        @foreach($availableLinks as $link)
                            <a href="{{ route($link['route']) }}" class="shortcut-card">
                                <div class="shortcut-badge">
                                    {{ $link['badge'] }}
                                </div>

                                <div>
                                    <h6>{{ $link['title'] }}</h6>
                                    <p>{{ $link['description'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>

                </div>
            @endif
        @endforeach

    </div>


    {{-- Bottom Sections --}}
    <div class="row g-4 mt-1">

        <div class="col-lg-6">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>تنبيهات تشغيلية</h5>
                        <small>تنبيهات عامة لا تحتوي على بيانات مالية حساسة</small>
                    </div>
                </div>

                <div class="empty-box">
                    لا توجد تنبيهات حالية.
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>آخر العمليات</h5>
                        <small>سيتم ربطها لاحقًا بآخر العمليات الخاصة بالمستخدم</small>
                    </div>
                </div>

                <div class="empty-box">
                    لا توجد عمليات حديثة للعرض.
                </div>
            </div>
        </div>

    </div>

</div>


<style>
    .employee-header {
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

    .employee-header h3 {
        color: #fff;
        font-weight: 900;
    }

    .employee-header p {
        color: #CFEFF3;
        font-weight: 700;
        line-height: 1.8;
    }

    .employee-avatar {
        width: 62px;
        height: 62px;
        border-radius: 20px;
        background: #2F6BFF;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
        font-weight: 900;
        box-shadow: 0 10px 25px rgba(47, 107, 255, .28);
        flex-shrink: 0;
    }

    .safe-notice {
        background: rgba(47, 107, 255, .08);
        border: 1px solid rgba(47, 107, 255, .18);
        border-right: 5px solid #2F6BFF;
        color: #071633;
        border-radius: 18px;
        padding: 14px 18px;
        font-weight: 800;
        line-height: 1.8;
    }

    .shortcut-groups {
        display: grid;
        gap: 20px;
    }

    .shortcut-group {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .shortcut-group-header {
        padding: 18px 20px;
        border-bottom: 1px solid #E5E7EB;
        background: #fff;
    }

    .shortcut-group-header h5 {
        margin: 0;
        color: #071633;
        font-weight: 900;
    }

    .shortcut-group-header small {
        display: block;
        margin-top: 5px;
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    .blue-group .shortcut-group-header {
        border-right: 5px solid #2F6BFF;
    }

    .green-group .shortcut-group-header {
        border-right: 5px solid #16A34A;
    }

    .dark-group .shortcut-group-header {
        border-right: 5px solid #071633;
    }

    .gray-group .shortcut-group-header {
        border-right: 5px solid #64748B;
    }

    .shortcut-grid {
        padding: 16px;
        background: #F8FAFC;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .shortcut-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 16px;
        display: flex;
        gap: 13px;
        text-decoration: none;
        color: #071633;
        transition: .2s ease;
        min-height: 105px;
    }

    .shortcut-card:hover {
        transform: translateY(-2px);
        border-color: #2F6BFF;
        color: #071633;
        box-shadow: 0 12px 26px rgba(7, 22, 51, 0.08);
    }

    .shortcut-badge {
        min-width: 48px;
        height: 36px;
        border-radius: 999px;
        background: #071633;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 900;
        padding: 0 10px;
        flex-shrink: 0;
    }

    .shortcut-card h6 {
        margin: 0 0 6px;
        color: #071633;
        font-weight: 900;
    }

    .shortcut-card p {
        margin: 0;
        color: #64748B;
        font-weight: 700;
        line-height: 1.7;
        font-size: 13px;
    }

    .wazin-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .wazin-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid #E5E7EB;
        background: #fff;
    }

    .wazin-card-header h5 {
        margin: 0;
        color: #071633;
        font-weight: 900;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    .empty-box {
        padding: 34px 20px;
        text-align: center;
        color: #64748B;
        font-weight: 800;
        background: #F8FAFC;
    }

    @media (max-width: 1200px) {
        .shortcut-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .employee-header {
            flex-direction: column;
            align-items: stretch;
        }

        .employee-avatar {
            width: 54px;
            height: 54px;
            font-size: 23px;
        }

        .shortcut-grid {
            grid-template-columns: 1fr;
        }

        .shortcut-card {
            min-height: auto;
        }
    }
</style>