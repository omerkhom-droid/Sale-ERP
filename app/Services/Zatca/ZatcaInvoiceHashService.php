<?php

namespace App\Services\Zatca;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;


class ZatcaInvoiceHashService
{
    private const NS_UBL =
        'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    private const NS_EXT =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    private const NS_CAC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const NS_CBC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    /**
     * يرجع Invoice Hash بصيغة Base64 المطلوبة من ZATCA.
     */
    public function hash(string $xml): string
    {
        $canonicalXml = $this->canonicalize($xml);

        /*
        |--------------------------------------------------------------------------
        | SHA-256 binary
        |--------------------------------------------------------------------------
        |
        | true مهمة جدًا.
        |
        | نريد 32-byte binary وليس HEX string.
        |
        */

        $binaryHash = hash(
            'sha256',
            $canonicalXml,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Base64 Digest
        |--------------------------------------------------------------------------
        */

        return base64_encode($binaryHash);
    }

    /**
     * يرجع SHA-256 كـ HEX للتشخيص فقط.
     */
    public function hashHex(string $xml): string
    {
        return hash(
            'sha256',
            $this->canonicalize($xml)
        );
    }

    /**
     * تجهيز XML قبل حساب الـ Hash.
     */
    public function canonicalize(string $xml): string
    {
        $document = $this->loadXml($xml);

        $this->removeWhitespaceOnlyTextNodes(
            $document
        );
        
        $xpath = new DOMXPath($document);

        $xpath->registerNamespace(
            'ubl',
            self::NS_UBL
        );

        $xpath->registerNamespace(
            'ext',
            self::NS_EXT
        );

        $xpath->registerNamespace(
            'cac',
            self::NS_CAC
        );

        $xpath->registerNamespace(
            'cbc',
            self::NS_CBC
        );

        /*
        |--------------------------------------------------------------------------
        | 1. Remove UBLExtensions
        |--------------------------------------------------------------------------
        */

        $this->removeNodes(
            $xpath,
            '/*/ext:UBLExtensions'
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Remove QR AdditionalDocumentReference
        |--------------------------------------------------------------------------
        */

        $this->removeNodes(
            $xpath,
            '/*/cac:AdditionalDocumentReference'
                . '[normalize-space(cbc:ID) = "QR"]'
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Remove cac:Signature
        |--------------------------------------------------------------------------
        */

        $this->removeNodes(
            $xpath,
            '/*/cac:Signature'
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Canonicalize
        |--------------------------------------------------------------------------
        |
        | بدون XML declaration.
        |
        */

        $canonicalXml = $document->documentElement?->C14N(
            false,
            false
        );

        if (
            $canonicalXml === false
            || $canonicalXml === ''
        ) {
            throw new RuntimeException(
                'فشل Canonicalization لفاتورة ZATCA.'
            );
        }

        return $canonicalXml;
    }

    private function loadXml(
        string $xml
    ): DOMDocument {
        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize XML whitespace
        |--------------------------------------------------------------------------
        |
        | مهم جدًا:
        | نريد أن تكون نتيجة DOM واحدة سواء كان XML
        | منسقًا بأسطر ومسافات أو مضغوطًا.
        |
        */

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        libxml_use_internal_errors(true);

        $loaded = $document->loadXML(
            $xml,
            LIBXML_NONET
        );

        if (! $loaded) {
            libxml_clear_errors();

            throw new RuntimeException(
                'Invoice XML غير صالح.'
            );
        }

        libxml_clear_errors();

        return $document;
    }

    private function removeNodes(
        DOMXPath $xpath,
        string $expression
    ): void {
        $nodes = $xpath->query(
            $expression
        );

        if ($nodes === false) {
            throw new RuntimeException(
                'فشل XPath أثناء تجهيز Invoice Hash: '
                . $expression
            );
        }

        /*
        |--------------------------------------------------------------------------
        | نحفظ العناصر أولًا
        |--------------------------------------------------------------------------
        |
        | حتى لا نعدّل DOMNodeList أثناء الدوران عليه.
        |
        */

        $toRemove = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild(
                $node
            );
        }
    }


    private function removeWhitespaceOnlyTextNodes(
        \DOMNode $node
    ): void {
        for (
            $i = $node->childNodes->length - 1;
            $i >= 0;
            $i--
        ) {
            $child = $node->childNodes->item($i);

            if (! $child) {
                continue;
            }

            if (
                $child->nodeType === XML_TEXT_NODE
                && trim($child->nodeValue ?? '') === ''
            ) {
                $node->removeChild($child);

                continue;
            }

            if ($child->hasChildNodes()) {
                $this->removeWhitespaceOnlyTextNodes(
                    $child
                );
            }
        }
    }

}