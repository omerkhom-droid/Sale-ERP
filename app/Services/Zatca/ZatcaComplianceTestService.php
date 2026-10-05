<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use Illuminate\Support\Str;
use RuntimeException;

class ZatcaComplianceTestService
{
    public function environment(ZatcaDevice $device): string
    {
        $environment = (string) config('services.zatca.environment');
        $paths = ['developer' => 'developer-portal', 'simulation' => 'simulation', 'production' => 'core'];
        if (!isset($paths[$environment]) || $device->environment !== $environment) {
            throw new RuntimeException('بيئة الجهاز لا تطابق بيئة النظام.');
        }
        $configured = rtrim((string) config("services.zatca.{$environment}_url"), '/');
        if ($configured !== 'https://gw-fatoora.zatca.gov.sa/e-invoicing/'.$paths[$environment]) {
            throw new RuntimeException('راجع رابط بيئة ZATCA في الإعدادات.');
        }
        return $environment;
    }

    public function definitions(ZatcaDevice $device): array
    {
        $map = (string) $device->invoice_type;
        if (!in_array($map, ['1000', '0100', '1100'], true)) {
            throw new RuntimeException('أنواع الجهاز المدعومة هنا: 1000 أو 0100 أو 1100.');
        }
        $tests = [];
        foreach (['standard' => ['قياسية', '0100000'], 'simplified' => ['مبسطة', '0200000']] as $kind => [$label, $name]) {
            if (($kind === 'standard' && $map[0] !== '1') || ($kind === 'simplified' && $map[1] !== '1')) {
                continue;
            }
            foreach (['invoice' => ['فاتورة', '388'], 'credit' => ['إشعار دائن', '381'], 'debit' => ['إشعار مدين', '383']] as $type => [$title, $code]) {
                $tests[$kind.'_'.$type] = ['label' => $title.' — '.$label, 'name' => $name, 'code' => $code];
            }
        }
        return $tests;
    }

    public function fingerprint(ZatcaDevice $device): string
    {
        return hash('sha256', json_encode([
            'version' => 1, 'environment' => $this->environment($device),
            'certificate' => (string) $device->compliance_certificate,
            'secret' => (string) $device->compliance_secret,
            'request' => (string) $device->compliance_request_id,
            'key' => (string) $device->private_key,
            'csr' => (string) $device->csr,
            'type' => (string) $device->invoice_type,
            'vat' => (string) $device->organization_identifier,
            'name' => (string) $device->organization_name,
        ], JSON_THROW_ON_ERROR));
    }

    public function assertReady(ZatcaDevice $device): void
    {
        $this->environment($device);
        if ($device->status !== 'compliance' || !$device->compliance_certificate || !$device->compliance_secret || !$device->compliance_request_id || !$device->private_key) {
            throw new RuntimeException('يلزم الحصول على Compliance CSID لهذا الجهاز أولاً.');
        }
        $this->assertKeyMatches($device);
    }

    public function assertKeyMatches(ZatcaDevice $device): void
    {
        $key = openssl_pkey_get_private((string) $device->private_key);
        $public = app(ZatcaCertificateService::class)->publicKey($device);
        $a = $key ? openssl_pkey_get_details($key) : false;
        $b = openssl_pkey_get_details($public);
        foreach (['x', 'y'] as $coordinate) {
            if (empty($a['ec'][$coordinate]) || empty($b['ec'][$coordinate]) || !hash_equals($a['ec'][$coordinate], $b['ec'][$coordinate])) {
                throw new RuntimeException('الشهادة لا تطابق المفتاح الخاص للجهاز.');
            }
        }
        if (($a['ec']['curve_name'] ?? '') !== 'secp256k1') {
            throw new RuntimeException('المفتاح يجب أن يستخدم secp256k1.');
        }
    }

