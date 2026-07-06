<?php

namespace App\Http\Controllers;

use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicAlertService;
use Illuminate\Http\Request;

class TeacherController extends BaseController
{
    public function index()
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        $subjects = Subject::where('teacher_id', $user->id)
            ->with(['course', 'scores.student', 'students'])
            ->get();

        $students = $subjects->flatMap(function ($subject) {
            return $subject->students;
        })->unique('id');

        return view('dashboard.teacher', compact('subjects', 'students'));
    }

    public function storeScore(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'student_id' => ['required', 'exists:users,id'],
            'label' => ['nullable', 'string', 'max:50'],
            'value' => ['required', 'numeric', 'between:0,100'],
        ]);

        $subject = Subject::find($validated['subject_id']);

        if ($subject->teacher_id !== $user->id) {
            return back()->withErrors(['subject_id' => 'No tiene permiso para usar esta materia']);
        }

        $label = $validated['label'] ?: 'General';

        $score = Score::updateOrCreate([
            'student_id' => $validated['student_id'],
            'subject_id' => $validated['subject_id'],
            'label' => $label,
        ], [
            'value' => $validated['value'],
        ]);

        AcademicAlertService::syncScoreAlert($score);

        return back()->with('message', 'Nota registrada correctamente');
    }

    public function destroyScore(Score $score)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        if ($score->subject->teacher_id !== $user->id) {
            return back()->withErrors(['score' => 'No tienes permiso para eliminar esta nota.']);
        }

        $score->delete();

        return back()->with('message', 'Nota eliminada correctamente');
    }
}
