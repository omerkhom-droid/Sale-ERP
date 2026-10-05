<?php

namespace App\Services\Zatca;

use App\Models\SalesInvoice;
use App\Models\ZatcaDevice;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ZatcaSalesInvoiceSubmissionService
{
    public function __construct(
        private ZatcaSalesInvoiceDataService $dataService,
        private ZatcaInvoiceSigningService $signingService,
        private ZatcaApiClient $apiClient,
    ) {
    }

    public function submit(
        SalesInvoice $invoice
    ): SalesInvoice {
        try {
            $result = DB::transaction(
                function () use ($invoice): array {
                    $device = ZatcaDevice::query()
                        ->where('status', 'production')
                        ->where('auto_submit', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $device) {
                        throw new RuntimeException(
                            'الإرسال التلقائي إلى ZATCA غير مفعل.'
                        );
                    }

                    $lockedInvoice = SalesInvoice::query()
                        ->with([
                            'customer',
                            'branch',
                            'items',
                        ])
                        ->lockForUpdate()
                        ->findOrFail($invoice->id);

                    if ($lockedInvoice->status !== 'posted') {
                        throw new RuntimeException(
                            'لا يمكن إرسال فاتورة غير مرحلة إلى ZATCA.'
                        );
                    }

                    if (
                        in_array(
                            $lockedInvoice->zatca_status,
                            ['reported', 'cleared'],
                            true
                        )
                    ) {
                        return [
                            'successful' => true,
                            'invoice' => $lockedInvoice,
                        ];
                    }

                    $expectedIcv =
                        ((int) $device->invoice_counter) + 1;

                    $previousHash =
                        (string) $device->previous_invoice_hash;

                    if ($previousHash === '') {
                        throw new RuntimeException(
                            'Previous Invoice Hash غير موجود في جهاز ZATCA.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | تثبيت ICV وPIH لأول محاولة
                    |--------------------------------------------------------------------------
                    */
                    if (empty($lockedInvoice->zatca_icv)) {
                        $pendingInvoiceExists =
                            SalesInvoice::query()
                                ->where(
                                    'zatca_icv',
                                    $expectedIcv
                                )
                                ->whereKeyNot(
                                    $lockedInvoice->id
                                )
                                ->whereNotIn(
                                    'zatca_status',
                                    ['reported', 'cleared']
                                )
                                ->exists();

                        if ($pendingInvoiceExists) {
                            throw new RuntimeException(
                                'توجد فاتورة سابقة معلقة في إرسال ZATCA. يجب معالجتها أولاً.'
                            );
                        }

                        $lockedInvoice->zatca_icv =
                            $expectedIcv;

                        $lockedInvoice
                            ->zatca_previous_invoice_hash =
                            $previousHash;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | منع كسر تسلسل الفواتير
                    |--------------------------------------------------------------------------
                    */
                    if (
                        (int) $lockedInvoice->zatca_icv
                        !== $expectedIcv
                    ) {
                        throw new RuntimeException(
                            'ترتيب ICV لهذه الفاتورة لا يطابق عداد جهاز ZATCA.'
                        );
                    }

                    if (
                        ! hash_equals(
                            $previousHash,
                            (string) $lockedInvoice
                                ->zatca_previous_invoice_hash
                        )
                    ) {
                        throw new RuntimeException(
                            'PIH الخاص بالفاتورة لا يطابق آخر Hash في جهاز ZATCA.'
                        );
                    }

                    $lockedInvoice->zatca_status =
                        'processing';

                    $lockedInvoice->zatca_attempts =
                        ((int) $lockedInvoice->zatca_attempts)
                        + 1;

                    $lockedInvoice->zatca_error = null;
                    $lockedInvoice->save();

                    /*
                    |--------------------------------------------------------------------------
                    | إنشاء وتوقيع XML مرة واحدة
                    |--------------------------------------------------------------------------
                    | عند إعادة المحاولة نرسل نفس XML وUUID وHash.
                    */
                    if (
                        blank($lockedInvoice->zatca_signed_xml)
                        || blank($lockedInvoice->zatca_uuid)
                        || blank($lockedInvoice->zatca_invoice_hash)
                    ) {
                        $data = $this->dataService->build(
                            $lockedInvoice,
                            (int) $lockedInvoice->zatca_icv,
                            (string) $lockedInvoice
                                ->zatca_previous_invoice_hash
                        );

                        $signed = $this->signingService->sign(
                            $device,
                            $data
                        );

                        $lockedInvoice->zatca_uuid =
                            $signed['uuid'];

                        $lockedInvoice->zatca_invoice_hash =
                            $signed['invoice_hash'];

                        $lockedInvoice->zatca_qr =
                            $signed['qr'];

                        $lockedInvoice->zatca_signed_xml =
                            $signed['signed_xml'];

                        $lockedInvoice->save();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | الإرسال حسب تصنيف الفاتورة
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $lockedInvoice
                            ->invoice_classification
                        === 'standard'
                    ) {
                        $response =
                            $this->apiClient->clearInvoice(
                                $device,
                                $lockedInvoice->zatca_uuid,
                                $lockedInvoice
                                    ->zatca_invoice_hash,
                                $lockedInvoice
                                    ->zatca_signed_xml
                            );

                        $successStatus = 'cleared';
                    } else {
                        $response =
                            $this->apiClient->reportInvoice(
                                $device,
                                $lockedInvoice->zatca_uuid,
                                $lockedInvoice
                                    ->zatca_invoice_hash,
                                $lockedInvoice
                                    ->zatca_signed_xml
                            );

                        $successStatus = 'reported';
                    }

                    $lockedInvoice->zatca_http_status =
                        $response['status'] ?? null;

                    $lockedInvoice->zatca_response =
                        $response['body'] ?? [];

                    /*
                    |--------------------------------------------------------------------------
                    | رفض ZATCA
                    |--------------------------------------------------------------------------
                    */
                    if (! ($response['successful'] ?? false)) {
                        $lockedInvoice->zatca_status =
                            'failed';

                        $lockedInvoice->zatca_error =
                            $this->responseError(
                                $response
                            );

                        $lockedInvoice->save();

                        return [
                            'successful' => false,
                            'invoice' => $lockedInvoice,
                            'error' =>
                                $lockedInvoice->zatca_error,
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | حفظ النسخة المعتمدة للفاتورة القياسية
                    |--------------------------------------------------------------------------
                    */
                    $clearedInvoice = data_get(
                        $response,
                        'body.clearedInvoice'
                    );

                    if (
                        is_string($clearedInvoice)
                        && $clearedInvoice !== ''
                    ) {
                        $decodedClearedInvoice =
                            base64_decode(
                                $clearedInvoice,
                                true
                            );

                        $lockedInvoice->zatca_cleared_xml =
                            $decodedClearedInvoice !== false
                                ? $decodedClearedInvoice
                                : $clearedInvoice;
                    }

                    $lockedInvoice->zatca_status =
                        $successStatus;

                    $lockedInvoice->zatca_error = null;
                    $lockedInvoice->zatca_sent_at = now();
                    $lockedInvoice->save();

                    /*
                    |--------------------------------------------------------------------------
                    | تحديث سلسلة الجهاز بعد القبول فقط
                    |--------------------------------------------------------------------------
                    */
                    $device->invoice_counter =
                        (int) $lockedInvoice->zatca_icv;

                    $device->previous_invoice_hash =
                        $lockedInvoice->zatca_invoice_hash;

                    $device->save();

                    return [
                        'successful' => true,
                        'invoice' => $lockedInvoice,
                    ];
                },
                3
            );
        } catch (Throwable $exception) {
            SalesInvoice::query()
                ->whereKey($invoice->id)
                ->update([
                    'zatca_status' => 'failed',
                    'zatca_error' =>
                        $exception->getMessage(),
                ]);

            throw $exception;
        }

        if (! $result['successful']) {
            throw new RuntimeException(
                $result['error']
                    ?? 'رفضت ZATCA الفاتورة.'
            );
        }

        return $result['invoice']->fresh([
            'customer',
            'branch',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
        ]);
    }

    private function responseError(
        array $response
    ): string {
        $messages = collect(
            data_get(
                $response,
                'body.validationResults.errorMessages',
                []
            )
        )
            ->pluck('message')
            ->filter()
            ->implode(' | ');

        if ($messages !== '') {
            return $messages;
        }

        $message = data_get(
            $response,
            'body.message'
        );

        if (is_string($message) && $message !== '') {
            return $message;
        }

        $rawBody = data_get(
            $response,
            'body.raw_body'
        );

        if (is_string($rawBody) && $rawBody !== '') {
            return $rawBody;
        }

        return 'رفضت ZATCA الفاتورة. HTTP '
            . ($response['status'] ?? 'Unknown');
    }
}