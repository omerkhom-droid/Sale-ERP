<?php

namespace App\Services\Zatca;

use App\Models\ZatcaDevice;
use DOMDocument;
use DOMElement;
use RuntimeException;

class ZatcaXadesService
{
    private const DS =
        'http://www.w3.org/2000/09/xmldsig#';

    private const XADES =
        'http://uri.etsi.org/01903/v1.3.2#';

    private const SIG =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2';

    private const SAC =
        'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2';

    private const SBC =
        'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2';

    private const CBC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    public function __construct(
        private ZatcaCertificateService $certificateService
    ) {
    }

    public function buildSignedProperties(
        ZatcaDevice $device,
        string $signingTime
    ): string {
        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $signedProperties =
            $document->createElementNS(
                self::XADES,
                'xades:SignedProperties'
            );

        $signedProperties->setAttribute(
            'Id',
            'xadesSignedProperties'
        );

        $document->appendChild(
            $signedProperties
        );

        $signedSignatureProperties =
            $document->createElementNS(
                self::XADES,
                'xades:SignedSignatureProperties'
            );

        $signedProperties->appendChild(
            $signedSignatureProperties
        );

        /*
        |--------------------------------------------------------------------------
        | Signing Time
        |--------------------------------------------------------------------------
        */

        $signingTimeElement =
            $document->createElementNS(
                self::XADES,
                'xades:SigningTime',
                $signingTime
            );

        $signedSignatureProperties->appendChild(
            $signingTimeElement
        );

        /*
        |--------------------------------------------------------------------------
        | Signing Certificate
        |--------------------------------------------------------------------------
        */

        $signingCertificate =
            $document->createElementNS(
                self::XADES,
                'xades:SigningCertificate'
            );

        $signedSignatureProperties->appendChild(
            $signingCertificate
        );

        $cert =
            $document->createElementNS(
                self::XADES,
                'xades:Cert'
            );

        $signingCertificate->appendChild(
            $cert
        );

        /*
        |--------------------------------------------------------------------------
        | Certificate Digest
        |--------------------------------------------------------------------------
        */

        $certDigest =
            $document->createElementNS(
                self::XADES,
                'xades:CertDigest'
            );

        $cert->appendChild(
            $certDigest
        );

        $digestMethod =
            $document->createElementNS(
                self::DS,
                'ds:DigestMethod'
            );

        $digestMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2001/04/xmlenc#sha256'
        );

        $certDigest->appendChild(
            $digestMethod
        );

        $digestValue =
            $document->createElementNS(
                self::DS,
                'ds:DigestValue',
                $this->certificateService
                    ->certificateHash($device)
            );

        $certDigest->appendChild(
            $digestValue
        );

        /*
        |--------------------------------------------------------------------------
        | Issuer Serial
        |--------------------------------------------------------------------------
        */

        $issuerSerial =
            $document->createElementNS(
                self::XADES,
                'xades:IssuerSerial'
            );

        $cert->appendChild(
            $issuerSerial
        );

        $issuerName =
            $document->createElementNS(
                self::DS,
                'ds:X509IssuerName'
            );

        $issuerName->appendChild(
            $document->createTextNode(
                $this->certificateService
                    ->issuerName($device)
            )
        );

        $issuerSerial->appendChild(
            $issuerName
        );

        $serialNumber =
            $document->createElementNS(
                self::DS,
                'ds:X509SerialNumber'
            );

        $serialNumber->appendChild(
            $document->createTextNode(
                $this->certificateService
                    ->serialNumber($device)
            )
        );

        $issuerSerial->appendChild(
            $serialNumber
        );

