<?php

namespace Tests\Unit;

use App\Models\ZatcaDevice;
use App\Services\Zatca\ZatcaComplianceTestService;
use Tests\TestCase;

class ZatcaComplianceTestServiceTest extends TestCase
{
    public function test_http_success_alone_does_not_pass_compliance(): void
    {
        $service = new ZatcaComplianceTestService;
        $this->assertFalse($service->passed(['successful' => true, 'status' => 200, 'body' => []]));
        $this->assertFalse($service->passed(['successful' => false, 'status' => 401,
            'body' => ['validationResults' => ['status' => 'PASS', 'errorMessages' => []]]]));
    }

    public function test_errors_block_pass_even_when_status_claims_pass(): void
    {
        $service = new ZatcaComplianceTestService;
        $this->assertFalse($service->passed(['successful' => true,
            'body' => ['validationResults' => ['status' => 'PASS', 'errorMessages' => [['code' => 'invalid-signature']]]]]));
        $this->assertFalse($service->passed(['successful' => true,
            'body' => ['validationResults' => ['status' => 'ERROR', 'errorMessages' => []]]]));
    }

    public function test_valid_response_with_warnings_is_accepted(): void
    {
        $service = new ZatcaComplianceTestService;
        $this->assertTrue($service->passed(['successful' => true,
            'body' => ['validationResults' => ['status' => 'WARNING', 'errorMessages' => [], 'warningMessages' => [['code' => 'warning']]]]]));
    }

    public function test_required_documents_follow_device_capabilities(): void
    {
        $service = new ZatcaComplianceTestService;
        $device = new ZatcaDevice;
        foreach (['1000' => 3, '0100' => 3, '1100' => 6] as $map => $count) {
            $device->invoice_type = (string) $map;
            $this->assertCount($count, $service->definitions($device));
        }
        $device->invoice_type = '1100';
        $tests = array_fill_keys(array_keys($service->definitions($device)), ['state' => 'passed']);
        $this->assertTrue($service->allPassed($device, $tests));
        unset($tests['simplified_debit']);
        $this->assertFalse($service->allPassed($device, $tests));
    }

    public function test_new_credentials_invalidate_old_results(): void
    {
        config(['services.zatca.environment' => 'simulation',
            'services.zatca.simulation_url' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation']);
        $device = new ZatcaDevice;
        $device->environment = 'simulation';
        $device->invoice_type = '1100';
        $device->compliance_certificate = 'old-test-certificate';
        $service = new ZatcaComplianceTestService;
        $before = $service->fingerprint($device);
        $device->compliance_certificate = 'new-test-certificate';
        $this->assertNotSame($before, $service->fingerprint($device));
    }
}
