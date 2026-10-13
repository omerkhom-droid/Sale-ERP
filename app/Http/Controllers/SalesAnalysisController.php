<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Services\SalesAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class SalesAnalysisController extends Controller
{
    private function filters(Request $request, string $kind, SalesAnalysisService $service): array
    {
        abort_unless(isset(SalesAnalysisService::TITLES[$kind]), 404);
        $f = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'regex:/^(cash|[1-9][0-9]*)$/'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'seller_id' => ['nullable', 'integer', 'min:1'],
            'representative_id' => ['nullable', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'payment_type' => ['nullable', Rule::in(['cash', 'credit', 'partial'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
            'include_vat' => ['nullable', 'boolean'],
            'net_returns' => ['nullable', 'boolean'],
        ]);
        $f['kind'] = $kind;
        $f['date_from'] = $f['date_from'] ?? now()->startOfMonth()->toDateString();
        $f['date_to'] = $f['date_to'] ?? now()->toDateString();
        $f['year'] = (int) ($f['year'] ?? now()->year);
        $f['include_vat'] = (bool) ($f['include_vat'] ?? false);
        $f['net_returns'] = (bool) ($f['net_returns'] ?? true);
        if ($kind !== 'monthly' && $f['date_to'] < $f['date_from']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['date_to' => 'تاريخ النهاية يجب ألا يسبق البداية.']);
        }
        if (!empty($f['branch_id'])) {
            abort_unless($service->branches()->whereKey($f['branch_id'])->exists(), 403);
        }
        return $f;
    }

    public function index(Request $request, string $kind, SalesAnalysisService $service)
    {
        $filters = $this->filters($request, $kind, $service);
        $branches = $service->branches()->orderBy('branch_name')->get(['id', 'branch_name']);
        $people = $service->people();
        $customers = $service->invoices()->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->select('c.id', 'c.customer_name')->distinct()->orderBy('c.customer_name')->get();
        $brands = DB::table('brands')->orderBy('brand_name')->get(['id', 'brand_name']);
        $columns = $service->columns($kind);
        $title = SalesAnalysisService::TITLES[$kind];
        return view('sales-analysis.index', compact('filters', 'branches', 'people', 'customers', 'brands', 'columns', 'title', 'kind'));
    }

    public function data(Request $request, string $kind, SalesAnalysisService $service)
    {
        $filters = $this->filters($request, $kind, $service);
        $query = DB::query()->fromSub($service->report($kind, $filters), 'r')->select('r.*');
        $dt = DataTables::of($query)->addIndexColumn();
        foreach ($service->columns($kind) as $key => $label) {
            $dt->editColumn($key, fn ($row) => $service->display($key, $row->$key));
        }
        if ($kind === 'staff') {
            $dt->addColumn('actions', function ($row) {
                $html = '';
                if (auth()->user()->can('sales_invoices.view')) {
                    $html = '<a class="btn btn-sm btn-outline-primary" href="'.e(route('sales-invoices.show', $row->invoice_id)).'">الفاتورة</a> ';
                }
                if ($row->document_type === 'invoice' && auth()->user()->can('sales_analysis.assign')) {
                    $html .= '<button class="btn btn-sm btn-outline-secondary assign-staff" data-id="'.(int) $row->invoice_id.'">البائع والمندوب</button>';
                }
                return $html;
            })->rawColumns(['actions']);
        }
        return $dt->with('totals', $service->totals($filters))->toJson();
    }

    public function print(Request $request, string $kind, SalesAnalysisService $service)
    {
        $filters = $this->filters($request, $kind, $service);
        // Iterate in chunks in the view, rather than loading the complete report into memory.
        $rows = $this->ordered($service, $kind, $filters)->lazy(500);
        $columns = $service->columns($kind);
        $totals = $service->totals($filters);
        $title = SalesAnalysisService::TITLES[$kind];
        return view('sales-analysis.print', compact('rows', 'columns', 'totals', 'title', 'filters', 'kind', 'service'));
    }

    private function ordered(SalesAnalysisService $service, string $kind, array $filters)
    {
        $q = DB::query()->fromSub($service->report($kind, $filters), 'r')->select('r.*');
        return match ($kind) {
            'staff' => $q->orderBy('document_date')->orderBy('document_type')->orderBy('document_id'),
            'customers' => $q->orderBy('customer_key'),
            'brands' => $q->orderBy('brand_key'),
            'monthly' => $q->orderBy('month_no'),
        };
    }

    public function export(Request $request, string $kind, SalesAnalysisService $service)
    {
        $filters = $this->filters($request, $kind, $service);
        $query = $this->ordered($service, $kind, $filters);
        $columns = $service->columns($kind);
        $totals = $service->totals($filters);
        // Genuine Excel SpreadsheetML: explicit cell types prevent formula execution.
        return response()->streamDownload(function () use ($query, $columns, $service, $totals, $kind, $filters) {
            $xml = fn ($v) => htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $v), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cell = function ($v, $numeric = false) use ($xml) {
                echo '<Cell><Data ss:Type="'.($numeric ? 'Number' : 'String').'">'.$xml($v).'</Data></Cell>';
            };
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            $startSheet = function ($number) use ($columns, $cell) {
                echo '<Worksheet ss:Name="Report '.$number.'"><Table><Row>';
                foreach ($columns as $label) $cell($label);
                echo '</Row>';
            };
            $sheet = 1; $count = 0; $startSheet($sheet);
            foreach ($query->lazy(500) as $row) {
                if ($count === 60000) {
                    echo '</Table></Worksheet>'; $startSheet(++$sheet); $count = 0;
                }
                echo '<Row>';
                foreach ($columns as $key => $label) {
                    $numeric = in_array($key, ['net', 'vat', 'cost', 'total', 'amount', 'cash', 'credit', 'profit', 'percent'], true);
                    $cell($numeric ? round((float) $row->$key, 2) : $service->display($key, $row->$key), $numeric);
                }
                echo '</Row>'; $count++;
            }
            echo '</Table></Worksheet><Worksheet ss:Name="Summary"><Table>';
            foreach (['net' => 'صافي المبيعات دون الضريبة', 'vat' => 'الضريبة', 'total' => 'الإجمالي', 'cost' => 'التكلفة', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'] as $key => $label) {
                echo '<Row>'; $cell($label); $cell(round($totals[$key], 2), true); echo '</Row>';
            }
            echo '</Table></Worksheet><Worksheet ss:Name="Filters"><Table>';
            foreach ($filters as $key => $value) {
                echo '<Row>'; $cell($key); $cell(is_bool($value) ? ($value ? '1' : '0') : $value); echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, 'sales-'.$kind.'-'.now()->format('Ymd-His').'.xml', ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function attribution(int $invoice, SalesAnalysisService $service)
    {
        abort_unless($service->invoices()->where('i.id', $invoice)->exists(), 404);
        return response()->json(DB::table('sales_report_attributions')->where('sales_invoice_id', $invoice)->first()
            ?? ['seller_id' => null, 'representative_id' => null]);
    }

    public function assign(Request $request, int $invoice, SalesAnalysisService $service)
    {
        $data = $request->validate(['seller_id' => ['nullable', 'integer'], 'representative_id' => ['nullable', 'integer']]);
        DB::transaction(function () use ($invoice, $data, $service) {
            $header = $service->invoices()->join('branches as b', 'b.id', '=', 'i.branch_id')
                ->where('i.id', $invoice)->select('i.*', 'b.company_id')->lockForUpdate()->first();
            abort_unless($header, 404);
            foreach (['seller_id', 'representative_id'] as $key) {
                if (!empty($data[$key])) {
                    abort_unless(DB::table('users')->where('id', $data[$key])->where('company_id', $header->company_id)
                        ->whereNull('deleted_at')->where('is_active', true)
                        ->where(fn ($q) => $q->where('branch_id', $header->branch_id)->orWhereNull('branch_id'))->exists(), 422, 'البائع أو المندوب يجب أن يكون مستخدماً نشطاً من شركة وفرع الفاتورة.');
                }
            }
            $before = DB::table('sales_report_attributions')->where('sales_invoice_id', $invoice)->first();
            $after = ['seller_id' => $data['seller_id'] ?? null, 'representative_id' => $data['representative_id'] ?? null];
            if ($before && (string) $before->seller_id === (string) $after['seller_id'] && (string) $before->representative_id === (string) $after['representative_id']) return;
            $now = now();
            if ($before) {
                DB::table('sales_report_attributions')->where('id', $before->id)->update($after + ['updated_by' => auth()->id(), 'updated_at' => $now]);
            } else {
                DB::table('sales_report_attributions')->insert($after + ['sales_invoice_id' => $invoice, 'updated_by' => auth()->id(), 'created_at' => $now, 'updated_at' => $now]);
            }
            DB::table('sales_report_attribution_events')->insert([
                'sales_invoice_id' => $invoice, 'user_id' => auth()->id(), 'branch_id' => $header->branch_id,
                'old_values' => json_encode($before ? ['seller_id' => $before->seller_id, 'representative_id' => $before->representative_id] : []),
                'new_values' => json_encode($after), 'created_at' => $now,
            ]);
            if (class_exists(\App\Services\AuditLogService::class)) {
                app(\App\Services\AuditLogService::class)->log(action: 'update', module: 'Sales Analysis',
                    description: 'تعيين البائع والمندوب للفاتورة '.$header->invoice_no,
                    model: SalesInvoice::findOrFail($invoice),
                    oldValues: $before ? ['seller_id' => $before->seller_id, 'representative_id' => $before->representative_id] : [],
                    newValues: $after, branchId: $header->branch_id);
            }
        });
        return response()->json(['message' => 'تم حفظ البائع والمندوب.']);
    }
}
