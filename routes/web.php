<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountSettingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCsvController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\OpeningStockBalanceController;
use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\WarehouseTransferController;
use App\Http\Controllers\InventoryDamageController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\SupplierPaymentVoucherController;
use App\Http\Controllers\SupplierStatementController;
use App\Http\Controllers\SupplierBalanceReportController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\InventoryMovementReportController;
use App\Http\Controllers\InventoryBalanceReportController;
use App\Http\Controllers\InventoryAccountingReconciliationReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\CustomerReceiptVoucherController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\CustomerRefundVoucherController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\SalesProfitReportController;
use App\Http\Controllers\CustomerBalanceReportController;
use App\Http\Controllers\AccountLedgerReportController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\GeneralReceiptVoucherController;
use App\Http\Controllers\GeneralPaymentVoucherController;
use App\Http\Controllers\ManualJournalEntryController;
use App\Http\Controllers\TrialBalanceReportController;
use App\Http\Controllers\IncomeStatementReportController;
use App\Http\Controllers\BalanceSheetReportController;
use App\Http\Controllers\CashFlowReportController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

// POS
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosSettingController;

use App\Http\Controllers\AjaxLookupController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'active.user', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});




Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/license-expired', [LicenseController::class, 'expired'])
        ->name('license.expired');

    Route::get('/license-settings', [LicenseController::class, 'index'])
        ->name('license.index');

    Route::put('/license-settings', [LicenseController::class, 'update'])
        ->name('license.update');
});


Route::middleware(['auth'])->prefix('ajax-lookup')->name('ajax-lookup.')->group(function () {
    Route::get('/products', [AjaxLookupController::class, 'products'])->name('products');
    Route::get('/customers', [AjaxLookupController::class, 'customers'])->name('customers');
    Route::get('/suppliers', [AjaxLookupController::class, 'suppliers'])->name('suppliers');
    Route::get('/product-by-barcode', [AjaxLookupController::class, 'productByBarcode'])->name('product-by-barcode');
});

