<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;
use App\Services\InternalNotifier;
use App\Services\ProjectAutoClose;
use App\Services\ProjectNumberGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectObserver
{
    public function creating(Project $project): void
    {
        if (filled($project->project_number)) {
            return;
        }

        if (! filled($project->organization_id)) {
            return;
        }

        $generator = app(ProjectNumberGenerator::class);
        $project->project_number = $this->generateUniqueProjectNumber($generator, (int) $project->organization_id);
    }

    public function created(Project $project): void
    {
        ActivityLogger::log('project.created', 'إنشاء مشروع جديد', $project);
        $this->pushAlert($project->id, 'project_created', 'تم إنشاء مشروع', 'تم إنشاء المشروع: ' . ($project->title ?? $project->name ?? ''));

        // Targeted in-app + email notifications via the recipient matrix
        // in config/notifications-recipients.php (admin, board, country
        // readiness approver, etc.).
        InternalNotifier::dispatch('project.created', $project);
    }

    /**
     * Auto-sync documentation_status whenever the set of documentation
     * URLs or the documentation_type changes. Behavior:
     *
     *   documentation_type = one_time             → status = complete
     *   documentation_type = continuous / periodic → status = partial
     *
     * Triggers when any of these attributes is dirty:
     *   - photo_album_url
     *   - video_album_url
     *   - documentation_type
     *
     * If the new URL set is empty (both URLs blank), reset status to
     * 'not_started' unless it was manually set to 'complete'.
     *
     * Runs before save so the status change is persisted in the same
     * UPDATE statement.
     */
    public function updating(Project $project): void
    {
        if (! Schema::hasColumn('projects', 'documentation_status')) {
            return;
        }

        $dirty = $project->getDirty();
        $relevant = ['photo_album_url', 'video_album_url', 'documentation_type'];
        $anyRelevantDirty = (bool) array_intersect(array_keys($dirty), $relevant);
        if (! $anyRelevantDirty) {
            return;
        }

        // Take the to-be-saved values (fall back to originals for fields
        // not in this update).
        $photo = (string) ($project->photo_album_url
            ?? $project->getOriginal('photo_album_url')
            ?? '');
        $video = (string) ($project->video_album_url
            ?? $project->getOriginal('video_album_url')
            ?? '');
        $type = (string) ($project->documentation_type
            ?? $project->getOriginal('documentation_type')
            ?? '');

        $currentStatus = (string) ($project->documentation_status
            ?? $project->getOriginal('documentation_status')
            ?? '');

        $hasAnyUrl = $photo !== '' || $video !== '';

        if (! $hasAnyUrl) {
            // All URLs cleared. Fall back to 'not_started' unless status
            // was manually marked as 'complete'.
            if ($currentStatus !== 'complete' && $currentStatus !== 'not_started') {
                $project->documentation_status = 'not_started';
            }
            return;
        }

        // URL(s) present but no documentation_type chosen yet — we cannot
        // infer the target status. Leave whatever status is currently set.
        if ($type === '') {
            return;
        }

        $newStatus = match ($type) {
            'one_time'               => 'complete',
            'continuous', 'periodic' => 'partial',
            default                  => null,
        };

        if ($newStatus === null || $newStatus === $currentStatus) {
            return;
        }

        // Don't clobber a manually-set 'complete' with 'partial'. An
        // explicit type change *to* one_time still upgrades to 'complete'
        // above because status is already 'complete' → no-op via the
        // equality check.
        if ($currentStatus === 'complete' && $newStatus === 'partial') {
            return;
        }

        $project->documentation_status = $newStatus;
    }

    public function updated(Project $project): void
    {
        $changes = collect($project->getChanges())->except('updated_at')->all();

        if (empty($changes)) {
            return;
        }

        ActivityLogger::log('project.updated', 'تحديث بيانات المشروع', $project, ['changes' => $changes]);

        $stateKey = array_key_exists('status', $changes) ? 'status' : (array_key_exists('state', $changes) ? 'state' : null);

        if ($stateKey) {
            $from = (string) $project->getOriginal($stateKey);
            $to = (string) data_get($project, $stateKey);
            $changedBy = auth()->id() ?? $project->updated_by ?? $project->created_by;

            if ($from !== $to && $changedBy) {
                DB::table('project_state_histories')->insert([
                    'project_id' => $project->id,
                    'from_state' => $from,
                    'to_state' => $to,
                    'changed_by' => $changedBy,
                    'notes' => filled($project->stateChangeNote)
                        ? (string) $project->stateChangeNote
                        : 'تغيير حالة تلقائي عبر النظام',
                    'created_at' => now(),
                ]);

                // consume note (single-shot)
                $project->stateChangeNote = null;

                $this->pushAlert($project->id, 'project_state_changed', 'تغيير حالة المشروع', "تم تغيير الحالة من {$from} إلى {$to}");
            }
        }

        if ($this->isDelayed($project)) {
            $this->pushAlertOncePerDay($project->id, 'project_delayed', 'مشروع متأخر', 'المشروع تجاوز تاريخ الانتهاء المتوقع');
        }

        // ─── Auto-close evaluation ────────────────────────────────────────
        // If the conditions are met (individual: doc link + zero balance,
        // institution: final report approved + zero balance), close the
        // project automatically. Only when the relevant fields changed.
        $autoCloseTriggers = [
            'photo_album_url',
            'video_album_url',
            'final_report_approved',
            'final_report_approved_at',
        ];

        if (! empty(array_intersect($autoCloseTriggers, array_keys($changes)))) {
            ProjectAutoClose::evaluate($project->fresh('organization'));
        }
    }

    protected function isDelayed(Project $project): bool
    {
        if (!Schema::hasColumn('projects', 'expected_end_date') || empty($project->expected_end_date)) {
            return false;
        }

        $state = Schema::hasColumn('projects', 'status')
            ? (string) data_get($project, 'status', '')
            : (string) data_get($project, 'state', '');

        if (in_array($state, ['completed', 'closed'], true)) {
            return false;
        }

        return Carbon::parse($project->expected_end_date)->isPast();
    }

    protected function pushAlert(int $projectId, string $type, string $title, string $body, string $severity = 'info'): void
    {
        AlertNotifier::storeAndSend($projectId, $type, $title, $body, $severity);
    }

    protected function pushAlertOncePerDay(int $projectId, string $type, string $title, string $body, string $severity = 'warning'): void
    {
        $exists = DB::table('alerts')
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if (! $exists) {
            $this->pushAlert($projectId, $type, $title, $body, $severity);
        }
    }

    protected function generateUniqueProjectNumber(ProjectNumberGenerator $generator, int $organizationId): string
    {
        $year = now()->format('Y');

        for ($i = 0; $i < 20; $i++) {
            $number = $generator->generate($organizationId, $year);

            $exists = DB::table('projects')
                ->where('project_number', $number)
                ->exists();

            if (! $exists) {
                return $number;
            }
        }

        return $generator->generate($organizationId, $year) . '-' . now()->format('His');
    }
}
