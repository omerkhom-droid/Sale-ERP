<?php

namespace App\Services\Zatca;

use DOMDocument;
use InvalidArgumentException;

class ZatcaAdjustmentXmlService
{
    private const NS_INVOICE =
    'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    private const NS_CAC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const NS_CBC =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    private const NS_EXT =
        'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    public function generate(array $data): string
    {
        $this->validate($data);

        $xml = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;

        $invoice = $xml->createElementNS(
            self::NS_INVOICE,
            'Invoice'
        );

        $invoice->setAttribute(
            'xmlns:cac',
            self::NS_CAC
        );

        $invoice->setAttribute(
            'xmlns:cbc',
            self::NS_CBC
        );

        $invoice->setAttribute(
            'xmlns:ext',
            self::NS_EXT
        );

        $xml->appendChild($invoice);

        /*
        |--------------------------------------------------------------------------
        | UBL Extensions
        |--------------------------------------------------------------------------
        |
        | سيستخدم لاحقًا للتوقيع الرقمي.
        |
        */

        $extensions = $xml->createElement(
            'ext:UBLExtensions'
        );

        $extension = $xml->createElement(
            'ext:UBLExtension'
        );

        $extensionContent = $xml->createElement(
            'ext:ExtensionContent'
        );

        $extension->appendChild(
            $extensionContent
        );

        $extensions->appendChild(
            $extension
        );

        $invoice->appendChild(
            $extensions
        );

        /*
        |--------------------------------------------------------------------------
        | Profile ID
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'ProfileID',
            'reporting:1.0'
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Number
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'ID',
            $data['invoice_number']
        );

        /*
        |--------------------------------------------------------------------------
        | UUID
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'UUID',
            $data['uuid']
        );

        /*
        |--------------------------------------------------------------------------
        | Issue Date
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'IssueDate',
            $data['issue_date']
        );

        /*
        |--------------------------------------------------------------------------
        | Issue Time
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'IssueTime',
            $data['issue_time']
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Type
        |--------------------------------------------------------------------------
        */

        $InvoiceTypeCode = $xml->createElement(
            'cbc:InvoiceTypeCode'
        );

        $InvoiceTypeCode->setAttribute(
            'name',
            $data['invoice_type_name']
        );

        $InvoiceTypeCode->nodeValue =
            $data['invoice_type_code'];

        $invoice->appendChild(
            $InvoiceTypeCode
        );

        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $invoice,
            'DocumentCurrencyCode',
            $data['currency']
        );

        $this->addCbc(
            $xml,
            $invoice,
            'TaxCurrencyCode',
            'SAR'
        );

        if (empty($data['reference_invoice_number'])) {
            throw new InvalidArgumentException(
                'رقم الفاتورة الأصلية مطلوب للإشعار الدائن.'
            );
        }

        $billingReference = $xml->createElement(
            'cac:BillingReference'
        );

        $invoiceDocumentReference = $xml->createElement(
            'cac:InvoiceDocumentReference'
        );

        $this->addCbc(
            $xml,
            $invoiceDocumentReference,
            'ID',
            $data['reference_invoice_number']
        );

        $billingReference->appendChild(
            $invoiceDocumentReference
        );

