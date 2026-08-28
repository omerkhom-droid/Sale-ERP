@php
    use Illuminate\Support\Facades\Route;

    $summaryCards = [
        [
            'title' => 'مبيعات اليوم',
            'value' => number_format($todaySales ?? 0, 2),
            'note' => 'إجمالي فواتير البيع المرحلة اليوم',
            'class' => 'blue-card',
        ],
        [
            'title' => 'مبيعات الشهر',
            'value' => number_format($monthSales ?? 0, 2),
            'note' => 'إجمالي مبيعات الشهر الحالي',
            'class' => 'green-card',
        ],
        [
            'title' => 'مشتريات الشهر',
            'value' => number_format($monthPurchases ?? 0, 2),
            'note' => 'إجمالي مشتريات الشهر الحالي',
            'class' => 'red-card',
        ],
        [
            'title' => 'صافي الشهر',
            'value' => number_format(($netMonthSales ?? $netProfit ?? 0), 2),
            'note' => 'مبيعات الشهر بعد خصم المرتجعات',
            'class' => 'dark-card',
        ],
    ];

    $quickLinks = [
        ['title' => 'شاشة POS ', 'route' => 'pos.index', 'icon' => 'bi-receipt'],
        ['title' => 'فاتورة بيع جديدة', 'route' => 'sales-invoices.create', 'icon' => 'bi-receipt'],
        ['title' => 'عرض سعر جديد', 'route' => 'quotations.create', 'icon' => 'bi-file-earmark-text'],
        ['title' => 'فاتورة شراء جديدة', 'route' => 'purchase-invoices.create', 'icon' => 'bi-cart-check'],
        ['title' => 'سند قبض عميل', 'route' => 'customer-receipt-vouchers.create', 'icon' => 'bi-cash-coin'],
        ['title' => 'سند صرف مورد', 'route' => 'supplier-payment-vouchers.create', 'icon' => 'bi-wallet2'],
        ['title' => 'تقرير المبيعات', 'route' => 'sales-reports.index', 'icon' => 'bi-bar-chart'],
        ['title' => 'ميزان المراجعة', 'route' => 'trial-balance-reports.index', 'icon' => 'bi-journal-check'],
    ];

    $operationCards = [
        [
            'title' => 'العملاء',
            'value' => $customersCount ?? 0,
            'note' => 'إجمالي العملاء المسجلين',
            'route' => 'customers.index',
            'class' => 'blue-soft-card',
        ],
        [
            'title' => 'الموردون',
            'value' => $suppliersCount ?? 0,
            'note' => 'إجمالي الموردين المسجلين',
            'route' => 'suppliers.index',
            'class' => 'green-soft-card',
        ],
        [
            'title' => 'فواتير البيع',
            'value' => $salesInvoicesCount ?? 0,
            'note' => 'عدد فواتير البيع في النظام',
            'route' => 'sales-invoices.index',
            'class' => 'dark-soft-card',
        ],
        [
            'title' => 'فواتير الشراء',
            'value' => $purchaseInvoicesCount ?? 0,
            'note' => 'عدد فواتير الشراء في النظام',
            'route' => 'purchase-invoices.index',
            'class' => 'purple-soft-card',
        ],
        [
            'title' => 'مخزون منخفض',
            'value' => $lowStockCount ?? 0,
            'note' => 'منتجات وصلت للحد الأدنى',
            'route' => 'products.index',
            'class' => 'red-soft-card',
        ],
    ];

    $managementCards = $managementCards ?? [
        [
            'title' => 'إدارة المبيعات',
            'description' => 'فواتير البيع، المرتجعات، عروض الأسعار وسندات القبض.',
            'icon' => 'bi-graph-up-arrow',
            'route' => 'sales-invoices.index',
            'value' => $salesInvoicesCount ?? 0,
            'label' => 'فاتورة',
        ],
        [
            'title' => 'إدارة المشتريات',
            'description' => 'فواتير الشراء، المرتجعات وسندات صرف الموردين.',
            'icon' => 'bi-cart-check',
            'route' => 'purchase-invoices.index',
            'value' => $purchaseInvoicesCount ?? 0,
            'label' => 'فاتورة',
        ],
        [
            'title' => 'إدارة المخزون',
            'description' => 'الأصناف، المستودعات، الأرصدة وحركة المخزون.',
            'icon' => 'bi-box-seam',
            'route' => 'products.index',
            'value' => $lowStockCount ?? 0,
            'label' => 'منخفض',
        ],
        [
            'title' => 'إدارة الحسابات',
            'description' => 'القيود اليومية، الأرصدة الافتتاحية والتقارير المالية.',
            'icon' => 'bi-journal-text',
            'route' => 'manual-journal-entries.index',
            'value' => $manualJournalEntriesCount ?? 0,
            'label' => 'قيد',
        ],
        [
            'title' => 'التقارير',
            'description' => 'تقارير المبيعات، الأرباح، العملاء، الموردين، المخزون والمالية.',
            'icon' => 'bi-bar-chart-line',
            'route' => 'sales-reports.index',
            'value' => 8,
            'label' => 'تقرير',
        ],
        [
            'title' => 'الإعدادات',
            'description' => 'الشركات، الفروع، المستخدمون، الصلاحيات والنسخ الاحتياطي.',
            'icon' => 'bi-gear',
            'route' => 'branches.index',
            'value' => $branchesCount ?? 0,
            'label' => 'فرع',
        ],
    ];

    $recentSections = [
        [
            'title' => 'آخر فواتير البيع',
            'items' => $recentSalesInvoices ?? [],
            'empty' => 'لا توجد فواتير بيع حديثة',
        ],
        [
            'title' => 'آخر فواتير الشراء',
            'items' => $recentPurchaseInvoices ?? [],
            'empty' => 'لا توجد فواتير شراء حديثة',
        ],
        [
            'title' => 'آخر سندات قبض العملاء',
            'items' => $recentCustomerReceipts ?? [],
            'empty' => 'لا توجد سندات قبض حديثة',
        ],
        [
            'title' => 'آخر سندات صرف الموردين',
            'items' => $recentSupplierPayments ?? [],
            'empty' => 'لا توجد سندات صرف حديثة',
        ],
    ];

    $statusLabels = [
        'draft' => 'مسودة',
        'posted' => 'مرحلة',
        'approved' => 'معتمدة',
        'cancelled' => 'ملغاة',
        'paid' => 'مدفوعة',
        'unpaid' => 'غير مدفوعة',
        'partial' => 'مدفوعة جزئيًا',
    ];
