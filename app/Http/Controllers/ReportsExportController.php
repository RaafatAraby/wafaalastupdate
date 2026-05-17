<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';
        $titleColumn = Schema::hasColumn('projects', 'title') ? 'title' : (Schema::hasColumn('projects', 'name') ? 'name' : 'project_name');

        $query = Project::query()->with(['country', 'funderOrganization']);

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        // Country scoping: country-scoped roles (e.g. enhancer_finance_central)
        // can only export projects in countries bound to them via user_countries.
        // Global-scope roles bypass this filter via hasGlobalDataScope().
        $user = Auth::user();
        if ($user instanceof User && ! $user->hasGlobalDataScope()) {
            $allowedCountryIds = $user->allowedCountryIds();
            $query->whereIn('country_id', $allowedCountryIds ?: [0]);

            // If the user passed a country filter outside their scope, ignore it
            // (the whereIn above already enforces the bounds, but we drop the
            // request param here for clarity rather than letting it noop).
            if ($request->filled('country_id')
                && ! in_array((int) $request->string('country_id')->toString(), array_map('intval', $allowedCountryIds), true)) {
                $request->merge(['country_id' => null]);
            }
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

        return response()->streamDownload(function () use ($query, $titleColumn) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM لضمان عرض العربية بشكل صحيح في Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'وقت انشاء المشروع',
                'رقم المشروع',
                'اسم المشروع',
                'الدولة',
                'الجهة الممولة',
                'مبلغ المشروع',
                'الوصف',
                'رابط التوثيق',
            ]);

            foreach ($query->cursor() as $project) {
                $documentationLinks = array_filter([
                    $project->photo_album_url ?? null,
                    $project->video_album_url ?? null,
                ], static fn ($url): bool => filled($url));

                fputcsv($handle, [
                    optional($project->created_at)->format('Y-m-d H:i:s') ?? '',
                    (string) ($project->project_number ?? ''),
                    (string) ($project->{$titleColumn} ?? ''),
                    (string) ($project->country->name_ar ?? ''),
                    (string) ($project->funderOrganization->name ?? ''),
                    (string) ($project->approved_amount ?? ''),
                    (string) ($project->description ?? ''),
                    implode(' | ', $documentationLinks),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
