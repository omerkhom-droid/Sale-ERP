@php
    $product = $product ?? new \App\Models\Product();
@endphp

<div class="row">

    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">SKU</label>
        <input type="text"
               class="form-control"
               id="sku"
               name="sku"
               value="{{ old('sku', $product->sku) }}"
               required>
    </div>

    <div class="col-md-5 mb-3">
        <label class="form-label fw-bold">اسم الصنف <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control"
               name="product_name_ar"
               value="{{ old('product_name_ar', $product->product_name_ar) }}"
               required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">الاسم الإنجليزي</label>
        <input type="text"
               class="form-control"
               name="product_name_en"
               value="{{ old('product_name_en', $product->product_name_en) }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">الاسم المختصر</label>
        <input type="text"
               class="form-control"
               name="short_name"
               value="{{ old('short_name', $product->short_name) }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">التصنيف <span class="text-danger">*</span></label>
        <select class="form-select" name="category_id" required>
            <option value="">اختر التصنيف</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                    @selected(old('category_id', $product->category_id) == $category->id)>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">العلامة التجارية</label>
        <select class="form-select" name="brand_id">
            <option value="">بدون</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}"
                    @selected(old('brand_id', $product->brand_id) == $brand->id)>
                    {{ $brand->brand_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">نوع الصنف</label>
        <select class="form-select" name="product_type" required>
            <option value="inventory"
                @selected(old('product_type', $product->product_type ?? 'inventory') == 'inventory')>
                مخزني
            </option>
            <option value="service"
                @selected(old('product_type', $product->product_type) == 'service')>
                خدمة
            </option>
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">الحد الأدنى</label>
        <input type="number"
               class="form-control"
               step="0.001"
               name="minimum_quantity"
               value="{{ old('minimum_quantity', $product->minimum_quantity ?? 0) }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">كلمات مفتاحية</label>
        <input type="text"
               class="form-control"
               name="keywords"
               value="{{ old('keywords', $product->keywords) }}"
               placeholder="تويوتا, فلتر, زيت">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">الوصف</label>
        <textarea class="form-control"
                  rows="4"
                  name="description">{{ old('description', $product->description) }}</textarea>
    </div>

    <div class="col-md-3">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   id="track_inventory"
                   name="track_inventory"
                   value="1"
                   @checked(old('track_inventory', $product->exists ? $product->track_inventory : true))>

            <label class="form-check-label" for="track_inventory">
                يتابع المخزون
            </label>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   id="is_active"
                   name="is_active"
                   value="1"
                   @checked(old('is_active', $product->exists ? $product->is_active : true))>

            <label class="form-check-label" for="is_active">
                نشط
            </label>
        </div>
    </div>


</div>

<div class="row">
    <div class="col-md-4">
        <label class="form-label d-block">نقطة البيع POS</label>

        <input type="hidden" name="show_in_pos" value="0">

        <div class="form-check form-switch mt-2">
            <input class="form-check-input"
                   type="checkbox"
                   name="show_in_pos"
                   id="show_in_pos"
                   value="1"
                   @checked(old('show_in_pos', $product->show_in_pos ?? false))>

            <label class="form-check-label fw-bold" for="show_in_pos">
                إظهار الصنف في شاشة POS
            </label>
        </div>

        <small class="text-muted">
            فعّل هذا الخيار للأصناف التي تظهر في شاشة الكاشير.
        </small>
    </div>

    <div class="col-md-4">
        <label class="form-label">ترتيب الظهور في POS</label>
        <input type="number"
               name="pos_sort_order"
               class="form-control"
               min="0"
               value="{{ old('pos_sort_order', $product->pos_sort_order ?? 0) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">لون زر الصنف في POS</label>
        <input type="color"
               name="pos_button_color"
               class="form-control form-control-color w-100"
               value="{{ old('pos_button_color', $product->pos_button_color ?? '#2F6BFF') }}">
    </div>
</div>