@endphp

<div class="container-fluid py-4" dir="rtl">

    <div class="dashboard-header mb-4">
        <div>
            <h3 class="mb-1">لوحة المدير</h3>
            <p class="mb-0">نظرة إدارية ومالية شاملة على أداء النظام.</p>
        </div>

        <div class="dashboard-header-badge">
            {{ now()->format('Y-m-d') }}
        </div>
    </div>

    <div class="dashboard-cards mb-4">
        @foreach($summaryCards as $card)
            <div class="dash-card {{ $card['class'] }}">
                <span>{{ $card['title'] }}</span>
                <strong>{{ $card['value'] }}</strong>
                <small>{{ $card['note'] }}</small>
            </div>
        @endforeach
    </div>

    <div class="operation-cards mb-4">
        @foreach($operationCards as $card)
            @if(Route::has($card['route']))
                <a href="{{ route($card['route']) }}" class="operation-card {{ $card['class'] }}">
                    <div>
                        <span>{{ $card['title'] }}</span>
                        <strong>{{ number_format($card['value']) }}</strong>
                        <small>{{ $card['note'] }}</small>
                    </div>
                </a>
            @else
                <div class="operation-card {{ $card['class'] }}">
                    <div>
                        <span>{{ $card['title'] }}</span>
                        <strong>{{ number_format($card['value']) }}</strong>
                        <small>{{ $card['note'] }}</small>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>المبيعات والمشتريات اليومية</h5>
                        <small>مقارنة آخر 7 أيام حسب المستندات المرحلة</small>
                    </div>
                </div>

                <div class="chart-box">
                    <canvas id="dailyFinancialChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>اختصارات سريعة</h5>
                        <small>أهم عمليات الإدارة اليومية</small>
                    </div>
                </div>

                <div class="quick-links">
                    @foreach($quickLinks as $link)
                        @if(Route::has($link['route']))
                            <a href="{{ route($link['route']) }}" class="quick-link">
                                <span><i class="bi {{ $link['icon'] }}"></i></span>
                                <strong>{{ $link['title'] }}</strong>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>المبيعات مقابل المشتريات</h5>
                        <small>مقارنة شهرية لآخر 6 أشهر</small>
                    </div>
                </div>

                <div class="chart-box large-chart-box">
                    <canvas id="monthlySalesPurchasesChart" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>حالة فواتير البيع</h5>
                        <small>توزيع الفواتير حسب الحالة</small>
                    </div>
                </div>

                <div class="chart-box status-chart-box">
                    <canvas id="salesStatusChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="wazin-card mt-4">
        <div class="wazin-card-header">
            <div>
                <h5>إدارات البرنامج</h5>
                <small>وصول سريع لأقسام النظام مع مؤشرات مختصرة لكل إدارة</small>
            </div>
        </div>

        <div class="management-grid">
            @foreach($managementCards as $card)
                @if(!empty($card['route']) && Route::has($card['route']))
                    <a href="{{ route($card['route']) }}" class="management-card">
                        <div class="management-icon">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>

                        <div class="management-body">
                            <h6>{{ $card['title'] }}</h6>
                            <p>{{ $card['description'] }}</p>
                        </div>

                        <div class="management-stat">
                            <strong>{{ number_format($card['value'] ?? 0) }}</strong>
                            <span>{{ $card['label'] ?? '' }}</span>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-4">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>أعلى الفروع مبيعًا</h5>
                        <small>حسب إجمالي فواتير البيع المرحلة</small>
                    </div>
                </div>

                <div class="ranking-list">
                    @forelse(($topBranchesBySales ?? []) as $row)
                        <div class="ranking-item">
                            <span>{{ $row['name'] ?? '-' }}</span>
                            <strong>{{ number_format($row['total'] ?? 0, 2) }}</strong>
                        </div>
                    @empty
                        <div class="empty-box">لا توجد بيانات فروع كافية</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>أعلى العملاء مبيعًا</h5>
                        <small>أفضل العملاء حسب إجمالي المبيعات</small>
                    </div>
                </div>

                <div class="ranking-list">
                    @forelse(($topCustomersBySales ?? []) as $row)
                        <div class="ranking-item">
                            <span>{{ $row['name'] ?? '-' }}</span>
                            <strong>{{ number_format($row['total'] ?? 0, 2) }}</strong>
                        </div>
                    @empty
                        <div class="empty-box">لا توجد بيانات عملاء كافية</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="wazin-card h-100">
                <div class="wazin-card-header">
                    <div>
                        <h5>أعلى الأصناف مبيعًا</h5>
                        <small>أفضل الأصناف حسب قيمة المبيعات</small>
                    </div>
                </div>

                <div class="ranking-list">
                    @forelse(($topProductsBySales ?? []) as $row)
                        <div class="ranking-item">
                            <span>{{ $row['name'] ?? '-' }}</span>
                            <strong>{{ number_format($row['total'] ?? 0, 2) }}</strong>
                        </div>
                    @empty
                        <div class="empty-box">لا توجد بيانات أصناف كافية</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        @foreach($recentSections as $section)
            <div class="col-lg-6">
                <div class="wazin-card h-100">
                    <div class="wazin-card-header">
                        <div>
                            <h5>{{ $section['title'] }}</h5>
                            <small>آخر 5 مستندات مسجلة في النظام</small>
                        </div>
                    </div>

                    <div class="recent-list">
                        @forelse($section['items'] as $item)
                            @php
                                $status = $item['status'] ?? null;
                                $statusText = $statusLabels[$status] ?? ($status ?: '-');
                            @endphp

                            <div class="recent-item">
                                <div class="recent-main">
                                    <strong>{{ $item['number'] }}</strong>
                                    <span>{{ $item['date'] }}</span>
                                </div>

                                <div class="recent-side">
                                    <span class="recent-amount">
                                        {{ number_format($item['amount'], 2) }}
                                    </span>

                                    <span class="recent-status">
                                        {{ $statusText }}
                                    </span>

                                    @if(!empty($item['route']) && Route::has($item['route']))
                                        <a href="{{ route($item['route'], $item['id']) }}" class="recent-link">
                                            عرض
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-box">
                                {{ $section['empty'] }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .dashboard-header h3 {
        color: #fff;
        font-weight: 900;
    }

    .dashboard-header p {
        color: #CFEFF3;
        font-weight: 700;
    }

    .dashboard-header-badge {
        background: rgba(255, 255, 255, .10);
        border: 1px solid rgba(255, 255, 255, .18);
        color: #fff;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        direction: ltr;
    }

    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .dash-card,
    .wazin-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .dash-card {
        padding: 18px;
    }

    .dash-card span,
    .operation-card span {
        display: block;
        color: #64748B;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .dash-card strong,
    .operation-card strong {
        display: block;
        direction: ltr;
        color: #071633;
        font-size: 26px;
        font-weight: 900;
    }

    .dash-card small,
    .operation-card small {
        display: block;
        color: #8EA0B8;
        font-weight: 700;
        margin-top: 6px;
        line-height: 1.7;
    }

    .blue-card,
    .blue-soft-card {
        border-right: 5px solid #2F6BFF;
    }

    .green-card,
    .green-soft-card {
        border-right: 5px solid #16A34A;
    }

    .red-card,
    .red-soft-card {
        border-right: 5px solid #E63B4A;
    }

    .dark-card,
    .dark-soft-card {
        border-right: 5px solid #071633;
    }

    .purple-soft-card {
        border-right: 5px solid #7C3AED;
    }

    .operation-cards {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
    }

    .operation-card {
        display: block;
        text-decoration: none;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
        transition: .2s ease;
    }

    .operation-card:hover,
    .management-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(7, 22, 51, 0.08);
        color: #071633;
    }

    .wazin-card {
        overflow: hidden;
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

    .chart-box {
        height: 320px;
        padding: 22px;
        background: #F8FAFC;
    }

    .large-chart-box {
        height: 360px;
    }

    .status-chart-box {
        height: 320px;
    }

    .quick-links {
        padding: 16px;
        display: grid;
        gap: 12px;
    }

    .quick-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: #F8FAFC;
        text-decoration: none;
        color: #071633;
        font-weight: 900;
        transition: .2s ease;
    }

    .quick-link:hover {
        background: #EEF2FF;
        color: #2F6BFF;
    }

    .quick-link span {
        background: #071633;
        color: #fff;
        border-radius: 12px;
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .management-grid {
        padding: 16px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        background: #F8FAFC;
    }

    .management-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 16px;
        text-decoration: none;
        color: #071633;
        display: grid;
        grid-template-columns: 48px 1fr auto;
        gap: 14px;
        align-items: center;
        transition: .2s ease;
    }

    .management-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #EEF2FF;
        color: #2F6BFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .management-body h6 {
        margin: 0 0 6px;
        font-weight: 900;
        color: #071633;
    }

    .management-body p {
        margin: 0;
        color: #64748B;
        font-weight: 700;
        line-height: 1.7;
        font-size: 13px;
    }

    .management-stat {
        min-width: 86px;
        text-align: center;
        background: #F8FAFC;
        border-radius: 14px;
        padding: 10px;
    }

    .management-stat strong {
        display: block;
        color: #071633;
        font-weight: 900;
        font-size: 20px;
        direction: ltr;
    }

    .management-stat span {
        display: block;
        color: #8EA0B8;
        font-size: 12px;
        font-weight: 900;
    }

    .ranking-list,
    .recent-list {
        padding: 14px;
        background: #F8FAFC;
    }

    .ranking-item,
    .recent-item {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        padding: 13px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .ranking-item:last-child,
    .recent-item:last-child {
        margin-bottom: 0;
    }

    .ranking-item span {
        color: #071633;
        font-weight: 900;
    }

    .ranking-item strong {
        color: #2F6BFF;
        font-weight: 900;
        direction: ltr;
    }

    .recent-main strong {
        display: block;
        color: #071633;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .recent-main span {
        color: #64748B;
        font-weight: 700;
        direction: ltr;
        display: inline-block;
    }

    .recent-side {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .recent-amount {
        direction: ltr;
        color: #071633;
        font-weight: 900;
        background: #EEF2F7;
        border-radius: 999px;
        padding: 6px 10px;
    }

    .recent-status {
        color: #2F6BFF;
        background: rgba(47, 107, 255, .10);
        border-radius: 999px;
        padding: 6px 10px;
        font-weight: 900;
        font-size: 12px;
    }

    .recent-link {
        text-decoration: none;
        background: #071633;
        color: #fff;
        border-radius: 999px;
        padding: 6px 12px;
        font-weight: 900;
        font-size: 12px;
    }

    .recent-link:hover {
        background: #2F6BFF;
        color: #fff;
    }

    .empty-box {
        background: #fff;
        border: 1px dashed #CBD5E1;
        color: #64748B;
        border-radius: 16px;
        padding: 18px;
        text-align: center;
        font-weight: 800;
    }

    @media (max-width: 1200px) {
        .operation-cards {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .management-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .dashboard-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .dashboard-cards,
        .operation-cards,
        .management-grid {
            grid-template-columns: 1fr;
        }

        .recent-item,
        .ranking-item {
            flex-direction: column;
            align-items: stretch;
        }

        .recent-side {
            justify-content: flex-start;
        }

        .management-card {
            grid-template-columns: 48px 1fr;
        }

        .management-stat {
            grid-column: 1 / -1;
            text-align: right;
        }
    }
</style>

<script src="{{ asset('assets/vendor/chartjs/chart.umd.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const numberFormatter = function (value) {
            return Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        const dailyCanvas = document.getElementById('dailyFinancialChart');

        if (dailyCanvas) {
            new Chart(dailyCanvas, {
                type: 'line',
                data: {
                    labels: @json($salesChartLabels ?? []),
                    datasets: [
                        {
                            label: 'المبيعات',
                            data: @json($salesChartValues ?? []),
                            tension: 0.35,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'المشتريات',
                            data: @json($purchaseChartValues ?? []),
                            tension: 0.35,
                            fill: false,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            rtl: true,
                            textDirection: 'rtl',
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + numberFormatter(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return Number(value).toLocaleString('en-US');
                                },
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }

        const monthlyCanvas = document.getElementById('monthlySalesPurchasesChart');

        if (monthlyCanvas) {
            new Chart(monthlyCanvas, {
                type: 'bar',
                data: {
                    labels: @json($monthlyChartLabels ?? []),
                    datasets: [
                        {
                            label: 'المبيعات',
                            data: @json($monthlySalesValues ?? []),
                            borderWidth: 1
                        },
                        {
                            label: 'المشتريات',
                            data: @json($monthlyPurchasesValues ?? []),
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            rtl: true,
                            textDirection: 'rtl',
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + numberFormatter(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return Number(value).toLocaleString('en-US');
                                },
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }

        const statusCanvas = document.getElementById('salesStatusChart');

        if (statusCanvas) {
            const salesStatus = @json($salesStatusChart ?? ['draft' => 0, 'posted' => 0, 'cancelled' => 0]);

            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['مسودة', 'مرحلة', 'ملغاة'],
                    datasets: [
                        {
                            data: [
                                salesStatus.draft || 0,
                                salesStatus.posted || 0,
                                salesStatus.cancelled || 0
                            ],
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Tahoma',
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            rtl: true,
                            textDirection: 'rtl'
                        }
                    }
                }
            });
        }
    });
</script>