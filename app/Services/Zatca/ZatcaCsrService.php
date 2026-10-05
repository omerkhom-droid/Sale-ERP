<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use Exception;

class ZatcaCsrService
{
    public function generate(ZatcaDevice $device): ZatcaDevice
    {
        $privateKey = $this->generatePrivateKey();

        $config = $this->buildOpenSslConfig($device);

        $tempConfig = tempnam(sys_get_temp_dir(), 'zatca_');

        file_put_contents($tempConfig, $config);

        try {
            $keyResource = openssl_pkey_get_private($privateKey);

            if (! $keyResource) {
                throw new Exception(
                    'تعذر قراءة المفتاح الخاص.'
                );
            }

            $dn = [
                'countryName' => $device->country_name,
                'organizationName' => $device->organization_name,
                'organizationalUnitName' => $device->organization_unit_name,
                'commonName' => $device->common_name,
            ];

            $csr = openssl_csr_new(
                $dn,
                $keyResource,
                [
                    'config' => $tempConfig,
                    'digest_alg' => 'sha256',
                    'req_extensions' => 'req_ext',
                ]
            );

            if (! $csr) {
                throw new Exception(
                    'فشل إنشاء CSR الخاص بـ ZATCA.'
                );
            }

            if (! openssl_csr_export($csr, $csrPem)) {
                throw new Exception(
                    'فشل تصدير CSR.'
                );
            }

            $device->update([
                'private_key' => $privateKey,
                'csr' => $csrPem,
                'status' => 'csr_generated',
            ]);

            return $device->fresh();

        } finally {
            @unlink($tempConfig);
        }
    }

    private function generatePrivateKey(): string
    {
        $openssl = config(
            'services.zatca.openssl_path',
            'openssl'
        );

        $privateKeyPath = tempnam(
            sys_get_temp_dir(),
            'zatca_private_'
        );

        if (! $privateKeyPath) {
            throw new \Exception(
                'تعذر إنشاء ملف مؤقت للمفتاح الخاص.'
            );
        }

        try {

            $command = sprintf(
                '"%s" ecparam -name secp256k1 -genkey -noout -out "%s" 2>&1',
                $openssl,
                $privateKeyPath
            );

            exec(
                $command,
                $output,
                $exitCode
            );

            if ($exitCode !== 0) {
                throw new \Exception(
                    'فشل OpenSSL في إنشاء المفتاح: '
                    . implode(' ', $output)
                );
            }

            $privateKey = file_get_contents(
                $privateKeyPath
            );

            if (! $privateKey) {
                throw new \Exception(
                    'تم تنفيذ OpenSSL ولكن لم يتم إنشاء المفتاح الخاص.'
                );
            }

            return $privateKey;

        } finally {

            if (file_exists($privateKeyPath)) {
                @unlink($privateKeyPath);
            }
        }
    }

//     private function buildOpenSslConfig(
//         ZatcaDevice $device
//     ): string {

//         $certificateTemplate = $device->environment === 'production'
//             ? 'ZATCA-Code-Signing'
//             : 'PREZATCA-Code-Signing';

//         return <<<CONF
// oid_section = OIDs

// [ OIDs ]
// certificateTemplateName = 1.3.6.1.4.1.311.20.2

// [ req ]
// prompt = no
// utf8 = yes
// default_md = sha256
// distinguished_name = dn
// req_extensions = req_ext

// [ dn ]
// C = {$device->country_name}
// O = {$device->organization_name}
// OU = {$device->organization_unit_name}
// CN = {$device->common_name}

// [ req_ext ]
// certificateTemplateName = ASN1:PRINTABLESTRING:{$certificateTemplate}
// subjectAltName = dirName:alt_names

// [ alt_names ]
// SN = {$device->egs_serial_number}
// UID = {$device->organization_identifier}
// title = {$device->invoice_type}
// registeredAddress = {$device->registered_address}
// businessCategory = {$device->business_category}

// CONF;
//     }

    private function buildOpenSslConfig(
        ZatcaDevice $device
    ): string {

        return <<<CONF
    oid_section = OIDs

    [ OIDs ]
    certificateTemplateName = 1.3.6.1.4.1.311.20.2

    [ req ]
    prompt = no
    utf8 = yes
    default_md = sha256
    distinguished_name = dn
    req_extensions = req_ext

    [ dn ]
    C = {$device->country_name}
    OU = {$device->organization_unit_name}
    O = {$device->organization_name}
    CN = {$device->common_name}

    [ req_ext ]
    certificateTemplateName = ASN1:PRINTABLESTRING:ZATCA-Code-Signing
    subjectAltName = dirName:alt_names

    [ alt_names ]
    SN = {$device->egs_serial_number}
    UID = {$device->organization_identifier}
    title = {$device->invoice_type}
    registeredAddress = {$device->registered_address}
    businessCategory = {$device->business_category}

    CONF;
    }


}