    public function fixtures(ZatcaDevice $device, array $supplier): array
    {
        $tests = [];
        $prefix = 'COMPLIANCE-'.Str::upper(Str::random(8));
        $date = now()->utc();
        foreach ($this->definitions($device) as $key => $definition) {
            $kind = explode('_', $key)[0];
            $data = [
                'invoice_number' => $prefix.'-'.$key,
                'uuid' => (string) Str::uuid(),
                'issue_date' => $date->format('Y-m-d'),
                'issue_time' => $date->format('H:i:s').'Z',
                'supply_date' => $date->format('Y-m-d'),
                'invoice_type_code' => $definition['code'],
                'invoice_type_name' => $definition['name'],
                'currency' => 'SAR', 'icv' => count($tests) + 1,
                'previous_invoice_hash' => '', 'qr' => '',
                'supplier' => $supplier,
                'customer' => $kind === 'standard' ? [
                    'name' => 'عميل اختبارات الامتثال', 'vat_number' => '300000000000003',
                    'registration_number' => '1010000000', 'registration_scheme' => 'CRN',
                    'street' => 'شارع الاختبار', 'building_number' => '1234',
                    'district' => 'حي الاختبار', 'city' => 'الرياض',
                    'postal_code' => '12345', 'country_code' => 'SA',
                ] : ['name' => 'عميل اختبارات الامتثال'],
                'lines' => [[
                    'id' => 1, 'name' => 'خدمة لاختبار الامتثال', 'quantity' => 1,
                    'unit_code' => 'PCE', 'unit_price' => 100, 'discount_amount' => 0,
                    'line_extension_amount' => 100, 'tax_category' => 'S',
                    'tax_percent' => 15, 'tax_amount' => 15, 'tax_inclusive_amount' => 115,
                ]],
                'line_extension_total' => 100, 'tax_exclusive_total' => 100,
                'tax_total' => 15, 'tax_inclusive_total' => 115, 'payable_total' => 115,
                'payment_means_code' => '10',
                'reference_invoice_number' => $prefix.'-'.$kind.'_invoice',
                'reason' => 'اختبار امتثال للإشعارات',
            ];
            $tests[$key] = ['state' => 'pending', 'data' => $data];
        }
        return $tests;
    }

    public function sign(ZatcaDevice $device, array $data): array
    {
        // Select Compliance for every component, including XAdES and QR.
        $signingDevice = clone $device;
        $signingDevice->status = 'compliance';
        $xmlService = $data['invoice_type_code'] === '388'
            ? app(ZatcaInvoiceXmlService::class) : app(ZatcaAdjustmentXmlService::class);
        $xml = $xmlService->generate($data);
        $hashService = app(ZatcaInvoiceHashService::class);
        $signatureService = app(ZatcaInvoiceSignatureService::class);
        $hash = $hashService->hash($xml);
        $signature = $signatureService->signInvoiceHash($signingDevice, $hash);
        if (!$signatureService->verifyInvoiceHash($signingDevice, $hash, $signature, app(ZatcaCertificateService::class))) {
            throw new RuntimeException('فشل التحقق المحلي من التوقيع.');
        }
        $extension = app(ZatcaXadesService::class)->buildSignatureExtension(
            $signingDevice, $hash, $signature, now()->utc()->format('Y-m-d\TH:i:s\Z')
        );
        $signedService = app(ZatcaSignedXmlService::class);
        $signed = $signedService->injectSignature($xml, $extension);
        $qr = app(ZatcaQrCodeService::class)->generate($signingDevice, $data, $hash, $signature, $data['invoice_type_name'] === '0200000');
        $signed = $signedService->injectQr($signed, $qr);
        if (!hash_equals($hash, $hashService->hash($signed))) {
            throw new RuntimeException('تغير هاش المستند بعد إضافة التوقيع أو QR.');
        }
        return ['uuid' => $data['uuid'], 'invoice_hash' => $hash, 'signed_xml' => $signed];
    }

    public function passed(array $response): bool
    {
        $validation = data_get($response, 'body.validationResults');
        return ($response['successful'] ?? false) === true
            && is_array($validation)
            && in_array($validation['status'] ?? '', ['PASS', 'WARNING'], true)
            && isset($validation['errorMessages'])
            && is_array($validation['errorMessages'])
            && count($validation['errorMessages']) === 0;
    }

    public function allPassed(ZatcaDevice $device, array $tests): bool
    {
        foreach (array_keys($this->definitions($device)) as $key) {
            if (($tests[$key]['state'] ?? '') !== 'passed') {
                return false;
            }
        }
        return true;
    }
}
