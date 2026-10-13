<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class SalesAnalysisService
{
    public const TITLES = [
        'staff' => 'مبيعات المستخدمين والبائعين',
        'customers' => 'مبيعات العملاء',
        'brands' => 'ملخص مبيعات الماركات',
        'monthly' => 'المبيعات الشهرية',
    ];
    public const MONTHS = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    public function branches()
    {
        $user = auth()->user();
        $q = Branch::query();
        if (in_array($user->user_type, ['master', 'system_admin'], true)) {
            return $q;
        }
        $q->where('company_id', $user->company_id ?: -1);
        if (!in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
            $q->whereKey($user->branch_id ?: -1);
        }
        return $q;
    }

    public function invoices()
    {
        return DB::table('sales_invoices as i')
            ->whereIn('i.branch_id', $this->branches()->select('id'));
    }

    public function people()
    {
        $q = DB::table('users')->whereNull('deleted_at');
        $user = auth()->user();
        if (!in_array($user->user_type, ['master', 'system_admin'], true)) {
            $q->where('company_id', $user->company_id ?: -1);
            if (!in_array($user->user_type, ['company_owner', 'company_admin'], true)) {
                $q->where('branch_id', $user->branch_id ?: -1);
            }
        }
        return $q->orderBy('name')->get(['id', 'name']);
    }

    // One financial event per line: returns keep their own date and negative sign.
    public function events(array $filters)
    {
        $sales = $this->lines('invoice', $filters);
        $sales->unionAll($this->lines('debit', $filters));
        $returns = $this->lines('return', $filters);
        return DB::query()->fromSub(($filters['net_returns'] ?? true) ? $sales->unionAll($returns) : $sales, 'e');
    }

    private function lines(string $type, array $f)
    {
        $return = $type === 'return';
        $q = $this->invoices();
        $header = 'i';
        $date = 'i.invoice_date';
        if ($return) {
            $q->join('sales_returns as r', 'r.sales_invoice_id', '=', 'i.id')
                ->join('sales_return_items as l', 'l.sales_return_id', '=', 'r.id')
                ->where('r.status', 'posted')
                ->whereIn('r.branch_id', $this->branches()->select('id'));
            $header = 'r';
            $date = 'r.return_date';
        } elseif ($type === 'debit') {
            $q->join('sales_debit_notes as d', 'd.sales_invoice_id', '=', 'i.id')
                ->join('sales_debit_note_items as l', 'l.sales_debit_note_id', '=', 'd.id')
                ->where('d.status', 'posted')
                ->whereIn('d.branch_id', $this->branches()->select('id'));
            $header = 'd';
            $date = 'd.note_date';
        } else {
            $q->join('sales_invoice_items as l', 'l.sales_invoice_id', '=', 'i.id');
        }
        $q->where('i.status', 'posted')
            ->leftJoin('sales_report_attributions as a', 'a.sales_invoice_id', '=', 'i.id')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id');
        $q->when($f['branch_id'] ?? null, fn ($q, $v) => $q->where($header.'.branch_id', $v))
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('i.created_by', $v))
            ->when($f['seller_id'] ?? null, fn ($q, $v) => $q->where('a.seller_id', $v))
            ->when($f['representative_id'] ?? null, fn ($q, $v) => $q->where('a.representative_id', $v))
            ->when($f['brand_id'] ?? null, fn ($q, $v) => $q->where('p.brand_id', $v))
            ->when($f['payment_type'] ?? null, fn ($q, $v) => $q->where('i.payment_type', $v))
            ->when($f['payment_method'] ?? null, fn ($q, $v) => $q->where('i.payment_method', $v));
        if (($f['customer_id'] ?? null) === 'cash') {
            $q->whereNull('i.customer_id');
        } elseif (!empty($f['customer_id'])) {
            $q->where('i.customer_id', $f['customer_id']);
        }
        if (($f['kind'] ?? null) === 'monthly') {
            $q->where($date, '>=', $f['year'].'-01-01')->where($date, '<', ($f['year'] + 1).'-01-01');
        } else {
            $q->where($date, '>=', $f['date_from'])->where($date, '<', date('Y-m-d', strtotime($f['date_to'].' +1 day')));
        }
        $sign = $return ? '-1' : '1';
        $number = match ($type) { 'return' => 'r.return_no', 'debit' => 'd.note_no', default => 'i.invoice_no' };
        return $q->selectRaw("i.id as invoice_id, $header.id as document_id, '$type' as document_type, $number as document_no,
            $date as document_date, $header.branch_id as branch_id, i.created_by as user_id,
            a.seller_id, a.representative_id, i.customer_id, i.customer_name,
            i.payment_type, i.payment_method, p.brand_id,
            $sign * l.net_amount as net, $sign * l.vat_amount as vat, $sign * l.total_cost as cost");
    }

    public function report(string $kind, array $f)
    {
        $e = $this->events(array_merge($f, ['kind' => $kind]));
        if ($kind === 'staff') {
            $q = $e->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
                ->leftJoin('users as u', 'u.id', '=', 'e.user_id')
                ->leftJoin('users as s', 's.id', '=', 'e.seller_id')
                ->selectRaw("e.document_type, e.document_id, e.invoice_id, e.document_no, e.document_date, e.branch_id,
                    COALESCE(b.branch_name, 'غير محدد') as branch_name, COALESCE(u.name, 'غير محدد') as user_name,
                    COALESCE(s.name, 'غير محدد') as seller_name, e.payment_type, e.payment_method,
                    SUM(e.net) as net, SUM(e.vat) as vat, SUM(e.cost) as cost")
                ->groupBy('e.document_type', 'e.document_id', 'e.invoice_id', 'e.document_no', 'e.document_date', 'e.branch_id', 'b.branch_name', 'u.name', 's.name', 'e.payment_type', 'e.payment_method');
        } elseif ($kind === 'customers') {
            $split = ($f['include_vat'] ?? false) ? '(e.net + e.vat)' : 'e.net';
            $q = $e->leftJoin('customers as c', 'c.id', '=', 'e.customer_id')
                ->selectRaw("COALESCE(e.customer_id, 0) as customer_key,
                    COALESCE(c.customer_name, CASE WHEN e.customer_id IS NULL THEN 'عملاء نقديون غير مسجلين' ELSE 'عميل محذوف' END) as customer_label,
                    SUM(CASE WHEN e.payment_type = 'cash' THEN $split ELSE 0 END) as cash,
                    SUM(CASE WHEN e.payment_type <> 'cash' THEN $split ELSE 0 END) as credit,
                    SUM(e.net) as net, SUM(e.vat) as vat, SUM(e.cost) as cost")
                ->groupBy('e.customer_id', 'c.customer_name');
        } elseif ($kind === 'brands') {
            $q = $e->leftJoin('brands as b', 'b.id', '=', 'e.brand_id')
                ->selectRaw("COALESCE(e.brand_id, 0) as brand_key, COALESCE(b.brand_name, 'بدون ماركة') as brand_label,
                    SUM(e.net) as net, SUM(e.vat) as vat, SUM(e.cost) as cost")
                ->groupBy('e.brand_id', 'b.brand_name');
        } else {
            // Calendar months are joined to the aggregate so even empty months remain visible.
            $split = ($f['include_vat'] ?? false) ? '(e.net + e.vat)' : 'e.net';
            $monthly = $e->selectRaw("MONTH(e.document_date) as month_no,
                SUM(CASE WHEN e.payment_type = 'cash' THEN $split ELSE 0 END) as cash,
                SUM(CASE WHEN e.payment_type <> 'cash' THEN $split ELSE 0 END) as credit,
                SUM(e.net) as net, SUM(e.vat) as vat, SUM(e.cost) as cost")
                ->groupByRaw('MONTH(e.document_date)');
            $months = DB::query()->selectRaw('1 as month_no');
            for ($n = 2; $n <= 12; $n++) {
                $months->unionAll(DB::query()->selectRaw($n.' as month_no'));
            }
            $q = DB::query()->fromSub($months, 'm')->leftJoinSub($monthly, 'a', 'a.month_no', '=', 'm.month_no')
                ->selectRaw('m.month_no, COALESCE(a.cash, 0) as cash, COALESCE(a.credit, 0) as credit,
                    COALESCE(a.net, 0) as net, COALESCE(a.vat, 0) as vat, COALESCE(a.cost, 0) as cost');
        }
        $base = DB::query()->fromSub($q, 'x');
        $amount = ($f['include_vat'] ?? false) ? '(x.net + x.vat)' : 'x.net';
        return $base->select('x.*')->selectRaw("$amount as amount, (x.net + x.vat) as total,
            (x.net - x.cost) as profit, CASE WHEN x.net = 0 THEN 0 ELSE (x.net - x.cost) * 100 / x.net END as percent");
    }

    public function totals(array $f): array
    {
        $v = $this->events($f)->selectRaw('COALESCE(SUM(net),0) as net, COALESCE(SUM(vat),0) as vat, COALESCE(SUM(cost),0) as cost')->first();
        $net = (float) $v->net;
        $profit = $net - (float) $v->cost;
        return ['net' => $net, 'vat' => (float) $v->vat, 'cost' => (float) $v->cost, 'total' => $net + (float) $v->vat,
            'profit' => $profit, 'percent' => $net == 0.0 ? 0 : $profit * 100 / $net];
    }

    public function columns(string $kind): array
    {
        return match ($kind) {
            'staff' => ['document_no' => 'رقم السند', 'document_date' => 'التاريخ', 'document_type' => 'نوع السند',
                'branch_name' => 'الفرع', 'user_name' => 'المستخدم', 'seller_name' => 'البائع', 'payment_type' => 'نوع السداد',
                'payment_method' => 'طريقة الدفع', 'amount' => 'قيمة المبيعات', 'cost' => 'التكلفة', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'],
            'customers' => ['customer_key' => 'رقم العميل', 'customer_label' => 'العميل', 'cash' => 'صافي النقدي', 'credit' => 'صافي الآجل والجزئي',
                'amount' => 'إجمالي المبيعات', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'],
            'brands' => ['brand_label' => 'الماركة', 'net' => 'صافي المبيعات', 'vat' => 'الضريبة', 'total' => 'الإجمالي', 'cost' => 'التكلفة', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'],
            'monthly' => ['month_no' => 'الشهر', 'cash' => 'صافي النقدي', 'credit' => 'صافي الآجل والجزئي', 'amount' => 'إجمالي المبيعات', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'],
        };
    }

    public function display(string $key, $value): string
    {
        if ($key === 'month_no') return self::MONTHS[(int) $value];
        if ($key === 'document_type') return ['return' => 'مردود مبيعات', 'debit' => 'إشعار مدين', 'invoice' => 'فاتورة مبيعات'][$value] ?? (string) $value;
        if ($key === 'payment_type') return ['cash' => 'نقدي', 'credit' => 'آجل', 'partial' => 'جزئي'][$value] ?? (string) $value;
        if ($key === 'payment_method') return ['cash' => 'نقد', 'card' => 'بطاقة', 'bank_transfer' => 'تحويل', 'other' => 'أخرى'][$value] ?? '—';
        if (in_array($key, ['net', 'vat', 'cost', 'total', 'amount', 'cash', 'credit', 'profit', 'percent'], true)) return number_format((float) $value, 2);
        return (string) $value;
    }
}
