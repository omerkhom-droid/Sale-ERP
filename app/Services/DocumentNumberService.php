<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function generate(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {

            $sequence = DocumentSequence::query()
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Document sequence not found or inactive: {$documentType}");
            }

            $nextNumber = (int) $sequence->last_number + 1;

            $sequence->update([
                'last_number' => $nextNumber,
            ]);

            return $sequence->prefix . '-' . str_pad(
                (string) $nextNumber,
                (int) $sequence->padding,
                '0',
                STR_PAD_LEFT
            );
        });
    }
}