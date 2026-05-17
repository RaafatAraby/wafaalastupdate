<?php

namespace App\Models;

use App\Concerns\ScopesByCountry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTransaction extends Model
{
    use ScopesByCountry;

    protected $fillable = [
        'project_id',
        'transaction_type',
        'funding_source_country',
        'reference_no',
        'amount',
        'transfer_method',
        'bank_name',
        'transaction_date',
        'sender_name',
        'receiver_name',
        'notes',
        'attachment_path',
        'approval_status',
        'review_note',
        'reviewed_by',
        'approved_at',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Filter by project country (we have no direct country_id column).
     */
    protected function applyCountryFilter(Builder $query, array $countryIds): Builder
    {
        return $query->whereHas('project', fn (Builder $q) => $q->whereIn('country_id', $countryIds));
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Human-readable Arabic country name for the stored ISO-2 code.
     */
    public function getFundingSourceCountryNameAttribute(): ?string
    {
        if (! $this->funding_source_country) {
            return null;
        }
        $list = (array) config('world_countries', []);
        return $list[$this->funding_source_country] ?? $this->funding_source_country;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}
