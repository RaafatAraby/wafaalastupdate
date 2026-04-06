<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'project_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'meta',
        'causer_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
