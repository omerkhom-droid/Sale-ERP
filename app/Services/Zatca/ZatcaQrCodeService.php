<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use RuntimeException;

class ZatcaQrCodeService
{
    public function __construct(
        private ZatcaCertificateService $certificateService
    ) {
    }

    public function generate(
        ZatcaDevice $device,
        array $data,
        string $invoiceHash,
        string $signature,
        bool $simplified = true
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Binary values
        |--------------------------------------------------------------------------
        |
        | invoiceHash و signature عندنا Base64.
        | QR يحتاج الـ bytes الأصلية وليس نص Base64.
        |
        */

        $decodedInvoiceHash = base64_decode(
            $invoiceHash,
            true
        );

        if (
            $decodedInvoiceHash === false
            || strlen($decodedInvoiceHash) !== 32
        ) {
            throw new RuntimeException(
                'Invoice Hash Base64 غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ZATCA QR Tag 6
        |--------------------------------------------------------------------------
        |
        | نتحقق من القيمة الثنائية، لكن نضع نص Base64 داخل TLV.
        |
        */

        $invoiceHashBytes = $invoiceHash;

        $decodedSignature = base64_decode(
            $signature,
            true
        );

        if ($decodedSignature === false) {
            throw new RuntimeException(
                'ECDSA Signature Base64 غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ZATCA QR Tag 7
        |--------------------------------------------------------------------------
        */

        $signatureBytes = $signature;

        $publicKeyBytes =
            $this->certificateService
                ->publicKeyDer($device);

        /*
        |--------------------------------------------------------------------------
        | Timestamp
        |--------------------------------------------------------------------------
        */

        $timestamp =
            $this->timestamp($data);

        /*
        |--------------------------------------------------------------------------
        | TLV
        |--------------------------------------------------------------------------
        */

        $tlv = '';

        $tlv .= $this->textTlv(
            1,
            (string) data_get(
                $data,
                'supplier.name'
            )
        );

        $tlv .= $this->textTlv(
            2,
            (string) data_get(
                $data,
                'supplier.vat_number'
            )
        );

        $tlv .= $this->textTlv(
            3,
            $timestamp
        );

        $tlv .= $this->textTlv(
            4,
            $this->amount(
                data_get(
                    $data,
                    'tax_inclusive_total'
                )
            )
        );

        $tlv .= $this->textTlv(
            5,
            $this->amount(
                data_get(
                    $data,
                    'tax_total'
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Phase 2 binary tags
        |--------------------------------------------------------------------------
        */

        $tlv .= $this->binaryTlv(
            6,
            $invoiceHashBytes
        );

        $tlv .= $this->binaryTlv(
            7,
            $signatureBytes
        );

        $tlv .= $this->binaryTlv(
            8,
            $publicKeyBytes
        );

        /*
        |--------------------------------------------------------------------------
        | Tag 9
        |--------------------------------------------------------------------------
        |
        | مطلوب للفاتورة المبسطة وملاحظاتها.
        |
        */

        if ($simplified) {
            $certificateSignature =
                $this->certificateService
                    ->certificateSignature(
                        $device
                    );

            $tlv .= $this->binaryTlv(
                9,
                $certificateSignature
            );
        }

        return base64_encode(
            $tlv
        );
    }

    private function timestamp(
        array $data
    ): string {
        $date = trim(
            (string) data_get(
                $data,
                'issue_date'
            )
        );

        $time = trim(
            (string) data_get(
                $data,
                'issue_time'
            )
        );

        if (
            $date === ''
            || $time === ''
        ) {
            throw new RuntimeException(
                'Invoice issue date/time غير موجود.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Test data generated in UTC/local-independent form
        |--------------------------------------------------------------------------
        */

        return $date
            . 'T'
            . $time;
    }

    private function amount(
        mixed $value
    ): string {
        if (! is_numeric($value)) {
            throw new RuntimeException(
                'QR amount غير صالح.'
            );
        }

        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private function textTlv(
        int $tag,
        string $value
    ): string {
        return $this->binaryTlv(
            $tag,
            $value
        );
    }

    private function binaryTlv(
        int $tag,
        string $value
    ): string {
        if (
            $tag < 1
            || $tag > 255
        ) {
            throw new RuntimeException(
                'QR Tag غير صالح.'
            );
        }

        $length = strlen(
            $value
        );

        if ($length > 255) {
            throw new RuntimeException(
                "QR Tag {$tag} تجاوز 255 bytes."
            );
        }

        return chr($tag)
            . chr($length)
            . $value;
    }
}