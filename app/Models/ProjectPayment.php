<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One scheduled payment instalment for a project. The system uses these
 * rows for automatic due-date reminders dispatched via the InternalNotifier.
 */
class ProjectPayment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_NOTIFIED = 'notified';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'project_id',
        'due_date',
        'amount',
        'currency',
        'original_amount',
        'exchange_rate',
        'percentage',
        'description',
        'status',
        'notified_at',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'percentage' => 'decimal:2',
            'notified_at' => 'datetime',
            'paid_at' => 'datetime',
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

    public function isOutstanding(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_NOTIFIED], true);
    }
}
