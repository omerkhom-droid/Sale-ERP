<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use RuntimeException;

class ZatcaAdjustmentSigningService
{
    public function __construct(
        private ZatcaAdjustmentXmlService $xmlService,
        private ZatcaInvoiceHashService $hashService,
        private ZatcaInvoiceSignatureService $signatureService,
        private ZatcaCertificateService $certificateService,
        private ZatcaXadesService $xadesService,
        private ZatcaSignedXmlService $signedXmlService,
        private ZatcaQrCodeService $qrService,
    ) {
    }

    public function sign(
        ZatcaDevice $device,
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Validate Device
        |--------------------------------------------------------------------------
        */

        if (empty($device->private_key)) {
            throw new RuntimeException(
                'ZATCA Private Key غير موجود.'
            );
        }

        if (empty($device->compliance_certificate)) {
            throw new RuntimeException(
                'ZATCA Compliance Certificate غير موجود.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UUID
        |--------------------------------------------------------------------------
        */

        $uuid = trim(
            (string) data_get(
                $data,
                'uuid'
            )
        );

        if ($uuid === '') {
            throw new RuntimeException(
                'Invoice UUID غير موجود.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Original Invoice XML
        |--------------------------------------------------------------------------
        */

        $xml = $this->xmlService->generate(
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Invoice Hash
        |--------------------------------------------------------------------------
        */

        $invoiceHash =
            $this->hashService->hash(
                $xml
            );

        $hashBinary =
            base64_decode(
                $invoiceHash,
                true
            );

        if (
            $hashBinary === false
            || strlen($hashBinary) !== 32
        ) {
            throw new RuntimeException(
                'Invoice Hash غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. ECDSA Signature
        |--------------------------------------------------------------------------
        */

        $signature =
            $this->signatureService
                ->signInvoiceHash(
                    $device,
                    $invoiceHash
                );

        /*
        |--------------------------------------------------------------------------
        | Verify Signature Locally
        |--------------------------------------------------------------------------
        */

        $verified =
            $this->signatureService
                ->verifyInvoiceHash(
                    $device,
                    $invoiceHash,
                    $signature,
                    $this->certificateService
                );

        if (! $verified) {
            throw new RuntimeException(
                'فشل التحقق المحلي من توقيع الفاتورة.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Signing Time
        |--------------------------------------------------------------------------
        */

        $signingTime =
            now()
                ->utc()
                ->format(
                    'Y-m-d\TH:i:s\Z'
                );

        /*
        |--------------------------------------------------------------------------
        | 5. XAdES Signature Extension
        |--------------------------------------------------------------------------
        */

        $signatureExtension =
            $this->xadesService
                ->buildSignatureExtension(
                    $device,
                    $invoiceHash,
                    $signature,
                    $signingTime
                );

        /*
        |--------------------------------------------------------------------------
        | 6. Inject Signature
        |--------------------------------------------------------------------------
        */

        $signedXml =
            $this->signedXmlService
                ->injectSignature(
                    $xml,
                    $signatureExtension
                );

        /*
        |--------------------------------------------------------------------------
        | Verify Hash After Signature
        |--------------------------------------------------------------------------
        */

        $hashAfterSignature =
            $this->hashService->hash(
                $signedXml
            );

        if (
            ! hash_equals(
                $invoiceHash,
                $hashAfterSignature
            )
        ) {
            throw new RuntimeException(
                'Invoice Hash تغير بعد إضافة XAdES.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Invoice Type
        |--------------------------------------------------------------------------
        */

        $invoiceTypeName =
            (string) data_get(
                $data,
                'invoice_type_name',
                ''
            );

        /*
        |--------------------------------------------------------------------------
        | 0200000 = Simplified
        |--------------------------------------------------------------------------
        */

        $simplified =
            $invoiceTypeName === '0200000';

        /*
        |--------------------------------------------------------------------------
        | 8. Generate QR
        |--------------------------------------------------------------------------
        */

        $qr =
            $this->qrService->generate(
                $device,
                $data,
                $invoiceHash,
                $signature,
                $simplified
            );

        /*
        |--------------------------------------------------------------------------
        | 9. Inject QR
        |--------------------------------------------------------------------------
        */

        $finalXml =
            $this->signedXmlService
                ->injectQr(
                    $signedXml,
                    $qr
                );

        /*
        |--------------------------------------------------------------------------
        | 10. Final Hash Verification
        |--------------------------------------------------------------------------
        */

        $finalHash =
            $this->hashService->hash(
                $finalXml
            );

        if (
            ! hash_equals(
                $invoiceHash,
                $finalHash
            )
        ) {
            throw new RuntimeException(
                'Invoice Hash تغير بعد إضافة QR.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return [
            'uuid' => $uuid,

            'invoice_hash' =>
                $invoiceHash,

            'signature' =>
                $signature,

            'qr' =>
                $qr,

            'signing_time' =>
                $signingTime,

            'original_xml' =>
                $xml,

            'signed_xml' =>
                $finalXml,

            'is_simplified' =>
                $simplified,
        ];
    }
}