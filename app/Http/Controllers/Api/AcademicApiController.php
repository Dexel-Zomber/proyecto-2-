<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Course;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;

class AcademicApiController extends Controller
{
    public function courses()
    {
        return response()->json([
            'data' => Course::withCount(['subjects', 'students'])->orderBy('name')->get(),
        ]);
    }

    public function subjects()
    {
        return response()->json([
            'data' => Subject::with(['course', 'teacher'])->orderBy('name')->get(),
        ]);
    }

    public function studentScores(User $student)
    {
        abort_unless($student->isStudent(), 404);

        return response()->json([
            'data' => Score::where('student_id', $student->id)
                ->with(['subject.course'])
                ->latest()
                ->get(),
        ]);
    }

    public function studentAlerts(User $student)
    {
        abort_unless($student->isStudent(), 404);

        return response()->json([
            'data' => Alert::where('student_id', $student->id)
                ->with('subject')
                ->latest()
                ->get(),
        ]);
    }
}
