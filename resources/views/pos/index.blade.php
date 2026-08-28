<x-pos-layout>

<div class="pos-page" dir="rtl">

    <div id="posAlert" class="pos-alert"></div>

    {{-- Header --}}
    <div class="pos-topbar">

        <div class="pos-title">
            <h3>شاشة POS</h3>

            <p>
                نقطة بيع سريعة للمطعم / الكاشير

                @if($openShift)
                    <span class="topbar-separator">|</span>
                    <span>الوردية: {{ $openShift->shift_no }}</span>

                    <span class="topbar-separator">|</span>
                    <span>{{ $openShift->branch?->branch_name ?? '-' }}</span>

                    <span class="topbar-separator">|</span>
                    <span>{{ $openShift->warehouse?->warehouse_name ?? '-' }}</span>

                    <span class="topbar-separator">|</span>
                    <span>الصندوق: {{ number_format((float) $openShift->opening_cash, 2) }}</span>
                @endif
            </p>
        </div>

        <div class="pos-actions">

            @can('pos.daily_report')
                <a href="{{ route('pos.shifts') }}" class="btn btn-light">
                    سجل الورديات
                </a>
            @endcan

            @if($openShift)
                @can('pos.daily_report')
                    <a href="{{ route('pos.shift-report', $openShift->id) }}"
                       target="_blank"
                       class="btn btn-light">
                        تقرير الوردية
                    </a>
                @endcan

                <button type="button"
                        class="btn btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#closeShiftModal">
                    إغلاق الوردية
                </button>
            @endif

            <button type="button"
                    class="btn btn-info"
                    onclick="openShiftOrdersModal()">
                طلبات الوردية
            </button>

            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                خروج
            </a>

        </div>

    </div>

    @if(! $openShift)

        {{-- Open Shift --}}
        <div class="open-shift-wrapper">

            <div class="open-shift-card">

                <div class="open-shift-header">
                    <h4 class="mb-1">فتح وردية كاشير</h4>
                    <p class="mb-0">يجب فتح وردية قبل استخدام شاشة POS.</p>
                </div>

                <form id="openShiftForm">
                    @csrf

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">الفرع <span class="text-danger">*</span></label>
                            <select name="branch_id" id="shift_branch_id" class="form-select" required>
                                <option value="">اختر الفرع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        @selected((int) old('branch_id', $defaultPosSetting?->branch_id) === (int) $branch->id)>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">المستودع <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="shift_warehouse_id" class="form-select" required>
                                <option value="">اختر المستودع</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}"
                                            data-branch-id="{{ $warehouse->branch_id }}"
                                            @selected((int) old('warehouse_id', $defaultPosSetting?->default_warehouse_id) === (int) $warehouse->id)>
                                        {{ $warehouse->warehouse_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">رصيد بداية الصندوق</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="opening_cash"
                                   class="form-control"
                                   value="0">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary px-5">
                                فتح الوردية
                            </button>
                        </div>

                    </div>
                </form>

            </div>

        </div>

    @else

        {{-- Main POS Layout --}}
        <div class="pos-main">

            {{-- Products --}}
            <main class="pos-products">

                {{-- Filters --}}
                <div class="filters-card">

                    <div class="row g-2 align-items-end">

                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <label class="form-label">بحث عن صنف</label>
                            <input type="text"
                                   id="productSearch"
                                   class="form-control"
                                   placeholder="اسم الصنف / SKU / كلمات مفتاحية">
                        </div>

                        <div class="col-xl-3 col-lg-3 col-md-6">
                            <label class="form-label">التصنيف / النوع</label>
                            <select id="categoryFilter" class="form-select">
                                <option value="">كل التصنيفات</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-2 col-md-6">
                            <label class="form-label">نوع الطلب</label>
                            <select id="orderType" class="form-select">
                                <option value="takeaway">سفري</option>
                                <option value="dine_in">محلي</option>
                                <option value="delivery">توصيل</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-3 col-md-6">
                            <label class="form-label">رقم الطاولة</label>
                            <input type="text"
                                   id="tableNo"
                                   class="form-control"
                                   placeholder="اختياري">
                        </div>

                    </div>

                </div>

                {{-- Products Panel --}}
                <section class="products-panel">

                    <div class="products-panel-header">
                        <div>
                            <h5 class="mb-1">الأصناف</h5>
                            <small>اختر الصنف لإضافته إلى الطلب</small>
                        </div>
                    </div>

                    <div class="categories-bar" id="categoriesBar">
                        <button type="button" class="category-btn active" data-category-id="">
                            الكل
                        </button>
                    </div>

                    <div class="products-grid-wrap">
                        <div class="products-grid" id="productsGrid">
                            <div class="loading-box">
                                جاري تحميل الأصناف...
                            </div>
                        </div>
                    </div>

                </section>

            </main>

            {{-- Cart --}}
            <aside class="pos-cart">

                <div class="cart-header">
                    <h5 class="mb-0">الطلب الحالي</h5>

                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="clearCart()">
                        تفريغ
                    </button>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        لا توجد أصناف في الطلب.
                    </div>
                </div>

                <div class="cart-totals">

                    <div class="total-row">
                        <span>الإجمالي قبل الضريبة</span>
                        <strong id="subtotalValue">0.00</strong>
                    </div>

                    <div class="total-row">
                        <span>الخصم</span>
                        <input type="number"
                               id="discountAmount"
                               class="form-control form-control-sm"
                               min="0"
                               step="0.01"
                               value="0">
                    </div>

                    <div class="total-row">
                        <span>ضريبة {{ number_format((float) ($currentPosSetting?->tax_rate ?? 15), 2) }}%</span>
                        <strong id="vatValue">0.00</strong>
                    </div>

                    <div class="grand-total">
                        <span>الإجمالي النهائي</span>
                        <strong id="grandTotalValue">0.00</strong>
                    </div>

                    <div class="payment-grid">

                        <div>
                            <label class="form-label">طريقة الدفع</label>
                            <select id="paymentMethod" class="form-select">
                                <option value="cash">نقدي</option>
                                <option value="card">شبكة</option>
                                <option value="bank_transfer">تحويل</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">المبلغ المدفوع</label>
                            <input type="number"
                                   id="paidAmount"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">
                        </div>

                    </div>

                    <div class="total-row mt-2">
                        <span>الباقي للعميل</span>
                        <strong id="changeValue">0.00</strong>
                    </div>

                    <button type="button"
                            id="checkoutButton"
                            class="btn btn-primary btn-lg w-100 mt-2"
                            onclick="checkout()">
                        إتمام الدفع
                    </button>

                </div>

            </aside>

        </div>

        {{-- Close Shift Modal --}}
        <div class="modal fade" id="closeShiftModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="closeShiftForm" class="modal-content">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">إغلاق الوردية</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="alert alert-info fw-bold">
                            أدخل النقد الفعلي الموجود في الصندوق.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">النقد الفعلي</label>
                            <input type="number"
                                   name="actual_cash"
                                   class="form-control"
                                   step="0.01"
                                   min="0"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            إلغاء
                        </button>

                        <button type="submit" class="btn btn-danger">
                            إغلاق الوردية
                        </button>
                    </div>

                </form>
            </div>
        </div>


        <div class="modal fade" id="shiftOrdersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content shift-orders-modal">

                    <div class="modal-header">
                        <h5 class="modal-title">طلبات الوردية الحالية</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div id="shiftOrdersAlert"></div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>الفاتورة</th>
                                        <th>الوقت</th>
                                        <th>النوع</th>
                                        <th>الدفع</th>
                                        <th>الإجمالي</th>
                                        <th>الحالة</th>
                                        <th width="180">الإجراء</th>
                                    </tr>
                                </thead>

                                <tbody id="shiftOrdersTableBody">
                                    <tr>
                                        <td colspan="8" class="py-4 text-muted fw-bold">
                                            جاري التحميل...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            إغلاق
                        </button>
                    </div>

                </div>
            </div>
        </div>

    @endif

</div>

<style>
    :root {
        --pos-navy: #071633;
        --pos-blue: #2F6BFF;
        --pos-bg: #EEF2F7;
        --pos-border: #E5E7EB;
        --pos-muted: #64748B;
        --pos-soft: #F8FAFC;
        --pos-danger: #E63B4A;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: var(--pos-bg);
    }

    .pos-page {
        width: 100%;
        height: 100vh;
        background: var(--pos-bg);
        padding: 8px;
        overflow: hidden;
    }

    .pos-alert {
        position: fixed;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: min(620px, calc(100% - 30px));
        z-index: 3000;
    }

    .pos-alert .alert {
        border-radius: 16px;
        margin: 0;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .16);
    }

    .pos-topbar {
        height: 66px;
        background: var(--pos-navy);
        color: #fff;
        border-radius: 20px;
        padding: 8px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .pos-title {
        min-width: 0;
    }

    .pos-topbar h3 {
        color: #fff;
        font-weight: 900;
        font-size: 24px;
        margin: 0;
        line-height: 1.15;
    }

    .pos-topbar p {
        color: #CFEFF3;
        font-weight: 800;
        font-size: 13px;
        margin: 4px 0 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .topbar-separator {
        color: rgba(255, 255, 255, .6);
        padding: 0 5px;
    }

    .pos-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: nowrap;
        flex-shrink: 0;
    }

    .btn {
        border-radius: 13px;
        font-weight: 900;
        min-height: 38px;
        padding: 7px 13px;
    }

    .btn-primary {
        background: var(--pos-blue) !important;
        border-color: var(--pos-blue) !important;
    }

    .btn-warning {
        background: #F59E0B !important;
        border-color: #F59E0B !important;
        color: #111827 !important;
    }

    .form-label {
        color: var(--pos-navy);
        font-weight: 900;
        margin-bottom: 4px;
        font-size: 13px;
    }

    .form-control,
    .form-select {
        border-radius: 13px;
        border: 1px solid var(--pos-border);
        min-height: 40px;
        font-weight: 800;
        box-shadow: none !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--pos-blue);
    }

    .open-shift-wrapper {
        height: calc(100vh - 82px);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .open-shift-card {
        width: min(100%, 980px);
        background: #fff;
        border: 1px solid var(--pos-border);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .10);
    }

    .open-shift-header {
        border-bottom: 1px solid #EEF2F7;
        padding-bottom: 16px;
        margin-bottom: 18px;
    }

    .open-shift-header h4 {
        color: var(--pos-navy);
        font-weight: 900;
    }

    .open-shift-header p {
        color: var(--pos-muted);
        font-weight: 800;
    }

    .pos-main {
        direction: ltr;
        height: calc(100vh - 82px);
        display: grid;
        grid-template-columns: minmax(0, 1fr) clamp(330px, 23vw, 380px);
        grid-template-areas: "products cart";
        gap: 10px;
        overflow: hidden;
    }

    .pos-products {
        direction: rtl;
        grid-area: products;
        min-width: 0;
        min-height: 0;
        height: 100%;
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        gap: 10px;
        overflow: hidden;
    }

    .pos-cart {
        direction: rtl;
        grid-area: cart;
        min-width: 0;
        min-height: 0;
        height: 100%;
        background: #fff;
        border: 1px solid var(--pos-border);
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }

    .filters-card {
        background: #fff;
        border: 1px solid var(--pos-border);
        border-radius: 18px;
        padding: 11px 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    }

    .products-panel {
        min-height: 0;
        background: #fff;
        border: 1px solid var(--pos-border);
        border-radius: 20px;
        padding: 11px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
        display: grid;
        grid-template-rows: auto auto minmax(0, 1fr);
        overflow: hidden;
    }

    .products-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-bottom: 7px;
        border-bottom: 1px solid #EEF2F7;
        margin-bottom: 8px;
    }

    .products-panel-header h5 {
        color: var(--pos-navy);
        font-weight: 900;
        font-size: 18px;
        margin: 0;
    }

    .products-panel-header small {
        color: var(--pos-muted);
        font-weight: 800;
        font-size: 12px;
    }

    .categories-bar {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
        align-content: start;
        max-height: 84px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-bottom: 5px;
        margin-bottom: 7px;
    }

    .category-btn {
        border: 1px solid var(--pos-border);
        background: #fff;
        color: var(--pos-navy);
        border-radius: 12px;
        padding: 7px 12px;
        font-weight: 900;
        white-space: nowrap;
        font-size: 13px;
        line-height: 1.2;
        min-height: 34px;
    }

    .category-btn.active {
        background: var(--pos-blue);
        color: #fff;
        border-color: var(--pos-blue);
    }

    .products-grid-wrap {
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 2px;
    }

    .products-grid {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(132px, 1fr));
        gap: 9px;
        align-content: start;
    }

    .product-card {
        height: 152px;
        border: 1px solid var(--pos-border);
        background: #fff;
        border-radius: 17px;
        padding: 7px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 5px;
        transition: all .15s ease-in-out;
        box-shadow: 0 6px 16px rgba(7, 22, 51, .04);
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-2px);
        border-color: var(--pos-blue);
        box-shadow: 0 10px 22px rgba(47, 107, 255, .12);
    }

    .product-image-wrap {
        width: 100%;
        height: 65px;
        background: #F1F5F9;
        border-radius: 13px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image-wrap.no-image::after {
        content: "لا توجد صورة";
        color: #94A3B8;
        font-weight: 900;
        font-size: 11px;
    }

    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-info {
        min-height: 33px;
        overflow: hidden;
    }

    .product-card h6 {
        color: var(--pos-navy);
        font-weight: 900;
        line-height: 1.28;
        font-size: 12.5px;
        margin: 0;
    }

    .product-card small {
        color: var(--pos-muted);
        font-weight: 800;
        font-size: 11px;
    }

    .product-card .price {
        color: var(--pos-blue);
        font-weight: 900;
        font-size: 16px;
        margin-top: auto;
        line-height: 1.1;
    }

    .loading-box,
    .empty-cart {
        border: 1px dashed #CBD5E1;
        border-radius: 16px;
        padding: 18px;
        color: var(--pos-muted);
        font-weight: 900;
        text-align: center;
        background: #fff;
        grid-column: 1 / -1;
    }

    .cart-header {
        height: 52px;
        background: var(--pos-navy);
        color: #fff;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-shrink: 0;
    }

    .cart-header h5 {
        color: #fff;
        font-weight: 900;
        font-size: 18px;
        margin: 0;
    }

    .cart-items {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 8px;
        background: #fff;
    }

    .cart-item {
        border: 1px solid var(--pos-border);
        border-radius: 12px;
        background: #F8FAFC;
        padding: 7px;
        margin-bottom: 6px;
    }

    .cart-item-main {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 7px;
        align-items: start;
        margin-bottom: 5px;
    }

    .cart-item-info {
        min-width: 0;
    }

    .cart-item-title {
        color: var(--pos-navy);
        font-weight: 900;
        font-size: 13px;
        line-height: 1.28;
        white-space: normal;
        word-break: break-word;
    }

    .cart-item-info small {
        display: block;
        color: var(--pos-muted);
        font-weight: 800;
        font-size: 11px;
        margin-top: 1px;
    }

    .cart-item-price {
        color: var(--pos-navy);
        font-weight: 900;
        font-size: 13px;
        white-space: nowrap;
    }

    .cart-item-actions {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .qty-btn,
    .remove-btn {
        width: 27px;
        height: 27px;
        border: 0;
        border-radius: 8px;
        color: #fff;
        font-weight: 900;
        line-height: 1;
    }

    .qty-btn {
        background: var(--pos-blue);
    }

    .remove-btn {
        background: var(--pos-danger);
        margin-inline-start: auto;
    }

    .cart-qty {
        min-width: 26px;
        height: 27px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid var(--pos-border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--pos-navy);
        font-weight: 900;
        font-size: 13px;
    }

    .cart-totals {
        flex-shrink: 0;
        border-top: 1px solid var(--pos-border);
        background: #fff;
        padding: 8px 10px;
    }

    .total-row,
    .grand-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
        color: var(--pos-navy);
        font-weight: 900;
        font-size: 13px;
    }

    .total-row input {
        max-width: 100px;
        height: 34px;
        text-align: center;
    }

    .grand-total {
        background: var(--pos-navy);
        color: #fff;
        border-radius: 12px;
        padding: 8px 11px;
        font-size: 16px;
    }

    .payment-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 5px;
    }

    .payment-grid .form-label {
        margin-bottom: 3px;
        font-size: 12px;
    }

    .payment-grid .form-control,
    .payment-grid .form-select {
        min-height: 36px;
    }

    #checkoutButton {
        min-height: 42px;
        font-size: 16px;
    }

    .modal-header {
        background: var(--pos-navy);
        color: #fff;
    }

    .modal-title {
        color: #fff;
        font-weight: 900;
    }


    .shift-orders-modal {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
    }

    .shift-orders-modal .modal-header {
        background: var(--pos-navy);
        color: #fff;
    }

    .shift-orders-modal .modal-title {
        color: #fff;
        font-weight: 900;
    }

    .shift-orders-modal .table thead th {
        background: var(--pos-navy);
        color: #fff;
        font-weight: 900;
        white-space: nowrap;
    }

    .shift-orders-modal .table td {
        font-weight: 800;
        vertical-align: middle;
    }

    .order-status-paid {
        background: #16A34A;
    }

    .order-status-cancelled {
        background: #DC2626;
    }

    .order-status-draft {
        background: #64748B;
    }

    @media (max-width: 1600px) {
        .pos-main {
            grid-template-columns: minmax(0, 1fr) 360px;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
        }

        .product-card {
            height: 148px;
        }

        .product-image-wrap {
            height: 62px;
        }

        .category-btn {
            padding: 7px 11px;
            font-size: 12.5px;
        }
    }

    @media (max-width: 1200px) {
        html,
        body {
            overflow: auto;
        }

        .pos-page {
            height: auto;
            min-height: 100vh;
            overflow: visible;
        }

        .pos-topbar {
            height: auto;
            flex-direction: column;
            align-items: stretch;
        }

        .pos-actions {
            width: 100%;
            flex-wrap: wrap;
        }

        .pos-actions .btn {
            flex: 1;
        }

        .pos-main {
            height: auto;
            grid-template-columns: 1fr;
            grid-template-areas:
                "products"
                "cart";
        }

        .pos-products,
        .pos-cart {
            min-height: 600px;
        }
    }

    @media (max-width: 767px) {
        .pos-page {
            padding: 8px;
        }

        .products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .filters-card .row > div {
            margin-bottom: 8px;
        }

        .pos-topbar h3 {
            font-size: 20px;
        }

        .pos-topbar p {
            font-size: 12px;
            white-space: normal;
        }
    }
