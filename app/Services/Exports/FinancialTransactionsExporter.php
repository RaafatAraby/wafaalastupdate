<?php

namespace App\Services\Exports;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds an Excel (.xlsx) export of the FinancialTransactions table with
 * a designed title row, summary card, styled header and zebra-striped
 * body rows. Always writes RTL because the audience is Arabic.
 */
class FinancialTransactionsExporter
{
    public function __construct(private readonly Builder $query) {}

    public function download(?string $filename = null): StreamedResponse
    {
        $filename = $filename ?: ('financial-transactions-'.now()->format('Y-m-d-His').'.xlsx');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الحركات المالية');
        $sheet->setRightToLeft(true);

        $headers = [
            'رقم المرجع',
            'المشروع',
            'رقم المشروع',
            'نوع الحركة',
            'العملة',
            'المبلغ الأصلي',
            'سعر الصرف',
            'المبلغ بالدولار (USD)',
            'تاريخ الحركة',
            'طريقة التحويل',
            'البنك',
            'المُحوِّل',
            'المستلم',
            'حالة الاعتماد',
            'ملاحظات',
            'تاريخ الإنشاء',
        ];
        $lastColumn = count($headers);
        $lastColumnLetter = ExcelStyle::columnLetter($lastColumn);

        // Row 1: title
        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'تقرير الحركات المالية - نظام وفاء');
        ExcelStyle::applyTitleRow($sheet, "A1:{$lastColumnLetter}1");
        $sheet->getRowDimension(1)->setRowHeight(34);

        // Row 2: generation date
        $sheet->mergeCells("A2:{$lastColumnLetter}2");
        $sheet->setCellValue('A2', 'تاريخ التصدير: '.now()->format('Y-m-d H:i'));
        ExcelStyle::applySubtitleRow($sheet, "A2:{$lastColumnLetter}2");

        // Summary card (row 3): totals computed on the (already scoped) query
        $totals = $this->collectTotals();
        $sheet->setCellValue('A3', 'إجمالي الحركات: '.$totals['count']);
        $sheet->setCellValue('B3', 'إجمالي الوارد (USD): '.number_format($totals['incoming'], 2));
        $sheet->setCellValue('C3', 'إجمالي الصادر (USD): '.number_format($totals['outgoing'], 2));
        $sheet->setCellValue('D3', 'الصافي (USD): '.number_format($totals['incoming'] - $totals['outgoing'], 2));
        $sheet->mergeCells("D3:{$lastColumnLetter}3");
        ExcelStyle::applySummaryCell($sheet, "A3:{$lastColumnLetter}3");
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Row 5: header. Leave row 4 empty as visual breathing room.
        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, 5], $label);
        }
        ExcelStyle::applyHeaderRow($sheet, "A5:{$lastColumnLetter}5");
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Body
        $row = 6;
        $approvalLabels = [
            'pending' => 'بانتظار الاعتماد',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            'returned' => 'مُعادة',
        ];
        $typeLabels = [
            'incoming' => 'وارد',
            'outgoing' => 'صادر',
        ];

        $this->query
            ->with(['project.country'])
            ->orderBy('id', 'desc')
            ->chunk(500, function ($transactions) use ($sheet, &$row, $approvalLabels, $typeLabels) {
                /** @var FinancialTransaction $tx */
                foreach ($transactions as $tx) {
                    $project = $tx->project;

                    $sheet->setCellValue([1, $row], (string) ($tx->reference_no ?: $tx->id));
                    $sheet->setCellValue([2, $row], (string) ($project?->title ?? '-'));
                    $sheet->setCellValue([3, $row], (string) ($project?->project_number ?? '-'));
                    $sheet->setCellValue([4, $row], $typeLabels[$tx->transaction_type] ?? (string) $tx->transaction_type);
                    $sheet->setCellValue([5, $row], (string) ($tx->currency ?: 'USD'));
                    $sheet->setCellValue([6, $row], $tx->original_amount !== null ? (float) $tx->original_amount : (float) $tx->amount);
                    $sheet->setCellValue([7, $row], $tx->exchange_rate !== null ? (float) $tx->exchange_rate : 1);
                    $sheet->setCellValue([8, $row], (float) $tx->amount);
                    $sheet->setCellValue([9, $row], optional($tx->transaction_date)->format('Y-m-d') ?? '');
                    $sheet->setCellValue([10, $row], (string) ($tx->transfer_method ?? ''));
                    $sheet->setCellValue([11, $row], (string) ($tx->bank_name ?? ''));
                    $sheet->setCellValue([12, $row], (string) ($tx->sender_name ?? ''));
                    $sheet->setCellValue([13, $row], (string) ($tx->receiver_name ?? ''));
                    $sheet->setCellValue([14, $row], $approvalLabels[$tx->approval_status] ?? (string) $tx->approval_status);
                    $sheet->setCellValue([15, $row], (string) ($tx->notes ?? ''));
                    $sheet->setCellValue([16, $row], optional($tx->created_at)->format('Y-m-d H:i') ?? '');

                    $row++;
                }
            });

        $lastRow = $row - 1;
        $firstDataRow = 6;

        if ($lastRow >= $firstDataRow) {
            $bodyRange = "A{$firstDataRow}:{$lastColumnLetter}{$lastRow}";
            ExcelStyle::applyBodyRange($sheet, $bodyRange);
            ExcelStyle::applyZebraStripes($sheet, $firstDataRow, $lastRow, $lastColumn);

            // Number formatting for the 3 numeric columns
            $sheet->getStyle("F{$firstDataRow}:F{$lastRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$firstDataRow}:G{$lastRow}")
                ->getNumberFormat()->setFormatCode('0.000000');
            $sheet->getStyle("H{$firstDataRow}:H{$lastRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("H{$firstDataRow}:H{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        ExcelStyle::autoSize($sheet, $lastColumn);
        $sheet->freezePane('A6');

        return $this->streamDownload($spreadsheet, $filename);
    }

    /**
     * @return array{count:int,incoming:float,outgoing:float}
     */
    private function collectTotals(): array
    {
        $count = (clone $this->query)->count();
        $incoming = (float) (clone $this->query)
            ->where('transaction_type', 'incoming')
            ->sum('amount');
        $outgoing = (float) (clone $this->query)
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        return [
            'count' => $count,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ];
    }

    private function streamDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
