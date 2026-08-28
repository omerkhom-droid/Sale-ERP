<x-app-layout>
@php
    $permissionGroupLabels = [
        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        'dashboard' => 'لوحة التحكم',

        /*
        |--------------------------------------------------------------------------
        | الإدارة العامة
        |--------------------------------------------------------------------------
        */
        'companies' => 'الشركات',
        'company_settings' => 'إعدادات الشركة',
        'license' => 'إدارة الاشتراك',
        'branches' => 'الفروع',
        'warehouses' => 'المستودعات',
        'backups' => 'النسخ الاحتياطي',

        /*
        |--------------------------------------------------------------------------
        | المنتجات والمخزون
        |--------------------------------------------------------------------------
        */
        'units' => 'الوحدات',
        'categories' => 'التصنيفات',
        'brands' => 'العلامات التجارية',
        'products' => 'المنتجات',
        'opening_stock' => 'الرصيد الافتتاحي للمخزون',
        'inventory_movements' => 'حركة المخزون',
        'inventory_movement_reports' => 'تقرير حركة المخزون',
        'inventory_counts' => 'الجرد المخزني',
        'inventory_damages' => 'التالف / إتلاف المخزون',
        'warehouse_transfers' => 'التحويل بين المستودعات',
        'inventory_balance_report' => 'أرصدة وتقييم المخزون',
        'inventory_accounting_reconciliation_report' => 'مطابقة المخزون مع الحسابات',

        /*
        |--------------------------------------------------------------------------
        | العملاء والموردون
        |--------------------------------------------------------------------------
        */
        'customers' => 'العملاء',
        'suppliers' => 'الموردون',

        /*
        |--------------------------------------------------------------------------
        | المبيعات
        |--------------------------------------------------------------------------
        */
        'quotations' => 'عروض الأسعار',
        'sales_invoices' => 'فواتير المبيعات',
        'sales_returns' => 'مردودات المبيعات',
        'customer_receipt_vouchers' => 'سندات قبض العملاء',
        'customer_refund_vouchers' => 'سندات رد مبالغ العملاء',

        /*
        |--------------------------------------------------------------------------
        | المشتريات
        |--------------------------------------------------------------------------
        */
        'purchase_invoices' => 'فواتير المشتريات',
        'purchase_returns' => 'مردودات المشتريات',
        'supplier_payment_vouchers' => 'سندات صرف الموردين',

        /*
        |--------------------------------------------------------------------------
        | المحاسبة
        |--------------------------------------------------------------------------
        */
        'accounts' => 'دليل الحسابات',
        'account_settings' => 'إعدادات الحسابات',
        'cost_centers' => 'مراكز التكلفة',
        'opening_balances' => 'الأرصدة الافتتاحية',
        'manual_journal_entries' => 'القيود اليومية',
        'general_receipt_vouchers' => 'سندات القبض العامة',
        'general_payment_vouchers' => 'سندات الصرف العامة',

        /*
        |--------------------------------------------------------------------------
        | التقارير التشغيلية
        |--------------------------------------------------------------------------
        */
        'sales_reports' => 'تقرير المبيعات',
        'sales_profit_reports' => 'تقرير أرباح المبيعات',

        /*
        |--------------------------------------------------------------------------
        | تقارير العملاء والموردين
        |--------------------------------------------------------------------------
        */
        'customer_statements' => 'كشف حساب عميل',
        'supplier_statements' => 'كشف حساب مورد',
        'customer_balance_reports' => 'أرصدة العملاء',
        'supplier_balance_reports' => 'أرصدة الموردين',

        /*
        |--------------------------------------------------------------------------
        | التقارير المالية
        |--------------------------------------------------------------------------
        */
        'account_ledger_reports' => 'دفتر الأستاذ العام',
        'trial_balance_reports' => 'ميزان المراجعة',
        'income_statement_reports' => 'قائمة الدخل',
        'balance_sheet_reports' => 'الميزانية العمومية',
        'cash_flow_reports' => 'التدفقات النقدية',

        /*
        |--------------------------------------------------------------------------
        | المستخدمون والصلاحيات
        |--------------------------------------------------------------------------
        */
        'users' => 'المستخدمون',
        'roles' => 'الأدوار والصلاحيات',
    ];

    $permissionLabels = [
        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        'dashboard.view' => 'عرض لوحة التحكم',

        /*
        |--------------------------------------------------------------------------
        | Companies
        |--------------------------------------------------------------------------
        */
        'companies.view' => 'عرض الشركات',
        'companies.create' => 'إضافة شركة',
        'companies.edit' => 'تعديل شركة',
        'companies.delete' => 'حذف شركة',

        /*
        |--------------------------------------------------------------------------
        | Company Settings
        |--------------------------------------------------------------------------
        */
        'company_settings.view' => 'عرض إعدادات الشركة',
        'company_settings.update' => 'تعديل إعدادات الشركة',

        /*
        |--------------------------------------------------------------------------
        | License
        |--------------------------------------------------------------------------
        */
        'license.view' => 'عرض الاشتراك',
        'license.update' => 'تعديل الاشتراك',

        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */
        'branches.view' => 'عرض الفروع',
        'branches.create' => 'إضافة فرع',
        'branches.edit' => 'تعديل فرع',
        'branches.delete' => 'حذف فرع',

        /*
        |--------------------------------------------------------------------------
        | Warehouses
        |--------------------------------------------------------------------------
        */
        'warehouses.view' => 'عرض المستودعات',
        'warehouses.create' => 'إضافة مستودع',
        'warehouses.edit' => 'تعديل مستودع',
        'warehouses.delete' => 'حذف مستودع',

        /*
        |--------------------------------------------------------------------------
        | Backups
        |--------------------------------------------------------------------------
        */
        'backups.view' => 'عرض النسخ الاحتياطي',
        'backups.create' => 'إنشاء نسخة احتياطية',
        'backups.download' => 'تحميل نسخة احتياطية',
        'backups.delete' => 'حذف نسخة احتياطية',

        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        */
        'units.view' => 'عرض الوحدات',
        'units.create' => 'إضافة وحدة',
        'units.edit' => 'تعديل وحدة',
        'units.delete' => 'حذف وحدة',

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */
        'categories.view' => 'عرض التصنيفات',
        'categories.create' => 'إضافة تصنيف',
        'categories.edit' => 'تعديل تصنيف',
        'categories.delete' => 'حذف تصنيف',

        /*
        |--------------------------------------------------------------------------
        | Brands
        |--------------------------------------------------------------------------
        */
        'brands.view' => 'عرض العلامات التجارية',
        'brands.create' => 'إضافة علامة تجارية',
        'brands.edit' => 'تعديل علامة تجارية',
        'brands.delete' => 'حذف علامة تجارية',

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        'products.view' => 'عرض المنتجات',
        'products.create' => 'إضافة منتج',
        'products.edit' => 'تعديل منتج',
        'products.delete' => 'حذف منتج',
        'products.import' => 'استيراد المنتجات',
        'products.export' => 'تصدير المنتجات',
        'products.import_csv' => 'استيراد المنتجات CSV',
        'products.export_csv' => 'تصدير المنتجات CSV',

        /*
        |--------------------------------------------------------------------------
        | Opening Stock
        |--------------------------------------------------------------------------
        */
        'opening_stock.view' => 'عرض الرصيد الافتتاحي للمخزون',
        'opening_stock.create' => 'إضافة رصيد افتتاحي للمخزون',
        'opening_stock.edit_draft' => 'تعديل مسودة رصيد المخزون',
        'opening_stock.post' => 'ترحيل رصيد المخزون',
        'opening_stock.cancel' => 'إلغاء رصيد المخزون',
        'opening_stock.print' => 'طباعة الرصيد الافتتاحي للمخزون',
        'opening_stock.delete_draft' => 'حذف مسودة رصيد المخزون',

        /*
        |--------------------------------------------------------------------------
        | Inventory Movements
        |--------------------------------------------------------------------------
        */
        'inventory_movements.view' => 'عرض حركة المخزون',

        /*
        |--------------------------------------------------------------------------
        | Inventory Movement Reports
        |--------------------------------------------------------------------------
        */
        'inventory_movement_reports.view' => 'عرض تقرير حركة المخزون',
        'inventory_movement_reports.print' => 'طباعة تقرير حركة المخزون',
        'inventory_movement_reports.export' => 'تصدير تقرير حركة المخزون',

        /*
        |--------------------------------------------------------------------------
        | Inventory Counts
        |--------------------------------------------------------------------------
        */
        'inventory_counts.view' => 'عرض الجرد المخزني',
        'inventory_counts.create' => 'إنشاء جرد مخزني',
        'inventory_counts.edit' => 'تعديل جرد مخزني',
        'inventory_counts.post' => 'ترحيل الجرد المخزني',
        'inventory_counts.cancel' => 'إلغاء الجرد المخزني',
        'inventory_counts.print' => 'طباعة الجرد المخزني',

        /*
        |--------------------------------------------------------------------------
        | Inventory Damages
        |--------------------------------------------------------------------------
        */
        'inventory_damages.view' => 'عرض التالف / إتلاف المخزون',
        'inventory_damages.create' => 'إنشاء سند تالف',
        'inventory_damages.post' => 'ترحيل سند التالف',
        'inventory_damages.cancel' => 'إلغاء سند التالف',
        'inventory_damages.print' => 'طباعة سند التالف',

        /*
        |--------------------------------------------------------------------------
        | Warehouse Transfers
        |--------------------------------------------------------------------------
        */
        'warehouse_transfers.view' => 'عرض التحويل بين المستودعات',
        'warehouse_transfers.create' => 'إنشاء تحويل بين المستودعات',
        'warehouse_transfers.post' => 'ترحيل تحويل بين المستودعات',
        'warehouse_transfers.cancel' => 'إلغاء تحويل بين المستودعات',
        'warehouse_transfers.print' => 'طباعة تحويل بين المستودعات',

        /*
        |--------------------------------------------------------------------------
        | Inventory Balance Report
        |--------------------------------------------------------------------------
        */
        'inventory_balance_report.view' => 'عرض أرصدة وتقييم المخزون',

        /*
        |--------------------------------------------------------------------------
        | Inventory Accounting Reconciliation Report
        |--------------------------------------------------------------------------
        */
        'inventory_accounting_reconciliation_report.view' => 'عرض مطابقة المخزون مع الحسابات',

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */
        'customers.view' => 'عرض العملاء',
        'customers.create' => 'إضافة عميل',
        'customers.edit' => 'تعديل عميل',
        'customers.delete' => 'حذف عميل',

        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */
        'suppliers.view' => 'عرض الموردين',
        'suppliers.create' => 'إضافة مورد',
        'suppliers.edit' => 'تعديل مورد',
        'suppliers.delete' => 'حذف مورد',

        /*
        |--------------------------------------------------------------------------
        | Quotations
        |--------------------------------------------------------------------------
        */
        'quotations.view' => 'عرض عروض الأسعار',
        'quotations.create' => 'إنشاء عرض سعر',
        'quotations.edit' => 'تعديل عرض سعر',
        'quotations.cancel' => 'إلغاء عرض سعر',
        'quotations.print' => 'طباعة عرض سعر',
        'quotations.convert' => 'تحويل عرض السعر إلى فاتورة',
        'quotations.approve' => 'اعتماد عرض سعر',
        'quotations.reject' => 'رفض عرض سعر',

        /*
        |--------------------------------------------------------------------------
        | Sales Invoices
        |--------------------------------------------------------------------------
        */
        'sales_invoices.view' => 'عرض فواتير المبيعات',
        'sales_invoices.create' => 'إنشاء فاتورة مبيعات',
        'sales_invoices.edit_draft' => 'تعديل مسودة فاتورة مبيعات',
        'sales_invoices.post' => 'ترحيل فاتورة مبيعات',
        'sales_invoices.cancel' => 'إلغاء فاتورة مبيعات',
        'sales_invoices.print' => 'طباعة فاتورة مبيعات',
        'sales_invoices.delete_draft' => 'حذف مسودة فاتورة مبيعات',

        /*
        |--------------------------------------------------------------------------
        | Sales Returns
        |--------------------------------------------------------------------------
        */
        'sales_returns.view' => 'عرض مردودات المبيعات',
        'sales_returns.create' => 'إنشاء مردود مبيعات',
        'sales_returns.edit_draft' => 'تعديل مسودة مردود مبيعات',
        'sales_returns.post' => 'ترحيل مردود مبيعات',
        'sales_returns.cancel' => 'إلغاء مردود مبيعات',
        'sales_returns.print' => 'طباعة مردود مبيعات',
        'sales_returns.delete_draft' => 'حذف مسودة مردود مبيعات',

        /*
        |--------------------------------------------------------------------------
        | Customer Receipt Vouchers
        |--------------------------------------------------------------------------
        */
        'customer_receipt_vouchers.view' => 'عرض سندات قبض العملاء',
        'customer_receipt_vouchers.create' => 'إنشاء سند قبض عميل',
        'customer_receipt_vouchers.edit_draft' => 'تعديل مسودة سند قبض عميل',
        'customer_receipt_vouchers.post' => 'ترحيل سند قبض عميل',
        'customer_receipt_vouchers.cancel' => 'إلغاء سند قبض عميل',
        'customer_receipt_vouchers.print' => 'طباعة سند قبض عميل',
        'customer_receipt_vouchers.delete_draft' => 'حذف مسودة سند قبض عميل',

        /*
        |--------------------------------------------------------------------------
        | Customer Refund Vouchers
        |--------------------------------------------------------------------------
        */
        'customer_refund_vouchers.view' => 'عرض سندات رد مبالغ العملاء',
        'customer_refund_vouchers.create' => 'إنشاء سند رد مبلغ عميل',
        'customer_refund_vouchers.edit_draft' => 'تعديل مسودة سند رد مبلغ عميل',
        'customer_refund_vouchers.post' => 'ترحيل سند رد مبلغ عميل',
        'customer_refund_vouchers.cancel' => 'إلغاء سند رد مبلغ عميل',
        'customer_refund_vouchers.print' => 'طباعة سند رد مبلغ عميل',
        'customer_refund_vouchers.delete_draft' => 'حذف مسودة سند رد مبلغ عميل',

        /*
        |--------------------------------------------------------------------------
        | Purchase Invoices
        |--------------------------------------------------------------------------
        */
        'purchase_invoices.view' => 'عرض فواتير المشتريات',
        'purchase_invoices.create' => 'إنشاء فاتورة مشتريات',
        'purchase_invoices.edit_draft' => 'تعديل مسودة فاتورة مشتريات',
        'purchase_invoices.post' => 'ترحيل فاتورة مشتريات',
        'purchase_invoices.cancel' => 'إلغاء فاتورة مشتريات',
        'purchase_invoices.print' => 'طباعة فاتورة مشتريات',
        'purchase_invoices.delete_draft' => 'حذف مسودة فاتورة مشتريات',

        /*
        |--------------------------------------------------------------------------
        | Purchase Returns
        |--------------------------------------------------------------------------
        */
        'purchase_returns.view' => 'عرض مردودات المشتريات',
        'purchase_returns.create' => 'إنشاء مردود مشتريات',
        'purchase_returns.edit_draft' => 'تعديل مسودة مردود مشتريات',
        'purchase_returns.post' => 'ترحيل مردود مشتريات',
        'purchase_returns.cancel' => 'إلغاء مردود مشتريات',
        'purchase_returns.print' => 'طباعة مردود مشتريات',
        'purchase_returns.delete_draft' => 'حذف مسودة مردود مشتريات',

        /*
        |--------------------------------------------------------------------------
        | Supplier Payment Vouchers
        |--------------------------------------------------------------------------
        */
        'supplier_payment_vouchers.view' => 'عرض سندات صرف الموردين',
        'supplier_payment_vouchers.create' => 'إنشاء سند صرف مورد',
        'supplier_payment_vouchers.edit_draft' => 'تعديل مسودة سند صرف مورد',
        'supplier_payment_vouchers.post' => 'ترحيل سند صرف مورد',
        'supplier_payment_vouchers.cancel' => 'إلغاء سند صرف مورد',
        'supplier_payment_vouchers.print' => 'طباعة سند صرف مورد',
        'supplier_payment_vouchers.delete_draft' => 'حذف مسودة سند صرف مورد',

        /*
        |--------------------------------------------------------------------------
        | Accounts
        |--------------------------------------------------------------------------
        */
        'accounts.view' => 'عرض دليل الحسابات',
        'accounts.create' => 'إضافة حساب',
        'accounts.edit' => 'تعديل حساب',
        'accounts.delete' => 'حذف حساب',

        /*
        |--------------------------------------------------------------------------
        | Account Settings
        |--------------------------------------------------------------------------
        */
        'account_settings.view' => 'عرض إعدادات الحسابات',
        'account_settings.update' => 'تعديل إعدادات الحسابات',

        /*
        |--------------------------------------------------------------------------
        | Cost Centers
        |--------------------------------------------------------------------------
        */
        'cost_centers.view' => 'عرض مراكز التكلفة',
        'cost_centers.create' => 'إضافة مركز تكلفة',
        'cost_centers.edit' => 'تعديل مركز تكلفة',
        'cost_centers.delete' => 'حذف مركز تكلفة',

        /*
        |--------------------------------------------------------------------------
        | Opening Balances
        |--------------------------------------------------------------------------
        */
        'opening_balances.view' => 'عرض الأرصدة الافتتاحية',
        'opening_balances.create' => 'إنشاء رصيد افتتاحي',
        'opening_balances.edit_draft' => 'تعديل مسودة رصيد افتتاحي',
        'opening_balances.post' => 'ترحيل رصيد افتتاحي',
        'opening_balances.cancel' => 'إلغاء رصيد افتتاحي',
        'opening_balances.print' => 'طباعة رصيد افتتاحي',
        'opening_balances.delete_draft' => 'حذف مسودة رصيد افتتاحي',

        /*
        |--------------------------------------------------------------------------
        | Manual Journal Entries
        |--------------------------------------------------------------------------
        */
        'manual_journal_entries.view' => 'عرض القيود اليومية',
        'manual_journal_entries.create' => 'إنشاء قيد يومية',
        'manual_journal_entries.edit' => 'تعديل مسودة قيد يومية',
        'manual_journal_entries.post' => 'ترحيل قيد يومية',
        'manual_journal_entries.cancel' => 'إلغاء قيد يومية',
        'manual_journal_entries.print' => 'طباعة قيد يومية',
        'manual_journal_entries.delete' => 'حذف مسودة قيد يومية',

        /*
        |--------------------------------------------------------------------------
        | General Receipt Vouchers
        |--------------------------------------------------------------------------
        */
        'general_receipt_vouchers.view' => 'عرض سندات القبض العامة',
        'general_receipt_vouchers.create' => 'إنشاء سند قبض عام',
        'general_receipt_vouchers.edit_draft' => 'تعديل مسودة سند قبض عام',
        'general_receipt_vouchers.post' => 'ترحيل سند قبض عام',
        'general_receipt_vouchers.cancel' => 'إلغاء سند قبض عام',
        'general_receipt_vouchers.print' => 'طباعة سند قبض عام',
        'general_receipt_vouchers.delete_draft' => 'حذف مسودة سند قبض عام',

        /*
        |--------------------------------------------------------------------------
        | General Payment Vouchers
        |--------------------------------------------------------------------------
        */
        'general_payment_vouchers.view' => 'عرض سندات الصرف العامة',
        'general_payment_vouchers.create' => 'إنشاء سند صرف عام',
        'general_payment_vouchers.edit_draft' => 'تعديل مسودة سند صرف عام',
        'general_payment_vouchers.post' => 'ترحيل سند صرف عام',
        'general_payment_vouchers.cancel' => 'إلغاء سند صرف عام',
        'general_payment_vouchers.print' => 'طباعة سند صرف عام',
        'general_payment_vouchers.delete_draft' => 'حذف مسودة سند صرف عام',

        /*
        |--------------------------------------------------------------------------
        | Sales Reports
        |--------------------------------------------------------------------------
        */
        'sales_reports.view' => 'عرض تقرير المبيعات',
        'sales_reports.print' => 'طباعة تقرير المبيعات',
        'sales_reports.export' => 'تصدير تقرير المبيعات',

        /*
        |--------------------------------------------------------------------------
        | Sales Profit Reports
        |--------------------------------------------------------------------------
        */
        'sales_profit_reports.view' => 'عرض تقرير أرباح المبيعات',
        'sales_profit_reports.print' => 'طباعة تقرير أرباح المبيعات',
        'sales_profit_reports.export' => 'تصدير تقرير أرباح المبيعات',

        /*
        |--------------------------------------------------------------------------
        | Customer Statements
        |--------------------------------------------------------------------------
        */
        'customer_statements.view' => 'عرض كشف حساب عميل',
        'customer_statements.print' => 'طباعة كشف حساب عميل',
        'customer_statements.export' => 'تصدير كشف حساب عميل',

        /*
        |--------------------------------------------------------------------------
        | Supplier Statements
        |--------------------------------------------------------------------------
        */
        'supplier_statements.view' => 'عرض كشف حساب مورد',
        'supplier_statements.print' => 'طباعة كشف حساب مورد',
        'supplier_statements.export' => 'تصدير كشف حساب مورد',

        /*
        |--------------------------------------------------------------------------
        | Customer Balance Reports
        |--------------------------------------------------------------------------
        */
        'customer_balance_reports.view' => 'عرض أرصدة العملاء',
        'customer_balance_reports.print' => 'طباعة أرصدة العملاء',
        'customer_balance_reports.export' => 'تصدير أرصدة العملاء',

        /*
        |--------------------------------------------------------------------------
        | Supplier Balance Reports
        |--------------------------------------------------------------------------
        */
        'supplier_balance_reports.view' => 'عرض أرصدة الموردين',
        'supplier_balance_reports.print' => 'طباعة أرصدة الموردين',
        'supplier_balance_reports.export' => 'تصدير أرصدة الموردين',

        /*
        |--------------------------------------------------------------------------
        | Account Ledger Reports
        |--------------------------------------------------------------------------
        */
        'account_ledger_reports.view' => 'عرض دفتر الأستاذ العام',
        'account_ledger_reports.print' => 'طباعة دفتر الأستاذ العام',
        'account_ledger_reports.export' => 'تصدير دفتر الأستاذ العام',

        /*
        |--------------------------------------------------------------------------
        | Trial Balance Reports
        |--------------------------------------------------------------------------
        */
        'trial_balance_reports.view' => 'عرض ميزان المراجعة',
        'trial_balance_reports.print' => 'طباعة ميزان المراجعة',
        'trial_balance_reports.export' => 'تصدير ميزان المراجعة',

        /*
        |--------------------------------------------------------------------------
        | Income Statement Reports
        |--------------------------------------------------------------------------
        */
        'income_statement_reports.view' => 'عرض قائمة الدخل',
        'income_statement_reports.print' => 'طباعة قائمة الدخل',
        'income_statement_reports.export' => 'تصدير قائمة الدخل',

        /*
        |--------------------------------------------------------------------------
        | Balance Sheet Reports
        |--------------------------------------------------------------------------
        */
        'balance_sheet_reports.view' => 'عرض الميزانية العمومية',
        'balance_sheet_reports.print' => 'طباعة الميزانية العمومية',
        'balance_sheet_reports.export' => 'تصدير الميزانية العمومية',

        /*
        |--------------------------------------------------------------------------
        | Cash Flow Reports
        |--------------------------------------------------------------------------
        */
        'cash_flow_reports.view' => 'عرض التدفقات النقدية',
        'cash_flow_reports.print' => 'طباعة التدفقات النقدية',
        'cash_flow_reports.export' => 'تصدير التدفقات النقدية',

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        'users.view' => 'عرض المستخدمين',
        'users.create' => 'إضافة مستخدم',
        'users.edit' => 'تعديل مستخدم',
        'users.delete' => 'حذف مستخدم',

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        'roles.view' => 'عرض الأدوار والصلاحيات',
        'roles.create' => 'إضافة دور',
        'roles.edit' => 'تعديل دور',
        'roles.delete' => 'حذف دور',
    ];
