<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\Branch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnController extends Controller
{
    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function actorBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        /*
            master / system_admin:
            يرى كل الفروع.
        */
        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        /*
            company_owner / company_admin:
            يرى فروع شركته فقط.
        */
        if (in_array($this->actorUserType(), ['company_owner', 'company_admin'], true)) {
            if (! $user->company_id) {
                return [];
            }

            return Branch::query()
                ->where('company_id', $user->company_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        /*
            branch_admin / user:
            يرى فرعه فقط.
        */
        if (! $user->branch_id) {
            return [];
        }

        return [(int) $user->branch_id];
    }

    private function applyPurchaseInvoiceScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            مهم:
            فواتير المشتريات تعرض حسب branch_id مباشرة.
            يعني عند إنشاء مردود مشتريات، يظهر فقط فواتير فرع المستخدم.
        */
        return $query->whereIn('branch_id', $branchIds);
    }

    private function applyPurchaseReturnScope($query)
    {
        /*
            مردود المشتريات مرتبط بفاتورة مشتريات.
            لذلك نفلتر المردود حسب فرع الفاتورة الأصلية.
        */
        return $query->whereHas('invoice', function ($invoiceQuery) {
            $this->applyPurchaseInvoiceScope($invoiceQuery);
        });
    }

    private function assertPurchaseInvoiceAllowed(?int $purchaseInvoiceId): PurchaseInvoice
    {
        abort_unless($purchaseInvoiceId, 403, 'فاتورة المشتريات غير صحيحة.');

        $query = PurchaseInvoice::query()
            ->with([
                'supplier',
                'warehouse',
                'items',
            ])
            ->whereKey($purchaseInvoiceId)
            ->where('status', 'posted');

        $this->applyPurchaseInvoiceScope($query);

        $purchaseInvoice = $query->first();

        abort_unless(
            $purchaseInvoice,
            403,
            'لا تملك صلاحية استخدام هذه الفاتورة أو أن الفاتورة غير مرحلة.'
        );

        return $purchaseInvoice;
    }

    private function assertPurchaseReturnAccess(PurchaseReturn $purchaseReturn): void
    {
        $purchaseReturn->loadMissing([
            'invoice',
            'warehouse',
        ]);

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        $returnBranchId = (int) (
            $purchaseReturn->invoice?->branch_id
            ?? $purchaseReturn->branch_id
            ?? $purchaseReturn->warehouse?->branch_id
            ?? 0
        );

        abort_unless(
            $returnBranchId && in_array($returnBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى مردود مشتريات من فرع آخر.'
        );
    }

    private function assertReturnItemsBelongToInvoice(array $data, PurchaseInvoice $purchaseInvoice): void
    {
        $items = collect($data['items'] ?? []);

        if ($items->isEmpty()) {
            return;
        }

        $invoiceItemIds = $items
            ->pluck('purchase_invoice_item_id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($invoiceItemIds->isEmpty()) {
            return;
        }

        $purchaseInvoice->loadMissing('items');

        $allowedItemIds = $purchaseInvoice->items
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($invoiceItemIds as $invoiceItemId) {
            abort_unless(
                in_array((int) $invoiceItemId, $allowedItemIds, true),
                403,
                'أحد أصناف المردود لا يتبع فاتورة المشتريات المحددة.'
            );
        }
    }

    private function prepareSecurePurchaseReturnData(array $data): array
    {
        /*
            نقبل purchase_invoice_id.
            وإذا كانت الواجهة القديمة ترسل invoice_id نحوله إلى purchase_invoice_id.
        */
        $purchaseInvoiceId = (int) (
            $data['purchase_invoice_id']
            ?? $data['invoice_id']
            ?? 0
        );

        /*
            هنا أهم حماية:
            لا يمكن إنشاء مردود إلا لفاتورة مشتريات مرحلة وداخل فرع المستخدم.
        */
        $purchaseInvoice = $this->assertPurchaseInvoiceAllowed($purchaseInvoiceId);

        $this->assertReturnItemsBelongToInvoice($data, $purchaseInvoice);

        /*
            لا نعتمد المورد أو المستودع أو الفرع من الفورم.
            نأخذهم من فاتورة المشتريات الأصلية.
        */
        $data['purchase_invoice_id'] = $purchaseInvoice->id;
        $data['supplier_id'] = $purchaseInvoice->supplier_id;
        $data['warehouse_id'] = $purchaseInvoice->warehouse_id;
        $data['branch_id'] = $purchaseInvoice->branch_id
            ?? $purchaseInvoice->warehouse?->branch_id;

        $data['created_by'] = auth()->id();

        return $data;
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('purchase_returns.view'), 403);

        return view('purchase-returns.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('purchase_returns.view'), 403);

        $user = auth()->user();

        $returns = PurchaseReturn::query()
            ->with([
                'invoice',
                'supplier',
                'warehouse',
            ]);

        $this->applyPurchaseReturnScope($returns);

        $returns->latest('id');

        return DataTables::of($returns)
            ->addIndexColumn()

            ->editColumn('return_date', function ($row) {
                return $row->return_date
                    ? Carbon::parse($row->return_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('invoice_no', function ($row) {
                return e($row->invoice?->invoice_no ?? '-');
            })

            ->addColumn('supplier_name', function ($row) {
                return e($row->supplier?->supplier_name ?? '-');
            })

            ->addColumn('warehouse_name', function ($row) {
                return e($row->warehouse?->warehouse_name ?? '-');
            })

            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحل</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغى</span>',
                    default => '<span class="badge bg-light text-dark">غير معروف</span>',
                };
            })

            ->editColumn('subtotal', function ($row) {
                return number_format((float) $row->subtotal, 2);
            })

            ->editColumn('vat_amount', function ($row) {
                return number_format((float) $row->vat_amount, 2);
            })

            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex gap-1 justify-content-center text-center flex-wrap">';

                if ($user && $user->can('purchase_returns.view')) {
                    $buttons .= '
                        <a href="' . route('purchase-returns.show', $row->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('purchase_returns.print')) {
                    $buttons .= '
                        <a href="' . route('purchase-returns.print', $row->id) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex gap-1 justify-content-center text-center flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('purchase_returns.create'), 403);

        /*
            هنا المطلوب:
            عرض فواتير المشتريات المرحلة فقط
            وضمن فرع المستخدم أو الفروع المسموحة له.
        */
        $purchaseInvoices = PurchaseInvoice::query()
            ->with([
                'supplier',
                'warehouse',
            ])
            ->where('status', 'posted');

        $this->applyPurchaseInvoiceScope($purchaseInvoices);

        $purchaseInvoices = $purchaseInvoices
            ->orderByDesc('invoice_date')
            ->get();

        return view('purchase-returns.create', compact('purchaseInvoices'));
    }

    public function invoiceItems(PurchaseInvoice $purchaseInvoice)
    {
        abort_unless(auth()->user()?->can('purchase_returns.create'), 403);

        /*
            حماية Ajax:
            حتى لو المستخدم غيّر رقم الفاتورة من المتصفح،
            لا يرجع أصناف إلا لفاتورة من فرعه أو فروعه المسموحة.
        */
        $purchaseInvoice = $this->assertPurchaseInvoiceAllowed($purchaseInvoice->id);

        $purchaseInvoice->load([
            'supplier',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'items.returnItems.purchaseReturn',
        ]);

        $items = $purchaseInvoice->items
            ->map(function ($item) {
                /*
                    نحسب الكمية التي تم إرجاعها سابقًا
                    في مردودات مرحلة فقط.
                */
                $returnedQuantity = $item->returnItems
                    ->filter(function ($returnItem) {
                        return $returnItem->purchaseReturn?->status === 'posted';
                    })
                    ->sum('quantity');

                $availableQuantity = round(
                    (float) $item->quantity - (float) $returnedQuantity,
                    3
                );

                if ($availableQuantity <= 0) {
                    return null;
                }

                return [
                    'purchase_invoice_item_id' => $item->id,

                    'product_name' => $item->product?->product_name_ar
                        ?? $item->product?->product_name
                        ?? $item->product?->name
                        ?? '-',

                    'product_sku' => $item->product?->sku ?? '-',

                    'unit_name' => $item->productUnit?->unit?->unit_name_ar
                        ?? $item->productUnit?->unit?->unit_name
                        ?? $item->productUnit?->unit?->name
                        ?? '-',

                    'invoice_quantity' => (float) $item->quantity,
                    'returned_quantity' => (float) $returnedQuantity,
                    'available_quantity' => (float) $availableQuantity,

                    'unit_cost' => (float) $item->unit_cost,
                    'discount_amount' => (float) $item->discount_amount,
                    'vat_rate' => (float) $item->vat_rate,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => true,
            'invoice' => [
                'id' => $purchaseInvoice->id,
                'invoice_no' => $purchaseInvoice->invoice_no,
                'supplier_name' => $purchaseInvoice->supplier?->supplier_name ?? '-',
                'warehouse_name' => $purchaseInvoice->warehouse?->warehouse_name ?? '-',
                'invoice_date' => $purchaseInvoice->invoice_date
                    ? Carbon::parse($purchaseInvoice->invoice_date)->format('Y-m-d')
                    : '-',
            ],
            'items' => $items,
        ]);
    }

    public function store(
        StorePurchaseReturnRequest $request,
        PurchaseReturnService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_returns.create'), 403);

        $data = $this->prepareSecurePurchaseReturnData($request->validated());

        try {
            $purchaseReturn = $service->store($data);

            return response()->json([
                'status' => true,
                'message' => 'تم حفظ مردود المشتريات بنجاح',
                'purchase_return_id' => $purchaseReturn->id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()?->can('purchase_returns.view'), 403);

        $this->assertPurchaseReturnAccess($purchaseReturn);

        $purchaseReturn->load([
            'invoice',
            'supplier',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'items.invoiceItem',
        ]);

        return view('purchase-returns.show', compact('purchaseReturn'));
    }

    public function print(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()?->can('purchase_returns.print'), 403);

        $this->assertPurchaseReturnAccess($purchaseReturn);

        $purchaseReturn->load([
            'invoice',
            'supplier',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'items.invoiceItem',
        ]);

        return view('purchase-returns.print', compact('purchaseReturn'));
    }

    public function post(
        PurchaseReturn $purchaseReturn,
        PurchaseReturnService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_returns.post'), 403);

        $this->assertPurchaseReturnAccess($purchaseReturn);

        if ($purchaseReturn->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن ترحيل مردود غير مسودة.',
            ], 422);
        }

        try {
            $service->post($purchaseReturn);

            return response()->json([
                'status' => true,
                'message' => 'تم ترحيل مردود المشتريات بنجاح',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(
        Request $request,
        PurchaseReturn $purchaseReturn,
        PurchaseReturnService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_returns.cancel'), 403);

        $this->assertPurchaseReturnAccess($purchaseReturn);

        if ($purchaseReturn->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'مردود المشتريات ملغى مسبقًا.',
            ], 422);
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel(
                $purchaseReturn,
                $request->cancel_reason
            );

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء مردود المشتريات بنجاح',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}