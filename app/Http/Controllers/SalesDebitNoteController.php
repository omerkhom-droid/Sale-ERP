<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesDebitNoteRequest;
use App\Models\SalesDebitNote;
use App\Models\SalesInvoice;
use App\Services\SalesDebitNoteAccess;
use App\Services\SalesDebitNoteService;
use Illuminate\Http\Request;

class SalesDebitNoteController extends Controller
{
    public function __construct(private SalesDebitNoteAccess $access, private SalesDebitNoteService $service) {}

    public function index(Request $request)
    {
        $this->access->authorize('view');
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $query = $this->access->scope(SalesDebitNote::with('salesInvoice'));
        if (filled($data['q'] ?? null)) {
            $term = '%' . $data['q'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('note_no', 'like', $term)->orWhereHas('salesInvoice', fn ($i) => $i->where('invoice_no', 'like', $term)->orWhere('customer_name', 'like', $term));
            });
        }
        return view('sales-debit-notes.index', ['notes' => $query->latest('id')->paginate(20)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $this->access->authorize('create');
        $data = $request->validate(['invoice_id' => 'nullable|integer', 'q' => 'nullable|string|max:100']);
        $invoices = $this->access->scope(SalesInvoice::query())->where('status', 'posted')->whereNotNull('customer_id');
        if (filled($data['q'] ?? null)) {
            $term = '%' . $data['q'] . '%';
            $invoices->where(fn ($q) => $q->where('invoice_no', 'like', $term)->orWhere('customer_name', 'like', $term));
        }
        $invoice = null;
        $id = old('sales_invoice_id', $data['invoice_id'] ?? null);
        if ($id) {
            $invoice = $this->access->scope(SalesInvoice::with('items.product'))->where('status', 'posted')->whereNotNull('customer_id')->findOrFail($id);
        }
        return view('sales-debit-notes.create', ['invoices' => $invoices->latest('id')->paginate(15)->withQueryString(), 'invoice' => $invoice]);
    }

    public function store(StoreSalesDebitNoteRequest $request)
    {
        $note = $this->service->store($request->validated());
        return redirect()->route('sales-debit-notes.show', $note)->with('success', 'تم حفظ الإشعار المدين.');
    }

    public function show(SalesDebitNote $salesDebitNote)
    {
        $this->access->authorize('view', $salesDebitNote);
        return view('sales-debit-notes.show', ['note' => $salesDebitNote->load(['salesInvoice', 'items', 'warehouse'])]);
    }

    public function print(SalesDebitNote $salesDebitNote)
    {
        $this->access->authorize('print', $salesDebitNote);
        return view('sales-debit-notes.print', ['note' => $salesDebitNote->load(['salesInvoice', 'items', 'warehouse'])]);
    }

    public function post(SalesDebitNote $salesDebitNote)
    {
        $this->service->post($salesDebitNote);
        return back()->with('success', 'تم ترحيل الإشعار وتحديث المديونية والمخزون والقيود.');
    }

    public function cancel(Request $request, SalesDebitNote $salesDebitNote)
    {
        $data = $request->validate(['cancel_reason' => 'required|string|max:2000']);
        $this->service->cancel($salesDebitNote, $data['cancel_reason']);
        return back()->with('success', 'تم إلغاء الإشعار.');
    }
}
