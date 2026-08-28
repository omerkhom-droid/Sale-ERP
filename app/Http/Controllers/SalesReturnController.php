<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesReturnRequest;
use App\Models\Branch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesReturnController extends Controller
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
            يرى كل فروع شركته.
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

    private function applySalesInvoiceScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            فواتير البيع تعرض حسب branch_id مباشرة.
            يعني عند إنشاء مردود مبيعات، يظهر فقط فواتير فرع المستخدم.
        */
        return $query->whereIn('branch_id', $branchIds);
    }

    private function applySalesReturnScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            مردود المبيعات يتبع branch_id.
            ولو عندك سجلات قديمة branch_id فارغ، يمكن تعديلها لاحقًا من الفاتورة.
        */
        return $query->whereIn('branch_id', $branchIds);
    }

    private function assertSalesInvoiceAllowed(?int $salesInvoiceId): SalesInvoice
    {
        abort_unless($salesInvoiceId, 403, 'فاتورة البيع غير صحيحة.');

        $query = SalesInvoice::query()
            ->with([
                'customer',
                'warehouse',
                'items',
            ])
            ->whereKey($salesInvoiceId)
            ->where('status', 'posted')
            ->where(function ($query) {
                $query->whereNull('return_status')
                    ->orWhere('return_status', '!=', 'full');
            });

        $this->applySalesInvoiceScope($query);

        $salesInvoice = $query->first();

        abort_unless(
            $salesInvoice,
            403,
            'لا تملك صلاحية استخدام هذه الفاتورة أو أن الفاتورة غير مرحلة أو تم إرجاعها بالكامل.'
        );

        return $salesInvoice;
    }

    private function assertSalesReturnAccess(SalesReturn $salesReturn): void
    {
        $salesReturn->loadMissing([
            'salesInvoice',
            'warehouse',
        ]);

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        $returnBranchId = (int) (
            $salesReturn->branch_id
            ?? $salesReturn->salesInvoice?->branch_id
            ?? $salesReturn->warehouse?->branch_id
            ?? 0
        );

        abort_unless(
            $returnBranchId && in_array($returnBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى مردود مبيعات من فرع آخر.'
        );
    }

    private function assertReturnItemsBelongToInvoice(array $data, SalesInvoice $salesInvoice): void
    {
        $items = collect($data['items'] ?? []);

        if ($items->isEmpty()) {
            return;
        }

        $invoiceItemIds = $items
            ->map(function ($row) {
                return $row['sales_invoice_item_id']
                    ?? $row['invoice_item_id']
                    ?? null;
            })
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($invoiceItemIds->isEmpty()) {
            return;
        }

        $salesInvoice->loadMissing('items');

        $allowedItemIds = $salesInvoice->items
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($invoiceItemIds as $invoiceItemId) {
            abort_unless(
                in_array((int) $invoiceItemId, $allowedItemIds, true),
                403,
                'أحد أصناف المردود لا يتبع فاتورة البيع المحددة.'
            );
        }
    }

    private function prepareSecureSalesReturnData(array $data): array
    {
        /*
            نقبل sales_invoice_id.
            ولو الواجهة القديمة ترسل invoice_id نحوله إلى sales_invoice_id.
        */
        $salesInvoiceId = (int) (
            $data['sales_invoice_id']
            ?? $data['invoice_id']
            ?? 0
        );

        /*
            أهم حماية:
            لا يمكن إنشاء مردود إلا لفاتورة بيع مرحلة وداخل فرع المستخدم.
        */
        $salesInvoice = $this->assertSalesInvoiceAllowed($salesInvoiceId);

        $this->assertReturnItemsBelongToInvoice($data, $salesInvoice);

        /*
            لا نعتمد العميل أو المستودع أو الفرع من الفورم.
            نأخذهم من فاتورة البيع الأصلية.
        */
        $data['sales_invoice_id'] = $salesInvoice->id;
        $data['customer_id'] = $salesInvoice->customer_id;
        $data['warehouse_id'] = $salesInvoice->warehouse_id;
        $data['branch_id'] = $salesInvoice->branch_id
            ?? $salesInvoice->warehouse?->branch_id;

        $data['created_by'] = auth()->id();

        return $data;
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('sales_returns.view'), 403);

        return view('sales-returns.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('sales_returns.view'), 403);

        $user = auth()->user();

        $query = SalesReturn::query()
            ->with([
                'salesInvoice',
                'customer',
                'warehouse',
            ]);

        $this->applySalesReturnScope($query);

        $query->latest('id');

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('return_date', function ($return) {
                return $return->return_date
                    ? Carbon::parse($return->return_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('invoice_no', function ($return) {
                return e($return->salesInvoice?->invoice_no ?? '-');
            })

            ->addColumn('customer_name', function ($return) {
                return e(
                    $return->customer?->customer_name
                    ?? $return->customer?->name
                    ?? $return->customer?->fullname
                    ?? $return->customer_name
                    ?? 'عميل نقدي'
                );
            })

            ->addColumn('warehouse_name', function ($return) {
                return e($return->warehouse?->warehouse_name ?? '-');
            })

            ->addColumn('total_amount_display', function ($return) {
                return number_format((float) $return->total_amount, 2);
            })

            ->addColumn('total_cost_display', function ($return) {
                return number_format((float) $return->total_cost, 2);
            })

            ->addColumn('status_badge', function ($return) {
                return match ($return->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحلة</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغاة</span>',
                    default => '<span class="badge bg-light text-dark">' . e($return->status) . '</span>',
                };
            })

            ->addColumn('actions', function ($return) use ($user) {
                $buttons = '<div class="d-flex gap-1 justify-content-center text-center flex-wrap">';

                if ($user && $user->can('sales_returns.view')) {
                    $buttons .= '
                        <a href="' . route('sales-returns.show', $return->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('sales_returns.print')) {
                    $buttons .= '
                        <a href="' . route('sales-returns.print', $return->id) . '"
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
        abort_unless(auth()->user()?->can('sales_returns.create'), 403);

        /*
            المطلوب:
            عرض فواتير البيع المرحلة فقط
            وضمن فرع المستخدم أو الفروع المسموحة له
            والتي لم يتم إرجاعها بالكامل.
        */
        $salesInvoices = SalesInvoice::query()
            ->with([
                'customer',
                'warehouse',
            ])
            ->where('status', 'posted')
            ->where(function ($query) {
                $query->whereNull('return_status')
                    ->orWhere('return_status', '!=', 'full');
            });

        $this->applySalesInvoiceScope($salesInvoices);

        $salesInvoices = $salesInvoices
            ->latest('id')
            ->get();

        return view('sales-returns.create', compact('salesInvoices'));
    }

    public function store(
        StoreSalesReturnRequest $request,
        SalesReturnService $service
    ) {
        abort_unless(auth()->user()?->can('sales_returns.create'), 403);

        try {
            $data = $this->prepareSecureSalesReturnData($request->validated());

            $salesReturn = $service->store($data);

            return redirect()
                ->route('sales-returns.show', $salesReturn->id)
                ->with('success', 'تم حفظ مردود المبيعات بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(SalesReturn $salesReturn)
    {
        abort_unless(auth()->user()?->can('sales_returns.view'), 403);

        $this->assertSalesReturnAccess($salesReturn);

        $salesReturn->load([
            'salesInvoice',
            'customer',
            'branch',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'items.salesInvoiceItem',
            'creator',
            'postedBy',
            'cancelledBy',
        ]);

        return view('sales-returns.show', compact('salesReturn'));
    }

    public function post(
        SalesReturn $salesReturn,
        SalesReturnService $service
    ) {
        abort_unless(auth()->user()?->can('sales_returns.post'), 403);

        $this->assertSalesReturnAccess($salesReturn);

        if ($salesReturn->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل مردود غير مسودة.');
        }

        try {
            $service->post($salesReturn);

            return redirect()
                ->route('sales-returns.show', $salesReturn->id)
                ->with('success', 'تم ترحيل مردود المبيعات بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        SalesReturn $salesReturn,
        SalesReturnService $service
    ) {
        abort_unless(auth()->user()?->can('sales_returns.cancel'), 403);

        $this->assertSalesReturnAccess($salesReturn);

        if ($salesReturn->status === 'cancelled') {
            return back()->with('error', 'مردود المبيعات ملغى مسبقًا.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel($salesReturn, $request->cancel_reason);

            return redirect()
                ->route('sales-returns.show', $salesReturn->id)
                ->with('success', 'تم إلغاء مردود المبيعات بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function print(SalesReturn $salesReturn)
    {
        abort_unless(auth()->user()?->can('sales_returns.print'), 403);

        $this->assertSalesReturnAccess($salesReturn);

        $salesReturn->load([
            'salesInvoice',
            'customer',
            'branch',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'items.salesInvoiceItem',
        ]);

        return view('sales-returns.print', compact('salesReturn'));
    }

    public function invoiceItems(SalesInvoice $salesInvoice)
    {
        abort_unless(auth()->user()?->can('sales_returns.create'), 403);

        /*
            حماية Ajax:
            حتى لو المستخدم غيّر رقم الفاتورة من المتصفح،
            لا يرجع أصناف إلا لفاتورة من فرعه أو فروعه المسموحة.
        */
        $salesInvoice = $this->assertSalesInvoiceAllowed($salesInvoice->id);

        $items = SalesInvoiceItem::query()
            ->with([
                'product',
                'productUnit.unit',
                'returnItems.salesReturn',
            ])
            ->where('sales_invoice_id', $salesInvoice->id)
            ->get()
            ->map(function ($item) {
                /*
                    الكمية المرتجعة سابقًا من مردودات مرحلة فقط.
                */
                $previousReturnedQty = $item->returnItems
                    ->filter(function ($returnItem) {
                        return $returnItem->salesReturn
                            && $returnItem->salesReturn->status === 'posted';
                    })
                    ->sum('quantity');

                $availableQty = round(
                    (float) $item->quantity - (float) $previousReturnedQty,
                    3
                );

                if ($availableQty <= 0) {
                    return null;
                }

                return [
                    /*
                        نرجع الاثنين للتوافق مع أي JavaScript قديم:
                        id
                        sales_invoice_item_id
                    */
                    'id' => $item->id,
                    'sales_invoice_item_id' => $item->id,

                    'product_id' => $item->product_id,

                    'product_name' => $item->product_name
                        ?? $item->product?->product_name_ar
                        ?? $item->product?->product_name
                        ?? '-',

                    'product_sku' => $item->product_sku
                        ?? $item->product?->sku
                        ?? $item->product?->product_code
                        ?? null,

                    'unit_name' => $item->unit_name
                        ?? $item->productUnit?->unit?->unit_name_ar
                        ?? $item->productUnit?->unit?->unit_name
                        ?? $item->productUnit?->unit?->name
                        ?? '-',

                    'quantity' => (float) $item->quantity,
                    'previous_returned_qty' => (float) $previousReturnedQty,
                    'available_qty' => (float) $availableQty,

                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => (float) $item->discount_amount,
                    'net_amount' => (float) $item->net_amount,
                    'vat_rate' => (float) $item->vat_rate,
                    'vat_amount' => (float) $item->vat_amount,
                    'line_total' => (float) $item->line_total,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'invoice' => [
                'id' => $salesInvoice->id,
                'invoice_no' => $salesInvoice->invoice_no,
                'invoice_date' => $salesInvoice->invoice_date
                    ? Carbon::parse($salesInvoice->invoice_date)->format('Y-m-d')
                    : '-',
                'total_amount' => (float) $salesInvoice->total_amount,
                'paid_amount' => (float) $salesInvoice->paid_amount,
                'remaining_amount' => (float) $salesInvoice->remaining_amount,
                'returned_amount' => (float) $salesInvoice->returned_amount,
            ],
        ]);
    }
}