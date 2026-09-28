<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Warehouse;
use App\Models\WorkshopAttachment;
use App\Models\WorkshopOrder;
use App\Models\WorkshopVehicle;
use App\Services\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkshopController extends Controller
{
    private function companyId(): int
    {
        $id = auth()->user()->company_id;
        abort_unless($id, 403, 'يجب اختيار حساب شركة للوصول إلى الورشة.');
        return (int) $id;
    }

    private function branches()
    {
        return Branch::where('company_id', $this->companyId())
            ->when(auth()->user()->branch_id, fn ($q) => $q->whereKey(auth()->user()->branch_id));
    }

    private function orders()
    {
        return WorkshopOrder::where('company_id', $this->companyId())
            ->whereIn('branch_id', $this->branches()->select('id'));
    }

    private function order(int $id): WorkshopOrder
    {
        return $this->orders()->findOrFail($id);
    }

    private function customer(int $id): Customer
    {
        $q = Customer::query();
        if (Schema::hasColumn('customers', 'company_id')) {
            $q->where('company_id', $this->companyId());
        }
        return $q->findOrFail($id);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(array_keys(WorkshopOrder::STATUSES))],
            'branch_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'document' => ['nullable', Rule::in(['with_quote', 'without_quote', 'with_invoice', 'without_invoice'])],
        ]);
        $query = $this->orders()->with(['vehicle.customer', 'branch', 'quotation'])
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']);
                $q->where(fn ($query) => $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('customer_name', 'like', "%{$search}%"))));
            })
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['branch_id']), fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when(($filters['document'] ?? null) === 'with_quote', fn ($q) => $q->whereNotNull('quotation_id'))
            ->when(($filters['document'] ?? null) === 'without_quote', fn ($q) => $q->whereNull('quotation_id'))
            ->when(($filters['document'] ?? null) === 'with_invoice', fn ($q) => $q->where(fn ($query) => $query->whereNotNull('sales_invoice_id')->orWhereHas('quotation', fn ($quote) => $quote->whereNotNull('converted_sales_invoice_id'))))
            ->when(($filters['document'] ?? null) === 'without_invoice', fn ($q) => $q->whereNull('sales_invoice_id')->whereDoesntHave('quotation', fn ($quote) => $quote->whereNotNull('converted_sales_invoice_id')));
        if (!empty($filters['branch_id']) && !$this->branches()->whereKey($filters['branch_id'])->exists()) abort(403);
        $orders = $query->latest()->paginate(20)->withQueryString();
        $branches = $this->branches()->orderBy('branch_name')->get();

        return view('workshop.index', compact('orders', 'branches'));
    }

    public function create()
    {
        $branches = $this->branches()->orderBy('branch_name')->get();
        $vehicles = WorkshopVehicle::where('company_id', $this->companyId())->with('customer')->latest()->get();
        return view('workshop.create', compact('branches', 'vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('company_id', $this->companyId())],
            'vehicle_id' => ['required', 'integer', Rule::exists('workshop_vehicles', 'id')->where('company_id', $this->companyId())],
            'customer_complaint' => ['required', 'string', 'max:5000'],
            'odometer' => ['nullable', 'integer', 'min:0'],
        ]);
        abort_unless($this->branches()->whereKey($data['branch_id'])->exists(), 403);
        $order = DB::transaction(function () use ($data) {
            $order = WorkshopOrder::create($data + [
                'company_id' => $this->companyId(),
                'order_number' => 'WO-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'created_by' => auth()->id(),
            ]);
            $order->events()->create(['to_status' => 'received', 'user_id' => auth()->id()]);
            return $order;
        });
        return redirect()->route('workshop.show', $order)->with('success', 'تم فتح أمر العمل.');
    }

    public function show(int $id)
    {
        $order = $this->order($id)->load(['vehicle.customer', 'branch', 'technician', 'items.product', 'events.user', 'quotation.convertedSalesInvoice', 'salesInvoice', 'attachments.uploader']);
        $products = Product::where('is_active', true)->with('defaultUnit')->orderBy('product_name_ar')->get();
        $warehouses = Warehouse::where('branch_id', $order->branch_id)->where('is_active', true)->orderBy('warehouse_name')->get();
        return view('workshop.show', compact('order', 'products', 'warehouses'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(WorkshopOrder::STATUSES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $order = $this->orders()->lockForUpdate()->findOrFail($id);
            if (in_array($order->status, ['delivered', 'cancelled'], true) || $order->status === $data['status']) {
                throw ValidationException::withMessages(['status' => 'لا يمكن تغيير هذه الحالة.']);
            }
            if ($data['status'] === 'delivered' && $order->status !== 'ready') {
                throw ValidationException::withMessages(['status' => 'يجب أن يكون أمر العمل جاهزًا قبل التسليم.']);
            }
            $previous = $order->status;
            $order->update(['status' => $data['status'], 'delivered_at' => $data['status'] === 'delivered' ? now() : null]);
            $order->events()->create(['from_status' => $previous, 'to_status' => $data['status'], 'note' => $data['note'] ?? null, 'user_id' => auth()->id()]);
        });
        return back()->with('success', 'تم تحديث مرحلة العمل.');
    }

    public function updateDiagnosis(Request $request, int $id)
    {
        $data = $request->validate(['diagnosis' => ['nullable', 'string', 'max:5000'], 'internal_notes' => ['nullable', 'string', 'max:5000']]);
        $order = $this->order($id);
        abort_if(in_array($order->status, ['delivered', 'cancelled'], true), 422);
        $order->update($data);
        return back()->with('success', 'تم حفظ الفحص والملاحظات.');
    }

    public function addItem(Request $request, int $id)
    {
        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'vat_rate' => ['required', 'numeric', 'between:0,100'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $order = $this->orders()->lockForUpdate()->findOrFail($id);
            abort_if(in_array($order->status, ['delivered', 'cancelled'], true) || $order->quotation_id || $order->sales_invoice_id, 422);
            $unit = ProductUnit::with('product')->findOrFail($data['product_unit_id']);
            abort_unless($unit->product && $unit->product->is_active, 422);
            $order->items()->create([
                'product_id' => $unit->product_id,
                'product_unit_id' => $unit->id,
                'description' => $unit->product->product_name_ar,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'vat_rate' => $data['vat_rate'],
            ]);
        });
        return back()->with('success', 'أضيف البند إلى أمر العمل دون حركة مخزون.');
    }

    public function removeItem(int $id, int $item)
    {
        DB::transaction(function () use ($id, $item) {
            $order = $this->orders()->lockForUpdate()->findOrFail($id);
            abort_if(in_array($order->status, ['delivered', 'cancelled'], true) || $order->quotation_id || $order->sales_invoice_id, 422);
            $order->items()->findOrFail($item)->delete();
        });
        return back()->with('success', 'حُذف البند.');
    }

    public function createQuotation(Request $request, int $id, QuotationService $service)
    {
        abort_unless(auth()->user()?->can('quotations.create'), 403);
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $quotation = DB::transaction(function () use ($id, $data, $service) {
            $order = $this->orders()->lockForUpdate()->findOrFail($id);
            if ($order->quotation_id || $order->sales_invoice_id || in_array($order->status, ['delivered', 'cancelled'], true)) {
                throw ValidationException::withMessages(['quotation' => 'لا يمكن إنشاء عرض سعر جديد لهذا الأمر.']);
            }
            $warehouse = Warehouse::whereKey($data['warehouse_id'])
                ->where('branch_id', $order->branch_id)->where('is_active', true)->firstOrFail();
            $order->load(['vehicle.customer', 'items']);
            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'أضف قطعة أو خدمة قبل إنشاء عرض السعر.']);
            }

            $quotation = $service->store([
                'customer_id' => $order->vehicle->customer_id,
                'customer_type' => 'credit',
                'branch_id' => $order->branch_id,
                'warehouse_id' => $warehouse->id,
                'quotation_date' => now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => 'أمر العمل '.$order->order_number,
                'save_action' => 'draft',
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_unit_id' => $item->product_unit_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => 0,
                    'vat_rate' => $item->vat_rate,
                ])->all(),
            ]);

            $order->update(['quotation_id' => $quotation->id]);
            return $quotation;
        });

        return redirect()->route('workshop.show', $id)->with('success', 'تم إنشاء عرض السعر '.$quotation->quotation_no.' من أمر العمل.');
    }

    public function uploadAttachment(Request $request, int $id)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(WorkshopAttachment::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);
        $order = $this->order($id);
        $file = $data['file'];
        $path = $file->store('workshop/'.$order->company_id.'/'.$order->id, 'local');
        try {
            $order->attachments()->create([
                'category' => $data['category'],
                'description' => $data['description'] ?? null,
                'original_name' => mb_substr(preg_replace('/[\\\\\/\x00-\x1F\x7F]/u', '_', $file->getClientOriginalName()), 0, 180),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        return back()->with('success', 'تم حفظ المرفق.');
    }

    public function downloadAttachment(int $id, int $attachment)
    {
        $file = $this->order($id)->attachments()->findOrFail($attachment);
        abort_unless(Storage::disk('local')->exists($file->storage_path), 404);
        return Storage::disk('local')->download($file->storage_path, $file->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteAttachment(int $id, int $attachment)
    {
        $file = $this->order($id)->attachments()->findOrFail($attachment);
        $path = $file->storage_path;
        $file->delete();
        Storage::disk('local')->delete($path);
        return back()->with('success', 'حُذف المرفق.');
    }

    public function vehicles()
    {
        $vehicles = WorkshopVehicle::where('company_id', $this->companyId())->with('customer')->latest()->paginate(20);
        $customers = Customer::query();
        if (Schema::hasColumn('customers', 'company_id')) $customers->where('company_id', $this->companyId());
        $customers = $customers->where('is_active', true)->orderBy('customer_name')->get(['id', 'customer_name']);
        return view('workshop.vehicles', compact('vehicles', 'customers'));
    }

    public function storeVehicle(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'plate_number' => ['required', 'string', 'max:30'],
            'vin' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'model_year' => ['nullable', 'integer', 'between:1900,2200'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->customer((int) $data['customer_id']);
        $data['plate_number'] = mb_strtoupper(trim($data['plate_number']));
        if (WorkshopVehicle::where('company_id', $this->companyId())->where('plate_number', $data['plate_number'])->exists()) {
            throw ValidationException::withMessages(['plate_number' => 'رقم اللوحة مسجل بالفعل في هذه الشركة.']);
        }
        WorkshopVehicle::create($data + ['company_id' => $this->companyId()]);
        return redirect()->route('workshop.vehicles')->with('success', 'تم تسجيل السيارة.');
    }
}
