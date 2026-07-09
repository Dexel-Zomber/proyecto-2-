<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class AdminReportExportController extends BaseController
{
    public function coursePdf(int $course, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->courseReport($course), $exportService, $auditLogService, 'pdf', "reporte-curso-{$course}.pdf");
    }

    public function courseExcel(int $course, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->courseReport($course), $exportService, $auditLogService, 'excel', "reporte-curso-{$course}.xls");
    }

    public function teacherPdf(int $teacher, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->teacherReport($teacher), $exportService, $auditLogService, 'pdf', "reporte-profesor-{$teacher}.pdf");
    }

    public function teacherExcel(int $teacher, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->teacherReport($teacher), $exportService, $auditLogService, 'excel', "reporte-profesor-{$teacher}.xls");
    }

    public function studentPdf(int $student, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->studentReport($student), $exportService, $auditLogService, 'pdf', "reporte-estudiante-{$student}.pdf");
    }

    public function studentExcel(int $student, Request $request, ReportService $reportService, ReportExportService $exportService, AuditLogService $auditLogService)
    {
        return $this->download($request, $reportService->studentReport($student), $exportService, $auditLogService, 'excel', "reporte-estudiante-{$student}.xls");
    }

    private function download(Request $request, ?array $report, ReportExportService $exportService, AuditLogService $auditLogService, string $format, string $filename)
    {
        abort_unless($report, 404);

        $auditLogService->record($request->attributes->get('authUser'), "reports.{$format}_exported", "Exporto {$filename}.", null, null, [
            'report_type' => $report['type'],
            'filename' => $filename,
        ], $request);

        if ($format === 'pdf') {
            return response($exportService->pdf($report), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        return response($exportService->excel($report), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
