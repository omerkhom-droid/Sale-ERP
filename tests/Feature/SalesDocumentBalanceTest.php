<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Services\SalesDocumentBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesDocumentBalanceTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = config('database.default');
        // Dedicated memory database: do not migrate, truncate, or reset the application's DB.
        config(['database.connections.debit_note_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'debit_note_test']);
        DB::purge('debit_note_test');
        Schema::create('sales_invoices', function (Blueprint $t) {
            $t->id();
            foreach (['total_amount','paid_amount','remaining_amount'] as $field) { $t->decimal($field,15,2)->default(0); }
            $t->string('payment_status')->default('unpaid'); $t->timestamps();
        });
        Schema::create('sales_debit_notes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('sales_invoice_id'); $t->string('status'); $t->decimal('total_amount',15,2);
        });
        Schema::create('sales_returns', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('sales_invoice_id'); $t->string('status');
            $t->decimal('applied_amount',15,2); $t->decimal('refundable_amount',15,2)->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::purge('debit_note_test');
        config(['database.default' => $this->previousConnection]);
        parent::tearDown();
    }

    private function invoice(float $paid = 0): SalesInvoice
    {
        $id = DB::table('sales_invoices')->insertGetId(['total_amount'=>100,'paid_amount'=>$paid,'remaining_amount'=>100-$paid]);
        return SalesInvoice::findOrFail($id);
    }

    private function debit(SalesInvoice $i, string $status, float $amount): void
    {
        DB::table('sales_debit_notes')->insert(['sales_invoice_id'=>$i->id,'status'=>$status,'total_amount'=>$amount]);
    }

    public function test_only_posted_debits_increase_the_balance(): void
    {
        $i=$this->invoice(); $this->debit($i,'posted',25); $this->debit($i,'draft',80); $this->debit($i,'cancelled',70);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(125, $i->fresh()->remaining_amount);
    }

    public function test_receipt_after_debit_preserves_the_extra_due(): void
    {
        $i=$this->invoice(40); $this->debit($i,'posted',25);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(85,$i->fresh()->remaining_amount);
        $this->assertSame('partial',$i->fresh()->payment_status);
    }

    public function test_refundable_credit_is_not_also_deducted_from_the_invoice(): void
    {
        $i=$this->invoice(100); $this->debit($i,'posted',10);
        DB::table('sales_returns')->insert(['sales_invoice_id'=>$i->id,'status'=>'posted','applied_amount'=>0,'refundable_amount'=>20]);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(10,$i->fresh()->remaining_amount);
    }

    public function test_applied_credit_and_debit_are_combined(): void
    {
        $i=$this->invoice(30); $this->debit($i,'posted',25);
        DB::table('sales_returns')->insert(['sales_invoice_id'=>$i->id,'status'=>'posted','applied_amount'=>20,'refundable_amount'=>0]);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(75,$i->fresh()->remaining_amount);
    }

    public function test_other_invoices_do_not_affect_the_balance(): void
    {
        $i=$this->invoice(); $other=$this->invoice(); $this->debit($other,'posted',500);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(100,$i->fresh()->remaining_amount);
    }

    public function test_cancelling_credit_can_exclude_its_applied_amount(): void
    {
        $i=$this->invoice();
        $id=DB::table('sales_returns')->insertGetId(['sales_invoice_id'=>$i->id,'status'=>'posted','applied_amount'=>20]);
        $this->assertEquals(80,app(SalesDocumentBalanceService::class)->netAmount($i));
        $this->assertEquals(100,app(SalesDocumentBalanceService::class)->netAmount($i,$id));
    }

    public function test_fully_settled_invoice_has_zero_due(): void
    {
        $i=$this->invoice(125); $this->debit($i,'posted',25);
        app(SalesDocumentBalanceService::class)->refresh($i);
        $this->assertEquals(0,$i->fresh()->remaining_amount);
        $this->assertSame('paid',$i->fresh()->payment_status);
    }
}
