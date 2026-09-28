<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopAttachment extends Model
{
    public const CATEGORIES = [
        'approval' => 'تعميد',
        'photos' => 'صور',
        'quotation' => 'عرض سعر',
        'clearance' => 'خطاب مخالصة',
        'other' => 'أخرى',
    ];

    protected $fillable = ['category', 'original_name', 'storage_path', 'mime_type', 'size_bytes', 'description', 'uploaded_by'];
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
