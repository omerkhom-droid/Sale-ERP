<x-app-layout>
<div class="container-fluid py-4" dir="rtl">
    <div class="page-header-card mb-4"><div><h3 class="page-title mb-1">أمر العمل {{ $order->order_number }}</h3><p class="page-subtitle mb-0">{{ $order->vehicle?->make }} {{ $order->vehicle?->model }} — {{ $order->vehicle?->plate_number }} — {{ $order->vehicle?->customer?->customer_name }}</p></div><a href="{{ route('workshop.index') }}" class="btn btn-outline-secondary">رجوع</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm wazin-card mb-4"><div class="card-header wazin-card-header"><h5 class="mb-0">الاستقبال والفحص</h5></div><div class="card-body">
                <p><strong>الفرع:</strong> {{ $order->branch?->branch_name }} &nbsp; <strong>العداد:</strong> {{ $order->odometer ?? '—' }}</p>
                <p><strong>شكوى العميل:</strong> {{ $order->customer_complaint }}</p>
                @can('workshop.manage')
                @unless(in_array($order->status, ['delivered', 'cancelled'], true))
                <form method="POST" action="{{ route('workshop.diagnosis', $order) }}">@csrf @method('PATCH')
                    <label class="form-label">نتيجة الفحص</label><textarea name="diagnosis" rows="3" class="form-control mb-3">{{ old('diagnosis', $order->diagnosis) }}</textarea>
                    <label class="form-label">ملاحظات داخلية</label><textarea name="internal_notes" rows="2" class="form-control mb-3">{{ old('internal_notes', $order->internal_notes) }}</textarea>
                    <button class="btn btn-primary">حفظ الفحص</button>
                </form>
                @endunless
                @else
                <p><strong>نتيجة الفحص:</strong> {{ $order->diagnosis ?: '—' }}</p>
                @endcan
            </div></div>

            <div class="card shadow-sm wazin-card mb-4"><div class="card-header wazin-card-header"><h5 class="mb-0">القطع والخدمات المخططة</h5></div><div class="card-body">
                <div class="table-responsive"><table class="table table-striped text-center"><thead><tr><th>البند</th><th>الكمية</th><th>السعر</th><th>الضريبة %</th><th>الإجمالي قبل الضريبة</th><th></th></tr></thead><tbody>
                    @forelse($order->items as $item)
                        <tr><td>{{ $item->description }} <small class="text-muted">({{ $item->product?->product_type === 'service' ? 'خدمة' : 'قطعة' }})</small></td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price, 2) }}</td><td>{{ $item->vat_rate }}</td><td>{{ number_format($item->quantity * $item->unit_price, 2) }}</td><td>
                            @can('workshop.manage')@if(!in_array($order->status, ['delivered', 'cancelled'], true) && !$order->quotation_id && !$order->sales_invoice_id)<form method="POST" action="{{ route('workshop.items.destroy', [$order, $item]) }}" onsubmit="return confirm('حذف هذا البند؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف</button></form>@endif @endcan
                        </td></tr>
                    @empty<tr><td colspan="6">لم تُضف بنود بعد.</td></tr>@endforelse
                </tbody><tfoot><tr><th colspan="4">الإجمالي التقديري قبل الضريبة والخصومات</th><th>{{ number_format($order->items->sum(fn ($item) => $item->quantity * $item->unit_price), 2) }}</th><th></th></tr></tfoot></table></div>
                @can('workshop.manage')@if(!in_array($order->status, ['delivered', 'cancelled'], true) && !$order->quotation_id && !$order->sales_invoice_id)
                    <form method="POST" action="{{ route('workshop.items.store', $order) }}" class="row g-2 align-items-end">@csrf
                        <div class="col-md-4"><label class="form-label">المنتج أو الخدمة</label><select name="product_unit_id" id="workshopProductUnit" class="form-select" required><option value="">اختر</option>@foreach($products as $product)@if($product->defaultUnit)<option value="{{ $product->defaultUnit->id }}" data-price="{{ $product->defaultUnit->sale_price }}" @selected(old('product_unit_id') == $product->defaultUnit->id)>{{ $product->product_name_ar }} — {{ $product->product_type === 'service' ? 'خدمة' : 'قطعة' }}</option>@endif @endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">الكمية</label><input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="0.001" step="0.001" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label">السعر</label><input type="number" name="unit_price" id="workshopUnitPrice" value="{{ old('unit_price', 0) }}" min="0" step="0.01" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label">ضريبة %</label><input type="number" name="vat_rate" value="{{ old('vat_rate', 15) }}" min="0" max="100" step="0.01" class="form-control" required></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">إضافة</button></div>
                    </form>
                    <script>document.getElementById('workshopProductUnit')?.addEventListener('change', function () { document.getElementById('workshopUnitPrice').value = this.selectedOptions[0]?.dataset.price || '0'; });</script>
                @endif @endcan
                <small class="text-muted d-block mt-3">هذه البنود تخطيطية؛ لا تُنشئ حركة مخزون أو فاتورة عند إضافتها.</small>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm wazin-card mb-4"><div class="card-header wazin-card-header"><h5 class="mb-0">مرحلة العمل</h5></div><div class="card-body">
                <p><span class="badge bg-info text-dark">{{ \App\Models\WorkshopOrder::STATUSES[$order->status] ?? $order->status }}</span></p>
                @can('workshop.manage')@unless(in_array($order->status, ['delivered', 'cancelled'], true))
                    <form method="POST" action="{{ route('workshop.status', $order) }}">@csrf @method('PATCH')
                        <label class="form-label">المرحلة التالية</label><select name="status" class="form-select mb-2" required>@foreach(\App\Models\WorkshopOrder::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status') === $key)>{{ $label }}</option>@endforeach</select>
                        <label class="form-label">ملاحظة الانتقال</label><textarea name="note" class="form-control mb-2">{{ old('note') }}</textarea><button class="btn btn-primary">تحديث المرحلة</button>
                    </form>
                @endunless @endcan
                <hr><h6>سجل المراحل</h6><ul class="list-group">@foreach($order->events as $event)<li class="list-group-item"><strong>{{ \App\Models\WorkshopOrder::STATUSES[$event->to_status] ?? $event->to_status }}</strong> — {{ $event->created_at?->format('Y-m-d H:i') }}<br><small>{{ $event->user?->name }} {{ $event->note }}</small></li>@endforeach</ul>
            </div></div>
            <div class="card shadow-sm wazin-card mb-4"><div class="card-header wazin-card-header"><h5 class="mb-0">عرض السعر والفاتورة</h5></div><div class="card-body">
                <p>عرض السعر: @if($order->quotation)@can('quotations.view')<a href="{{ route('quotations.show', $order->quotation) }}">{{ $order->quotation->quotation_no }}</a>@else {{ $order->quotation->quotation_no }} @endcan @else لم يُربط بعد @endif</p>
                @php($linkedInvoice = $order->salesInvoice ?? $order->quotation?->convertedSalesInvoice)
                <p>الفاتورة: @if($linkedInvoice)@can('sales_invoices.view')<a href="{{ route('sales-invoices.show', $linkedInvoice) }}">{{ $linkedInvoice->invoice_no }}</a>@else {{ $linkedInvoice->invoice_no }} @endcan @else لم تُصدر بعد @endif</p>
                @if(!$order->quotation && !$linkedInvoice && !in_array($order->status, ['delivered', 'cancelled'], true))
                    @can('workshop.manage')@can('quotations.create')
                    <form method="POST" action="{{ route('workshop.quotation.store', $order) }}">@csrf
                        <label class="form-label">المستودع لعرض السعر والفاتورة</label><select name="warehouse_id" class="form-select mb-2" required><option value="">اختر المستودع</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>@endforeach</select>
                        <label class="form-label">صالح حتى</label><input type="date" name="valid_until" class="form-control mb-2" min="{{ now()->toDateString() }}">
                        <button class="btn btn-primary" @disabled($warehouses->isEmpty() || $order->items->isEmpty())>إنشاء عرض سعر من بنود الأمر</button>
                    </form>
                    @endcan @endcan
                @endif
                @if($order->quotation && !$linkedInvoice && !in_array($order->quotation->status, ['cancelled', 'rejected'], true))
                    @can('quotations.convert')<p class="text-muted mb-1">يمكن تحويل عرض السعر إلى فاتورة من صفحة العرض بعد إكمال الموافقة.</p>@endcan
                @endif
            </div></div>

            <div class="card shadow-sm wazin-card"><div class="card-header wazin-card-header"><h5 class="mb-0">مرفقات أمر العمل</h5></div><div class="card-body">
                @can('workshop.manage')
                <form method="POST" enctype="multipart/form-data" action="{{ route('workshop.attachments.store', $order) }}" class="row g-2 mb-3">@csrf
                    <div class="col-md-5"><label class="form-label">نوع المرفق</label><select name="category" class="form-select" required>@foreach(\App\Models\WorkshopAttachment::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-7"><label class="form-label">الملف (PDF أو صورة، 10 ميجابايت)</label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required></div>
                    <div class="col-12"><input name="description" class="form-control" value="{{ old('description') }}" placeholder="وصف المرفق، اختياري"></div>
                    <div class="col-12"><button class="btn btn-outline-primary">رفع الملف</button></div>
                </form>
                @endcan
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>النوع</th><th>الملف</th><th>التاريخ</th><th></th></tr></thead><tbody>
                    @forelse($order->attachments as $attachment)<tr><td>{{ \App\Models\WorkshopAttachment::CATEGORIES[$attachment->category] ?? $attachment->category }}</td><td><a href="{{ route('workshop.attachments.download', [$order, $attachment]) }}">{{ $attachment->original_name }}</a><div class="small text-muted">{{ $attachment->description }}</div></td><td>{{ $attachment->created_at?->format('Y-m-d H:i') }}</td><td>@can('workshop.manage')<form method="POST" action="{{ route('workshop.attachments.destroy', [$order, $attachment]) }}" onsubmit="return confirm('حذف المرفق؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف</button></form>@endcan</td></tr>
                    @empty<tr><td colspan="4" class="text-muted">لا توجد مرفقات.</td></tr>@endforelse
                </tbody></table></div>
            </div></div>
        </div>
    </div>
</div>
</x-app-layout>