@endphp

<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">الأدوار والصلاحيات</h3>
            <p class="page-subtitle mb-0">
                إدارة أدوار المستخدمين وتحديد الصلاحيات المتاحة لكل دور داخل النظام.
            </p>
        </div>

        @can('roles.create')
            <button type="button" id="add_button" class="btn btn-primary">
                + دور جديد
            </button>
        @endcan
    </div>

    <div id="alert_action"></div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    {{-- Table --}}
    <div class="card shadow-sm wazin-card">
        <div class="card-header wazin-card-header">
            <div>
                <h5 class="mb-0 fw-bold">قائمة الأدوار</h5>
                <small>عرض وتعديل الأدوار وربطها بالصلاحيات التفصيلية</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-center align-middle w-100" id="rolesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الدور</th>
                            <th>المستوى</th>
                            <th>عدد الصلاحيات</th>
                            <th>عدد المستخدمين</th>
                            <th width="180">الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modal --}}
@canany(['roles.create', 'roles.edit'])
<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <form method="POST" id="roleForm" action="{{ route('roles.store') }}" class="modal-content">
            @csrf

            <input type="hidden" name="_method" id="roleFormMethod" value="POST">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="roleModalTitle">إضافة دور</h5>
                    <small>حدد اسم الدور والصلاحيات المسموح بها لهذا الدور</small>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="roleFormErrorBox" class="alert alert-danger d-none mb-3"></div>
                <div id="hiddenPermissionsNotice" class="alert alert-warning d-none mb-3"></div>

                <div class="form-section-title">بيانات الدور</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">اسم الدور <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="role_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">بحث داخل الصلاحيات</label>
                        <input type="text" id="permissionSearch" class="form-control" placeholder="اكتب للبحث عن صلاحية أو مجموعة...">
                    </div>
                </div>

                <div class="permissions-toolbar mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">الصلاحيات</h6>
                        <small>تظهر هنا فقط الصلاحيات المسموح لك بمنحها.</small>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAllPermissions()">
                            تحديد الظاهر
                        </button>

                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearAllPermissions()">
                            إلغاء الكل
                        </button>
                    </div>
                </div>

                <div class="row g-3" id="permissionsGrid">

                    @forelse($permissions as $group => $groupPermissions)
                        <div class="col-md-6 permission-group-col"
                             data-search="{{ $group }} {{ $permissionGroupLabels[$group] ?? $group }}">

                            <div class="permission-card h-100">

                                <div class="permission-card-header">
                                    <div class="form-check m-0">
                                        <input type="checkbox"
                                               class="form-check-input permission-group-check"
                                               id="group_{{ $group }}"
                                               data-group="{{ $group }}"
                                               onchange="togglePermissionGroup('{{ $group }}', this.checked)">

                                        <label class="form-check-label fw-bold" for="group_{{ $group }}">
                                            {{ $permissionGroupLabels[$group] ?? $group }}
                                        </label>
                                    </div>
                                </div>

                                <div class="permission-card-body">
                                    @foreach($groupPermissions as $permission)
                                        <div class="form-check permission-item mb-2"
                                             data-search="{{ $permission->name }} {{ $permissionLabels[$permission->name] ?? $permission->name }}">

                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->name }}"
                                                   class="form-check-input permission-checkbox permission-group-{{ $group }}"
                                                   id="permission_{{ str_replace(['.', ' '], '_', $permission->name) }}">

                                            <label class="form-check-label" for="permission_{{ str_replace(['.', ' '], '_', $permission->name) }}">
                                                <span>{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                                <small class="text-muted d-block">{{ $permission->name }}</small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                لا توجد صلاحيات متاحة لك لمنحها.
                            </div>
                        </div>
                    @endforelse

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    إغلاق
                </button>

                <button type="submit" class="btn btn-primary">
                    حفظ
                </button>
            </div>

        </form>
    </div>
</div>
@endcanany

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

    #rolesTable thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
    }

    #rolesTable tbody td {
        vertical-align: middle;
        font-weight: 600;
    }

    .modal-header {
        background: #071633;
        color: #fff;
        border-bottom: 0;
        padding: 18px 22px;
    }

    .modal-header small {
        color: #CFEFF3;
        font-weight: 600;
    }

    .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(7, 22, 51, 0.22);
    }

    .modal-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .modal-footer {
        background: #fff;
        border-top: 1px solid #E5E7EB;
        padding: 16px 22px;
    }

    .form-section-title {
        color: #071633;
        font-weight: 900;
        margin: 0 0 14px;
        padding: 10px 14px;
        background: #fff;
        border-right: 5px solid #2F6BFF;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .form-label {
        color: #071633;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 600;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .permissions-toolbar {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .permissions-toolbar h6 {
        color: #071633;
        font-weight: 900;
    }

    .permissions-toolbar small {
        color: #64748B;
        font-weight: 700;
    }

    .permission-card {
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .permission-card-header {
        background: #071633;
        color: #fff;
        padding: 13px 15px;
        border-bottom: 1px solid #101F3C;
    }

    .permission-card-header label {
        cursor: pointer;
        font-weight: 900;
    }

    .permission-card-body {
        padding: 14px;
        max-height: 280px;
        overflow-y: auto;
    }

    .permission-card-body::-webkit-scrollbar {
        width: 6px;
    }

    .permission-card-body::-webkit-scrollbar-track {
        background: #F1F5F9;
    }

    .permission-card-body::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 20px;
    }

    .permission-item {
        padding: 9px 10px;
        border: 1px solid #EEF2F7;
        border-radius: 13px;
        background: #F8FAFC;
        transition: all .18s ease-in-out;
        display: flex !important;
        align-items: flex-start;
        gap: 12px;
        direction: rtl;
    }

    .permission-item:hover {
        background: rgba(47, 107, 255, 0.06);
        border-color: rgba(47, 107, 255, 0.25);
    }

    .permission-card .form-check {
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin: 0 !important;
    }

    .permission-card-header .form-check {
        display: flex;
        align-items: center;
        gap: 12px;
        direction: rtl;
    }

    .permission-card-header .form-check-input {
        float: none !important;
        margin: 0 !important;
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 2px solid rgba(255, 255, 255, 0.75);
        background-color: #fff;
        cursor: pointer;
        flex: 0 0 auto;
    }

    .permission-card-header .form-check-input:checked {
        background-color: #2F6BFF;
        border-color: #2F6BFF;
    }

    .permission-card-header .form-check-label {
        margin: 0;
        flex: 1;
        text-align: right;
        color: #fff;
        font-weight: 900;
    }

    .permission-item .form-check-input {
        float: none !important;
        margin: 5px 0 0 0 !important;
        width: 18px;
        height: 18px;
        min-width: 18px;
        border-radius: 5px;
        border: 2px solid #CBD5E1;
        cursor: pointer;
        flex: 0 0 auto;
    }

    .permission-item .form-check-input:checked {
        background-color: #2F6BFF;
        border-color: #2F6BFF;
    }

    .permission-item .form-check-label {
        margin: 0;
        flex: 1;
        text-align: right;
        cursor: pointer;
        width: 100%;
        font-weight: 800;
        color: #071633;
        line-height: 1.7;
    }

    .permission-item .form-check-label span {
        display: block;
        color: #071633;
        font-weight: 900;
        line-height: 1.7;
    }

    .permission-item .form-check-label small {
        display: block;
        margin-top: 4px;
        color: #64748B !important;
        font-size: 11px;
        font-weight: 700;
        direction: ltr;
        text-align: right;
        word-break: break-word;
    }

    .form-check-input {
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #2F6BFF;
        border-color: #2F6BFF;
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

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    .badge {
        font-weight: 900;
        padding: 7px 10px;
        border-radius: 10px;
    }

    /* DataTables Wazin Style */
    #rolesTable_wrapper {
        direction: rtl;
    }

    #rolesTable_wrapper .dataTables_length,
    #rolesTable_wrapper .dataTables_filter {
        margin-bottom: 18px;
    }

    #rolesTable_wrapper .dataTables_length label,
    #rolesTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #071633;
        font-weight: 900;
        font-size: 14px;
        white-space: nowrap;
    }

    #rolesTable_wrapper .dataTables_filter {
        float: left;
        text-align: left;
    }

    #rolesTable_wrapper .dataTables_length {
        float: right;
        text-align: right;
    }

    #rolesTable_wrapper .dataTables_filter input {
        width: 280px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 8px 14px;
        margin: 0;
        outline: none;
        color: #111827;
        font-weight: 700;
        background: #fff;
        transition: all .18s ease-in-out;
    }

    #rolesTable_wrapper .dataTables_filter input:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    #rolesTable_wrapper .dataTables_length select {
        width: 90px;
        height: 44px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 6px 12px;
        margin: 0 8px;
        outline: none;
        color: #111827;
        font-weight: 800;
        background: #fff;
    }

    #rolesTable_wrapper .dataTables_info {
        color: #64748B;
        font-weight: 800;
        padding-top: 16px;
        font-size: 14px;
    }

    #rolesTable_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #rolesTable_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #E5E7EB !important;
        background: #fff !important;
        color: #071633 !important;
        border-radius: 12px !important;
        padding: 8px 14px !important;
        margin: 0 2px !important;
        font-weight: 900;
        cursor: pointer;
        transition: all .18s ease-in-out;
    }

    #rolesTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
    }

    #rolesTable_wrapper .dataTables_paginate .paginate_button.current,
    #rolesTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(47, 107, 255, 0.22);
    }

    #rolesTable_wrapper .dataTables_paginate .paginate_button.disabled,
    #rolesTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #F1F5F9 !important;
        color: #94A3B8 !important;
        border-color: #E5E7EB !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    #rolesTable_wrapper::after {
        content: "";
        display: block;
        clear: both;
    }

    @media (max-width: 767px) {
        .page-header-card,
        .permissions-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn,
        .permissions-toolbar .btn {
            width: 100%;
        }

        #rolesTable_wrapper .dataTables_filter,
        #rolesTable_wrapper .dataTables_length {
            float: none;
            text-align: right;
            width: 100%;
        }

        #rolesTable_wrapper .dataTables_length label,
        #rolesTable_wrapper .dataTables_filter label {
            justify-content: space-between;
            width: 100%;
        }

        #rolesTable_wrapper .dataTables_filter input {
            width: 100%;
        }

        #rolesTable_wrapper .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
