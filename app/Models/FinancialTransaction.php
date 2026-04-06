<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'project_id',
        'transaction_type',
        'reference_no',
        'amount',
        'transfer_method',
        'transaction_date',
        'sender_name',
        'receiver_name',
        'notes',
        'attachment_path',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
