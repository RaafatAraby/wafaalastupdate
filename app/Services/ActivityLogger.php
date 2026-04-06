<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActivityLogger
{
    public static function log(string $event, string $description, ?Model $subject = null, array $meta = []): void
    {
        DB::table('activity_logs')->insert([
            'project_id' => self::resolveProjectId($subject, $meta),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'meta' => empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'causer_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected static function resolveProjectId(?Model $subject, array $meta): ?int
    {
        if (isset($meta['project_id'])) return (int) $meta['project_id'];
        if ($subject && isset($subject->project_id)) return (int) $subject->project_id;
        return $subject instanceof \App\Models\Project ? (int) $subject->id : null;
    }
}
