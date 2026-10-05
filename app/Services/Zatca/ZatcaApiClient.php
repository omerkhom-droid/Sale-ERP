<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use Exception;
use Illuminate\Support\Facades\Http;

class ZatcaApiClient
{
    /**
     * طلب Compliance CSID من ZATCA Developer Portal.
     */
    public function requestComplianceCsid(
        ZatcaDevice $device,
        string $otp
    ): array {
        if (! preg_match('/\A[0-9]{6}\z/', $otp)) {
            throw new \InvalidArgumentException(
                'رمز OTP يجب أن يتكون من 6 أرقام.'
            );
        }

        $response = Http::withHeaders([
            'OTP' => $otp,
            'Accept-Version' => 'V2',
            'Accept-Language' => 'en',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->post(
                $this->baseUrl() . '/compliance',
                [
                    'csr' => $this->prepareCsr(
                        $device->csr
                    ),
                ]
            );

        if ($response->failed()) {
            throw new Exception(
                'ZATCA Compliance CSID HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Exception(
                'استجابة ZATCA غير صالحة.'
            );
        }

        return $data;
    }

    /**
     * ZATCA تتوقع CSR بصيغة Base64.
     */
    private function prepareCsr(string $csr): string
    {
        return base64_encode($csr);
    }


/**
 * طلب Production CSID بعد نجاح اختبارات Compliance.
 */
    public function requestProductionCsid(
        \App\Models\ZatcaDevice $device
    ): array {
        if (
            empty($device->compliance_certificate)
            || empty($device->compliance_secret)
        ) {
            throw new \RuntimeException(
                'Compliance CSID غير موجود لهذا الجهاز.'
            );
        }

        if (empty($device->compliance_request_id)) {
            throw new \RuntimeException(
                'Compliance Request ID غير موجود لهذا الجهاز.'
            );
        }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            $device->compliance_certificate,
            $device->compliance_secret
        )
            ->withHeaders([
                'Accept-Version' => 'V2',
                'Accept-Language' => 'en',
                'Content-Type' => 'application/json',
            ])
            ->post(
                $this->baseUrl() . '/production/csids',
                [
                    'compliance_request_id' =>
                        (string) $device->compliance_request_id,
                ]
            );

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json()
                ?: [
                    'raw_body' => $response->body(),
                ],
        ];
    }

    /**
     * رابط Developer Portal.
     */
    private function baseUrl(): string
    {
        $environment = config(
            'services.zatca.environment',
            'developer'
        );

        $url = match ($environment) {
            'simulation' => config(
                'services.zatca.simulation_url'
            ),

            'production' => config(
                'services.zatca.production_url'
            ),

            default => config(
                'services.zatca.developer_url'
            ),
        };

        if (! $url) {
            throw new Exception(
                'لم يتم ضبط رابط بيئة ZATCA في الإعدادات.'
            );
        }

        return rtrim($url, '/');
    }

    public function checkInvoiceCompliance(
        ZatcaDevice $device,
        string $uuid,
        string $invoiceHash,
        string $signedInvoiceXml
    ): array {
        if (
            empty($device->compliance_certificate)
            || empty($device->compliance_secret)
        ) {
            throw new \RuntimeException(
                'Compliance CSID غير موجود لهذا الجهاز.'
            );
        }

        if (trim($uuid) === '') {
            throw new \RuntimeException(
                'Invoice UUID غير موجود.'
            );
        }

        if (strlen(base64_decode($invoiceHash, true) ?: '') !== 32) {
            throw new \RuntimeException(
                'Invoice Hash غير صالح.'
            );
        }

        if (trim($signedInvoiceXml) === '') {
            throw new \RuntimeException(
                'Signed Invoice XML فارغ.'
            );
        }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            $device->compliance_certificate,
            $device->compliance_secret
        )
            ->withHeaders([
                'Accept-Version' => 'V2',
                'Accept-Language' => 'en',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post(
                $this->baseUrl() . '/compliance/invoices',
                [
                    'invoiceHash' => $invoiceHash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode(
                        $signedInvoiceXml
                    ),
                ]
            );

        $body = $response->json();

        if (! is_array($body)) {
            $body = [
                'raw_body' => $response->body(),
            ];
        }

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
        ];
    }

    /**
     * إرسال فاتورة مبسطة إلى Reporting API.
     */
    public function reportInvoice(
        \App\Models\ZatcaDevice $device,
        string $uuid,
        string $invoiceHash,
        string $signedInvoiceXml
    ): array {
        if (
            empty($device->production_certificate)
            || empty($device->production_secret)
        ) {
            throw new \RuntimeException(
                'Production CSID غير موجود لهذا الجهاز.'
            );
        }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            $device->production_certificate,
            $device->production_secret
        )
            ->withHeaders([
                'Accept-Version' => 'V2',
                'Accept-Language' => 'en',
                'Accept' => 'application/json',
                'Clearance-Status' => '0',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(10)
            ->timeout(30)
            ->post(
                $this->baseUrl() . '/invoices/reporting/single',
                [
                    'invoiceHash' => $invoiceHash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode(
                        $signedInvoiceXml
                    ),
                ]
            );

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json()
                ?: [
                    'raw_body' => $response->body(),
                ],
        ];
    }

    /**
     * إرسال فاتورة قياسية إلى Clearance API.
     */
    public function clearInvoice(
        ZatcaDevice $device,
        string $uuid,
        string $invoiceHash,
        string $signedInvoiceXml
    ): array {
        if (
            empty($device->production_certificate)
            || empty($device->production_secret)
        ) {
            throw new \RuntimeException(
                'Production CSID غير موجود لهذا الجهاز.'
            );
        }

        if (trim($uuid) === '') {
            throw new \RuntimeException(
                'Invoice UUID غير موجود.'
            );
        }

        if (
            strlen(
                base64_decode($invoiceHash, true) ?: ''
            ) !== 32
        ) {
            throw new \RuntimeException(
                'Invoice Hash غير صالح.'
            );
        }

        if (trim($signedInvoiceXml) === '') {
            throw new \RuntimeException(
                'Signed Invoice XML فارغ.'
            );
        }

        $response = Http::withBasicAuth(
            $device->production_certificate,
            $device->production_secret
        )
            ->withHeaders([
                'Accept-Version' => 'V2',
                'Accept-Language' => 'en',
                'Accept' => 'application/json',
                'Clearance-Status' => '1',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(10)
            ->timeout(30)
            ->post(
                $this->baseUrl()
                    . '/invoices/clearance/single',
                [
                    'invoiceHash' => $invoiceHash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode(
                        $signedInvoiceXml
                    ),
                ]
            );

        $body = $response->json();

        if (! is_array($body)) {
            $body = [
                'raw_body' => $response->body(),
            ];
        }

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
        ];
    }

}