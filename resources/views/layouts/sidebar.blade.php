<button type="button" class="sidebar-mobile-toggle" id="sidebarMobileToggle">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="app-sidebar d-flex flex-column" id="appSidebar">
    @php
        $authUser = auth()->user();
        $authType = $authUser->user_type ?? 'user';

        $isSystemUser = in_array($authType, ['master', 'system_admin'], true);
        $isCompanyManager = in_array($authType, ['company_owner', 'company_admin'], true);
        $canAccessBranchesPage = in_array($authType, ['master', 'system_admin', 'company_owner', 'company_admin'], true);
    @endphp
    
    <div class="sidebar-brand">
        <div class="brand-icon">W</div>
        <div>
            <h4 class="mb-0">وازن ERP</h4>
            <small>نظام المبيعات والمخزون والمحاسبة</small>
        </div>
    </div>

    <div class="sidebar-menu flex-grow-1" id="sidebarMenu">

        @can('dashboard.view')
            <div class="sidebar-title">الرئيسية</div>

            <div class="sidebar-section">
                <a href="{{ route('dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high sidebar-icon"></i> {{-- لوحة التحكم --}}
                    <span>لوحة التحكم</span>
                </a>
            </div>
        @endcan


        @canany(['companies.view', 'company_settings.view', 'branches.view', 'warehouses.view'])
            <div class="sidebar-title">الإدارة العامة</div>

            <div class="sidebar-section">
                @if(in_array(auth()->user()->user_type, ['master', 'system_admin'], true))
                    <a href="{{ route('license.index') }}"
                       class="sidebar-link {{ request()->routeIs('license.*') ? 'active' : '' }}">
                       <i class="fa-solid fa-id-card sidebar-icon"></i> {{-- إدارة الاشتراك --}}
                          <span> إدارة الاشتراك  </span>
                    </a>
                @endif

                @can('companies.view')
                    @if($isSystemUser)
                        <a href="{{ route('companies.index') }}"
                           class="sidebar-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-building sidebar-icon"></i> {{-- الشركات --}}
                            <span>الشركات</span>
                        </a>
                    @endif
                @endcan
                

                @can('company_settings.view')
                    @if($isCompanyManager)
                        <a href="{{ route('company-settings.index') }}"
                           class="sidebar-link {{ request()->routeIs('company-settings.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gear sidebar-icon"></i> {{-- إعدادات الشركة --}}
                            <span>إعدادات الشركة</span>
                        </a>
                    @endif
                @endcan

                @can('branches.view')
                    @if($canAccessBranchesPage)
                        <a href="{{ route('branches.index') }}"
                           class="sidebar-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-code-branch sidebar-icon"></i> {{-- الفروع --}}
                            <span>الفروع</span>
                        </a>
                    @endif
                @endcan

                @can('warehouses.view')
                    <a href="{{ route('warehouses.index') }}"
                       class="sidebar-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-warehouse sidebar-icon"></i> {{-- المستودعات --}}
                        <span>المستودعات</span>
                    </a>
                @endcan

                @can('backups.view')
                    @if(in_array(auth()->user()->user_type ?? 'user', ['master', 'system_admin'], true))
                        <a href="{{ route('backups.index') }}"
                           class="sidebar-link {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-database sidebar-icon"></i> {{-- النسخ الاحتياطي --}}
                            <span>النسخ الاحتياطي</span>
                        </a>
                    @endif
                @endcan
                
            </div>
        @endcanany


        @canany(['users.view', 'roles.view'])
            <div class="sidebar-title">الإدارة والصلاحيات</div>

            <div class="sidebar-section">

                @can('users.view')
                    <a href="{{ route('users.index') }}"
                       class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users-gear sidebar-icon"></i> {{-- المستخدمون --}}
                        <span>المستخدمون</span>
                    </a>
                @endcan

                
                @can('roles.view')
                    <a href="{{ route('roles.index') }}"
                       class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shield-halved sidebar-icon"></i> {{-- الأدوار والصلاحيات --}}
                        <span>الأدوار والصلاحيات</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'categories.view',
            'brands.view',
            'units.view',
            'products.view',
        ])
            <div class="sidebar-title">المنتجات</div>

            <div class="sidebar-section">

                @can('categories.view')
                    <a href="{{ route('categories.index') }}"
                       class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-layer-group sidebar-icon"></i> {{-- التصنيفات --}}
                        <span>التصنيفات</span>
                    </a>
                @endcan

                @can('brands.view')
                    <a href="{{ route('brands.index') }}"
                       class="sidebar-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-tags sidebar-icon"></i> {{-- العلامات التجارية --}}
                        <span>العلامات التجارية</span>
                    </a>
                @endcan

                @can('units.view')
                    <a href="{{ route('units.index') }}"
                       class="sidebar-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-ruler-combined sidebar-icon"></i> {{-- الوحدات --}}
                        <span>الوحدات</span>
                    </a>
                @endcan

                @can('products.view')
                    <a href="{{ route('products.index') }}"
                       class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-box sidebar-icon"></i> {{-- المنتجات --}}
                        <span>المنتجات</span>
                    </a>
                @endcan
            </div>
        @endcanany

        @canany([
            'inventory_movements.view',
            'opening_stock.view',
            'inventory_counts.view'
        ])
            <div class="sidebar-title">المخزون</div>
            <div class="sidebar-section">
                @can('opening_stock.view')
                    <a href="{{ route('opening-stock.index') }}"
                       class="sidebar-link {{ request()->routeIs('opening-stock.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked sidebar-icon"></i> {{-- الرصيد الافتتاحي للمخزون --}}
                        <span>الرصيد الافتتاحي للمخزون</span>
                    </a>
                @endcan

                @can('inventory_counts.view')
                    <a href="{{ route('inventory-counts.index') }}"
                       class="sidebar-link {{ request()->routeIs('inventory-counts.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-clipboard-check sidebar-icon"></i> {{-- الجرد المخزني --}}
                        <span>الجرد المخزني</span>
                    </a>
                @endcan
                
                @can('inventory_damages.view')
                    <a href="{{ route('inventory-damages.index') }}"
                       class="sidebar-link {{ request()->routeIs('inventory-damages.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-trash-can sidebar-icon"></i> {{-- التالف / إتلاف المخزون --}}
                        <span>التالف / إتلاف المخزون</span>
                    </a>
                @endcan

                @can('warehouse_transfers.view')
                    <a href="{{ route('warehouse-transfers.index') }}"
                       class="sidebar-link {{ request()->routeIs('warehouse-transfers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-right-left sidebar-icon"></i> {{-- التحويل بين المستودعات --}}
                        <span>التحويل بين المستودعات</span>
                    </a>
                @endcan
                
                @can('inventory_movements.view')
                    <a href="{{ route('inventory-movements.index') }}"
                       class="sidebar-link {{ request()->routeIs('inventory-movements.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-arrow-right-arrow-left sidebar-icon"></i> {{-- تقرير حركة المخزون --}}
                        <span>تقرير حركة المخزون</span>
                    </a>
                @endcan

                @can('inventory_balance_report.view')
                    <a href="{{ route('reports.inventory-balances.index') }}"
                       class="sidebar-link {{ request()->routeIs('reports.inventory-balances.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-column sidebar-icon"></i> {{-- أرصدة وتقييم المخزون --}}
                        <span>أرصدة وتقييم المخزون</span>
                    </a>
                @endcan
            </div>
        @endcanany

        @canany(['customers.view', 'suppliers.view'])
            <div class="sidebar-title">العملاء والموردون</div>

            <div class="sidebar-section">

                @can('customers.view')
                    <a href="{{ route('customers.index') }}"
                       class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users sidebar-icon"></i> {{-- العملاء --}}
                        <span>العملاء</span>
                    </a>
                @endcan

                @can('suppliers.view')
                    <a href="{{ route('suppliers.index') }}"
                       class="sidebar-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-truck-field sidebar-icon"></i> {{-- الموردون --}}
                        <span>الموردون</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @can('pos.view')
            <div class="sidebar-title">نقطة البيع</div>

            <div class="sidebar-section">
                <a href="{{ route('pos.index') }}"
                   class="sidebar-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-cash-register sidebar-icon"></i>
                    <span>شاشة POS</span>
                </a>
                @can('pos.daily_report')
                    <a href="{{ route('pos.shifts') }}"
                       class="sidebar-link {{ request()->routeIs('pos.shifts', 'pos.shift-report') ? 'active' : '' }}">
                        <i class="fa-solid fa-clock-rotate-left sidebar-icon"></i>
                        <span>سجل ورديات POS</span>
                    </a>
                @endcan
                @can('pos.daily_report')
                    <a href="{{ route('pos.reports.items') }}"
                       class="sidebar-link {{ request()->routeIs('pos.reports.items') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-column sidebar-icon"></i>
                        <span>تقرير مبيعات الأصناف</span>
                    </a>
                @endcan

                @can('pos.daily_report')
                    <a href="{{ route('pos.reports.categories') }}"
                       class="sidebar-link {{ request()->routeIs('pos.reports.categories') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie sidebar-icon"></i>
                        <span>تقرير مبيعات التصنيفات</span>
                    </a>
                @endcan

                @can('pos.daily_report')
                    <a href="{{ route('pos.reports.payments') }}"
                       class="sidebar-link {{ request()->routeIs('pos.reports.payments') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-transfer sidebar-icon"></i>
                        <span>تقرير طرق الدفع</span>
                    </a>
                @endcan

                @can('pos.settings')
                    <a href="{{ route('pos.settings.index') }}"
                       class="sidebar-link {{ request()->routeIs('pos.settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders sidebar-icon"></i>
                        <span>إعدادات POS</span>
                    </a>
                @endcan
            </div>
        @endcan



        @canany([
            'sales_invoices.view',
            'sales_returns.view',
            'customer_receipt_vouchers.view',
            'customer_refund_vouchers.view'
        ])
            <div class="sidebar-title">المبيعات</div>

            <div class="sidebar-section">

                @can('quotations.view')
                    <a href="{{ route('quotations.index') }}"
                       class="sidebar-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-lines sidebar-icon"></i> {{-- عروض الأسعار --}}
                        <span>عروض الأسعار</span>
                    </a>
                @endcan
                
                @can('sales_invoices.view')
                    <a href="{{ route('sales-invoices.index') }}"
                       class="sidebar-link {{ request()->routeIs('sales-invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar sidebar-icon"></i> {{-- فواتير المبيعات --}}
                        <span>فواتير المبيعات</span>
                    </a>
                @endcan

                @can('sales_returns.view')
                    <a href="{{ route('sales-returns.index') }}"
                       class="sidebar-link {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-rotate-left sidebar-icon"></i> {{-- مردودات المبيعات --}}
                        <span>مردودات المبيعات</span>
                    </a>
                @endcan

                @can('customer_receipt_vouchers.view')
                    <a href="{{ route('customer-receipt-vouchers.index') }}"
                       class="sidebar-link {{ request()->routeIs('customer-receipt-vouchers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-wave sidebar-icon"></i> {{-- سندات قبض العملاء --}}
                        <span>سندات قبض العملاء</span>
                    </a>
                @endcan

                @can('customer_refund_vouchers.view')
                    <a href="{{ route('customer-refund-vouchers.index') }}"
                       class="sidebar-link {{ request()->routeIs('customer-refund-vouchers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-credit-card sidebar-icon"></i> {{-- سندات رد مبالغ العملاء --}}
                        <span>سندات رد مبالغ العملاء</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'purchase_invoices.view',
            'purchase_returns.view',
            'supplier_payment_vouchers.view'
        ])
            <div class="sidebar-title">المشتريات</div>

            <div class="sidebar-section">

                @can('purchase_invoices.view')
                    <a href="{{ route('purchase-invoices.index') }}"
                       class="sidebar-link {{ request()->routeIs('purchase-invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice sidebar-icon"></i> {{-- فواتير المشتريات --}}
                        <span>فواتير المشتريات</span>
                    </a>
                @endcan

                @can('purchase_returns.view')
                    <a href="{{ route('purchase-returns.index') }}"
                       class="sidebar-link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-rotate-left sidebar-icon"></i> {{-- مردودات المشتريات --}}
                        <span>مردودات المشتريات</span>
                    </a>
                @endcan

                @can('supplier_payment_vouchers.view')
                    <a href="{{ route('supplier-payment-vouchers.index') }}"
                       class="sidebar-link {{ request()->routeIs('supplier-payment-vouchers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-transfer sidebar-icon"></i> {{-- سندات صرف الموردين --}}
                        <span>سندات صرف الموردين</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'accounts.view',
            'cost_centers.view',
            'account_settings.view',
            'opening_balances.view',
            'manual_journal_entries.view',
            'general_receipt_vouchers.view',
            'general_payment_vouchers.view'
        ])
            <div class="sidebar-title">المحاسبة</div>

            <div class="sidebar-section">

                @can('accounts.view')
                    <a href="{{ route('accounts.index') }}"
                       class="sidebar-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-book sidebar-icon"></i> {{-- دليل الحسابات --}}
                        <span>دليل الحسابات</span>
                    </a>
                @endcan

                @can('cost_centers.view')
                    <a href="{{ route('cost-centers.index') }}"
                       class="sidebar-link {{ request()->routeIs('cost-centers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-bullseye sidebar-icon"></i> {{-- مراكز التكلفة --}}
                        <span>مراكز التكلفة</span>
                    </a>
                @endcan

                
                @if($isSystemUser)
                    @can('account_settings.view')
                        <a href="{{ route('account-settings.index') }}"
                           class="sidebar-link {{ request()->routeIs('account-settings.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-sliders sidebar-icon"></i> {{-- إعدادات الحسابات --}}
                            <span>إعدادات الحسابات</span>
                        </a>
                    @endcan
                @endif
                

                @can('opening_balances.view')
                    <a href="{{ route('opening-balances.index') }}"
                       class="sidebar-link {{ request()->routeIs('opening-balances.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-thumbtack sidebar-icon"></i> {{-- الأرصدة الافتتاحية --}}
                        <span>الأرصدة الافتتاحية</span>
                    </a>
                @endcan

                @can('manual_journal_entries.view')
                    <a href="{{ route('manual-journal-entries.index') }}"
                       class="sidebar-link {{ request()->routeIs('manual-journal-entries.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-book-open sidebar-icon"></i> {{-- القيود اليومية --}}
                        <span>القيود اليومية</span>
                    </a>
                @endcan

                @can('general_receipt_vouchers.view')
                    <a href="{{ route('general-receipt-vouchers.index') }}"
                       class="sidebar-link {{ request()->routeIs('general-receipt-vouchers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt sidebar-icon"></i> {{-- سند قبض عام --}}
                        <span>سند قبض عام</span>
                    </a>
                @endcan

                @can('general_payment_vouchers.view')
                    <a href="{{ route('general-payment-vouchers.index') }}"
                       class="sidebar-link {{ request()->routeIs('general-payment-vouchers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-check-dollar sidebar-icon"></i> {{-- سند صرف عام --}}
                        <span>سند صرف عام</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'sales_reports.view',
            'sales_profit_reports.view',
        ])
            <div class="sidebar-title">التقارير التشغيلية</div>

            <div class="sidebar-section">

                @can('sales_reports.view')
                    <a href="{{ route('sales-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('sales-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-simple sidebar-icon"></i> {{-- تقرير المبيعات --}}
                        <span>تقرير المبيعات</span>
                    </a>
                @endcan

                @can('sales_profit_reports.view')
                    <a href="{{ route('sales-profit-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('sales-profit-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line sidebar-icon"></i> {{-- تقرير أرباح المبيعات --}}
                        <span>تقرير أرباح المبيعات</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'customer_statements.view',
            'supplier_statements.view',
            'customer_balance_reports.view',
            'supplier_balance_reports.view'
        ])
            <div class="sidebar-title">تقارير العملاء والموردين</div>

            <div class="sidebar-section">

                @can('customer_statements.view')
                    <a href="{{ route('customer-statements.index') }}"
                       class="sidebar-link {{ request()->routeIs('customer-statements.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-lines sidebar-icon"></i> {{-- كشف حساب عميل --}}
                        <span>كشف حساب عميل</span>
                    </a>
                @endcan

                @can('supplier_statements.view')
                    <a href="{{ route('supplier-statements.index') }}"
                       class="sidebar-link {{ request()->routeIs('supplier-statements.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice sidebar-icon"></i> {{-- كشف حساب مورد --}}
                        <span>كشف حساب مورد</span>
                    </a>
                @endcan

                @can('customer_balance_reports.view')
                    <a href="{{ route('customer-balance-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('customer-balance-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users-line sidebar-icon"></i> {{-- أرصدة العملاء --}}
                        <span>أرصدة العملاء</span>
                    </a>
                @endcan

                @can('supplier_balance_reports.view')
                    <a href="{{ route('supplier-balance-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('supplier-balance-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-truck-field sidebar-icon"></i> {{-- أرصدة الموردين --}}
                        <span>أرصدة الموردين</span>
                    </a>
                @endcan

            </div>
        @endcanany


        @canany([
            'account_ledger_reports.view',
            'trial_balance_reports.view',
            'income_statement_reports.view',
            'balance_sheet_reports.view',
            'cash_flow_reports.view',
            'inventory_accounting_reconciliation_report.view'
        ])
            <div class="sidebar-title">التقارير المالية</div>

            <div class="sidebar-section">

                @can('account_ledger_reports.view')
                    <a href="{{ route('account-ledger-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('account-ledger-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-book-journal-whills sidebar-icon"></i> {{-- دفتر الأستاذ العام --}}
                        <span>دفتر الأستاذ العام</span>
                    </a>
                @endcan

                @can('trial_balance_reports.view')
                    <a href="{{ route('trial-balance-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('trial-balance-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-scale-balanced sidebar-icon"></i> {{-- ميزان المراجعة --}}
                        <span>ميزان المراجعة</span>
                    </a>
                @endcan

                @can('income_statement_reports.view')
                    <a href="{{ route('income-statement-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('income-statement-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line sidebar-icon"></i> {{-- قائمة الدخل --}}
                        <span>قائمة الدخل</span>
                    </a>
                @endcan

                @can('balance_sheet_reports.view')
                    <a href="{{ route('balance-sheet-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('balance-sheet-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-building-columns sidebar-icon"></i> {{-- الميزانية العمومية --}}
                        <span>الميزانية العمومية</span>
                    </a>
                @endcan

                @can('cash_flow_reports.view')
                    <a href="{{ route('cash-flow-reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('cash-flow-reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-trend-up sidebar-icon"></i> {{-- التدفقات النقدية --}}
                        <span>التدفقات النقدية</span>
                    </a>
                @endcan
                
                @can('inventory_accounting_reconciliation_report.view')
                    <a href="{{ route('reports.inventory-accounting-reconciliation.index') }}"
                       class="sidebar-link {{ request()->routeIs('reports.inventory-accounting-reconciliation.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-diagram-project sidebar-icon"></i> {{-- مطابقة المخزون مع الحسابات --}}
                        <span>مطابقة المخزون مع الحسابات</span>
                    </a>
                @endcan
            </div>
        @endcanany

    </div>

    <div class="sidebar-user">

        <div class="user-box">
            <a href="{{ route('profile.edit') }}" class="sidebar-link">
                <div class="user-avatar">
                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1, 'UTF-8'), 'UTF-8') }}
                </div>

                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <small>مستخدم النظام</small>
                </div>
            </a>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-danger w-100 mt-3">
                تسجيل الخروج
            </button>
        </form>

    </div>