Route::middleware(['auth', 'active.user', 'branch.access', 'license.valid'])->group(function () {

    Route::prefix('workshop')->name('workshop.')->group(function () {
        Route::get('/', [WorkshopController::class, 'index'])->middleware('permission:workshop.view')->name('index');
        Route::get('/vehicles', [WorkshopController::class, 'vehicles'])->middleware('permission:workshop.view')->name('vehicles');
        Route::post('/vehicles', [WorkshopController::class, 'storeVehicle'])->middleware('permission:workshop.create')->name('vehicles.store');
        Route::get('/create', [WorkshopController::class, 'create'])->middleware('permission:workshop.create')->name('create');
        Route::post('/', [WorkshopController::class, 'store'])->middleware('permission:workshop.create')->name('store');
        Route::get('/{id}', [WorkshopController::class, 'show'])->middleware('permission:workshop.view')->whereNumber('id')->name('show');
        Route::patch('/{id}/status', [WorkshopController::class, 'updateStatus'])->middleware('permission:workshop.manage')->whereNumber('id')->name('status');
        Route::patch('/{id}/diagnosis', [WorkshopController::class, 'updateDiagnosis'])->middleware('permission:workshop.manage')->whereNumber('id')->name('diagnosis');
        Route::post('/{id}/items', [WorkshopController::class, 'addItem'])->middleware('permission:workshop.manage')->whereNumber('id')->name('items.store');
        Route::delete('/{id}/items/{item}', [WorkshopController::class, 'removeItem'])->middleware('permission:workshop.manage')->where(['id' => '[0-9]+', 'item' => '[0-9]+'])->name('items.destroy');
        Route::post('/{id}/quotation', [WorkshopController::class, 'createQuotation'])->middleware('permission:workshop.manage')->whereNumber('id')->name('quotation.store');
        Route::post('/{id}/attachments', [WorkshopController::class, 'uploadAttachment'])->middleware('permission:workshop.manage')->whereNumber('id')->name('attachments.store');
        Route::get('/{id}/attachments/{attachment}', [WorkshopController::class, 'downloadAttachment'])->middleware('permission:workshop.view')->where(['id' => '[0-9]+', 'attachment' => '[0-9]+'])->name('attachments.download');
        Route::delete('/{id}/attachments/{attachment}', [WorkshopController::class, 'deleteAttachment'])->middleware('permission:workshop.manage')->where(['id' => '[0-9]+', 'attachment' => '[0-9]+'])->name('attachments.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::get('/users/company-branches/{company}', [UserManagementController::class, 'branchesByCompany'])
    ->whereNumber('company')
    ->middleware('permission:users.view')
    ->name('users.company-branches');
    
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/fetch', [UserManagementController::class, 'fetch'])->name('users.fetch');
    });

    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');

    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->middleware('permission:users.edit')
        ->name('users.edit');

    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->middleware('permission:users.edit')
        ->name('users.update');

    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');


    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'active.user', 'super.admin'])->group(function () {

        Route::get('/roles', [RoleManagementController::class, 'index'])
            ->name('roles.index');

        Route::get('/roles/fetch', [RoleManagementController::class, 'fetch'])
            ->name('roles.fetch');

        Route::post('/roles', [RoleManagementController::class, 'store'])
            ->name('roles.store');

        Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])
            ->name('roles.edit');

        Route::put('/roles/{role}', [RoleManagementController::class, 'update'])
            ->name('roles.update');

        Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])
            ->name('roles.destroy');
    });

        
    /*
    |--------------------------------------------------------------------------
    | companies
    |--------------------------------------------------------------------------
    */
    Route::get('/companies', [CompanyController::class, 'index'])
        ->middleware('permission:companies.view')
        ->name('companies.index');

    Route::get('/companies/fetch', [CompanyController::class, 'fetch'])
        ->middleware('permission:companies.view')
        ->name('companies.fetch');

    Route::post('/companies', [CompanyController::class, 'store'])
        ->middleware('permission:companies.create')
        ->name('companies.store');

    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])
        ->whereNumber('company')
        ->middleware('permission:companies.edit')
        ->name('companies.edit');

    Route::put('/companies/{company}', [CompanyController::class, 'update'])
        ->whereNumber('company')
        ->middleware('permission:companies.edit')
        ->name('companies.update');

    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])
        ->whereNumber('company')
        ->middleware('permission:companies.delete')
        ->name('companies.destroy');

    Route::get('/company-settings', [CompanySettingsController::class, 'index'])
        ->middleware('permission:company_settings.view')
        ->name('company-settings.index');

    Route::put('/company-settings', [CompanySettingsController::class, 'update'])
        ->middleware('permission:company_settings.edit')
        ->name('company-settings.update');
        
    /*
    |--------------------------------------------------------------------------
    | Branches
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:branches.view')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/fetch', [BranchController::class, 'fetch'])->name('branches.fetch');
    });

    Route::post('/branches/store', [BranchController::class, 'store'])
        ->middleware('permission:branches.create')
        ->name('branches.store');

    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
        ->middleware('permission:branches.edit')
        ->name('branches.edit');

    Route::put('/branches/{branch}', [BranchController::class, 'update'])
        ->middleware('permission:branches.edit')
        ->name('branches.update');

    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
        ->middleware('permission:branches.delete')
        ->name('branches.destroy');


    /*
    |--------------------------------------------------------------------------
    | Warehouses
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:warehouses.view')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/fetch', [WarehouseController::class, 'fetch'])->name('warehouses.fetch');
    });

    Route::post('/warehouses/store', [WarehouseController::class, 'store'])
        ->middleware('permission:warehouses.create')
        ->name('warehouses.store');

    Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])
        ->middleware('permission:warehouses.edit')
        ->name('warehouses.edit');

    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])
        ->middleware('permission:warehouses.edit')
        ->name('warehouses.update');

    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
        ->middleware('permission:warehouses.delete')
        ->name('warehouses.destroy');


    /*
    |--------------------------------------------------------------------------
    | Accounts & Settings
    |--------------------------------------------------------------------------
    */
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('permission:accounts.view')
        ->name('accounts.index');

    Route::post('/accounts', [AccountController::class, 'store'])
        ->middleware('permission:accounts.create')
        ->name('accounts.store');
    
    Route::get('/accounts/generate-code', [AccountController::class, 'generateCode'])
        ->middleware('permission:accounts.create|accounts.edit')
        ->name('accounts.generate-code');

    Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])
        ->middleware('permission:accounts.edit')
        ->name('accounts.edit');

    Route::put('/accounts/{account}', [AccountController::class, 'update'])
        ->middleware('permission:accounts.edit')
        ->name('accounts.update');

    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])
        ->middleware('permission:accounts.delete')
        ->name('accounts.destroy');

    Route::get('/account-settings', [AccountSettingController::class, 'index'])
        ->middleware('permission:account_settings.view')
        ->name('account-settings.index');

    Route::put('/account-settings', [AccountSettingController::class, 'update'])
        ->middleware('permission:account_settings.update')
        ->name('account-settings.update');


    /*
    |--------------------------------------------------------------------------
    | Cost Centers
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:cost_centers.view')->group(function () {
        Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
        Route::get('/cost-centers/fetch', [CostCenterController::class, 'fetch'])->name('cost-centers.fetch');
    });

    Route::post('/cost-centers', [CostCenterController::class, 'store'])
        ->middleware('permission:cost_centers.create')
        ->name('cost-centers.store');

    Route::put('/cost-centers/{costCenter}', [CostCenterController::class, 'update'])
        ->middleware('permission:cost_centers.edit')
        ->name('cost-centers.update');

    Route::delete('/cost-centers/{costCenter}', [CostCenterController::class, 'destroy'])
        ->middleware('permission:cost_centers.delete')
        ->name('cost-centers.destroy');


    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:units.view')->group(function () {
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/units/fetch', [UnitController::class, 'fetch'])->name('units.fetch');
    });

    Route::post('/units/store', [UnitController::class, 'store'])
        ->middleware('permission:units.create')
        ->name('units.store');

    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])
        ->middleware('permission:units.edit')
        ->name('units.edit');

    Route::put('/units/{unit}', [UnitController::class, 'update'])
        ->middleware('permission:units.edit')
        ->name('units.update');

    Route::delete('/units/{unit}', [UnitController::class, 'destroy'])
        ->middleware('permission:units.delete')
        ->name('units.destroy');


    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('permission:categories.view')
        ->name('categories.index');

    Route::post('/categories/store', [CategoryController::class, 'store'])
        ->middleware('permission:categories.create')
        ->name('categories.store');

    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('permission:categories.edit')
        ->name('categories.edit');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:categories.edit')
        ->name('categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:categories.delete')
        ->name('categories.destroy');


    /*
    |--------------------------------------------------------------------------
    | Brands
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:brands.view')->group(function () {
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('/brands/fetch', [BrandController::class, 'fetch'])->name('brands.fetch');
    });

    Route::post('/brands/store', [BrandController::class, 'store'])
        ->middleware('permission:brands.create')
        ->name('brands.store');

    Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])
        ->middleware('permission:brands.edit')
        ->name('brands.edit');

    Route::put('/brands/{brand}', [BrandController::class, 'update'])
        ->middleware('permission:brands.edit')
        ->name('brands.update');

    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])
        ->middleware('permission:brands.delete')
        ->name('brands.destroy');


    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */
    Route::get('/products-csv', [ProductCsvController::class, 'index'])
        ->middleware('permission:products.edit')
        ->name('products.csv.index');

    Route::get('/products-csv/template', [ProductCsvController::class, 'template'])
        ->middleware('permission:products.edit')
        ->name('products.csv.template');

    Route::get('/products-csv/export', [ProductCsvController::class, 'export'])
        ->middleware('permission:products.view')
        ->name('products.csv.export');

    Route::post('/products-csv/import', [ProductCsvController::class, 'import'])
        ->middleware('permission:products.edit')
        ->name('products.csv.import');
    
    // ========================================

    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/fetch', [ProductController::class, 'fetch'])->name('products.fetch');
    });

    Route::get('/products/create', [ProductController::class, 'create'])
        ->middleware('permission:products.create')
        ->name('products.create');

    Route::post('/products/store', [ProductController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('products.store');

    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('permission:products.edit')
        ->name('products.edit');

    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products.edit')
        ->name('products.update');

    Route::delete('/product-images/{image}', [ProductController::class, 'deleteImage'])
        ->middleware('permission:products.edit')
        ->name('product-images.delete');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.delete')
        ->name('products.destroy');


    /*
    |--------------------------------------------------------------------------
    | Opening Stock & Inventory Movements
    |--------------------------------------------------------------------------
    */
    Route::get('/opening-stock/product-units/{product}', [OpeningStockBalanceController::class, 'productUnits'])
    ->middleware('permission:opening_stock.create')
    ->name('opening-stock.product-units');

    Route::middleware('permission:opening_stock.view')->group(function () {
        Route::get('/opening-stock/fetch', [OpeningStockBalanceController::class, 'fetch'])->name('opening-stock.fetch');
        Route::get('/opening-stock', [OpeningStockBalanceController::class, 'index'])->name('opening-stock.index');
    });

    Route::post('/opening-stock/store', [OpeningStockBalanceController::class, 'store'])
        ->middleware('permission:opening_stock.create')
        ->name('opening-stock.store');

    Route::get('/inventory-movements', [InventoryMovementReportController::class, 'index'])
        ->middleware('permission:inventory_movements.view')
        ->name('inventory-movements.index');

    /*
    |--------------------------------------------------------------------------
    | Inventory Balance Report
    |--------------------------------------------------------------------------
    */

    Route::get('/reports/inventory-balances', [InventoryBalanceReportController::class, 'index'])
        ->middleware('permission:inventory_balance_report.view')
        ->name('reports.inventory-balances.index');

    /*
    |--------------------------------------------------------------------------
    | Inventory Accounting Reconciliation Report
    |--------------------------------------------------------------------------
    */

    Route::get('/reports/inventory-accounting-reconciliation', [InventoryAccountingReconciliationReportController::class, 'index'])
        ->middleware('permission:inventory_accounting_reconciliation_report.view')
        ->name('reports.inventory-accounting-reconciliation.index');

        
        
    /*
    |--------------------------------------------------------------------------
    | Inventory Counts
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:inventory_counts.view')->group(function () {
        Route::get('/inventory-counts', [InventoryCountController::class, 'index'])
            ->name('inventory-counts.index');

        Route::get('/inventory-counts/{inventoryCount}', [InventoryCountController::class, 'show'])
            ->whereNumber('inventoryCount')
            ->name('inventory-counts.show');
    });

    Route::get('/inventory-counts/create', [InventoryCountController::class, 'create'])
        ->middleware('permission:inventory_counts.create')
        ->name('inventory-counts.create');

    Route::post('/inventory-counts', [InventoryCountController::class, 'store'])
        ->middleware('permission:inventory_counts.create')
        ->name('inventory-counts.store');

    Route::put('/inventory-counts/{inventoryCount}/items', [InventoryCountController::class, 'updateItems'])
        ->whereNumber('inventoryCount')
        ->middleware('permission:inventory_counts.edit')
        ->name('inventory-counts.items.update');

    Route::post('/inventory-counts/{inventoryCount}/post', [InventoryCountController::class, 'post'])
        ->whereNumber('inventoryCount')
        ->middleware('permission:inventory_counts.post')
        ->name('inventory-counts.post');

    Route::post('/inventory-counts/{inventoryCount}/cancel', [InventoryCountController::class, 'cancel'])
        ->whereNumber('inventoryCount')
        ->middleware('permission:inventory_counts.cancel')
        ->name('inventory-counts.cancel');

    Route::get('/inventory-counts/{inventoryCount}/print', [InventoryCountController::class, 'print'])
        ->whereNumber('inventoryCount')
        ->middleware('permission:inventory_counts.print')
        ->name('inventory-counts.print');

    /*
    |--------------------------------------------------------------------------
    | Inventory Damages
    |--------------------------------------------------------------------------
    */

    Route::get('/inventory-damages/product-units/{product}', [InventoryDamageController::class, 'productUnits'])
        ->whereNumber('product')
        ->middleware('permission:inventory_damages.create')
        ->name('inventory-damages.product-units');

    Route::middleware('permission:inventory_damages.view')->group(function () {
        Route::get('/inventory-damages', [InventoryDamageController::class, 'index'])
            ->name('inventory-damages.index');

        Route::get('/inventory-damages/{inventoryDamage}', [InventoryDamageController::class, 'show'])
            ->whereNumber('inventoryDamage')
            ->name('inventory-damages.show');
    });

    Route::get('/inventory-damages/create', [InventoryDamageController::class, 'create'])
        ->middleware('permission:inventory_damages.create')
        ->name('inventory-damages.create');

    Route::post('/inventory-damages', [InventoryDamageController::class, 'store'])
        ->middleware('permission:inventory_damages.create')
        ->name('inventory-damages.store');

    Route::post('/inventory-damages/{inventoryDamage}/post', [InventoryDamageController::class, 'post'])
        ->whereNumber('inventoryDamage')
        ->middleware('permission:inventory_damages.post')
        ->name('inventory-damages.post');

    Route::post('/inventory-damages/{inventoryDamage}/cancel', [InventoryDamageController::class, 'cancel'])
        ->whereNumber('inventoryDamage')
        ->middleware('permission:inventory_damages.cancel')
        ->name('inventory-damages.cancel');

    Route::get('/inventory-damages/{inventoryDamage}/print', [InventoryDamageController::class, 'print'])
        ->whereNumber('inventoryDamage')
        ->middleware('permission:inventory_damages.print')
        ->name('inventory-damages.print');


            
    /*
    |--------------------------------------------------------------------------
    | Warehouse Transfers
    |--------------------------------------------------------------------------
    */

    Route::get('/warehouse-transfers/product-units/{product}', [WarehouseTransferController::class, 'productUnits'])
        ->whereNumber('product')
        ->middleware('permission:warehouse_transfers.create')
        ->name('warehouse-transfers.product-units');

    Route::middleware('permission:warehouse_transfers.view')->group(function () {
        Route::get('/warehouse-transfers', [WarehouseTransferController::class, 'index'])
            ->name('warehouse-transfers.index');

        Route::get('/warehouse-transfers/{warehouseTransfer}', [WarehouseTransferController::class, 'show'])
            ->whereNumber('warehouseTransfer')
            ->name('warehouse-transfers.show');
    });

    Route::get('/warehouse-transfers/create', [WarehouseTransferController::class, 'create'])
        ->middleware('permission:warehouse_transfers.create')
        ->name('warehouse-transfers.create');

    Route::post('/warehouse-transfers', [WarehouseTransferController::class, 'store'])
        ->middleware('permission:warehouse_transfers.create')
        ->name('warehouse-transfers.store');

    Route::post('/warehouse-transfers/{warehouseTransfer}/post', [WarehouseTransferController::class, 'post'])
        ->whereNumber('warehouseTransfer')
        ->middleware('permission:warehouse_transfers.post')
        ->name('warehouse-transfers.post');

    Route::post('/warehouse-transfers/{warehouseTransfer}/cancel', [WarehouseTransferController::class, 'cancel'])
        ->whereNumber('warehouseTransfer')
        ->middleware('permission:warehouse_transfers.cancel')
        ->name('warehouse-transfers.cancel');

    Route::get('/warehouse-transfers/{warehouseTransfer}/print', [WarehouseTransferController::class, 'print'])
        ->whereNumber('warehouseTransfer')
        ->middleware('permission:warehouse_transfers.print')
        ->name('warehouse-transfers.print');

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers/fetch', [CustomerController::class, 'fetch'])->name('customers.fetch');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    });

    Route::post('/customers/store', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');

    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.edit')
        ->name('customers.edit');

    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');

    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');


    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('/suppliers/fetch', [SupplierController::class, 'fetch'])->name('suppliers.fetch');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    });

    Route::post('/suppliers/store', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.create')
        ->name('suppliers.store');

    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('permission:suppliers.edit')
        ->name('suppliers.edit');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.edit')
        ->name('suppliers.update');

    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->name('suppliers.destroy');


    
    /*
    |--------------------------------------------------------------------------
    | quotations
    |--------------------------------------------------------------------------
    */

    Route::get('/quotations', [QuotationController::class, 'index'])
        ->middleware('permission:quotations.view')
        ->name('quotations.index');

    Route::get('/quotations/fetch', [QuotationController::class, 'fetch'])
        ->middleware('permission:quotations.view')
        ->name('quotations.fetch');

    Route::get('/quotations/create', [QuotationController::class, 'create'])
        ->middleware('permission:quotations.create')
        ->name('quotations.create');

    Route::post('/quotations', [QuotationController::class, 'store'])
        ->middleware('permission:quotations.create')
        ->name('quotations.store');

    Route::get('/quotations/product-units/{product}', [QuotationController::class, 'productUnits'])
        ->whereNumber('product')
        ->middleware('permission:quotations.create')
        ->name('quotations.product-units');

    Route::get('/quotations/customer-data/{customer}', [QuotationController::class, 'customerData'])
        ->whereNumber('customer')
        ->middleware('permission:quotations.create')
        ->name('quotations.customer-data');

    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.print')
        ->name('quotations.print');

    Route::post('/quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.convert')
        ->name('quotations.convert-to-invoice');

    Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.approve')
        ->name('quotations.approve');

    Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.reject')
        ->name('quotations.reject');

    Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.edit')
        ->name('quotations.edit');

    Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.edit')
        ->name('quotations.update');
        
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.view')
        ->name('quotations.show');

    Route::post('/quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])
        ->whereNumber('quotation')
        ->middleware('permission:quotations.cancel')
        ->name('quotations.cancel');


    /*
    |--------------------------------------------------------------------------
    | Sales Invoices
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:sales_invoices.create')->group(function () {
        Route::get('/sales-invoices/create', [SalesInvoiceController::class, 'create'])
            ->name('sales-invoices.create');

        Route::post('/sales-invoices', [SalesInvoiceController::class, 'store'])
            ->name('sales-invoices.store');

        Route::get('/sales-invoices/customer-data/{customer}', [SalesInvoiceController::class, 'customerData'])
            ->whereNumber('customer')
            ->name('sales-invoices.customer-data');

        Route::get('/sales-invoices/product-units/{product}', [SalesInvoiceController::class, 'productUnits'])
            ->whereNumber('product')
            ->name('sales-invoices.product-units');
    });

    Route::middleware('permission:sales_invoices.view')->group(function () {
        Route::get('/sales-invoices/fetch', [SalesInvoiceController::class, 'fetch'])
            ->name('sales-invoices.fetch');

        Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])
            ->name('sales-invoices.index');

        Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])
            ->whereNumber('salesInvoice')
            ->name('sales-invoices.show');
    });

    Route::post('/sales-invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post'])
        ->middleware('permission:sales_invoices.post')
        ->name('sales-invoices.post');

    Route::post('/sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel'])
        ->middleware('permission:sales_invoices.cancel')
        ->name('sales-invoices.cancel');

    Route::get('/sales-invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])
        ->middleware('permission:sales_invoices.print')
        ->name('sales-invoices.print');


    /*
    |--------------------------------------------------------------------------
    | Sales Returns
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:sales_returns.view')->group(function () {
        Route::get('/sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
        Route::get('/sales-returns/fetch', [SalesReturnController::class, 'fetch'])->name('sales-returns.fetch');
        Route::get('/sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
        Route::get('/sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');
    });

    Route::middleware('permission:sales_returns.create')->group(function () {
        Route::post('/sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
        Route::get('/sales-returns/invoice-items/{salesInvoice}', [SalesReturnController::class, 'invoiceItems'])->name('sales-returns.invoice-items');
    });

    Route::post('/sales-returns/{salesReturn}/post', [SalesReturnController::class, 'post'])
        ->middleware('permission:sales_returns.post')
        ->name('sales-returns.post');

    Route::post('/sales-returns/{salesReturn}/cancel', [SalesReturnController::class, 'cancel'])
        ->middleware('permission:sales_returns.cancel')
        ->name('sales-returns.cancel');

    Route::get('/sales-returns/{salesReturn}/print', [SalesReturnController::class, 'print'])
        ->middleware('permission:sales_returns.print')
        ->name('sales-returns.print');

    require __DIR__ . '/sales-debit-notes.php';

    /*
    |--------------------------------------------------------------------------
    | Customer Receipt Vouchers
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:customer_receipt_vouchers.create')->group(function () {
        Route::get('/customer-receipt-vouchers/create', [CustomerReceiptVoucherController::class, 'create'])
            ->name('customer-receipt-vouchers.create');

        Route::post('/customer-receipt-vouchers', [CustomerReceiptVoucherController::class, 'store'])
            ->name('customer-receipt-vouchers.store');

        Route::get('/customer-receipt-vouchers/customer-invoices/{customer}', [CustomerReceiptVoucherController::class, 'customerInvoices'])
            ->whereNumber('customer')
            ->name('customer-receipt-vouchers.customer-invoices');
    });

    Route::middleware('permission:customer_receipt_vouchers.view')->group(function () {
        Route::get('/customer-receipt-vouchers/fetch', [CustomerReceiptVoucherController::class, 'fetch'])
            ->name('customer-receipt-vouchers.fetch');

        Route::get('/customer-receipt-vouchers', [CustomerReceiptVoucherController::class, 'index'])
            ->name('customer-receipt-vouchers.index');

        Route::get('/customer-receipt-vouchers/{customerReceiptVoucher}', [CustomerReceiptVoucherController::class, 'show'])
            ->whereNumber('customerReceiptVoucher')
            ->name('customer-receipt-vouchers.show');
    });

    Route::post('/customer-receipt-vouchers/{customerReceiptVoucher}/post', [CustomerReceiptVoucherController::class, 'post'])
        ->whereNumber('customerReceiptVoucher')
        ->middleware('permission:customer_receipt_vouchers.post')
        ->name('customer-receipt-vouchers.post');

    Route::post('/customer-receipt-vouchers/{customerReceiptVoucher}/cancel', [CustomerReceiptVoucherController::class, 'cancel'])
        ->whereNumber('customerReceiptVoucher')
        ->middleware('permission:customer_receipt_vouchers.cancel')
        ->name('customer-receipt-vouchers.cancel');

    Route::get('/customer-receipt-vouchers/{customerReceiptVoucher}/print', [CustomerReceiptVoucherController::class, 'print'])
        ->whereNumber('customerReceiptVoucher')
        ->middleware('permission:customer_receipt_vouchers.print')
        ->name('customer-receipt-vouchers.print');


    /*
    |--------------------------------------------------------------------------
    | Customer Refund Vouchers
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:customer_refund_vouchers.create')->group(function () {
        Route::get('/customer-refund-vouchers/create', [CustomerRefundVoucherController::class, 'create'])->name('customer-refund-vouchers.create');
        Route::post('/customer-refund-vouchers', [CustomerRefundVoucherController::class, 'store'])->name('customer-refund-vouchers.store');
        Route::get('/customer-refund-vouchers/sales-return-data/{salesReturn}', [CustomerRefundVoucherController::class, 'salesReturnData'])->name('customer-refund-vouchers.sales-return-data');
    });

    Route::middleware('permission:customer_refund_vouchers.view')->group(function () {
        Route::get('/customer-refund-vouchers/fetch', [CustomerRefundVoucherController::class, 'fetch'])->name('customer-refund-vouchers.fetch');
        Route::get('/customer-refund-vouchers', [CustomerRefundVoucherController::class, 'index'])->name('customer-refund-vouchers.index');
        Route::get('/customer-refund-vouchers/{customerRefundVoucher}', [CustomerRefundVoucherController::class, 'show'])->name('customer-refund-vouchers.show');
    });

    Route::post('/customer-refund-vouchers/{customerRefundVoucher}/post', [CustomerRefundVoucherController::class, 'post'])
        ->middleware('permission:customer_refund_vouchers.post')
        ->name('customer-refund-vouchers.post');

    Route::post('/customer-refund-vouchers/{customerRefundVoucher}/cancel', [CustomerRefundVoucherController::class, 'cancel'])
        ->middleware('permission:customer_refund_vouchers.cancel')
        ->name('customer-refund-vouchers.cancel');

    Route::get('/customer-refund-vouchers/{customerRefundVoucher}/print', [CustomerRefundVoucherController::class, 'print'])
        ->middleware('permission:customer_refund_vouchers.print')
        ->name('customer-refund-vouchers.print');


    /*
    |--------------------------------------------------------------------------
    | Purchase Invoices
    |--------------------------------------------------------------------------
    */
    Route::get('/purchase-invoices/product-units/{product}', [PurchaseInvoiceController::class, 'productUnits'])
    ->middleware('permission:purchase_invoices.create')
    ->name('purchase-invoices.product-units');
    
    Route::middleware('permission:purchase_invoices.create')->group(function () {
        Route::get('/purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
        Route::post('/purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
    });

    Route::middleware('permission:purchase_invoices.view')->group(function () {
        Route::get('/purchase-invoices/fetch', [PurchaseInvoiceController::class, 'fetch'])->name('purchase-invoices.fetch');
        Route::get('/purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
        Route::get('/purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');
    });


    Route::post('/purchase-invoices/{purchaseInvoice}/post', [PurchaseInvoiceController::class, 'post'])
        ->middleware('permission:purchase_invoices.post')
        ->name('purchase-invoices.post');

    Route::post('/purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])
        ->middleware('permission:purchase_invoices.cancel')
        ->name('purchase-invoices.cancel');

    Route::get('/purchase-invoices/{purchaseInvoice}/print', [PurchaseInvoiceController::class, 'print'])
        ->middleware('permission:purchase_invoices.print')
        ->name('purchase-invoices.print');


    /*
    |--------------------------------------------------------------------------
    | Purchase Returns
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:purchase_returns.create')->group(function () {
        Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
        Route::get('/purchase-returns/invoice-items/{purchaseInvoice}', [PurchaseReturnController::class, 'invoiceItems'])->name('purchase-returns.invoice-items');
    });

    Route::middleware('permission:purchase_returns.view')->group(function () {
        Route::get('/purchase-returns/fetch', [PurchaseReturnController::class, 'fetch'])->name('purchase-returns.fetch');
        Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
        Route::get('/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    });


    Route::post('/purchase-returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post'])
        ->middleware('permission:purchase_returns.post')
        ->name('purchase-returns.post');

    Route::post('/purchase-returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])
        ->middleware('permission:purchase_returns.cancel')
        ->name('purchase-returns.cancel');

    Route::get('/purchase-returns/{purchaseReturn}/print', [PurchaseReturnController::class, 'print'])
        ->middleware('permission:purchase_returns.print')
        ->name('purchase-returns.print');


    /*
    |--------------------------------------------------------------------------
    | Supplier Payment Vouchers
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:supplier_payment_vouchers.create')->group(function () {
        Route::get('/supplier-payment-vouchers/create', [SupplierPaymentVoucherController::class, 'create'])->name('supplier-payment-vouchers.create');
        Route::post('/supplier-payment-vouchers', [SupplierPaymentVoucherController::class, 'store'])->name('supplier-payment-vouchers.store');
        Route::get('/supplier-payment-vouchers/open-invoices/{supplier}', [SupplierPaymentVoucherController::class, 'openInvoices'])->name('supplier-payment-vouchers.open-invoices');
    });

    Route::middleware('permission:supplier_payment_vouchers.view')->group(function () {
        Route::get('/supplier-payment-vouchers/fetch', [SupplierPaymentVoucherController::class, 'fetch'])->name('supplier-payment-vouchers.fetch');
        Route::get('/supplier-payment-vouchers', [SupplierPaymentVoucherController::class, 'index'])->name('supplier-payment-vouchers.index');
        Route::get('/supplier-payment-vouchers/{supplierPaymentVoucher}', [SupplierPaymentVoucherController::class, 'show'])->name('supplier-payment-vouchers.show');
    });


    Route::post('/supplier-payment-vouchers/{supplierPaymentVoucher}/post', [SupplierPaymentVoucherController::class, 'post'])
        ->middleware('permission:supplier_payment_vouchers.post')
        ->name('supplier-payment-vouchers.post');

    Route::post('/supplier-payment-vouchers/{supplierPaymentVoucher}/cancel', [SupplierPaymentVoucherController::class, 'cancel'])
        ->middleware('permission:supplier_payment_vouchers.cancel')
        ->name('supplier-payment-vouchers.cancel');

    Route::get('/supplier-payment-vouchers/{supplierPaymentVoucher}/print', [SupplierPaymentVoucherController::class, 'print'])
        ->middleware('permission:supplier_payment_vouchers.print')
        ->name('supplier-payment-vouchers.print');


    /*
    |--------------------------------------------------------------------------
    | Opening Balances
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:opening_balances.create')->group(function () {
        Route::get('/opening-balances/create', [OpeningBalanceController::class, 'create'])->name('opening-balances.create');
        Route::post('/opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');
    });

    Route::middleware('permission:opening_balances.view')->group(function () {
        Route::get('/opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
        Route::get('/opening-balances/{openingBalance}', [OpeningBalanceController::class, 'show'])->name('opening-balances.show');
    });


    Route::post('/opening-balances/{openingBalance}/post', [OpeningBalanceController::class, 'post'])
        ->middleware('permission:opening_balances.post')
        ->name('opening-balances.post');

    Route::post('/opening-balances/{openingBalance}/cancel', [OpeningBalanceController::class, 'cancel'])
        ->middleware('permission:opening_balances.cancel')
        ->name('opening-balances.cancel');


    /*
    |--------------------------------------------------------------------------
    | General Receipt Vouchers
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:general_receipt_vouchers.create')->group(function () {
        Route::get('/general-receipt-vouchers/create', [GeneralReceiptVoucherController::class, 'create'])->name('general-receipt-vouchers.create');
        Route::post('/general-receipt-vouchers', [GeneralReceiptVoucherController::class, 'store'])->name('general-receipt-vouchers.store');
    });

    Route::get('/general-receipt-vouchers/{generalReceiptVoucher}/print', [GeneralReceiptVoucherController::class, 'print'])
        ->middleware('permission:general_receipt_vouchers.print')
        ->name('general-receipt-vouchers.print');

        
    Route::middleware('permission:general_receipt_vouchers.view')->group(function () {
        Route::get('/general-receipt-vouchers/fetch', [GeneralReceiptVoucherController::class, 'fetch'])->name('general-receipt-vouchers.fetch');
        Route::get('/general-receipt-vouchers', [GeneralReceiptVoucherController::class, 'index'])->name('general-receipt-vouchers.index');
        Route::get('/general-receipt-vouchers/{generalReceiptVoucher}', [GeneralReceiptVoucherController::class, 'show'])->name('general-receipt-vouchers.show');
    });

    Route::post('/general-receipt-vouchers/{generalReceiptVoucher}/post', [GeneralReceiptVoucherController::class, 'post'])
        ->middleware('permission:general_receipt_vouchers.post')
        ->name('general-receipt-vouchers.post');

    Route::post('/general-receipt-vouchers/{generalReceiptVoucher}/cancel', [GeneralReceiptVoucherController::class, 'cancel'])
        ->middleware('permission:general_receipt_vouchers.cancel')
        ->name('general-receipt-vouchers.cancel');


    /*
    |--------------------------------------------------------------------------
    | General Payment Vouchers
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:general_payment_vouchers.create')->group(function () {
        Route::get('/general-payment-vouchers/create', [GeneralPaymentVoucherController::class, 'create'])->name('general-payment-vouchers.create');
        Route::post('/general-payment-vouchers', [GeneralPaymentVoucherController::class, 'store'])->name('general-payment-vouchers.store');
    });
    
    Route::get('/general-payment-vouchers/{generalPaymentVoucher}/print', [GeneralPaymentVoucherController::class, 'print'])
    ->middleware('permission:general_payment_vouchers.print')
    ->name('general-payment-vouchers.print');

    Route::middleware('permission:general_payment_vouchers.view')->group(function () {
        Route::get('/general-payment-vouchers/fetch', [GeneralPaymentVoucherController::class, 'fetch'])->name('general-payment-vouchers.fetch');
        Route::get('/general-payment-vouchers', [GeneralPaymentVoucherController::class, 'index'])->name('general-payment-vouchers.index');
        Route::get('/general-payment-vouchers/{generalPaymentVoucher}', [GeneralPaymentVoucherController::class, 'show'])->name('general-payment-vouchers.show');
    });


    Route::post('/general-payment-vouchers/{generalPaymentVoucher}/post', [GeneralPaymentVoucherController::class, 'post'])
        ->middleware('permission:general_payment_vouchers.post')
        ->name('general-payment-vouchers.post');

    Route::post('/general-payment-vouchers/{generalPaymentVoucher}/cancel', [GeneralPaymentVoucherController::class, 'cancel'])
        ->middleware('permission:general_payment_vouchers.cancel')
        ->name('general-payment-vouchers.cancel');


    /*
    |--------------------------------------------------------------------------
    | Manual Journal Entries
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:manual_journal_entries.create')->group(function () {
        Route::get('/manual-journal-entries/create', [ManualJournalEntryController::class, 'create'])->name('manual-journal-entries.create');
        Route::post('/manual-journal-entries', [ManualJournalEntryController::class, 'store'])->name('manual-journal-entries.store');
    });
    Route::get('/manual-journal-entries/{manualJournalEntry}/print', [ManualJournalEntryController::class, 'print'])
        ->middleware('permission:manual_journal_entries.print')
        ->name('manual-journal-entries.print');

    Route::middleware('permission:manual_journal_entries.view')->group(function () {
        Route::get('/manual-journal-entries', [ManualJournalEntryController::class, 'index'])->name('manual-journal-entries.index');
        Route::get('/manual-journal-entries/fetch', [ManualJournalEntryController::class, 'fetch'])->name('manual-journal-entries.fetch');
        Route::get('/manual-journal-entries/{manualJournalEntry}', [ManualJournalEntryController::class, 'show'])->name('manual-journal-entries.show');
    });


    Route::post('/manual-journal-entries/{manualJournalEntry}/post', [ManualJournalEntryController::class, 'post'])
        ->middleware('permission:manual_journal_entries.post')
        ->name('manual-journal-entries.post');

    Route::post('/manual-journal-entries/{manualJournalEntry}/cancel', [ManualJournalEntryController::class, 'cancel'])
        ->middleware('permission:manual_journal_entries.cancel')
        ->name('manual-journal-entries.cancel');


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/sales-reports', [SalesReportController::class, 'index'])
        ->middleware('permission:sales_reports.view')
        ->name('sales-reports.index');

    Route::get('/sales-profit-reports', [SalesProfitReportController::class, 'index'])
        ->middleware('permission:sales_profit_reports.view')
        ->name('sales-profit-reports.index');

    Route::get('/customer-statements', [CustomerStatementController::class, 'index'])
        ->middleware('permission:customer_statements.view')
        ->name('customer-statements.index');

    Route::get('/supplier-statements', [SupplierStatementController::class, 'index'])
        ->middleware('permission:supplier_statements.view')
        ->name('supplier-statements.index');

    Route::get('/customer-balance-reports', [CustomerBalanceReportController::class, 'index'])
        ->middleware('permission:customer_balance_reports.view')
        ->name('customer-balance-reports.index');

    Route::get('/supplier-balance-reports', [SupplierBalanceReportController::class, 'index'])
        ->middleware('permission:supplier_balance_reports.view')
        ->name('supplier-balance-reports.index');

    Route::get('/account-ledger-reports', [AccountLedgerReportController::class, 'index'])
        ->middleware('permission:account_ledger_reports.view')
        ->name('account-ledger-reports.index');

    Route::get('/trial-balance-reports', [TrialBalanceReportController::class, 'index'])
        ->middleware('permission:trial_balance_reports.view')
        ->name('trial-balance-reports.index');

    Route::get('/income-statement-reports', [IncomeStatementReportController::class, 'index'])
        ->middleware('permission:income_statement_reports.view')
        ->name('income-statement-reports.index');

    Route::get('/balance-sheet-reports', [BalanceSheetReportController::class, 'index'])
        ->middleware('permission:balance_sheet_reports.view')
        ->name('balance-sheet-reports.index');

    Route::get('/cash-flow-reports', [CashFlowReportController::class, 'index'])
        ->middleware('permission:cash_flow_reports.view')
        ->name('cash-flow-reports.index');

        Route::get('/backups', [BackupController::class, 'index'])
    ->middleware('permission:backups.view')
    ->name('backups.index');


    // ==========================================
    // backups
    // ==========================================
    Route::post('/backups/create', [BackupController::class, 'create'])
        ->middleware('permission:backups.create')
        ->name('backups.create');

    Route::get('/backups/download/{file}', [BackupController::class, 'download'])
        ->middleware('permission:backups.download')
        ->name('backups.download');

    Route::delete('/backups/{file}', [BackupController::class, 'destroy'])
        ->middleware('permission:backups.delete')
        ->name('backups.destroy');


    /*
    |--------------------------------------------------------------------------
    | POS
    |--------------------------------------------------------------------------
    */

    Route::prefix('pos')->name('pos.')->group(function () {

        Route::middleware('permission:pos.view')->group(function () {
            Route::get('/', [PosController::class, 'index'])
                ->name('index');

            Route::get('/categories', [PosController::class, 'categories'])
                ->name('categories');

            Route::get('/products', [PosController::class, 'products'])
                ->name('products');
        });

        Route::get('/current-shift/orders', [PosController::class, 'currentShiftOrders'])
                ->name('current-shift.orders');

        Route::post('/open-shift', [PosController::class, 'openShift'])
            ->middleware('permission:pos.open_shift')
            ->name('open-shift');

        Route::post('/shifts/{posShift}/close', [PosController::class, 'closeShift'])
            ->whereNumber('posShift')
            ->middleware('permission:pos.close_shift')
            ->name('close-shift');

        Route::post('/checkout', [PosController::class, 'checkout'])
            ->middleware('permission:pos.checkout')
            ->name('checkout');

        Route::get('/orders/{posOrder}/receipt', [PosController::class, 'receipt'])
            ->whereNumber('posOrder')
            ->middleware('permission:pos.print_receipt')
            ->name('receipt');

        Route::post('/orders/{posOrder}/cancel', [PosController::class, 'cancelOrder'])
            ->whereNumber('posOrder')
            ->middleware('permission:pos.cancel_order')
            ->name('orders.cancel');

        Route::middleware('permission:pos.daily_report')->group(function () {
            Route::get('/shifts', [PosController::class, 'shifts'])
                ->name('shifts');

            Route::get('/shifts/{posShift}/report', [PosController::class, 'shiftReport'])
                ->whereNumber('posShift')
                ->name('shift-report');

            Route::get('/reports/items', [PosController::class, 'itemsReport'])
                ->name('reports.items');

            Route::get('/reports/categories', [PosController::class, 'categoriesReport'])
                ->name('reports.categories');

            Route::get('/reports/payments', [PosController::class, 'paymentsReport'])
                ->name('reports.payments');
        });

        Route::middleware('permission:pos.settings')->group(function () {
            Route::get('/settings', [PosSettingController::class, 'index'])
                ->name('settings.index');

            Route::post('/settings', [PosSettingController::class, 'save'])
                ->name('settings.save');
        });

    });

});

require __DIR__.'/auth.php';
