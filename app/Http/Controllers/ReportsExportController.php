<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';
        $titleColumn = Schema::hasColumn('projects', 'title') ? 'title' : (Schema::hasColumn('projects', 'name') ? 'name' : 'project_name');

        $query = Project::query()->with(['country', 'organization']);

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->string('country_id'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->string('organization_id'));
        }

        if ($request->filled('state')) {
            $query->where($stateColumn, $request->string('state'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        $fileName = 'projects-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query, $stateColumn, $titleColumn) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'project_number',
                'project_title',
                'country',
                'organization',
                'state',
                'approved_amount',
                'created_at',
            ]);

            foreach ($query->cursor() as $project) {
                fputcsv($handle, [
                    $project->project_number,
                    $project->{$titleColumn},
                    $project->country->name_ar ?? '',
                    $project->organization->name ?? '',
                    $project->{$stateColumn},
                    $project->approved_amount,
                    optional($project->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
