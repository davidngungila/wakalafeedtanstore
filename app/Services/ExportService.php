<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Get business info for report headers.
     */
    public function businessInfo(): array
    {
        $general = \App\Models\Setting::where('key', 'general')->first();
        $values = $general?->value ?? [];

        return [
            'name' => $values['business_name'] ?? 'Wakala Feed Tan Store',
            'address' => $values['address'] ?? 'Kiborilon Moshi Kilimanjaro',
            'phone' => $values['contact_phone'] ?? '+255 7xx xxx xxx',
            'email' => $values['contact_email'] ?? 'wakala@feedtanstore.com',
        ];
    }

    /**
     * Filter columns to only selected ones, or return all if none selected.
     *
     * @param array<int, array{key: string, label: string}> $available
     * @param array<int, string>|null $selected
     * @return array<int, array{key: string, label: string}>
     */
    public function resolveColumns(array $available, ?array $selected): array
    {
        if (empty($selected)) {
            return $available;
        }

        $selected = array_map('strval', $selected);
        $filtered = array_filter($available, fn ($col) => in_array($col['key'], $selected, true));

        return array_values($filtered) ?: $available;
    }

    /**
     * Generate a PDF download response.
     *
     * @param string $title
     * @param string $subtitle
     * @param array<int, array{key: string, label: string}> $columns
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows
     * @param array<string, mixed> $meta
     */
    public function pdf(string $title, string $subtitle, array $columns, Collection $rows, array $meta = []): \Illuminate\Http\Response
    {
        $business = $this->businessInfo();
        $user = auth()->user();
        $generatedAt = now()->format('d M Y H:i');
        $generatedBy = $user?->name ?? 'System';

        $html = view('exports.generic-pdf', compact('title', 'subtitle', 'columns', 'rows', 'business', 'meta', 'generatedAt', 'generatedBy'))->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true]);

        $filename = $this->filename($title, 'pdf');

        return $pdf->download($filename);
    }

    /**
     * Generate an Excel download response.
     *
     * @param string $title
     * @param array<int, array{key: string, label: string}> $columns
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows
     */
    public function excel(string $title, array $columns, Collection $rows): StreamedResponse
    {
        $business = $this->businessInfo();
        $filename = $this->filename($title, 'xlsx');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        // Business header
        $sheet->setCellValue('A1', $business['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('C2592B'));
        $sheet->setCellValue('A2', $business['address'].' | '.$business['phone'].' | '.$business['email']);
        $sheet->getStyle('A2')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('7A5C42'));

        // Title
        $sheet->setCellValue('A4', $title);
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1a1a1a'));
        $sheet->setCellValue('A5', 'Generated: '.now()->format('d M Y H:i').' by '.(auth()->user()?->name ?? 'System').' | Records: '.$rows->count());
        $sheet->getStyle('A5')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6B5A48'));

        // Headings
        $headingRow = 7;
        $colIndex = 1;
        foreach ($columns as $col) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex).$headingRow;
            $sheet->setCellValue($cell, $col['label']);
            $colIndex++;
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns));
        $headerRange = 'A'.$headingRow.':'.$lastCol.$headingRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5E6E3F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5DDD0']]],
        ]);
        $sheet->getRowDimension($headingRow)->setRowHeight(22);

        // Data rows
        $rowIndex = $headingRow + 1;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($columns as $col) {
                $key = $col['key'];
                $value = $row[$key] ?? '';
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex).$rowIndex;
                $sheet->setCellValue($cell, $value);
                // Style amount columns right-aligned
                if (in_array($key, ['amount', 'fee', 'commission', 'balance', 'total_volume', 'total_commission', 'volume', 'debits', 'credits', 'opening', 'closing'], true)) {
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $colIndex++;
            }
            // Alternating row color
            if ($rowIndex % 2 === 0) {
                $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4ECDC');
            }
            $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5DDD0');
            $rowIndex++;
        }

        // Auto size columns
        foreach (range(1, count($columns)) as $i) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header
        $sheet->freezePane('A'.($headingRow + 1));

        // Print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getHeaderFooter()->setOddFooter('&L'.$business['name'].' &CPage &P of &N &R'.now()->format('d/m/Y'));

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function filename(string $title, string $ext): string
    {
        $slug = \Illuminate\Support\Str::slug($title, '-');
        $slug = $slug ?: 'export';
        return $slug.'-'.now()->format('Ymd-His').'.'.$ext;
    }
}
