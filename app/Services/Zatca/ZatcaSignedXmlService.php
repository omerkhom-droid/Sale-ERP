<?php

namespace App\Services\Zatca;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class ZatcaSignedXmlService
{
    private const EXT =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    private const CAC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const CBC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    public function injectSignature(
        string $invoiceXml,
        string $signatureExtension
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Load Invoice XML
        |--------------------------------------------------------------------------
        */

        $invoice = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $invoice->preserveWhiteSpace = false;
        $invoice->formatOutput = false;

        if (! $invoice->loadXML(
            $invoiceXml,
            LIBXML_NONET
        )) {
            throw new RuntimeException(
                'Invoice XML غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find UBLExtensions
        |--------------------------------------------------------------------------
        */

        $xpath = new DOMXPath(
            $invoice
        );

        $xpath->registerNamespace(
            'ext',
            self::EXT
        );

        $xpath->registerNamespace(
            'cac',
            self::CAC
        );

        $xpath->registerNamespace(
            'cbc',
            self::CBC
        );

        $extensions = $xpath->query(
            '/*/ext:UBLExtensions'
        );

        if (
            $extensions === false
            || $extensions->length === 0
        ) {
            throw new RuntimeException(
                'ext:UBLExtensions غير موجود داخل الفاتورة.'
            );
        }

        $ublExtensions =
            $extensions->item(0);

        if (! $ublExtensions instanceof DOMElement) {
            throw new RuntimeException(
                'UBLExtensions غير صالح.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find or create UBLExtension
        |--------------------------------------------------------------------------
        */

        $ublExtension = null;

        foreach (
            $ublExtensions->childNodes
            as $child
        ) {
            if (
                $child instanceof DOMElement
                && $child->localName === 'UBLExtension'
                && $child->namespaceURI === self::EXT
            ) {
                $ublExtension = $child;
                break;
            }
        }

        if (! $ublExtension) {
            $ublExtension =
                $invoice->createElementNS(
                    self::EXT,
                    'ext:UBLExtension'
                );

            $ublExtensions->appendChild(
                $ublExtension
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find or create ExtensionContent
        |--------------------------------------------------------------------------
        */

        $extensionContent = null;

        foreach (
            $ublExtension->childNodes
            as $child
        ) {
            if (
                $child instanceof DOMElement
                && $child->localName === 'ExtensionContent'
                && $child->namespaceURI === self::EXT
            ) {
                $extensionContent = $child;
                break;
            }
        }

        if (! $extensionContent) {
            $extensionContent =
                $invoice->createElementNS(
                    self::EXT,
                    'ext:ExtensionContent'
                );

            $ublExtension->appendChild(
                $extensionContent
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clear old signature
        |--------------------------------------------------------------------------
        |
        | مهم عند إعادة توقيع نفس XML أثناء الاختبارات.
        |
        */

        while (
            $extensionContent->firstChild
        ) {
            $extensionContent->removeChild(
                $extensionContent->firstChild
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load Signature Fragment
        |--------------------------------------------------------------------------
        */

        $signatureDocument =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $signatureDocument->preserveWhiteSpace =
            false;

        $signatureDocument->formatOutput =
            false;

        if (! $signatureDocument->loadXML(
            $signatureExtension,
            LIBXML_NONET
        )) {
            throw new RuntimeException(
                'Signature Extension XML غير صالح.'
            );
        }

        if (
            ! $signatureDocument
                ->documentElement
        ) {
            throw new RuntimeException(
                'Signature Extension فارغ.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Import into original invoice
        |--------------------------------------------------------------------------
        */

        $signatureNode =
            $invoice->importNode(
                $signatureDocument
                    ->documentElement,
                true
            );

        $extensionContent->appendChild(
            $signatureNode
        );

        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        $xml = $invoice->saveXML();

        if ($xml === false) {
            throw new RuntimeException(
                'فشل إنشاء Signed Invoice XML.'
            );
        }

        return $xml;
    }


    public function injectQr(
        string $xml,
        string $qr
    ): string {
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
                'Invoice XML غير صالح.'
            );
        }

        $xpath = new DOMXPath(
            $document
        );

        $xpath->registerNamespace(
            'cac',
            self::CAC
        );

        $xpath->registerNamespace(
            'cbc',
            self::CBC
        );

        /*
        |--------------------------------------------------------------------------
        | Find existing QR reference
        |--------------------------------------------------------------------------
        */

        $existing = $xpath->query(
            "/*[local-name()='Invoice' or local-name()='CreditNote' or local-name()='DebitNote']"
            . "/*[local-name()='AdditionalDocumentReference']"
            . "[*[local-name()='ID' and normalize-space(text())='QR']]"
        );

        if (
            $existing !== false
            && $existing->length > 0
        ) {
            $qrReference =
                $existing->item(0);

            if (! $qrReference instanceof DOMElement) {
                throw new RuntimeException(
                    'QR AdditionalDocumentReference غير صالح.'
                );
            }

            while ($qrReference->firstChild) {
                $qrReference->removeChild(
                    $qrReference->firstChild
                );
            }
        } else {
            $qrReference =
                $document->createElementNS(
                    self::CAC,
                    'cac:AdditionalDocumentReference'
                );

            /*
            |--------------------------------------------------------------------------
            | مكان QR
            |--------------------------------------------------------------------------
            |
            | نضيفه قبل AccountingSupplierParty.
            |
            */

            $supplier = $xpath->query(
                "/*[local-name()='Invoice']"
                . "/*[local-name()='AccountingSupplierParty']"
            )?->item(0);

            if ($supplier) {
                $document->documentElement
                    ->insertBefore(
                        $qrReference,
                        $supplier
                    );
            } else {
                $document->documentElement
                    ->appendChild(
                        $qrReference
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | cbc:ID = QR
        |--------------------------------------------------------------------------
        */

        $id = $document->createElementNS(
            self::CBC,
            'cbc:ID',
            'QR'
        );

        $qrReference->appendChild(
            $id
        );

        /*
        |--------------------------------------------------------------------------
        | Attachment
        |--------------------------------------------------------------------------
        */

        $attachment =
            $document->createElementNS(
                self::CAC,
                'cac:Attachment'
            );

        $qrReference->appendChild(
            $attachment
        );

        $binaryObject =
            $document->createElementNS(
                self::CBC,
                'cbc:EmbeddedDocumentBinaryObject'
            );

        $binaryObject->setAttribute(
            'mimeCode',
            'text/plain'
        );

        $binaryObject->appendChild(
            $document->createTextNode(
                $qr
            )
        );

        $attachment->appendChild(
            $binaryObject
        );

        $result = $document->saveXML();

        if ($result === false) {
            throw new RuntimeException(
                'فشل إنشاء XML بعد إضافة QR.'
            );
        }

        return $result;
    }

}