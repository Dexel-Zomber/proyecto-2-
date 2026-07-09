<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class ReportApiController extends Controller
{
    public function course(int $course, ReportService $reportService)
    {
        return $this->reportResponse($reportService->courseReport($course));
    }

    public function teacher(int $teacher, ReportService $reportService)
    {
        return $this->reportResponse($reportService->teacherReport($teacher));
    }

    public function student(int $student, ReportService $reportService)
    {
        return $this->reportResponse($reportService->studentReport($student));
    }

    private function reportResponse(?array $report)
    {
        abort_unless($report, 404);

        return response()->json([
            'data' => $report,
        ]);
    }
}
