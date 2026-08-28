@php
    $printDocument = $document
        ?? $invoice
        ?? $salesInvoice
        ?? $purchaseInvoice
        ?? $quotation
        ?? $salesReturn
        ?? $purchaseReturn
        ?? $voucher
        ?? $receiptVoucher
        ?? $paymentVoucher
        ?? $customerReceiptVoucher
        ?? $supplierPaymentVoucher
        ?? $generalReceiptVoucher
        ?? $generalPaymentVoucher
        ?? $customerRefundVoucher
        ?? $supplierRefundVoucher
        ?? $model
        ?? null;

    $printBranch = $branch
        ?? $printDocument?->branch
        ?? auth()->user()?->branch
        ?? null;

    $printCompany = $company
        ?? $printBranch?->company
        ?? $printDocument?->company
        ?? auth()->user()?->company
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | من الشركة فقط
    |--------------------------------------------------------------------------
    */

    $companyName = $printCompany?->company_name_ar
        ?? $printCompany?->company_name
        ?? $printCompany?->name_ar
        ?? $printCompany?->name
        ?? config('app.name');

    $companyNameEn = $printCompany?->company_name_en
        ?? $printCompany?->name_en
        ?? null;

    $logoPath = $printCompany?->logo
        ?? $printCompany?->logo_path
        ?? $printCompany?->company_logo
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | من الفرع فقط
    |--------------------------------------------------------------------------
    */

    $branchName = $printBranch?->branch_name
        ?? $printBranch?->branch_name_ar
        ?? $printBranch?->name
        ?? null;

    $taxNumber = $printBranch?->tax_registration_number
        ?? null;

    $branchPhone = $printBranch?->phone
        ?? null;

    $branchAddress = $printBranch?->details
        ?? null;

    $branchCity = $printBranch?->city
        ?? null;

    $branchNeighborhood = $printBranch?->neighborhood
        ?? null;

    $branchStreet = $printBranch?->street_name
        ?? null;

    $branchBuildingNumber = $printBranch?->building_number
        ?? null;

    $branchPostalZone = $printBranch?->postal_zone
        ?? null;

    $commercialRegister = $printBranch?->license_number
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | بيانات المستند
    |--------------------------------------------------------------------------
    */

    $resolvedDocumentTitle = $documentTitle
        ?? (isset($quotation) ? 'عرض سعر' : null)
        ?? 'مستند';

    $resolvedDocumentTitleEn = $documentTitleEn
        ?? (isset($quotation) ? 'Quotation' : null);

    $resolvedDocumentNumber = $documentNumber
        ?? $printDocument?->quotation_no
        ?? $printDocument?->invoice_no
        ?? $printDocument?->invoice_number
        ?? $printDocument?->voucher_no
        ?? $printDocument?->voucher_number
        ?? $printDocument?->return_no
        ?? $printDocument?->return_number
        ?? $printDocument?->document_number
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | رابط الشعار
    |--------------------------------------------------------------------------
    */

    $logoUrl = null;

    if ($logoPath) {
        $cleanLogo = ltrim($logoPath, '/');

        if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
            $logoUrl = $logoPath;
        } elseif (str_starts_with($cleanLogo, 'storage/')) {
            $logoUrl = asset($cleanLogo);
        } else {
            $logoUrl = asset('storage/' . $cleanLogo);
        }
    }

    $statusLabels = [
        'draft' => 'مسودة',
        'sent' => 'مرسل',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'converted' => 'محول',
        'cancelled' => 'ملغى',
        'posted' => 'مرحل',
        'paid' => 'مدفوع',
        'partial' => 'مدفوع جزئيًا',
        'unpaid' => 'غير مدفوع',
    ];

    $documentStatusValue = $documentStatus
        ?? $printDocument?->status
        ?? null;

    $statusClass = 'status-' . ($documentStatusValue ?? 'draft');

    $statusText = $statusLabels[$documentStatusValue ?? '']
        ?? $documentStatusValue
        ?? null;
@endphp

<div class="company-side">
    <div class="logo-box">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="company-logo">
        @else
            <div class="logo-placeholder">
                {{ mb_substr($companyName, 0, 2) }}
            </div>
        @endif
    </div>

    <div class="company-info">
        <h1 class="company-name">
            {{ $companyName }}
        </h1>

        @if($companyNameEn)
            <div class="company-name-en">
                {{ $companyNameEn }}
            </div>
        @endif

        <div class="company-meta">
            @if($branchName)
                <span>الفرع: {{ $branchName }}</span>
            @endif

            @if($taxNumber)
                <span>الرقم الضريبي: {{ $taxNumber }}</span>
            @endif

            @if($commercialRegister)
                <span>رقم الترخيص: {{ $commercialRegister }}</span>
            @endif

            @if($branchPhone)
                <span>الهاتف: {{ $branchPhone }}</span>
            @endif
        </div>

        @if($branchAddress)
            <div class="company-address">
                {{ $branchAddress }}
            </div>
        @endif

<!--         @if($branchCity || $branchNeighborhood || $branchStreet || $branchBuildingNumber || $branchPostalZone)
            <div class="company-address">
                @if($branchCity)
                    {{ $branchCity }}
                @endif

                @if($branchNeighborhood)
                    - {{ $branchNeighborhood }}
                @endif

                @if($branchStreet)
                    - {{ $branchStreet }}
                @endif

                @if($branchBuildingNumber)
                    - مبنى {{ $branchBuildingNumber }}
                @endif

                @if($branchPostalZone)
                    - الرمز البريدي {{ $branchPostalZone }}
                @endif
            </div>
        @endif -->
    </div>
</div>

<div class="document-side">
    <div class="document-title">
        {{ $resolvedDocumentTitle }}
    </div>

    @if($resolvedDocumentTitleEn)
        <div class="document-title-en">
            {{ $resolvedDocumentTitleEn }}
        </div>
    @endif

    @if($resolvedDocumentNumber)
        <div class="document-number">
            {{ $resolvedDocumentNumber }}
        </div>
    @endif

    @if($statusText)
        <span class="status {{ $statusClass }}">
            {{ $statusText }}
        </span>
    @endif
</div>