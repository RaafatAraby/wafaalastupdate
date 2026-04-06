<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectObserver
{
    public function created(Project $project): void
    {
        ActivityLogger::log('project.created', 'إنشاء مشروع جديد', $project);
        $this->pushAlert($project->id, 'project_created', 'تم إنشاء مشروع', 'تم إنشاء المشروع: ' . ($project->title ?? $project->name ?? ''));
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
                    'notes' => 'تغيير حالة تلقائي عبر النظام',
                    'created_at' => now(),
                ]);

                $this->pushAlert($project->id, 'project_state_changed', 'تغيير حالة المشروع', "تم تغيير الحالة من {$from} إلى {$to}");
            }
        }

        if ($this->isDelayed($project)) {
            $this->pushAlertOncePerDay($project->id, 'project_delayed', 'مشروع متأخر', 'المشروع تجاوز تاريخ الانتهاء المتوقع');
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
}
