<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatRequest;
use App\Models\Alert;
use App\Models\Score;
use App\Services\AiAssistantService;
use App\Services\RiskAssessmentService;

class StudentController extends BaseController
{
    public function index(RiskAssessmentService $riskAssessmentService)
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
        $riskProfile = $riskAssessmentService->assessStudent($user, $scores, $alerts);
        $recommendations = $riskProfile['recommendations'];

        return view('dashboard.student', compact('user', 'scores', 'alerts', 'average', 'recommendations', 'riskProfile'));
    }

    public function aiChat(AiChatRequest $request)
    {
        $user = $this->currentUser();

        $validated = $request->validated();

        $scores = Score::where('student_id', $user->id)
            ->with('subject')
            ->get()
            ->map(fn ($score) => [
                'subject' => $score->subject?->name ?? 'Materia',
                'label' => $score->label,
                'value' => $score->value,
            ])->all();

        $alerts = Alert::where('student_id', $user->id)
            ->where('resolved', false)
            ->with('subject')
            ->get()
            ->map(fn ($alert) => [
                'subject' => $alert->subject?->name ?? 'General',
                'message' => $alert->message,
            ])->all();

        $answer = AiAssistantService::chatWithStudent($user, $scores, $alerts, $validated['question']);

        return back()->with('aiQuestion', $validated['question'])->with('aiAnswer', $answer);
    }
}