        $invoice->appendChild(
            $billingReference
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Counter Value
        |--------------------------------------------------------------------------
        */

        $this->addAdditionalDocumentReference(
            xml: $xml,
            invoice: $invoice,
            id: 'ICV',
            value: (string) $data['icv']
        );

        /*
        |--------------------------------------------------------------------------
        | Previous Invoice Hash
        |--------------------------------------------------------------------------
        */

        $this->addPreviousInvoiceHash(
            $xml,
            $invoice,
            $data['previous_invoice_hash']
        );

        /*
        |--------------------------------------------------------------------------
        | QR placeholder
        |--------------------------------------------------------------------------
        |
        | سيتم استبداله لاحقًا بعد إنشاء QR الفعلي.
        |
        */

        $this->addQrReference(
            $xml,
            $invoice,
            $data['qr'] ?? ''
        );

        /*
        |--------------------------------------------------------------------------
        | UBL Signature Reference
        |--------------------------------------------------------------------------
        */

        $this->addSignatureReference(
            $xml,
            $invoice
        );

        /*
        |--------------------------------------------------------------------------
        | Supplier
        |--------------------------------------------------------------------------
        */

        $this->addSupplier(
            $xml,
            $invoice,
            $data['supplier']
        );

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $this->addCustomer(
            $xml,
            $invoice,
            $data['customer']
        );

        if (! empty($data['supply_date'])) {
            $delivery = $xml->createElement(
                'cac:Delivery'
            );

            $this->addCbc(
                $xml,
                $delivery,
                'ActualDeliveryDate',
                $data['supply_date']
            );

            $invoice->appendChild($delivery);
        }

        if (empty($data['reason'])) {
            throw new InvalidArgumentException(
                'سبب إصدار الإشعار الدائن مطلوب.'
            );
        }

        $paymentMeans = $xml->createElement(
            'cac:PaymentMeans'
        );

        $this->addCbc(
            $xml,
            $paymentMeans,
            'PaymentMeansCode',
            $data['payment_means_code'] ?? '10'
        );

        $this->addCbc(
            $xml,
            $paymentMeans,
            'InstructionNote',
            $data['reason']
        );

        $invoice->appendChild(
            $paymentMeans
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Total
        |--------------------------------------------------------------------------
        */

        $this->addTaxTotal(
            $xml,
            $invoice,
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Total In Tax Accounting Currency
        |--------------------------------------------------------------------------
        |
        | مطلوب عند وجود TaxCurrencyCode.
        | يجب أن يحتوي على TaxAmount فقط دون TaxSubtotal.
        |
        */

        $taxCurrencyTotal = $xml->createElement(
            'cac:TaxTotal'
        );

        $this->addAmount(
            $xml,
            $taxCurrencyTotal,
            'TaxAmount',
            $data['tax_total'],
            'SAR'
        );

        $invoice->appendChild(
            $taxCurrencyTotal
        );

        /*
        |--------------------------------------------------------------------------
        | Legal Monetary Total
        |--------------------------------------------------------------------------
        */

        $this->addLegalMonetaryTotal(
            $xml,
            $invoice,
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Lines
        |--------------------------------------------------------------------------
        */

        foreach ($data['lines'] as $line) {
            $this->addInvoiceLine(
                $xml,
                $invoice,
                $line,
                $data['currency']
            );
        }

        return $xml->saveXML();
    }

    private function addSupplier(
        DOMDocument $xml,
        \DOMElement $invoice,
        array $supplier
    ): void {
        $supplierParty = $xml->createElement(
            'cac:AccountingSupplierParty'
        );

        $party = $xml->createElement(
            'cac:Party'
        );

        /*
        |----------------------------------------------------------------------
        | CR / Registration Number
        |----------------------------------------------------------------------
        */

        if (! empty($supplier['registration_number'])) {
            $identification = $xml->createElement(
                'cac:PartyIdentification'
            );

            $id = $xml->createElement(
                'cbc:ID'
            );

            $id->setAttribute(
                'schemeID',
                $supplier['registration_scheme'] ?? 'CRN'
            );

            $id->nodeValue =
                $supplier['registration_number'];

            $identification->appendChild($id);

            $party->appendChild(
                $identification
            );
        }

        /*
        |----------------------------------------------------------------------
        | Address
        |----------------------------------------------------------------------
        */

        $address = $xml->createElement(
            'cac:PostalAddress'
        );

        $this->addCbc(
            $xml,
            $address,
            'StreetName',
            $supplier['street']
        );

        if (! empty($supplier['building_number'])) {
            $this->addCbc(
                $xml,
                $address,
                'BuildingNumber',
                $supplier['building_number']
            );
        }

        $this->addCbc(
            $xml,
            $address,
            'CitySubdivisionName',
            $supplier['district']
        );

        $this->addCbc(
            $xml,
            $address,
            'CityName',
            $supplier['city']
        );

        if (! empty($supplier['postal_code'])) {
            $this->addCbc(
                $xml,
                $address,
                'PostalZone',
                $supplier['postal_code']
            );
        }

        $country = $xml->createElement(
            'cac:Country'
        );

        $this->addCbc(
            $xml,
            $country,
            'IdentificationCode',
            $supplier['country_code'] ?? 'SA'
        );

        $address->appendChild($country);
        $party->appendChild($address);

        /*
        |----------------------------------------------------------------------
        | VAT
        |----------------------------------------------------------------------
        */

        $taxScheme = $xml->createElement(
            'cac:PartyTaxScheme'
        );

        $this->addCbc(
            $xml,
            $taxScheme,
            'CompanyID',
            $supplier['vat_number']
        );

        $tax = $xml->createElement(
            'cac:TaxScheme'
        );

        $this->addCbc(
            $xml,
            $tax,
            'ID',
            'VAT'
        );

        $taxScheme->appendChild($tax);

        $party->appendChild(
            $taxScheme
        );

        /*
        |----------------------------------------------------------------------
        | Legal Entity
        |----------------------------------------------------------------------
        */

        $legal = $xml->createElement(
            'cac:PartyLegalEntity'
        );

        $this->addCbc(
            $xml,
            $legal,
            'RegistrationName',
            $supplier['name']
        );

        $party->appendChild($legal);

        $supplierParty->appendChild($party);

        $invoice->appendChild(
            $supplierParty
        );
    }

    private function addCustomer(
        DOMDocument $xml,
        \DOMElement $invoice,
        array $customer
    ): void {
        $customerParty = $xml->createElement(
            'cac:AccountingCustomerParty'
        );

        $party = $xml->createElement(
            'cac:Party'
        );

        /*
        |--------------------------------------------------------------------------
        | Customer Registration Number
        |--------------------------------------------------------------------------
        */

        if (! empty($customer['registration_number'])) {
            $identification = $xml->createElement(
                'cac:PartyIdentification'
            );

            $id = $xml->createElement(
                'cbc:ID',
                htmlspecialchars(
                    (string) $customer['registration_number'],
                    ENT_XML1 | ENT_COMPAT,
                    'UTF-8'
                )
            );

            $id->setAttribute(
                'schemeID',
                $customer['registration_scheme'] ?? 'CRN'
            );

            $identification->appendChild($id);
            $party->appendChild($identification);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Address
        |--------------------------------------------------------------------------
        */

        $hasAddress =
            ! empty($customer['street'])
            || ! empty($customer['city'])
            || ! empty($customer['country_code']);

        if ($hasAddress) {
            $address = $xml->createElement(
                'cac:PostalAddress'
            );

            if (! empty($customer['street'])) {
                $this->addCbc(
                    $xml,
                    $address,
                    'StreetName',
                    $customer['street']
                );
            }

            if (! empty($customer['building_number'])) {
                $this->addCbc(
                    $xml,
                    $address,
                    'BuildingNumber',
                    $customer['building_number']
                );
            }

            if (! empty($customer['district'])) {
                $this->addCbc(
                    $xml,
                    $address,
                    'CitySubdivisionName',
                    $customer['district']
                );
            }

            if (! empty($customer['city'])) {
                $this->addCbc(
                    $xml,
                    $address,
                    'CityName',
                    $customer['city']
                );
            }

            if (! empty($customer['postal_code'])) {
                $this->addCbc(
                    $xml,
                    $address,
                    'PostalZone',
                    $customer['postal_code']
                );
            }

            if (! empty($customer['country_code'])) {
                $country = $xml->createElement(
                    'cac:Country'
                );

                $this->addCbc(
                    $xml,
                    $country,
                    'IdentificationCode',
                    $customer['country_code']
                );

                $address->appendChild($country);
            }

            $party->appendChild($address);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer VAT Number
        |--------------------------------------------------------------------------
        */

        if (! empty($customer['vat_number'])) {
            $taxScheme = $xml->createElement(
                'cac:PartyTaxScheme'
            );

            $this->addCbc(
                $xml,
                $taxScheme,
                'CompanyID',
                $customer['vat_number']
            );

            $tax = $xml->createElement(
                'cac:TaxScheme'
            );

            $this->addCbc(
                $xml,
                $tax,
                'ID',
                'VAT'
            );

            $taxScheme->appendChild($tax);
            $party->appendChild($taxScheme);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Name
        |--------------------------------------------------------------------------
        */

        if (! empty($customer['name'])) {
            $legal = $xml->createElement(
                'cac:PartyLegalEntity'
            );

            $this->addCbc(
                $xml,
                $legal,
                'RegistrationName',
                $customer['name']
            );

            $party->appendChild($legal);
        }

        $customerParty->appendChild($party);
        $invoice->appendChild($customerParty);
    }


    private function addTaxTotal(
        DOMDocument $xml,
        \DOMElement $invoice,
        array $data
    ): void {
        $currency = $data['currency'] ?? 'SAR';

        /*
        |--------------------------------------------------------------------------
        | Tax Total
        |--------------------------------------------------------------------------
        */

        $taxTotal = $xml->createElement(
            'cac:TaxTotal'
        );

        /*
        |--------------------------------------------------------------------------
        | Total Tax Amount
        |--------------------------------------------------------------------------
        */

        $this->addAmount(
            $xml,
            $taxTotal,
            'TaxAmount',
            $data['tax_total'],
            $currency
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Subtotal
        |--------------------------------------------------------------------------
        */

        $taxSubtotal = $xml->createElement(
            'cac:TaxSubtotal'
        );

        /*
        |--------------------------------------------------------------------------
        | Taxable Amount
        |--------------------------------------------------------------------------
        */

        $this->addAmount(
            $xml,
            $taxSubtotal,
            'TaxableAmount',
            $data['tax_exclusive_total'],
            $currency
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Amount
        |--------------------------------------------------------------------------
        */

        $this->addAmount(
            $xml,
            $taxSubtotal,
            'TaxAmount',
            $data['tax_total'],
            $currency
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Category
        |--------------------------------------------------------------------------
        */

        $taxCategory = $xml->createElement(
            'cac:TaxCategory'
        );

        $this->addCbc(
            $xml,
            $taxCategory,
            'ID',
            'S'
        );

        $this->addCbc(
            $xml,
            $taxCategory,
            'Percent',
            '15.00'
        );

        /*
        |--------------------------------------------------------------------------
        | Tax Scheme
        |--------------------------------------------------------------------------
        */

        $taxScheme = $xml->createElement(
            'cac:TaxScheme'
        );

        $this->addCbc(
            $xml,
            $taxScheme,
            'ID',
            'VAT'
        );

        $taxCategory->appendChild(
            $taxScheme
        );

        $taxSubtotal->appendChild(
            $taxCategory
        );

        $taxTotal->appendChild(
            $taxSubtotal
        );

        $invoice->appendChild(
            $taxTotal
        );
    }

    private function addLegalMonetaryTotal(
        DOMDocument $xml,
        \DOMElement $invoice,
        array $data
    ): void {
        $total = $xml->createElement(
            'cac:LegalMonetaryTotal'
        );

        $this->addAmount(
            $xml,
            $total,
            'LineExtensionAmount',
            $data['line_extension_total'],
            $data['currency']
        );

        $this->addAmount(
            $xml,
            $total,
            'TaxExclusiveAmount',
            $data['tax_exclusive_total'],
            $data['currency']
        );

        $this->addAmount(
            $xml,
            $total,
            'TaxInclusiveAmount',
            $data['tax_inclusive_total'],
            $data['currency']
        );

        $this->addAmount(
            $xml,
            $total,
            'PayableAmount',
            $data['payable_total'],
            $data['currency']
        );

        $invoice->appendChild($total);
    }

    private function addInvoiceLine(
        DOMDocument $xml,
        \DOMElement $invoice,
        array $line,
        string $currency
    ): void {
        $invoiceLine = $xml->createElement(
            'cac:InvoiceLine'
        );

        $this->addCbc(
            $xml,
            $invoiceLine,
            'ID',
            (string) $line['id']
        );

        $qty = $xml->createElement(
            'cbc:InvoicedQuantity',
            $this->quantity($line['quantity'])
        );

        $qty->setAttribute(
            'unitCode',
            $line['unit_code'] ?? 'PCE'
        );

        $invoiceLine->appendChild($qty);

        $this->addAmount(
            $xml,
            $invoiceLine,
            'LineExtensionAmount',
            $line['line_extension_amount'],
            $currency
        );

        /*
        |--------------------------------------------------------------------------
        | Line Tax
        |--------------------------------------------------------------------------
        */

        $taxTotal = $xml->createElement(
            'cac:TaxTotal'
        );

        $this->addAmount(
            $xml,
            $taxTotal,
            'TaxAmount',
            $line['tax_amount'],
            $currency
        );

        $this->addAmount(
            $xml,
            $taxTotal,
            'RoundingAmount',
            $line['tax_inclusive_amount'],
            $currency
        );

        $invoiceLine->appendChild(
            $taxTotal
        );

        /*
        |--------------------------------------------------------------------------
        | Item
        |--------------------------------------------------------------------------
        */

        $item = $xml->createElement(
            'cac:Item'
        );

        $this->addCbc(
            $xml,
            $item,
            'Name',
            $line['name']
        );

        $classifiedTax = $xml->createElement(
            'cac:ClassifiedTaxCategory'
        );

        $this->addCbc(
            $xml,
            $classifiedTax,
            'ID',
            $line['tax_category'] ?? 'S'
        );

        $this->addCbc(
            $xml,
            $classifiedTax,
            'Percent',
            $this->percentage(
                $line['tax_percent']
            )
        );

        $taxScheme = $xml->createElement(
            'cac:TaxScheme'
        );

        $this->addCbc(
            $xml,
            $taxScheme,
            'ID',
            'VAT'
        );

        $classifiedTax->appendChild(
            $taxScheme
        );

        $item->appendChild(
            $classifiedTax
        );

        $invoiceLine->appendChild($item);

        /*
        |--------------------------------------------------------------------------
        | Price
        |--------------------------------------------------------------------------
        */

        $price = $xml->createElement(
            'cac:Price'
        );

        $this->addAmount(
            $xml,
            $price,
            'PriceAmount',
            $line['unit_price'],
            $currency
        );

        $invoiceLine->appendChild($price);

        $invoice->appendChild(
            $invoiceLine
        );
    }

    private function addAdditionalDocumentReference(
        DOMDocument $xml,
        \DOMElement $invoice,
        string $id,
        string $value
    ): void {
        $reference = $xml->createElement(
            'cac:AdditionalDocumentReference'
        );

        $this->addCbc(
            $xml,
            $reference,
            'ID',
            $id
        );

        $this->addCbc(
            $xml,
            $reference,
            'UUID',
            $value
        );

        $invoice->appendChild($reference);
    }

    private function addPreviousInvoiceHash(
        DOMDocument $xml,
        \DOMElement $invoice,
        string $hash
    ): void {
        $reference = $xml->createElement(
            'cac:AdditionalDocumentReference'
        );

        $this->addCbc(
            $xml,
            $reference,
            'ID',
            'PIH'
        );

        $attachment = $xml->createElement(
            'cac:Attachment'
        );

        $binary = $xml->createElement(
            'cbc:EmbeddedDocumentBinaryObject',
            $hash
        );

        $binary->setAttribute(
            'mimeCode',
            'text/plain'
        );

        $attachment->appendChild($binary);
        $reference->appendChild($attachment);
        $invoice->appendChild($reference);
    }

    private function addQrReference(
        DOMDocument $xml,
        \DOMElement $invoice,
        string $qr
    ): void {
        $reference = $xml->createElement(
            'cac:AdditionalDocumentReference'
        );

        $this->addCbc(
            $xml,
            $reference,
            'ID',
            'QR'
        );

        $attachment = $xml->createElement(
            'cac:Attachment'
        );

        $binary = $xml->createElement(
            'cbc:EmbeddedDocumentBinaryObject',
            $qr
        );

        $binary->setAttribute(
            'mimeCode',
            'text/plain'
        );

        $attachment->appendChild($binary);
        $reference->appendChild($attachment);
        $invoice->appendChild($reference);
    }

    private function addSignatureReference(
        DOMDocument $xml,
        \DOMElement $invoice
    ): void {
        $signature = $xml->createElement(
            'cac:Signature'
        );

        /*
        |--------------------------------------------------------------------------
        | Signature ID
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $signature,
            'ID',
            'urn:oasis:names:specification:ubl:signature:Invoice'
        );

        /*
        |--------------------------------------------------------------------------
        | Signature Method
        |--------------------------------------------------------------------------
        */

        $this->addCbc(
            $xml,
            $signature,
            'SignatureMethod',
            'urn:oasis:names:specification:ubl:dsig:enveloped:xades'
        );

        $invoice->appendChild(
            $signature
        );
    }

    private function addCbc(
        DOMDocument $xml,
        \DOMElement $parent,
        string $name,
        string $value
    ): \DOMElement {
        $element = $xml->createElement(
            'cbc:' . $name
        );

        $element->appendChild(
            $xml->createTextNode($value)
        );

        $parent->appendChild($element);

        return $element;
    }

    private function addAmount(
        DOMDocument $xml,
        \DOMElement $parent,
        string $name,
        float|int|string $amount,
        string $currency
    ): void {
        $element = $xml->createElement(
            'cbc:' . $name,
            $this->money($amount)
        );

        $element->setAttribute(
            'currencyID',
            $currency
        );

        $parent->appendChild($element);
    }

    private function money(
        float|int|string $value
    ): string {
        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private function quantity(
        float|int|string $value
    ): string {
        return number_format(
            (float) $value,
            6,
            '.',
            ''
        );
    }

    private function percentage(
        float|int|string $value
    ): string {
        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private function validate(array $data): void
    {
        $required = [
            'invoice_number',
            'uuid',
            'issue_date',
            'issue_time',
            'invoice_type_code',
            'invoice_type_name',
            'currency',
            'icv',
            'previous_invoice_hash',

            'supplier',
            'customer',
            'lines',

            'line_extension_total',
            'tax_exclusive_total',
            'tax_total',
            'tax_inclusive_total',
            'payable_total',
        ];

        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException(
                    "حقل ZATCA مفقود: {$field}"
                );
            }
        }

        if (empty($data['lines'])) {
            throw new InvalidArgumentException(
                'الفاتورة يجب أن تحتوي على بند واحد على الأقل.'
            );
        }
    }
}