</aside>

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
        --wazin-muted: #8EA0B8;
        --wazin-danger: #E63B4A;
        --wazin-danger-hover: #CC2F3D;
    }

    .app-sidebar {
        width: 280px;
        min-width: 280px;
        height: 100vh;
        background: var(--wazin-navy);
        color: #fff;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        overflow: hidden;
    }

    .sidebar-brand {
        min-height: 88px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: var(--wazin-navy-2);
    }

    .sidebar-icon {
        width: 24px;
        min-width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: inherit;
        line-height: 1;
    }

    .sidebar-brand h4 {
        font-size: 20px;
        font-weight: 900;
        color: #fff;
        letter-spacing: .2px;
    }

    .sidebar-brand small {
        color: var(--wazin-muted);
        font-size: 12px;
        font-weight: 600;
    }

    .sidebar-menu {
        padding: 14px 12px;
        overflow-y: auto;
        overflow-x: hidden;
        height: calc(100vh - 235px);
    }

    .sidebar-menu::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-menu::-webkit-scrollbar-track {
        background: var(--wazin-navy);
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(207, 239, 243, 0.26);
        border-radius: 20px;
    }

    .sidebar-title {
        color: var(--wazin-muted);
        font-size: 12px;
        font-weight: 900;
        padding: 16px 12px 8px;
        letter-spacing: .3px;
    }

    .sidebar-section {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .sidebar-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 42px;
        padding: 10px 12px;
        border-radius: 14px;
        color: #E5EAF3;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        transition: all .18s ease-in-out;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        transform: translateX(-2px);
    }

    .sidebar-link.active {
        background: var(--wazin-blue);
        color: #fff;
        box-shadow: 0 10px 24px rgba(47, 107, 255, 0.34);
    }

    .sidebar-link.active::before {
        content: "";
        position: absolute;
        right: -12px;
        top: 9px;
        width: 4px;
        height: 24px;
        border-radius: 10px;
        background: var(--wazin-cyan);
    }


    .sidebar-user {
        padding: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: var(--wazin-navy-2);
    }

    .user-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--wazin-navy-3);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        border: 1px solid rgba(207, 239, 243, 0.16);
    }

    .user-info {
        overflow: hidden;
    }

    .user-name {
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 170px;
    }

    .user-info small {
        color: var(--wazin-muted);
        font-size: 11px;
        font-weight: 600;
    }

    .sidebar-user .btn-danger {
        background: var(--wazin-danger) !important;
        border-color: var(--wazin-danger) !important;
        color: #fff !important;
        border-radius: 14px;
        font-weight: 900;
        padding: 10px 14px;
    }

    .sidebar-user .btn-danger:hover {
        background: var(--wazin-danger-hover) !important;
        border-color: var(--wazin-danger-hover) !important;
    }

    .sidebar-mobile-toggle {
        display: none;
    }

    .sidebar-overlay {
        display: none;
    }

    @media (max-width: 992px) {
        .sidebar-mobile-toggle {
            display: inline-flex;
            position: fixed;
            top: 14px;
            right: 14px;
            z-index: 1102;
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 14px;
            background: var(--wazin-blue);
            color: #fff;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 10px 24px rgba(47, 107, 255, 0.34);
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1101;
            width: 280px;
            min-width: 280px;
            max-width: 86vw;
            height: 100vh;
            transform: translateX(105%);
            transition: transform .25s ease-in-out;
            box-shadow: -16px 0 40px rgba(0, 0, 0, .22);
        }

        body.sidebar-open .app-sidebar {
            transform: translateX(0);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1100;
            background: rgba(2, 6, 23, .55);
            backdrop-filter: blur(2px);
        }

        body.sidebar-open .sidebar-overlay {
            display: block;
        }

        .sidebar-brand {
            padding-top: 68px;
        }

        .sidebar-menu {
            height: calc(100vh - 285px);
        }

        body.sidebar-open {
            overflow: hidden;
        }
    }

    @media (max-width: 576px) {
        .app-sidebar {
            width: 82vw;
            min-width: 82vw;
        }

        .sidebar-brand h4 {
            font-size: 18px;
        }

        .sidebar-brand small {
            font-size: 11px;
        }

        .sidebar-link {
            min-height: 44px;
            font-size: 13px;
        }

        .sidebar-title {
            font-size: 11px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarMenu = document.getElementById('sidebarMenu');
        const activeLink = sidebarMenu ? sidebarMenu.querySelector('.sidebar-link.active') : null;

        if (sidebarMenu && activeLink) {
            const menuHeight = sidebarMenu.clientHeight;
            const linkOffsetTop = activeLink.offsetTop;
            const linkHeight = activeLink.offsetHeight;

            sidebarMenu.scrollTop = linkOffsetTop - (menuHeight / 2) + (linkHeight / 2);
        }

        const toggleButton = document.getElementById('sidebarMobileToggle');
        const overlay = document.getElementById('sidebarOverlay');
        const sidebarLinks = document.querySelectorAll('.app-sidebar .sidebar-link');

        function openSidebar() {
            document.body.classList.add('sidebar-open');
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-open');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        sidebarLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 992) {
                closeSidebar();
            }
        });
    });
</script>