</style>

@push('scripts')
<script>
    let rolesTable;

    $(document).ready(function () {

        rolesTable = $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('roles.fetch') }}",
            language: {
                processing: 'جاري المعالجة...',
                search: 'بحث:',
                lengthMenu: 'عرض _MENU_ سجل',
                info: 'إظهار _START_ إلى _END_ من أصل _TOTAL_ سجل',
                infoEmpty: 'لا توجد سجلات',
                infoFiltered: '(تمت التصفية من أصل _MAX_ سجل)',
                loadingRecords: 'جاري التحميل...',
                zeroRecords: 'لا توجد بيانات مطابقة',
                emptyTable: 'لا توجد بيانات متاحة',
                paginate: {
                    first: 'الأول',
                    previous: 'السابق',
                    next: 'التالي',
                    last: 'الأخير'
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'level_badge', name: 'level_badge', orderable: false, searchable: false },
                { data: 'permissions_count_badge', name: 'permissions_count_badge', orderable: false, searchable: false },
                { data: 'users_count_badge', name: 'users_count_badge', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[1, 'asc']]
        });

        $('#add_button').on('click', function () {
            openCreateRoleModal();
        });

        $('#roleForm').on('submit', function (e) {
            e.preventDefault();

            let form = this;
            let formData = new FormData(form);

            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                beforeSend: function () {
                    $('#roleForm button[type="submit"]').prop('disabled', true).text('جاري الحفظ...');
                    hideRoleFormError();
                },
                success: function (response) {
                    $('#roleForm button[type="submit"]').prop('disabled', false).text('حفظ');

                    const modalElement = document.getElementById('roleModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);

                    if (modal) {
                        modal.hide();
                    }

                    form.reset();

                    rolesTable.ajax.reload(null, false);

                    showPageAlert(response.message ?? 'تم حفظ الدور بنجاح.', 'success');
                },
                error: function (xhr) {
                    $('#roleForm button[type="submit"]').prop('disabled', false).text('حفظ');

                    let message = 'حدث خطأ أثناء الحفظ.';

                    if (xhr.status === 403) {
                        message = xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لتنفيذ هذه العملية.';
                    } else if (xhr.status === 401) {
                        message = 'انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.';
                    } else if (xhr.status === 419) {
                        message = 'انتهت صلاحية الجلسة، قم بتحديث الصفحة وحاول مرة أخرى.';
                    } else if (xhr.status === 422) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else {
                            message = xhr.responseJSON?.message ?? 'يرجى مراجعة البيانات المدخلة.';
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    showRoleFormError(message);
                }
            });
        });

        $('#permissionSearch').on('input', function () {
            filterPermissions(this.value);
        });

    });

    function openCreateRoleModal() {
        hideRoleFormError();
        hideHiddenPermissionsNotice();

        document.getElementById('roleModalTitle').innerText = 'إضافة دور';

        const form = document.getElementById('roleForm');
        form.action = "{{ route('roles.store') }}";

        document.getElementById('roleFormMethod').value = 'POST';
        document.getElementById('role_name').value = '';
        document.getElementById('role_name').removeAttribute('readonly');

        document.getElementById('permissionSearch').value = '';
        filterPermissions('');

        clearAllPermissions();

        $('#roleForm button[type="submit"]').prop('disabled', false).text('حفظ');

        const modal = new bootstrap.Modal(document.getElementById('roleModal'));
        modal.show();
    }

    function openEditRoleModal(editUrl, updateUrl) {
        hideRoleFormError();
        hideHiddenPermissionsNotice();

        fetch(editUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (! response.ok) {
                throw response;
            }

            return response.json();
        })
        .then(role => {
            document.getElementById('roleModalTitle').innerText = 'تعديل الدور';

            const form = document.getElementById('roleForm');
            form.action = updateUrl;

            document.getElementById('roleFormMethod').value = 'PUT';
            document.getElementById('role_name').value = role.name ?? '';

            /*
            |--------------------------------------------------------------------------
            | الأدوار الأساسية لا يتم تغيير اسمها
            |--------------------------------------------------------------------------
            */
            if (role.is_system_role === true) {
                document.getElementById('role_name').setAttribute('readonly', 'readonly');
            } else {
                document.getElementById('role_name').removeAttribute('readonly');
            }

            if ((role.hidden_permissions_count ?? 0) > 0) {
                showHiddenPermissionsNotice(
                    'هذا الدور يحتوي على ' + role.hidden_permissions_count + ' صلاحية لا تظهر لك لأنها خارج صلاحياتك، ولن يتم حذفها عند الحفظ.'
                );
            }

            document.getElementById('permissionSearch').value = '';
            filterPermissions('');

            clearAllPermissions();

            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                checkbox.checked = Array.isArray(role.permissions) && role.permissions.includes(checkbox.value);
            });

            updateGroupChecks();

            $('#roleForm button[type="submit"]').prop('disabled', false).text('تحديث');

            const modal = new bootstrap.Modal(document.getElementById('roleModal'));
            modal.show();
        })
        .catch(function () {
            showPageAlert('تعذر جلب بيانات الدور أو لا تملك صلاحية تعديله.', 'danger');
        });
    }

    function deleteRole(deleteUrl) {
        if (! confirm('هل أنت متأكد من حذف الدور؟')) {
            return;
        }

        $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: "{{ csrf_token() }}"
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                rolesTable.ajax.reload(null, false);
                showPageAlert(response.message ?? 'تم حذف الدور بنجاح.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    showPageAlert(xhr.responseJSON?.message ?? 'لا توجد لديك صلاحية لحذف الأدوار.', 'danger');
                    return;
                }

                if (xhr.status === 422) {
                    showPageAlert(xhr.responseJSON?.message ?? 'لا يمكن حذف هذا الدور.', 'danger');
                    return;
                }

                showPageAlert(xhr.responseJSON?.message ?? 'حدث خطأ أثناء الحذف.', 'danger');
            }
        });
    }

    function selectAllPermissions() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            const groupCol = checkbox.closest('.permission-group-col');
            const item = checkbox.closest('.permission-item');

            const groupVisible = groupCol && groupCol.style.display !== 'none';
            const itemVisible = item && item.style.display !== 'none';

            if (groupVisible && itemVisible) {
                checkbox.checked = true;
            }
        });

        updateGroupChecks();
    }

    function clearAllPermissions() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        document.querySelectorAll('.permission-group-check').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    function togglePermissionGroup(group, checked) {
        document.querySelectorAll('.permission-group-' + group).forEach(checkbox => {
            const item = checkbox.closest('.permission-item');

            if (! item || item.style.display !== 'none') {
                checkbox.checked = checked;
            }
        });

        updateGroupChecks();
    }

    function updateGroupChecks() {
        document.querySelectorAll('.permission-group-check').forEach(groupCheckbox => {
            const group = groupCheckbox.dataset.group;
            const groupPermissions = document.querySelectorAll('.permission-group-' + group);

            if (! groupPermissions.length) {
                groupCheckbox.checked = false;
                return;
            }

            const visiblePermissions = [...groupPermissions].filter(item => {
                const permissionItem = item.closest('.permission-item');
                return ! permissionItem || permissionItem.style.display !== 'none';
            });

            if (! visiblePermissions.length) {
                groupCheckbox.checked = false;
                return;
            }

            groupCheckbox.checked = visiblePermissions.every(item => item.checked);
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('permission-checkbox')) {
            updateGroupChecks();
        }
    });

    function filterPermissions(value) {
        const keyword = String(value || '').trim().toLowerCase();

        document.querySelectorAll('.permission-group-col').forEach(groupCol => {
            const groupText = (groupCol.dataset.search || '').toLowerCase();
            let hasVisiblePermission = false;

            groupCol.querySelectorAll('.permission-item').forEach(item => {
                const itemText = (item.dataset.search || '').toLowerCase();

                const visible = ! keyword || itemText.includes(keyword) || groupText.includes(keyword);

                item.style.display = visible ? '' : 'none';

                if (visible) {
                    hasVisiblePermission = true;
                }
            });

            groupCol.style.display = hasVisiblePermission || groupText.includes(keyword) ? '' : 'none';
        });

        updateGroupChecks();
    }

    function hideRoleFormError() {
        const errorBox = document.getElementById('roleFormErrorBox');

        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.innerHTML = '';
        }
    }

    function showRoleFormError(message) {
        const errorBox = document.getElementById('roleFormErrorBox');

        if (! errorBox) {
            alert(message);
            return;
        }

        errorBox.innerHTML = message;
        errorBox.classList.remove('d-none');

        errorBox.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function hideHiddenPermissionsNotice() {
        const noticeBox = document.getElementById('hiddenPermissionsNotice');

        if (noticeBox) {
            noticeBox.classList.add('d-none');
            noticeBox.innerHTML = '';
        }
    }

    function showHiddenPermissionsNotice(message) {
        const noticeBox = document.getElementById('hiddenPermissionsNotice');

        if (! noticeBox) {
            return;
        }

        noticeBox.innerHTML = message;
        noticeBox.classList.remove('d-none');
    }

    function showPageAlert(message, type = 'success') {
        $('#alert_action').html(`
            <div class="alert alert-${type} mb-4">
                ${message}
            </div>
        `);

        setTimeout(function () {
            $('#alert_action').html('');
        }, 3500);
    }
</script>
@endpush

</x-app-layout>