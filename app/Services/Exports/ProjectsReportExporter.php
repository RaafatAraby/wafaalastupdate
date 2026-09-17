<?php

namespace App\Services\Exports;

use App\Models\FinancialTransaction;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Produces a fully-designed Excel report for the Projects section: a
 * cover summary, a per-project detail sheet, a state breakdown and the
 * list of late projects — all RTL-formatted, header-styled, with totals
 * and zebra-stripe rows.
 */
class ProjectsReportExporter
{
    public function __construct(
        private readonly Builder $projectsQuery,
        /** @var array<string, mixed> */
        private readonly array $filters = [],
    ) {}

    public function download(?string $filename = null): StreamedResponse
    {
        $filename = $filename ?: ('projects-report-'.now()->format('Y-m-d-His').'.xlsx');

        $spreadsheet = new Spreadsheet;
        $this->renderSummarySheet($spreadsheet->getActiveSheet());

        $projectsSheet = $spreadsheet->createSheet();
        $this->renderProjectsSheet($projectsSheet);

        $stateSheet = $spreadsheet->createSheet();
        $this->renderStateBreakdownSheet($stateSheet);

        $lateSheet = $spreadsheet->createSheet();
        $this->renderLateProjectsSheet($lateSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function renderSummarySheet(Worksheet $sheet): void
    {
        $sheet->setTitle('الملخص');
        $sheet->setRightToLeft(true);

        $lastColumn = 4;
        $lastColumnLetter = ExcelStyle::columnLetter($lastColumn);

        // Title
        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'تقرير المشاريع - نظام وفاء');
        ExcelStyle::applyTitleRow($sheet, "A1:{$lastColumnLetter}1");
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Generation date
        $sheet->mergeCells("A2:{$lastColumnLetter}2");
        $sheet->setCellValue('A2', 'تاريخ التصدير: '.now()->format('Y-m-d H:i'));
        ExcelStyle::applySubtitleRow($sheet, "A2:{$lastColumnLetter}2");

        // Active filters
        $activeFilters = array_filter([
            'الدولة' => $this->filters['country_name'] ?? null,
            'الجهة الممولة' => $this->filters['organization_name'] ?? null,
            'الحالة' => $this->filters['state_label'] ?? null,
            'من تاريخ' => $this->filters['date_from'] ?? null,
            'إلى تاريخ' => $this->filters['date_to'] ?? null,
        ], static fn ($v) => filled($v));

        $row = 4;
        if (! empty($activeFilters)) {
            $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
            $sheet->setCellValue("A{$row}", 'الفلاتر المُطبَّقة: '
                .collect($activeFilters)->map(fn ($v, $k) => "{$k}: {$v}")->implode('  |  '));
            ExcelStyle::applySubtitleRow($sheet, "A{$row}:{$lastColumnLetter}{$row}");
            $row += 2;
        } else {
            $row++;
        }

        // KPI card grid: 6 metrics in 2 rows × 3 columns
        $summary = $this->collectSummary();
        $kpis = [
            ['عدد المشاريع', $summary['total_projects']],
            ['المشاريع المكتملة', $summary['completed_projects']],
            ['المشاريع المتأخرة', $summary['delayed_projects']],
            ['إجمالي الوارد (USD)', number_format($summary['incoming_total'], 2)],
            ['إجمالي الصادر (USD)', number_format($summary['outgoing_total'], 2)],
            ['الرصيد الصافي (USD)', number_format($summary['balance'], 2)],
        ];

        $sheet->setCellValue("A{$row}", 'المؤشرات العامة');
        $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
        ExcelStyle::applyHeaderRow($sheet, "A{$row}:{$lastColumnLetter}{$row}");
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        $kpiStartRow = $row;
        foreach (array_chunk($kpis, 2) as $pair) {
            $sheet->setCellValue("A{$row}", $pair[0][0]);
            $sheet->setCellValue("B{$row}", $pair[0][1]);

            if (isset($pair[1])) {
                $sheet->setCellValue("C{$row}", $pair[1][0]);
                $sheet->setCellValue("D{$row}", $pair[1][1]);
            }

            ExcelStyle::applySummaryCell($sheet, "A{$row}:D{$row}");
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        // Note
        $row++;
        $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
        $sheet->setCellValue("A{$row}", 'يمكنك تصفّح الأوراق الأخرى لعرض تفاصيل المشاريع والتوزيع حسب الحالة والمشاريع المتأخرة.');
        ExcelStyle::applySubtitleRow($sheet, "A{$row}:{$lastColumnLetter}{$row}");

        ExcelStyle::autoSize($sheet, $lastColumn);
    }

    private function renderProjectsSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('المشاريع');
        $sheet->setRightToLeft(true);

        $headers = [
            'رقم المشروع',
            'اسم المشروع',
            'الدولة',
            'الجهة الممولة',
            'الحالة',
            'تاريخ البدء',
            'تاريخ الانتهاء المتوقع',
            'المبلغ المعتمد (USD)',
            'إجمالي الوارد (USD)',
            'إجمالي الصادر (USD)',
            'الرصيد (USD)',
            'تاريخ الإنشاء',
            'رابط التوثيق',
        ];
        $lastColumn = count($headers);
        $lastColumnLetter = ExcelStyle::columnLetter($lastColumn);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'تفاصيل المشاريع');
        ExcelStyle::applyTitleRow($sheet, "A1:{$lastColumnLetter}1");
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, 3], $label);
        }
        ExcelStyle::applyHeaderRow($sheet, "A3:{$lastColumnLetter}3");
        $sheet->getRowDimension(3)->setRowHeight(28);

        $row = 4;
        $totalIncoming = 0.0;
        $totalOutgoing = 0.0;

        $titleColumn = $this->resolveTitleColumn();
        $stateColumn = $this->resolveStateColumn();

        (clone $this->projectsQuery)
            ->with(['country', 'funderOrganization'])
            ->orderBy('id', 'desc')
            ->chunk(200, function ($projects) use ($sheet, &$row, &$totalIncoming, &$totalOutgoing, $titleColumn, $stateColumn) {
                $projectIds = $projects->pluck('id')->all();

                /** @var array<int, array{incoming: float, outgoing: float}> $finance */
                $finance = FinancialTransaction::query()
                    ->whereIn('project_id', $projectIds)
                    ->selectRaw('project_id, '
                        ."SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END) AS incoming_total, "
                        ."SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END) AS outgoing_total")
                    ->groupBy('project_id')
                    ->get()
                    ->keyBy('project_id')
                    ->map(fn ($r) => [
                        'incoming' => (float) $r->incoming_total,
                        'outgoing' => (float) $r->outgoing_total,
                    ])
                    ->all();

                /** @var Project $project */
                foreach ($projects as $project) {
                    $stats = $finance[$project->id] ?? ['incoming' => 0.0, 'outgoing' => 0.0];
                    $balance = $stats['incoming'] - $stats['outgoing'];
                    $totalIncoming += $stats['incoming'];
                    $totalOutgoing += $stats['outgoing'];

                    $documentation = array_filter([
                        $project->photo_album_url ?? null,
                        $project->video_album_url ?? null,
                    ], static fn ($url): bool => filled($url));

                    $sheet->setCellValue([1, $row], (string) ($project->project_number ?? $project->id));
                    $sheet->setCellValue([2, $row], (string) ($project->{$titleColumn} ?? '-'));
                    $sheet->setCellValue([3, $row], (string) ($project->country->name_ar ?? '-'));
                    $sheet->setCellValue([4, $row], (string) ($project->funderOrganization->name ?? '-'));
                    $sheet->setCellValue([5, $row], $this->stateLabel((string) ($project->{$stateColumn} ?? '')));
                    $sheet->setCellValue([6, $row], optional($project->start_date)->format('Y-m-d') ?? '-');
                    $sheet->setCellValue([7, $row], optional($project->expected_end_date)->format('Y-m-d') ?? '-');
                    $sheet->setCellValue([8, $row], (float) ($project->approved_amount ?? 0));
                    $sheet->setCellValue([9, $row], $stats['incoming']);
                    $sheet->setCellValue([10, $row], $stats['outgoing']);
                    $sheet->setCellValue([11, $row], $balance);
                    $sheet->setCellValue([12, $row], optional($project->created_at)->format('Y-m-d H:i') ?? '-');
                    $sheet->setCellValue([13, $row], implode(' | ', $documentation));

                    $row++;
                }
            });

        $lastRow = $row - 1;
        $firstDataRow = 4;

        if ($lastRow >= $firstDataRow) {
            $bodyRange = "A{$firstDataRow}:{$lastColumnLetter}{$lastRow}";
            ExcelStyle::applyBodyRange($sheet, $bodyRange);
            ExcelStyle::applyZebraStripes($sheet, $firstDataRow, $lastRow, $lastColumn);

            // Number format for numeric columns
            foreach (['H', 'I', 'J', 'K'] as $col) {
                $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastRow}")
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Totals row
            $totalRow = $lastRow + 1;
            $sheet->setCellValue("A{$totalRow}", 'الإجمالي');
            $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
            $sheet->setCellValue("I{$totalRow}", $totalIncoming);
            $sheet->setCellValue("J{$totalRow}", $totalOutgoing);
            $sheet->setCellValue("K{$totalRow}", $totalIncoming - $totalOutgoing);
            ExcelStyle::applySummaryCell($sheet, "A{$totalRow}:{$lastColumnLetter}{$totalRow}");
            $sheet->getStyle("I{$totalRow}:K{$totalRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getRowDimension($totalRow)->setRowHeight(24);
        }

        ExcelStyle::autoSize($sheet, $lastColumn);
        $sheet->freezePane('A4');
    }

    private function renderStateBreakdownSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('التوزيع حسب الحالة');
        $sheet->setRightToLeft(true);

        $headers = ['الحالة', 'عدد المشاريع'];
        $lastColumnLetter = ExcelStyle::columnLetter(count($headers));

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'توزيع المشاريع حسب الحالة');
        ExcelStyle::applyTitleRow($sheet, "A1:{$lastColumnLetter}1");
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, 3], $label);
        }
        ExcelStyle::applyHeaderRow($sheet, "A3:{$lastColumnLetter}3");

        $stateColumn = $this->resolveStateColumn();
        $rows = (clone $this->projectsQuery)
            ->selectRaw("{$stateColumn} as state, COUNT(*) as total")
            ->groupBy('state')
            ->pluck('total', 'state')
            ->all();

        $row = 4;
        $total = 0;
        foreach ($this->knownStates() as $state) {
            $count = (int) ($rows[$state] ?? 0);
            $total += $count;
            $sheet->setCellValue("A{$row}", $this->stateLabel($state));
            $sheet->setCellValue("B{$row}", $count);
            $row++;
        }

        $lastRow = $row - 1;
        ExcelStyle::applyBodyRange($sheet, "A4:{$lastColumnLetter}{$lastRow}");
        ExcelStyle::applyZebraStripes($sheet, 4, $lastRow, count($headers));

        // Totals
        $sheet->setCellValue("A{$row}", 'الإجمالي');
        $sheet->setCellValue("B{$row}", $total);
        ExcelStyle::applySummaryCell($sheet, "A{$row}:{$lastColumnLetter}{$row}");

        ExcelStyle::autoSize($sheet, count($headers));
    }

    private function renderLateProjectsSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('المشاريع المتأخرة');
        $sheet->setRightToLeft(true);

        $headers = [
            'رقم المشروع',
            'اسم المشروع',
            'الحالة',
            'تاريخ الانتهاء المتوقع',
            'عدد أيام التأخر',
        ];
        $lastColumn = count($headers);
        $lastColumnLetter = ExcelStyle::columnLetter($lastColumn);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'المشاريع المتأخرة عن موعد الانتهاء');
        ExcelStyle::applyTitleRow($sheet, "A1:{$lastColumnLetter}1");
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, 3], $label);
        }
        ExcelStyle::applyHeaderRow($sheet, "A3:{$lastColumnLetter}3");

        $stateColumn = $this->resolveStateColumn();
        $titleColumn = $this->resolveTitleColumn();
        $today = now()->startOfDay();

        $late = (clone $this->projectsQuery)
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', $today->toDateString())
            ->whereNotIn($stateColumn, ['completed', 'closed'])
            ->orderBy('expected_end_date')
            ->get();

        $row = 4;
        foreach ($late as $project) {
            $end = optional($project->expected_end_date);
            $sheet->setCellValue("A{$row}", (string) ($project->project_number ?? $project->id));
            $sheet->setCellValue("B{$row}", (string) ($project->{$titleColumn} ?? '-'));
            $sheet->setCellValue("C{$row}", $this->stateLabel((string) ($project->{$stateColumn} ?? '')));
            $sheet->setCellValue("D{$row}", $end?->format('Y-m-d') ?? '-');
            $sheet->setCellValue("E{$row}", $end ? $end->diffInDays($today) : 0);
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            ExcelStyle::applyBodyRange($sheet, "A4:{$lastColumnLetter}{$lastRow}");
            ExcelStyle::applyZebraStripes($sheet, 4, $lastRow, $lastColumn);
        }

        ExcelStyle::autoSize($sheet, $lastColumn);
    }

    /**
     * @return array{total_projects:int,delayed_projects:int,completed_projects:int,incoming_total:float,outgoing_total:float,balance:float}
     */
    private function collectSummary(): array
    {
        $stateColumn = $this->resolveStateColumn();
        $projectIds = (clone $this->projectsQuery)->pluck('id');

        $incoming = (float) FinancialTransaction::query()
            ->whereIn('project_id', $projectIds)
            ->where('transaction_type', 'incoming')
            ->sum('amount');

        $outgoing = (float) FinancialTransaction::query()
            ->whereIn('project_id', $projectIds)
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        return [
            'total_projects' => (int) (clone $this->projectsQuery)->count(),
            'delayed_projects' => (int) (clone $this->projectsQuery)->where($stateColumn, 'delayed')->count(),
            'completed_projects' => (int) (clone $this->projectsQuery)->whereIn($stateColumn, ['completed', 'closed'])->count(),
            'incoming_total' => $incoming,
            'outgoing_total' => $outgoing,
            'balance' => $incoming - $outgoing,
        ];
    }

    private function resolveStateColumn(): string
    {
        return Schema::hasColumn('projects', 'status') ? 'status' : 'state';
    }

    private function resolveTitleColumn(): string
    {
        foreach (['title', 'name', 'project_name'] as $col) {
            if (Schema::hasColumn('projects', $col)) {
                return $col;
            }
        }

        return 'id';
    }

    /**
     * @return array<int, string>
     */
    private function knownStates(): array
    {
        return [
            'new',
            'pending_readiness',
            'ready_for_execution',
            'in_execution',
            'pending_documentation',
            'delayed',
            'completed',
            'closed',
        ];
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
