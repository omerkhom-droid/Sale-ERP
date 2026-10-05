<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use RuntimeException;

class ZatcaInvoiceSignatureService
{
    public function signInvoiceHash(
        ZatcaDevice $device,
        string $invoiceHash
    ): string {
        $binaryHash = base64_decode(
            $invoiceHash,
            true
        );

        if (
            $binaryHash === false
            || strlen($binaryHash) !== 32
        ) {
            throw new RuntimeException(
                'Invoice Hash غير صالح.'
            );
        }

        $privateKey = openssl_pkey_get_private(
            $device->private_key
        );

        if (! $privateKey) {
            throw new RuntimeException(
                'تعذر قراءة Private Key.'
            );
        }

        $signature = '';

        $result = openssl_sign(
            $binaryHash,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (! $result) {
            throw new RuntimeException(
                'فشل إنشاء توقيع ECDSA.'
            );
        }

        return base64_encode(
            $signature
        );
    }

    public function verifyInvoiceHash(
        ZatcaDevice $device,
        string $invoiceHash,
        string $signature,
        ZatcaCertificateService $certificateService
    ): bool {
        $binaryHash = base64_decode(
            $invoiceHash,
            true
        );

        $binarySignature = base64_decode(
            $signature,
            true
        );

        if (
            $binaryHash === false
            || $binarySignature === false
        ) {
            return false;
        }

        try {
            $publicKey =
                $certificateService->publicKey(
                    $device
                );
        } catch (\Throwable) {
            return false;
        }

        return openssl_verify(
            $binaryHash,
            $binarySignature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
    }
}