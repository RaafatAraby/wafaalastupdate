<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'project_id',
        'financial_transaction_id',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment) {
            if (empty($attachment->uploaded_by) && auth()->check()) {
                $attachment->uploaded_by = auth()->id();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
