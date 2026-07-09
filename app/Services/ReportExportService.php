<?php

namespace App\Services;

class ReportExportService
{
    public function excel(array $report): string
    {
        $rows = $this->rows($report);
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>'.e($report['title']).'</h2>';
        $html .= '<table border="1"><thead><tr>';

        foreach ($this->headers($report) as $header) {
            $html .= '<th>'.e($header).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.e((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    public function pdf(array $report): string
    {
        $lines = [$report['title'], 'Generado: '.now()->format('d/m/Y H:i'), ''];
        $lines[] = implode(' | ', $this->headers($report));

        foreach ($this->rows($report) as $row) {
            $lines[] = implode(' | ', array_map(fn ($value) => (string) $value, $row));
        }

        if (! empty($report['ai_summary'])) {
            $lines[] = '';
            $lines[] = 'Resumen IA: '.$report['ai_summary'];
        }

        return $this->simplePdf($lines);
    }

    private function headers(array $report): array
    {
        return match ($report['type']) {
            'course' => ['Estudiante', 'Promedio'],
            'teacher' => ['Materia', 'Promedio', 'Estudiantes'],
            'student' => ['Materia', 'Curso', 'Parcial', 'Nota'],
            default => ['Detalle'],
        };
    }

    private function rows(array $report): array
    {
        return match ($report['type']) {
            'course' => $report['rows']->map(fn ($row) => [
                $row['student']->name,
                $row['average'],
            ])->all(),
            'teacher' => $report['rows']->map(fn ($row) => [
                $row['subject']->name,
                $row['average'],
                $row['studentCount'],
            ])->all(),
            'student' => $report['scores']->map(fn ($score) => [
                $score->subject?->name ?? 'N/A',
                $score->subject?->course?->name ?? 'N/A',
                $score->label,
                $score->value,
            ])->all(),
            default => [],
        };
    }

    private function simplePdf(array $lines): string
    {
        $content = "BT\n/F1 11 Tf\n50 790 Td\n14 TL\n";

        foreach ($lines as $line) {
            foreach (str_split($this->pdfText($line), 95) as $part) {
                $content .= '('.$part.") Tj\nT*\n";
            }
        }

        $content .= "ET\n";

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}endstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
