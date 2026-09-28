<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\ProductStock;
use App\Models\SalesDebitNote;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesDebitNoteService
{
    public function __construct(
        private JournalEntryService $journal,
        private SalesDocumentBalanceService $balance,
        private SalesDebitNoteAccess $access,
    ) {}

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['debit_note' => $message]);
    }

    public function store(array $data): SalesDebitNote
    {
        return DB::transaction(function () use ($data) {
            $invoice = SalesInvoice::with('items.product')->lockForUpdate()->findOrFail($data['sales_invoice_id']);
            $this->access->authorize('create', $invoice);
            if (($data['save_action'] ?? 'draft') === 'post') { $this->access->authorize('post', $invoice); }
            $this->validateInvoice($invoice);
            // The invoice lock serializes repeat submissions for the same form.
            $existing = SalesDebitNote::where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                if ((int) $existing->sales_invoice_id !== (int) $invoice->id || (int) $existing->created_by !== (int) auth()->id()) {
                    $this->fail('رمز الطلب مستخدم. افتح نموذج إنشاء جديد.');
                }
                return $existing;
            }
            if ($data['note_date'] < $invoice->invoice_date->format('Y-m-d')) {
                $this->fail('تاريخ الإشعار يجب ألا يسبق تاريخ الفاتورة الأصلية.');
            }
            $sourceItems = $invoice->items->keyBy('id');
            $rows = []; $seen = [];
            foreach ($data['items'] as $row) {
                $quantity = round((float) $row['quantity'], 3);
                if ($quantity <= 0) { continue; }
                $source = $sourceItems->get((int) $row['sales_invoice_item_id']);
                if (!$source || isset($seen[$source->id])) { $this->fail('البند مكرر أو لا يخص الفاتورة الأصلية.'); }
                $seen[$source->id] = true;
                if (!$source->product || !$source->product->is_active) { $this->fail('يوجد صنف غير متاح أو غير نشط.'); }
                $kind = $row['adjustment_type'];
                if (!in_array($kind, ['quantity', 'price'], true)) { $this->fail('نوع الزيادة غير صحيح.'); }
                if ($kind === 'price' && $quantity > (float) $source->quantity) {
                    $this->fail('كمية تصحيح السعر لا تتجاوز كمية البند الأصلي.');
                }
                $unitPrice = round((float) $row['unit_price'], 2);
                if ($unitPrice <= 0) { $this->fail('أدخل سعرًا موجبًا للكمية الإضافية أو فرق السعر.'); }
                $net = round($quantity * $unitPrice, 2);
                $vat = round($net * (float) $source->vat_rate / 100, 2);
                $stock = $kind === 'quantity' && (bool) $source->product->track_inventory;
                // Use the original invoice's conversion ratio, not today's editable unit factor.
                $factor = (float) $source->quantity > 0 ? (float) $source->base_quantity / (float) $source->quantity : 0;
                if ($stock && $factor <= 0) { $this->fail('معامل تحويل الوحدة في الفاتورة الأصلية غير صالح.'); }
                $base = $stock ? round($quantity * $factor, 3) : 0;
                if ($stock && ($base <= 0 || $base > 999999999.999)) { $this->fail('الكمية الأساسية خارج النطاق المسموح.'); }
                $rows[] = [
                    'sales_invoice_item_id' => $source->id, 'product_id' => $source->product_id,
                    'product_unit_id' => $source->product_unit_id, 'product_name' => $source->product_name,
                    'unit_name' => $source->unit_name, 'adjustment_type' => $kind, 'affects_stock' => $stock,
                    'quantity' => $quantity, 'base_quantity' => $base, 'unit_price' => $unitPrice,
                    'net_amount' => $net, 'vat_rate' => $source->vat_rate, 'vat_amount' => $vat,
                    'line_total' => round($net + $vat, 2),
                ];
            }
            if (!$rows) { $this->fail('أدخل كمية موجبة لبند واحد على الأقل.'); }
            $total = round(array_sum(array_column($rows, 'line_total')), 2);
            if ($total <= 0 || $total > 9999999999999.99) { $this->fail('إجمالي الإشعار خارج النطاق المسموح.'); }
            $note = SalesDebitNote::create([
                'note_no' => 'SDN-' . Str::ulid(), 'submission_token' => $data['submission_token'],
                'sales_invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id, 'cost_center_id' => $invoice->cost_center_id,
                'warehouse_id' => $invoice->warehouse_id, 'note_date' => $data['note_date'],
                'reason' => trim($data['reason']), 'status' => 'draft', 'created_by' => auth()->id(),
                'subtotal' => round(array_sum(array_column($rows, 'net_amount')), 2),
                'vat_amount' => round(array_sum(array_column($rows, 'vat_amount')), 2), 'total_amount' => $total,
            ]);
            $note->items()->createMany($rows);
            if (($data['save_action'] ?? 'draft') === 'post') { return $this->post($note); }
            return $note;
        });
    }

    public function post(SalesDebitNote $note): SalesDebitNote
    {
        return DB::transaction(function () use ($note) {
            // All note mutations lock their original invoice first.
            $invoice = SalesInvoice::lockForUpdate()->findOrFail($note->sales_invoice_id);
            $note = SalesDebitNote::with('items')->lockForUpdate()->findOrFail($note->id);
            $this->access->authorize('post', $invoice);
            $this->validateInvoice($invoice);
            if ($note->status !== 'draft') { $this->fail('يمكن ترحيل الإشعار المسودة فقط.'); }
            if (!$note->items->count()) { $this->fail('لا توجد بنود في الإشعار.'); }
            if ($note->items->contains('affects_stock', true)) {
                app(InventoryGuardService::class)->assertDateAfterLastPostedInventoryCount(
                    warehouseId: (int) $note->warehouse_id, documentDate: $note->note_date, documentName: 'إشعار مدين'
                );
            }
            foreach ($note->items->sortBy('product_id') as $item) {
                if (!$item->affects_stock) { continue; }
                $stock = ProductStock::where('warehouse_id', $note->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->first();
                if (!$stock) { $this->fail('لا يوجد رصيد مخزون للصنف: ' . $item->product_name); }
                $before = (float) $stock->quantity;
                $after = round($before - (float) $item->base_quantity, 3);
                if ($after < 0) { $this->fail('المخزون لا يكفي للصنف: ' . $item->product_name); }
                $unitCost = round((float) $stock->average_cost, 2);
                $cost = round((float) $item->base_quantity * $unitCost, 2);
                if ($unitCost < 0 || $cost < 0 || $cost > 9999999999.99) { $this->fail('تكلفة المخزون خارج النطاق المسموح.'); }
                $item->update(['unit_cost' => $unitCost, 'total_cost' => $cost]);
                $stock->update(['quantity' => $after]);
                $this->movement($note, $item, $before, $after, false);
            }
            $note->total_cost = round($note->items->sum('total_cost'), 2);
            $this->createEntries($note);
            $note->forceFill(['status' => 'posted', 'posted_at' => now(), 'posted_by' => auth()->id()])->save();
            $this->balance->refresh($invoice);
            $this->audit($note, 'post');
            return $note->fresh();
        });
    }

    public function cancel(SalesDebitNote $note, string $reason): SalesDebitNote
    {
        return DB::transaction(function () use ($note, $reason) {
            $invoice = SalesInvoice::lockForUpdate()->findOrFail($note->sales_invoice_id);
            $note = SalesDebitNote::with('items')->lockForUpdate()->findOrFail($note->id);
            $this->access->authorize('cancel', $invoice);
            if ($note->status === 'cancelled') { $this->fail('الإشعار ملغى مسبقًا.'); }
            if ($note->status === 'posted') {
                $this->balance->refresh($invoice);
                if (round((float) $invoice->remaining_amount - (float) $note->total_amount, 2) < 0) {
                    $this->fail('تعذر الإلغاء لأن جزءًا من المستحق سُدد أو سُوي. اعكس التسوية المرتبطة أولًا.');
                }
                if ($note->items->contains('affects_stock', true)) {
                    app(InventoryGuardService::class)->assertDateAfterLastPostedInventoryCount(
                        warehouseId: (int) $note->warehouse_id, documentDate: now(), documentName: 'إلغاء إشعار مدين'
                    );
                }
                foreach ($note->items->sortBy('product_id') as $item) {
                    if (!$item->affects_stock) { continue; }
                    $stock = ProductStock::where('warehouse_id', $note->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->first();
                    if (!$stock) { $this->fail('رصيد المخزون غير موجود لإلغاء الحركة.'); }
                    $before = (float) $stock->quantity;
                    $after = round($before + (float) $item->base_quantity, 3);
                    $value = $before * (float) $stock->average_cost + (float) $item->total_cost;
                    $stock->update(['quantity' => $after, 'average_cost' => $after > 0 ? round($value / $after, 2) : 0]);
                    $this->movement($note, $item, $before, $after, true);
                }
                $entries = JournalEntry::where('reference_type', SalesDebitNote::class)->where('reference_id', $note->id)->lockForUpdate()->get();
                if ($entries->isEmpty()) { $this->fail('لم يتم العثور على قيود الإشعار.'); }
                foreach ($entries as $entry) { $this->journal->reverse($entry, $reason); }
            }
            $note->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => auth()->id(), 'cancel_reason' => trim($reason)]);
            $this->balance->refresh($invoice);
            $this->audit($note, 'cancel');
            return $note->fresh();
        });
    }

    private function validateInvoice(SalesInvoice $invoice): void
    {
        if ($invoice->status !== 'posted') { $this->fail('اختر فاتورة بيع مرحلة.'); }
        if (!$invoice->customer_id) { $this->fail('زيادة المديونية تتطلب عميلًا مسجلًا في الفاتورة الأصلية.'); }
    }

    private function movement($note, $item, float $before, float $after, bool $reverse): void
    {
        InventoryTransaction::create([
            'transaction_no' => ($reverse ? 'SDNR-' : 'SDN-') . Str::ulid(),
            'product_id' => $item->product_id, 'warehouse_id' => $note->warehouse_id,
            'product_unit_id' => $item->product_unit_id, 'transaction_type' => $reverse ? 'sale_return' : 'sale',
            'quantity' => ($reverse ? 1 : -1) * (float) $item->base_quantity,
            'unit_cost' => $item->unit_cost, 'total_cost' => ($reverse ? 1 : -1) * (float) $item->total_cost,
            'balance_before' => $before, 'balance_after' => $after,
            'reference_type' => SalesDebitNote::class, 'reference_id' => $note->id,
            'notes' => ($reverse ? 'إلغاء إشعار مدين ' : 'إشعار مدين ') . $note->note_no . ' - بند ' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function createEntries(SalesDebitNote $note): void
    {
        $meta = ['customer_id' => $note->customer_id, 'branch_id' => $note->branch_id, 'cost_center_id' => $note->cost_center_id];
        $line = fn ($key, $debit, $credit) => array_merge($meta, [
            'account_id' => $this->accountId($key, $note), 'debit' => $debit, 'credit' => $credit,
            'description' => 'إشعار مدين ' . $note->note_no,
        ]);
        $lines = [$line('accounts_receivable', $note->total_amount, 0), $line('sales_account', 0, $note->subtotal)];
        if ((float) $note->vat_amount > 0) { $lines[] = $line('vat_output_account', 0, $note->vat_amount); }
        $entry = ['entry_date' => $note->note_date, 'reference_type' => SalesDebitNote::class, 'reference_id' => $note->id, 'description' => 'إشعار مدين ' . $note->note_no];
        $this->journal->create(array_merge($entry, ['lines' => $lines]));
        if ((float) $note->total_cost > 0) {
            $this->journal->create(array_merge($entry, ['description' => 'تكلفة إشعار مدين ' . $note->note_no, 'lines' => [
                $line('cost_of_goods_sold', $note->total_cost, 0), $line('inventory_account', 0, $note->total_cost),
            ]]));
        }
    }

    private function accountId(string $key, SalesDebitNote $note): int
    {
        $query = AccountSetting::query();
        $keyColumn = collect(['setting_key', 'account_key', 'key'])->first(fn ($column) => Schema::hasColumn('account_settings', $column));
        if (!$keyColumn) { $this->fail('لم يتم العثور على عمود إعداد الحسابات.'); }
        $query->where($keyColumn, $key);
        if (Schema::hasColumn('account_settings', 'company_id')) {
            $companyId = $note->branch?->company_id;
            if (!$companyId) { $this->fail('تعذر تحديد شركة الإشعار لاختيار الحسابات.'); }
            $query->where('company_id', $companyId);
        }
        if (Schema::hasColumn('account_settings', 'branch_id')) {
            $query->where(function ($q) use ($note) { $q->where('branch_id', $note->branch_id)->orWhereNull('branch_id'); })
                ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END');
        }
        $id = $query->first()?->account_id;
        if (!$id) { $this->fail('يرجى ضبط الحساب المحاسبي: ' . $key); }
        return (int) $id;
    }

    private function audit(SalesDebitNote $note, string $action): void
    {
        app(AuditLogService::class)->log(action: $action, module: 'Sales Debit Note',
            description: ($action === 'post' ? 'ترحيل ' : 'إلغاء ') . 'إشعار مدين ' . $note->note_no,
            model: $note, newValues: ['status' => $note->status, 'total_amount' => $note->total_amount], branchId: $note->branch_id);
    }
}
