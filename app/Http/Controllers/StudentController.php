<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Score;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;

class StudentController extends BaseController
{
    public function index()
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isStudent()) {
            return redirect('/login');
        }

        $scores = Score::where('student_id', $user->id)
            ->with(['subject.course'])
            ->get();

        $alerts = Alert::where('student_id', $user->id)
            ->where('resolved', false)
            ->with('subject')
            ->latest()
            ->get();

        $average = $scores->avg('value') ?: 0;
        $recommendations = [];

        if ($average < 70) {
            $recommendations[] = 'Revisa tus materias con notas bajas y pide apoyo a tus profesores.';
        }

        if ($alerts->count()) {
            $recommendations[] = 'Sigue las recomendaciones del sistema para mejorar tus resultados.';
        }

        if ($average >= 70 && $average < 85) {
            $recommendations[] = 'Mantén el ritmo y revisa las materias donde hayas sacado menos de 80.';
        }

        if ($average >= 85) {
            $recommendations[] = 'Excelente desempeño, continúa con el mismo enfoque.';
        }

        return view('dashboard.student', compact('user', 'scores', 'alerts', 'average', 'recommendations'));
    }

    public function aiChat(Request $request)
    {
        $user = $this->currentUser();

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $scores = Score::where('student_id', $user->id)
            ->with('subject')
            ->get()
            ->map(fn ($s) => [
                'subject' => $s->subject?->name ?? 'Materia',
                'label' => $s->label,
                'value' => $s->value,
            ])->all();

        $alerts = Alert::where('student_id', $user->id)
            ->where('resolved', false)
            ->with('subject')
            ->get()
            ->map(fn ($a) => [
                'subject' => $a->subject?->name ?? 'General',
                'message' => $a->message,
            ])->all();

        $answer = AiAssistantService::chatWithStudent($user, $scores, $alerts, $validated['question']);

        return back()->with('aiQuestion', $validated['question'])->with('aiAnswer', $answer);
    }
}