</style>

<script>
    const hasOpenShift = @json((bool) $openShift);
    const openShiftId = @json($openShift?->id);
    const csrfToken = @json(csrf_token());

    const posSettings = {
        show_product_images: @json((bool) ($currentPosSetting?->show_product_images ?? true)),
        auto_print_receipt: @json((bool) ($currentPosSetting?->auto_print_receipt ?? true)),
        receipt_copies: @json(max(1, min(5, (int) ($currentPosSetting?->receipt_copies ?? 1)))),
        tax_rate: @json((float) ($currentPosSetting?->tax_rate ?? 15)),
    };

    let selectedCategoryId = '';
    let products = [];
    let cart = [];

    document.addEventListener('DOMContentLoaded', function () {
        bindOpenShiftForm();
        bindCloseShiftForm();

        if (hasOpenShift) {
            loadCategories();
            loadProducts();

            document.getElementById('productSearch')?.addEventListener('input', debounce(function () {
                loadProducts();
            }, 300));

            document.getElementById('categoryFilter')?.addEventListener('change', function () {
                selectedCategoryId = this.value || '';
                syncCategoryButtons();
                loadProducts();
            });

            document.getElementById('discountAmount')?.addEventListener('input', calculateTotals);
            document.getElementById('paidAmount')?.addEventListener('input', calculateTotals);
        }

        document.getElementById('shift_branch_id')?.addEventListener('change', filterWarehousesByBranch);
    });

    function bindOpenShiftForm() {
        const form = document.getElementById('openShiftForm');

        if (! form) {
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerText = 'جاري فتح الوردية...';

            fetch(@json(route('pos.open-shift')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            })
            .then(async response => {
                const data = await response.json();

                if (! response.ok || data.success === false) {
                    throw new Error(data.message || 'تعذر فتح الوردية.');
                }

                showAlert(data.message || 'تم فتح الوردية بنجاح.', 'success');

                setTimeout(function () {
                    window.location.reload();
                }, 650);
            })
            .catch(error => {
                showAlert(error.message, 'danger');
                submitButton.disabled = false;
                submitButton.innerText = 'فتح الوردية';
            });
        });
    }

    function bindCloseShiftForm() {
        const form = document.getElementById('closeShiftForm');

        if (! form || ! openShiftId) {
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerText = 'جاري الإغلاق...';

            const url = @json(url('/pos/shifts')) + '/' + openShiftId + '/close';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            })
            .then(async response => {
                const data = await response.json();

                if (! response.ok || data.success === false) {
                    throw new Error(data.message || 'تعذر إغلاق الوردية.');
                }

                showAlert(data.message || 'تم إغلاق الوردية بنجاح.', 'success');

                if (data.report_url) {
                    window.open(data.report_url, '_blank');
                }

                setTimeout(function () {
                    window.location.reload();
                }, 900);
            })
            .catch(error => {
                showAlert(error.message, 'danger');
                submitButton.disabled = false;
                submitButton.innerText = 'إغلاق الوردية';
            });
        });
    }

    function filterWarehousesByBranch() {
        const branchId = this.value;
        const warehouseSelect = document.getElementById('shift_warehouse_id');

        if (! warehouseSelect) {
            return;
        }

        Array.from(warehouseSelect.options).forEach(function (option) {
            if (! option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = branchId && option.dataset.branchId !== branchId;
        });

        warehouseSelect.value = '';
    }

    function loadCategories() {
        fetch(@json(route('pos.categories')), {
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(categories => {
            const buttonsContainer = document.getElementById('categoriesBar');
            const select = document.getElementById('categoryFilter');

            if (select) {
                select.innerHTML = '<option value="">كل التصنيفات</option>';
            }

            if (buttonsContainer) {
                buttonsContainer.innerHTML = `
                    <button type="button" class="category-btn active" data-category-id="">
                        الكل
                    </button>
                `;
            }

            if (! Array.isArray(categories) || categories.length === 0) {
                if (buttonsContainer) {
                    const empty = document.createElement('span');
                    empty.className = 'text-muted fw-bold px-2';
                    empty.innerText = 'لا توجد تصنيفات نشطة';
                    buttonsContainer.appendChild(empty);
                }

                return;
            }

            categories.forEach(category => {
                const categoryName = category.category_name ?? category.name ?? 'تصنيف';

                if (select) {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.innerText = categoryName;
                    select.appendChild(option);
                }

                if (buttonsContainer) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'category-btn';
                    button.dataset.categoryId = category.id;
                    button.innerText = categoryName;
                    buttonsContainer.appendChild(button);
                }
            });

            if (buttonsContainer) {
                buttonsContainer.querySelectorAll('.category-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        selectedCategoryId = this.dataset.categoryId || '';

                        if (select) {
                            select.value = selectedCategoryId;
                        }

                        syncCategoryButtons();
                        loadProducts();
                    });
                });
            }
        })
        .catch(() => {
            const buttonsContainer = document.getElementById('categoriesBar');

            if (buttonsContainer) {
                buttonsContainer.innerHTML = `
                    <button type="button" class="category-btn active" data-category-id="">
                        الكل
                    </button>
                    <span class="text-danger fw-bold px-2">تعذر تحميل التصنيفات</span>
                `;
            }
        });
    }

    function syncCategoryButtons() {
        const buttonsContainer = document.getElementById('categoriesBar');

        if (! buttonsContainer) {
            return;
        }

        buttonsContainer.querySelectorAll('.category-btn').forEach(button => {
            const buttonCategoryId = button.dataset.categoryId || '';

            if (buttonCategoryId === selectedCategoryId) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    function loadProducts() {
        const search = document.getElementById('productSearch')?.value || '';

        const params = new URLSearchParams();

        if (selectedCategoryId) {
            params.append('category_id', selectedCategoryId);
        }

        if (search.trim() !== '') {
            params.append('q', search.trim());
        }

        const url = @json(route('pos.products')) + '?' + params.toString();

        const grid = document.getElementById('productsGrid');

        if (grid) {
            grid.innerHTML = '<div class="loading-box">جاري تحميل الأصناف...</div>';
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            products = Array.isArray(data) ? data : [];
            renderProducts();
        })
        .catch(() => {
            if (grid) {
                grid.innerHTML = '<div class="loading-box text-danger">تعذر تحميل الأصناف.</div>';
            }
        });
    }

    function renderProducts() {
        const grid = document.getElementById('productsGrid');

        if (! grid) {
            return;
        }

        if (! products.length) {
            grid.innerHTML = '<div class="loading-box">لا توجد أصناف مفعلة للـ POS.</div>';
            return;
        }

        grid.innerHTML = '';

        products.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';

            if (product.color) {
                card.style.borderTop = '5px solid ' + product.color;
            }

            const imageHtml = (posSettings.show_product_images && product.image_url)
                ? `
                    <div class="product-image-wrap">
                        <img src="${escapeHtml(product.image_url)}"
                             alt="${escapeHtml(product.name || '-')}"
                             class="product-image"
                             onerror="this.style.display='none'; this.closest('.product-image-wrap').classList.add('no-image');">
                    </div>
                `
                : '';

            card.innerHTML = `
                ${imageHtml}

                <div class="product-info">
                    <h6>${escapeHtml(product.name || '-')}</h6>
                    <small>${escapeHtml(product.unit_name || '')}</small>
                </div>

                <div class="price">${formatMoney(product.price || 0)}</div>
            `;

            card.addEventListener('click', function () {
                addToCart(product);
            });

            grid.appendChild(card);
        });
    }

    function addToCart(product) {
        if (! product.unit_id) {
            showAlert('هذا الصنف لا يحتوي على وحدة افتراضية.', 'danger');
            return;
        }

        const existing = cart.find(item => Number(item.product_id) === Number(product.id));

        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                product_id: product.id,
                product_unit_id: product.unit_id,
                name: product.name,
                unit_name: product.unit_name,
                unit_price: Number(product.price || 0),
                quantity: 1,
            });
        }

        renderCart();
        calculateTotals();
    }

    function renderCart() {
        const container = document.getElementById('cartItems');

        if (! container) {
            return;
        }

        if (! cart.length) {
            container.innerHTML = '<div class="empty-cart">لا توجد أصناف في الطلب.</div>';
            return;
        }

        container.innerHTML = '';

        cart.forEach((item, index) => {
            const total = item.quantity * item.unit_price;

            const row = document.createElement('div');
            row.className = 'cart-item';

            row.innerHTML = `
                <div class="cart-item-main">

                    <div class="cart-item-info">
                        <div class="cart-item-title">${escapeHtml(item.name)}</div>
                        <small>${formatMoney(item.unit_price)} / ${escapeHtml(item.unit_name || '')}</small>
                    </div>

                    <div class="cart-item-price">
                        ${formatMoney(total)}
                    </div>

                </div>

                <div class="cart-item-actions">
                    <button type="button" class="qty-btn" onclick="increaseQty(${index})">+</button>

                    <span class="cart-qty">${item.quantity}</span>

                    <button type="button" class="qty-btn" onclick="decreaseQty(${index})">-</button>

                    <button type="button" class="remove-btn" onclick="removeCartItem(${index})">×</button>
                </div>
            `;

            container.appendChild(row);
        });
    }

    function increaseQty(index) {
        cart[index].quantity += 1;
        renderCart();
        calculateTotals();
    }

    function decreaseQty(index) {
        cart[index].quantity -= 1;

        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }

        renderCart();
        calculateTotals();
    }

    function removeCartItem(index) {
        cart.splice(index, 1);
        renderCart();
        calculateTotals();
    }

    function clearCart() {
        cart = [];

        const discountInput = document.getElementById('discountAmount');
        const paidInput = document.getElementById('paidAmount');

        if (discountInput) {
            discountInput.value = 0;
        }

        if (paidInput) {
            paidInput.value = 0;
        }

        renderCart();
        calculateTotals();
    }

    function calculateTotals() {
        const subtotal = cart.reduce((sum, item) => {
            return sum + (Number(item.quantity) * Number(item.unit_price));
        }, 0);

        let discount = Number(document.getElementById('discountAmount')?.value || 0);

        if (discount < 0) {
            discount = 0;
        }

        if (discount > subtotal) {
            discount = subtotal;
        }

        const taxableAmount = subtotal - discount;
        const taxRate = Number(posSettings.tax_rate || 15);
        const vat = taxableAmount * (taxRate / 100);
        const grandTotal = taxableAmount + vat;

        const paidAmount = Number(document.getElementById('paidAmount')?.value || 0);
        const change = Math.max(paidAmount - grandTotal, 0);

        document.getElementById('subtotalValue').innerText = formatMoney(subtotal);
        document.getElementById('vatValue').innerText = formatMoney(vat);
        document.getElementById('grandTotalValue').innerText = formatMoney(grandTotal);
        document.getElementById('changeValue').innerText = formatMoney(change);
    }

    function checkout() {
        if (! cart.length) {
            showAlert('أضف صنفًا واحدًا على الأقل.', 'danger');
            return;
        }

        calculateTotals();

        const subtotal = cart.reduce((sum, item) => {
            return sum + (Number(item.quantity) * Number(item.unit_price));
        }, 0);

        const discount = Number(document.getElementById('discountAmount')?.value || 0);
        const taxableAmount = subtotal - discount;
        const vat = taxableAmount * 0.15;
        const grandTotal = taxableAmount + vat;

        const paidAmount = Number(document.getElementById('paidAmount')?.value || 0);
        const paymentMethod = document.getElementById('paymentMethod')?.value || 'cash';

        if (discount < 0) {
            showAlert('الخصم غير صحيح.', 'danger');
            return;
        }

        if (discount > subtotal) {
            showAlert('الخصم لا يمكن أن يكون أكبر من إجمالي الطلب.', 'danger');
            return;
        }

        if (paidAmount < grandTotal) {
            showAlert('المبلغ المدفوع أقل من إجمالي الطلب.', 'danger');
            return;
        }

        const checkoutButton = document.getElementById('checkoutButton');

        if (checkoutButton) {
            checkoutButton.disabled = true;
            checkoutButton.innerText = 'جاري إتمام الدفع...';
        }

        const payload = {
            order_type: document.getElementById('orderType')?.value || 'takeaway',
            table_no: document.getElementById('tableNo')?.value || null,
            discount_amount: discount,
            payment_method: paymentMethod,
            paid_amount: paidAmount,
            items: cart.map(item => {
                return {
                    product_id: item.product_id,
                    product_unit_id: item.product_unit_id,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                };
            }),
        };

        fetch(@json(route('pos.checkout')), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(async response => {
            const data = await response.json();

            if (! response.ok || data.success === false) {
                throw new Error(data.message || 'تعذر إتمام الدفع.');
            }

            showAlert(
                'تم الدفع بنجاح. رقم الطلب: ' + data.order_no + ' - رقم الفاتورة: ' + (data.invoice_no || '-'),
                'success'
            );

            if (data.order_id && data.auto_print_receipt) {
                const copies = Number(data.receipt_copies || posSettings.receipt_copies || 1);
                const receiptUrl = @json(url('/pos/orders')) + '/' + data.order_id + '/receipt?auto=1&copies=' + copies;

                window.open(receiptUrl, '_blank');
            }

            clearCart();

            setTimeout(function () {
                loadProducts();
            }, 500);
        })
        .catch(error => {
            showAlert(error.message, 'danger');
        })
        .finally(() => {
            if (checkoutButton) {
                checkoutButton.disabled = false;
                checkoutButton.innerText = 'إتمام الدفع';
            }
        });
    }

    function showAlert(message, type = 'success') {
        const alertBox = document.getElementById('posAlert');

        if (! alertBox) {
            alert(message);
            return;
        }

        alertBox.innerHTML = `
            <div class="alert alert-${type} fw-bold">
                ${escapeHtml(message)}
            </div>
        `;

        setTimeout(function () {
            alertBox.innerHTML = '';
        }, 3500);
    }

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function debounce(callback, delay) {
        let timer;

        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => callback.apply(this, arguments), delay);
        };
    }


    function openShiftOrdersModal() {
        const modalElement = document.getElementById('shiftOrdersModal');

        if (! modalElement) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        loadCurrentShiftOrders();
    }

    function loadCurrentShiftOrders() {
        const tbody = document.getElementById('shiftOrdersTableBody');

        if (! tbody) {
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="py-4 text-muted fw-bold">
                    جاري تحميل الطلبات...
                </td>
            </tr>
        `;

        fetch(@json(route('pos.current-shift.orders')), {
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (! data.success) {
                throw new Error(data.message || 'تعذر تحميل طلبات الوردية.');
            }

            renderCurrentShiftOrders(data.orders || []);
        })
        .catch(error => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="py-4 text-danger fw-bold">
                        ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;
        });
    }

    function renderCurrentShiftOrders(orders) {
        const tbody = document.getElementById('shiftOrdersTableBody');

        if (! tbody) {
            return;
        }

        if (! orders.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="py-4 text-muted fw-bold">
                        لا توجد طلبات في هذه الوردية.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML = '';

        orders.forEach(order => {
            const paymentText = (order.payments || [])
                .map(payment => `${payment.label} - ${formatMoney(payment.amount)}`)
                .join('<br>');

            const statusClass = order.status === 'paid'
                ? 'order-status-paid'
                : (order.status === 'cancelled' ? 'order-status-cancelled' : 'order-status-draft');

            const cancelButton = order.can_cancel
                ? `
                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="cancelShiftOrder('${order.cancel_url}', '${escapeHtml(order.order_no)}')">
                        إلغاء
                    </button>
                `
                : '';

            const row = document.createElement('tr');

            if (order.status === 'cancelled') {
                row.classList.add('table-danger');
            }

            row.innerHTML = `
                <td class="fw-bold">${escapeHtml(order.order_no)}</td>
                <td>${escapeHtml(order.invoice_no || '-')}</td>
                <td>${escapeHtml(order.paid_at || '-')}</td>
                <td>${escapeHtml(order.order_type_label || '-')}</td>
                <td>${paymentText || '-'}</td>
                <td class="fw-bold">${formatMoney(order.total_amount)}</td>
                <td>
                    <span class="badge ${statusClass}">
                        ${escapeHtml(order.status_label)}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="window.open('${order.receipt_url}', '_blank')">
                            طباعة
                        </button>

                        ${cancelButton}
                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });
    }

    function cancelShiftOrder(cancelUrl, orderNo) {
        const reason = prompt('اكتب سبب إلغاء الطلب رقم ' + orderNo);

        if (! reason || reason.trim().length < 3) {
            alert('سبب الإلغاء مطلوب ولا يقل عن 3 أحرف.');
            return;
        }

        fetch(cancelUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cancel_reason: reason.trim(),
            }),
        })
        .then(async response => {
            const data = await response.json();

            if (! response.ok || data.success === false) {
                throw new Error(data.message || 'تعذر إلغاء الطلب.');
            }

            showAlert(data.message || 'تم إلغاء الطلب بنجاح.', 'success');

            loadCurrentShiftOrders();
            loadProducts();
        })
        .catch(error => {
            alert(error.message);
        });
    }
</script>

</x-pos-layout>
