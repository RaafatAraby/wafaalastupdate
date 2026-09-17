<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\Exports\ProjectsReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the formatted XLSX project report. Country scoping is enforced
 * the same way it is on the Reports page: country-scoped roles only see
 * projects whose country_id is bound to them in user_countries.
 */
class ReportsExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';

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

            // If the user passed a country filter outside their scope, drop it.
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

        $filters = [
            'country_name' => $request->filled('country_id')
                ? DB::table('countries')->where('id', $request->string('country_id'))->value('name_ar')
                : null,
            'organization_name' => $request->filled('organization_id')
                ? DB::table('organizations')->where('id', $request->string('organization_id'))->value('name')
                : null,
            'state_label' => $request->filled('state')
                ? $this->stateLabel((string) $request->string('state'))
                : null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];

        return (new ProjectsReportExporter($query, $filters))
            ->download('projects-report-'.now()->format('Y-m-d-His').'.xlsx');
    }

    private function stateLabel(string $state): string
    {
        return [
            'new' => 'جديد',
            'pending_readiness' => 'بانتظار الجاهزية',
            'ready_for_execution' => 'جاهز للتنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'pending_documentation' => 'بانتظار التوثيق',
            'delayed' => 'متأخر',
            'completed' => 'مكتمل',
            'closed' => 'مغلق',
        ][$state] ?? $state;
    }
}
