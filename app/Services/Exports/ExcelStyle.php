<?php

namespace App\Services\Exports;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared spreadsheet styling helpers used by every Excel export the
 * system produces (financial transactions, project reports, …).
 * Centralised so the design stays consistent across files.
 */
class ExcelStyle
{
    public const HEADER_FILL = 'FF1E40AF';     // tailwind blue-800

    public const HEADER_TEXT = 'FFFFFFFF';

    public const SUMMARY_FILL = 'FFE0E7FF';    // tailwind indigo-100

    public const ZEBRA_FILL = 'FFF8FAFC';      // tailwind slate-50

    public const TITLE_FILL = 'FF0F172A';      // tailwind slate-900

    public static function applyTitleRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => self::HEADER_TEXT],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::TITLE_FILL],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'readOrder' => Alignment::READORDER_RTL,
            ],
        ]);
    }

    public static function applySubtitleRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 11,
                'color' => ['argb' => 'FF334155'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'readOrder' => Alignment::READORDER_RTL,
            ],
        ]);
    }

    public static function applyHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => self::HEADER_TEXT],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_FILL],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
                'readOrder' => Alignment::READORDER_RTL,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
    }

    public static function applyBodyRange(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
                'readOrder' => Alignment::READORDER_RTL,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ]);
    }

    /**
     * Light zebra-stripe on alternating rows. $firstDataRow is the
     * 1-based row index of the first body row, $lastRow inclusive.
     */
    public static function applyZebraStripes(Worksheet $sheet, int $firstDataRow, int $lastRow, int $lastColumn): void
    {
        for ($row = $firstDataRow; $row <= $lastRow; $row++) {
            if (($row - $firstDataRow) % 2 === 1) {
                $range = 'A'.$row.':'.self::columnLetter($lastColumn).$row;
                $sheet->getStyle($range)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => self::ZEBRA_FILL],
                    ],
                ]);
            }
        }
    }

    public static function applySummaryCell(Worksheet $sheet, string $range, bool $bold = true): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => $bold,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::SUMMARY_FILL],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'readOrder' => Alignment::READORDER_RTL,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
    }

    /**
     * Auto-size every column in the active sheet up to $lastColumn.
     */
    public static function autoSize(Worksheet $sheet, int $lastColumn): void
    {
        for ($col = 1; $col <= $lastColumn; $col++) {
            $sheet->getColumnDimension(self::columnLetter($col))->setAutoSize(true);
        }
    }

    /**
     * Convert a 1-based column index to its Excel letter (1 → A, 27 → AA).
     */
    public static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = (int) (($index - $mod) / 26);
        }

        return $letter;
    }
}
