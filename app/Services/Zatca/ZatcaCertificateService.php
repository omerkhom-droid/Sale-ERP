<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use RuntimeException;

class ZatcaCertificateService
{
   public function certificatePem(
        ZatcaDevice $device
    ): string {
        $environment = (string) $device->environment;
        if (!in_array($environment, ['developer', 'simulation', 'production'], true)) {
            throw new RuntimeException('بيئة الجهاز غير صالحة.');
        }
        $useProductionCertificate = $device->status === 'production'
            && $environment !== 'developer';
        $token = trim((string) ($useProductionCertificate
            ? $device->production_certificate : $device->compliance_certificate));
        if ($token === '') {
            throw new RuntimeException($useProductionCertificate
                ? 'لا توجد Production Certificate لهذا الجهاز.'
                : 'لا توجد Compliance Certificate لهذا الجهاز.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1) لو المخزن PEM مباشرة
        |--------------------------------------------------------------------------
        */
        if (str_contains(
            $token,
            '-----BEGIN CERTIFICATE-----'
        )) {
            return $this->normalizePem(
                $token
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2) أول Base64 decode
        |--------------------------------------------------------------------------
        */
        $decoded1 = base64_decode(
            $token,
            true
        );

        if ($decoded1 === false) {
            throw new RuntimeException(
                'binarySecurityToken ليس Base64 صالحًا.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | لو الناتج PEM
        |--------------------------------------------------------------------------
        */
        if (str_contains(
            $decoded1,
            '-----BEGIN CERTIFICATE-----'
        )) {
            return $this->normalizePem(
                $decoded1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3) نحاول قراءة الناتج الأول كـ DER
        |--------------------------------------------------------------------------
        */
        $pemFromFirstDecode = $this->derToPem(
            $decoded1
        );

        if (
            @openssl_x509_read(
                $pemFromFirstDecode
            ) !== false
        ) {
            return $pemFromFirstDecode;
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Developer Portal قد يرجع Base64 داخل Base64
        |--------------------------------------------------------------------------
        */
        $innerBase64 = preg_replace(
            '/\s+/',
            '',
            $decoded1
        );

        $decoded2 = base64_decode(
            $innerBase64,
            true
        );

        if ($decoded2 === false) {
            throw new RuntimeException(
                'تعذر فك binarySecurityToken إلى شهادة X.509.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | لو الناتج الثاني PEM
        |--------------------------------------------------------------------------
        */
        if (str_contains(
            $decoded2,
            '-----BEGIN CERTIFICATE-----'
        )) {
            return $this->normalizePem(
                $decoded2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | وإلا الناتج الثاني DER
        |--------------------------------------------------------------------------
        */
        $pem = $this->derToPem(
            $decoded2
        );

        if (
            @openssl_x509_read($pem)
            === false
        ) {
            throw new RuntimeException(
                'تم فك binarySecurityToken ولكن الناتج ليس شهادة X.509 صالحة.'
            );
        }

        return $pem;
    }

    public function certificate(
        ZatcaDevice $device
    ): OpenSSLCertificate {
        $certificate = openssl_x509_read(
            $this->certificatePem($device)
        );

        if (! $certificate) {
            throw new RuntimeException(
                'تعذر قراءة شهادة ZATCA بواسطة OpenSSL.'
            );
        }

        return $certificate;
    }

    public function publicKey(
        ZatcaDevice $device
    ): OpenSSLAsymmetricKey {
        $publicKey = openssl_pkey_get_public(
            $this->certificate($device)
        );

        if (! $publicKey) {
            throw new RuntimeException(
                'تعذر استخراج المفتاح العام من شهادة ZATCA.'
            );
        }

        return $publicKey;
    }

    public function publicKeyPem(
        ZatcaDevice $device
    ): string {
        $details = openssl_pkey_get_details(
            $this->publicKey($device)
        );

        if (
            ! is_array($details)
            || empty($details['key'])
        ) {
            throw new RuntimeException(
                'تعذر استخراج Public Key PEM.'
            );
        }

        return $details['key'];
    }

    public function certificateInfo(
        ZatcaDevice $device
    ): array {
        $info = openssl_x509_parse(
            $this->certificate($device)
        );

        if (! is_array($info)) {
            throw new RuntimeException(
                'تعذر قراءة بيانات شهادة ZATCA.'
            );
        }

        return $info;
    }

    public function serialNumber(
        ZatcaDevice $device
    ): string {
        $info = $this->certificateInfo(
            $device
        );

        return (string) (
            $info['serialNumber']
            ?? $info['serialNumberHex']
            ?? ''
        );
    }

    public function issuerName(
        ZatcaDevice $device
    ): string {
        $info = $this->certificateInfo(
            $device
        );

        $issuer = $info['issuer'] ?? null;

        if (! is_array($issuer)) {
            throw new RuntimeException(
                'لم يتم العثور على Issuer داخل الشهادة.'
            );
        }

        $parts = [];

        foreach ($issuer as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = $key . '=' . $item;
                }

                continue;
            }

            $parts[] = $key . '=' . $value;
        }

        return implode(
            ', ',
            $parts
        );
    }

    public function certificateBody(
        ZatcaDevice $device
    ): string {
        $pem = $this->certificatePem(
            $device
        );

        return preg_replace(
            [
                '/-----BEGIN CERTIFICATE-----/',
                '/-----END CERTIFICATE-----/',
                '/\s+/',
            ],
            '',
            $pem
        ) ?? '';
    }

    public function certificateHash(
        ZatcaDevice $device
    ): string {
        $pem = $this->certificatePem($device);

        $body = preg_replace(
            [
                '/-----BEGIN CERTIFICATE-----/',
                '/-----END CERTIFICATE-----/',
                '/\s+/',
            ],
            '',
            $pem
        );

        if (! $body) {
            throw new RuntimeException(
                'تعذر استخراج Certificate DER.'
            );
        }

        $der = base64_decode(
            $body,
            true
        );

        if ($der === false) {
            throw new RuntimeException(
                'Certificate DER غير صالح.'
            );
        }

        return base64_encode(
            hash(
                'sha256',
                $der,
                true
            )
        );
    }


    private function normalizePem(
        string $pem
    ): string {
        $body = preg_replace(
            [
                '/-----BEGIN CERTIFICATE-----/',
                '/-----END CERTIFICATE-----/',
                '/\s+/',
            ],
            '',
            trim($pem)
        );

        if (! $body) {
            throw new RuntimeException(
                'الشهادة PEM غير صالحة.'
            );
        }

        return
            "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(
                $body,
                64,
                "\n"
            )
            . "-----END CERTIFICATE-----\n";
    }

    private function derToPem(
        string $der
    ): string {
        return
            "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(
                base64_encode($der),
                64,
                "\n"
            )
            . "-----END CERTIFICATE-----\n";
    }


    public function certificateBase64(
        ZatcaDevice $device
    ): string {
        $pem = $this->certificatePem(
            $device
        );

        $body = preg_replace(
            [
                '/-----BEGIN CERTIFICATE-----/',
                '/-----END CERTIFICATE-----/',
                '/\s+/',
            ],
            '',
            $pem
        );

        if (! $body) {
            throw new RuntimeException(
                'تعذر تجهيز X509 Certificate.'
            );
        }

        return $body;
    }

    public function publicKeyDer(
        ZatcaDevice $device
    ): string {
        $pem = $this->publicKeyPem(
            $device
        );

        $body = preg_replace(
            [
                '/-----BEGIN PUBLIC KEY-----/',
                '/-----END PUBLIC KEY-----/',
                '/\s+/',
            ],
            '',
            $pem
        );

        if (! $body) {
            throw new RuntimeException(
                'تعذر استخراج Public Key DER.'
            );
        }

        $der = base64_decode(
            $body,
            true
        );

        if ($der === false) {
            throw new RuntimeException(
                'Public Key DER غير صالح.'
            );
        }

        return $der;
    }

    public function certificateSignature(
        ZatcaDevice $device
    ): string {
        $certificateBase64 =
            $this->certificateBase64(
                $device
            );

        $der = base64_decode(
            $certificateBase64,
            true
        );

        if ($der === false) {
            throw new RuntimeException(
                'Certificate DER غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | X.509 Certificate
        |--------------------------------------------------------------------------
        |
        | Certificate ::= SEQUENCE {
        |     tbsCertificate       TBSCertificate,
        |     signatureAlgorithm   AlgorithmIdentifier,
        |     signatureValue       BIT STRING
        | }
        |
        */

        $offset = 0;

        $root = $this->readDerElement(
            $der,
            $offset
        );

        if ($root['tag'] !== 0x30) {
            throw new RuntimeException(
                'X509 Certificate structure غير صالح.'
            );
        }

        $content = $root['value'];
        $innerOffset = 0;

        // TBSCertificate
        $this->readDerElement(
            $content,
            $innerOffset
        );

        // Signature Algorithm
        $this->readDerElement(
            $content,
            $innerOffset
        );

        // Signature BIT STRING
        $signatureElement =
            $this->readDerElement(
                $content,
                $innerOffset
            );

        if ($signatureElement['tag'] !== 0x03) {
            throw new RuntimeException(
                'Certificate Signature BIT STRING غير موجود.'
            );
        }

        $value = $signatureElement['value'];

        if ($value === '') {
            throw new RuntimeException(
                'Certificate Signature فارغ.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | First byte = number of unused bits
        |--------------------------------------------------------------------------
        */

        $unusedBits = ord(
            $value[0]
        );

        if ($unusedBits !== 0) {
            throw new RuntimeException(
                'Certificate Signature BIT STRING غير مدعوم.'
            );
        }

        return substr(
            $value,
            1
        );
    }

    private function readDerElement(
        string $der,
        int &$offset
    ): array {
        $totalLength = strlen(
            $der
        );

        if ($offset >= $totalLength) {
            throw new RuntimeException(
                'DER data انتهت بشكل غير متوقع.'
            );
        }

        $tag = ord(
            $der[$offset]
        );

        $offset++;

        if ($offset >= $totalLength) {
            throw new RuntimeException(
                'DER length غير موجود.'
            );
        }

        $firstLengthByte = ord(
            $der[$offset]
        );

        $offset++;

        if (
            ($firstLengthByte & 0x80)
            === 0
        ) {
            $length =
                $firstLengthByte;
        } else {
            $lengthBytes =
                $firstLengthByte & 0x7F;

            if (
                $lengthBytes === 0
                || $lengthBytes > 4
            ) {
                throw new RuntimeException(
                    'DER length غير مدعوم.'
                );
            }

            if (
                $offset + $lengthBytes
                > $totalLength
            ) {
                throw new RuntimeException(
                    'DER length غير صالح.'
                );
            }

            $length = 0;

            for (
                $i = 0;
                $i < $lengthBytes;
                $i++
            ) {
                $length =
                    ($length << 8)
                    | ord($der[$offset]);

                $offset++;
            }
        }

        if (
            $offset + $length
            > $totalLength
        ) {
            throw new RuntimeException(
                'DER element تجاوز حجم البيانات.'
            );
        }

        $value = substr(
            $der,
            $offset,
            $length
        );

        $offset += $length;

        return [
            'tag' => $tag,
            'length' => $length,
            'value' => $value,
        ];
    }

}
