<?php

namespace App\Models;

use App\Concerns\ScopesByCountry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use ScopesByCountry;

    protected $fillable = [
        'project_id',
        'financial_transaction_id',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'beneficiaries_count',
        'approval_status',
        'review_note',
        'reviewed_by',
        'approved_at',
    ];

    protected $casts = [
        'file_path' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment) {
            if (empty($attachment->uploaded_by) && auth()->check()) {
                $attachment->uploaded_by = auth()->id();
            }
        });
    }

    /**
     * Filter by project country (no direct country_id column).
     */
    protected function applyCountryFilter(Builder $query, array $countryIds): Builder
    {
        return $query->whereHas('project', fn (Builder $q) => $q->whereIn('country_id', $countryIds));
    }

    public function getFilesCountAttribute(): int
    {
        return is_array($this->file_path) ? count($this->file_path) : 0;
    }

    public function getPrimaryFilePathAttribute(): ?string
    {
        return is_array($this->file_path) && count($this->file_path) ? $this->file_path[0] : null;
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}
