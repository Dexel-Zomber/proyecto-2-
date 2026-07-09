<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ReportService;
use App\Services\XmlExportService;
use Illuminate\Http\Request;

class AdminReportXmlController extends BaseController
{
    public function course(int $course, Request $request, ReportService $reportService, XmlExportService $xmlExportService, AuditLogService $auditLogService)
    {
        return $this->xmlResponse($reportService->courseReport($course), $xmlExportService, $auditLogService, $request, "reporte-curso-{$course}.xml");
    }

    public function teacher(int $teacher, Request $request, ReportService $reportService, XmlExportService $xmlExportService, AuditLogService $auditLogService)
    {
        return $this->xmlResponse($reportService->teacherReport($teacher), $xmlExportService, $auditLogService, $request, "reporte-profesor-{$teacher}.xml");
    }

    public function student(int $student, Request $request, ReportService $reportService, XmlExportService $xmlExportService, AuditLogService $auditLogService)
    {
        return $this->xmlResponse($reportService->studentReport($student), $xmlExportService, $auditLogService, $request, "reporte-estudiante-{$student}.xml");
    }

    private function xmlResponse(?array $report, XmlExportService $xmlExportService, AuditLogService $auditLogService, Request $request, string $filename)
    {
        abort_unless($report, 404);

        $auditLogService->record($request->attributes->get('authUser'), 'reports.xml_exported', "Exporto {$filename}.", null, null, [
            'report_type' => $report['type'],
            'filename' => $filename,
        ], $request);

        return response($xmlExportService->reportToXml($report), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