        return $document->saveXML(
            $signedProperties
        );
    }

    public function signedPropertiesHash(
        ZatcaDevice $device,
        string $signingTime
    ): string {
        $xml = $this->buildSignedProperties(
            $device,
            $signingTime
        );

        /*
        |--------------------------------------------------------------------------
        | ZATCA Step 5
        |--------------------------------------------------------------------------
        |
        | الهيئة تصف العملية بأنها:
        | - أخذ SignedProperties فقط
        | - Linearize XML
        | - إزالة المسافات
        | - SHA-256
        | - Base64
        |
        */

        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        if (! $document->loadXML(
            $xml,
            LIBXML_NONET
        )) {
            throw new RuntimeException(
                'SignedProperties XML غير صالح.'
            );
        }

        $linearized = $document->saveXML(
            $document->documentElement
        );

        if (
            $linearized === false
            || $linearized === ''
        ) {
            throw new RuntimeException(
                'فشل تجهيز SignedProperties.'
            );
        }

        return base64_encode(
            hash(
                'sha256',
                $linearized,
                true
            )
        );
    }

    public function buildSignatureExtension(
        ZatcaDevice $device,
        string $invoiceHash,
        string $signatureValue,
        string $signingTime
    ): string {
        $signedPropertiesHash =
            $this->signedPropertiesHash(
                $device,
                $signingTime
            );

        $certificate =
            $this->certificateService
                ->certificateBase64($device);

        $signedProperties =
            $this->buildSignedProperties(
                $device,
                $signingTime
            );

        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        /*
        |--------------------------------------------------------------------------
        | sig:UBLDocumentSignatures
        |--------------------------------------------------------------------------
        */

        $ublSignatures =
            $document->createElementNS(
                self::SIG,
                'sig:UBLDocumentSignatures'
            );

        $document->appendChild(
            $ublSignatures
        );

        /*
        |--------------------------------------------------------------------------
        | sac:SignatureInformation
        |--------------------------------------------------------------------------
        */

        $signatureInformation =
            $document->createElementNS(
                self::SAC,
                'sac:SignatureInformation'
            );

        $ublSignatures->appendChild(
            $signatureInformation
        );


        /*
        |--------------------------------------------------------------------------
        | Signature Information ID
        |--------------------------------------------------------------------------
        */

        $signatureInformationId =
            $document->createElementNS(
                self::CBC,
                'cbc:ID',
                'urn:oasis:names:specification:ubl:signature:1'
            );

        $signatureInformation->appendChild(
            $signatureInformationId
        );

        /*
        |--------------------------------------------------------------------------
        | Referenced Signature ID
        |--------------------------------------------------------------------------
        */

        $referencedSignatureId =
            $document->createElementNS(
                self::SBC,
                'sbc:ReferencedSignatureID',
                'urn:oasis:names:specification:ubl:signature:Invoice'
            );

        $signatureInformation->appendChild(
            $referencedSignatureId
        );

        
        /*
        |--------------------------------------------------------------------------
        | ds:Signature
        |--------------------------------------------------------------------------
        */

        $signature =
            $document->createElementNS(
                self::DS,
                'ds:Signature'
            );

        $signature->setAttribute(
            'Id',
            'signature'
        );

        $signatureInformation->appendChild(
            $signature
        );

        /*
        |--------------------------------------------------------------------------
        | ds:SignedInfo
        |--------------------------------------------------------------------------
        */

        $signedInfo =
            $document->createElementNS(
                self::DS,
                'ds:SignedInfo'
            );

        $signature->appendChild(
            $signedInfo
        );

        $canonicalizationMethod =
            $document->createElementNS(
                self::DS,
                'ds:CanonicalizationMethod'
            );

        $canonicalizationMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2006/12/xml-c14n11'
        );

        $signedInfo->appendChild(
            $canonicalizationMethod
        );

        $signatureMethod =
            $document->createElementNS(
                self::DS,
                'ds:SignatureMethod'
            );

        $signatureMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'
        );

        $signedInfo->appendChild(
            $signatureMethod
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Reference
        |--------------------------------------------------------------------------
        */

        $invoiceReference =
            $document->createElementNS(
                self::DS,
                'ds:Reference'
            );

        $invoiceReference->setAttribute(
            'Id',
            'invoiceSignedData'
        );

        $invoiceReference->setAttribute(
            'URI',
            ''
        );

        $signedInfo->appendChild(
            $invoiceReference
        );

        $transforms =
            $document->createElementNS(
                self::DS,
                'ds:Transforms'
            );

        $invoiceReference->appendChild(
            $transforms
        );

        $this->appendXPathTransform(
            $document,
            $transforms,
            'not(//ancestor-or-self::ext:UBLExtensions)'
        );

        $this->appendXPathTransform(
            $document,
            $transforms,
            'not(//ancestor-or-self::cac:Signature)'
        );

        $this->appendXPathTransform(
            $document,
            $transforms,
            'not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID="QR"])'
        );

        $c14nTransform =
            $document->createElementNS(
                self::DS,
                'ds:Transform'
            );

        $c14nTransform->setAttribute(
            'Algorithm',
            'http://www.w3.org/2006/12/xml-c14n11'
        );

        $transforms->appendChild(
            $c14nTransform
        );

        $invoiceDigestMethod =
            $document->createElementNS(
                self::DS,
                'ds:DigestMethod'
            );

        $invoiceDigestMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2001/04/xmlenc#sha256'
        );

        $invoiceReference->appendChild(
            $invoiceDigestMethod
        );

        $invoiceDigest =
            $document->createElementNS(
                self::DS,
                'ds:DigestValue',
                $invoiceHash
            );

        $invoiceReference->appendChild(
            $invoiceDigest
        );

        /*
        |--------------------------------------------------------------------------
        | SignedProperties Reference
        |--------------------------------------------------------------------------
        */

        $propertiesReference =
            $document->createElementNS(
                self::DS,
                'ds:Reference'
            );

        $propertiesReference->setAttribute(
            'Type',
            'http://uri.etsi.org/01903#SignedProperties'
        );

        $propertiesReference->setAttribute(
            'URI',
            '#xadesSignedProperties'
        );

        $signedInfo->appendChild(
            $propertiesReference
        );

        $propertiesDigestMethod =
            $document->createElementNS(
                self::DS,
                'ds:DigestMethod'
            );

        $propertiesDigestMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2001/04/xmlenc#sha256'
        );

        $propertiesReference->appendChild(
            $propertiesDigestMethod
        );

        $propertiesDigest =
            $document->createElementNS(
                self::DS,
                'ds:DigestValue',
                $signedPropertiesHash
            );

        $propertiesReference->appendChild(
            $propertiesDigest
        );

        /*
        |--------------------------------------------------------------------------
        | Signature Value
        |--------------------------------------------------------------------------
        */

        $signatureValueElement =
            $document->createElementNS(
                self::DS,
                'ds:SignatureValue',
                $signatureValue
            );

        $signature->appendChild(
            $signatureValueElement
        );

        /*
        |--------------------------------------------------------------------------
        | KeyInfo
        |--------------------------------------------------------------------------
        */

        $keyInfo =
            $document->createElementNS(
                self::DS,
                'ds:KeyInfo'
            );

        $signature->appendChild(
            $keyInfo
        );

        $x509Data =
            $document->createElementNS(
                self::DS,
                'ds:X509Data'
            );

        $keyInfo->appendChild(
            $x509Data
        );

        $x509Certificate =
            $document->createElementNS(
                self::DS,
                'ds:X509Certificate',
                $certificate
            );

        $x509Data->appendChild(
            $x509Certificate
        );

        /*
        |--------------------------------------------------------------------------
        | ds:Object / XAdES
        |--------------------------------------------------------------------------
        */

        $object =
            $document->createElementNS(
                self::DS,
                'ds:Object'
            );

        $signature->appendChild(
            $object
        );

        $qualifyingProperties =
            $document->createElementNS(
                self::XADES,
                'xades:QualifyingProperties'
            );

        $qualifyingProperties->setAttribute(
            'Target',
            '#signature'
        );

        $object->appendChild(
            $qualifyingProperties
        );

        $fragment = $document->createDocumentFragment();

        if (! $fragment->appendXML(
            $signedProperties
        )) {
            throw new RuntimeException(
                'فشل دمج SignedProperties.'
            );
        }

        $qualifyingProperties->appendChild(
            $fragment
        );

        return $document->saveXML(
            $ublSignatures
        );
    }

    private function appendXPathTransform(
        DOMDocument $document,
        DOMElement $transforms,
        string $xpath
    ): void {
        $transform =
            $document->createElementNS(
                self::DS,
                'ds:Transform'
            );

        $transform->setAttribute(
            'Algorithm',
            'http://www.w3.org/TR/1999/REC-xpath-19991116'
        );

        $xpathElement =
            $document->createElementNS(
                self::DS,
                'ds:XPath'
            );

        $xpathElement->appendChild(
            $document->createTextNode(
                $xpath
            )
        );

        $transform->appendChild(
            $xpathElement
        );

        $transforms->appendChild(
            $transform
        );
    }
}