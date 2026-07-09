<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class ReportService
{
    public function courseReport(int|string|null $courseId): ?array
    {
        $course = Course::with('subjects')->find($courseId);

        if (! $course) {
            return null;
        }

        $subjectIds = $course->subjects->pluck('id')->all();
        $studentsInCourse = $course->students()->with(['scores' => function ($query) use ($subjectIds) {
            $query->whereIn('subject_id', $subjectIds)->with('subject');
        }])->get();

        $rows = $studentsInCourse->map(function ($student) {
            $scores = $student->scores->pluck('value');

            return [
                'student' => $student,
                'average' => $scores->count() ? round($scores->avg(), 1) : 0,
                'subjects' => $student->scores,
            ];
        });

        return [
            'type' => 'course',
            'title' => "Reporte por curso: {$course->name}",
            'course' => $course,
            'rows' => $rows,
            'ai_summary' => AiAssistantService::reportSummary('curso', $course->name, $rows->map(fn ($row) => [
                'estudiante' => $row['student']->name,
                'promedio' => $row['average'],
            ])->all()),
        ];
    }

    public function teacherReport(int|string|null $teacherId): ?array
    {
        $teacher = User::where('role', 'teacher')->with(['subjects.scores.student'])->find($teacherId);

        if (! $teacher) {
            return null;
        }

        $rows = $teacher->subjects->map(function ($subject) {
            $scores = $subject->scores->pluck('value');

            return [
                'subject' => $subject,
                'average' => $scores->count() ? round($scores->avg(), 1) : 0,
                'studentCount' => $subject->scores->pluck('student_id')->unique()->count(),
            ];
        });

        return [
            'type' => 'teacher',
            'title' => "Reporte por profesor: {$teacher->name}",
            'teacher' => $teacher,
            'rows' => $rows,
            'ai_summary' => AiAssistantService::reportSummary('profesor', $teacher->name, $rows->map(fn ($row) => [
                'materia' => $row['subject']->name,
                'promedio' => $row['average'],
                'estudiantes' => $row['studentCount'],
            ])->all()),
        ];
    }

    public function studentReport(int|string|null $studentId): ?array
    {
        $student = User::where('role', 'student')->with(['scores.subject.course', 'alerts'])->find($studentId);

        if (! $student) {
            return null;
        }

        $scores = $student->scores;
        $average = $scores->count() ? round($scores->avg('value'), 1) : 0;

        return [
            'type' => 'student',
            'title' => "Reporte por estudiante: {$student->name}",
            'student' => $student,
            'average' => $average,
            'scores' => $scores,
            'alerts' => $student->alerts,
            'ai_summary' => AiAssistantService::reportSummary('estudiante', $student->name, $scores->map(fn ($score) => [
                'materia' => $score->subject?->name,
                'parcial' => $score->label,
                'nota' => $score->value,
            ])->all()),
        ];
    }
}
