<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;

class ProjectNumberGenerator
{
    public function generate(int $organizationId, ?string $year = null): string
    {
        $organization = Organization::findOrFail($organizationId);
        $year = $year ?: now()->format('Y');

        $prefix = $organization->entity_type === 'individual'
            ? "IVD-D-{$year}-"
            : "IVD-{$year}-";

        $lastProject = Project::query()
            ->where('project_number', 'like', $prefix . '%')
            ->orderByDesc('project_number')
            ->first();

        $lastSequence = 0;

        if ($lastProject) {
            $parts = explode('-', $lastProject->project_number);
            $lastSequence = (int) end($parts);
        }

        $nextSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return $prefix . $nextSequence;
    }
}
