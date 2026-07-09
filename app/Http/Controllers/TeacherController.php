<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportScoresRequest;
use App\Http\Requests\StoreScoreRequest;
use App\Models\Alert;
use App\Models\Score;
use App\Models\Subject;
use App\Services\AuditLogService;
use App\Services\BulkImportService;
use App\Services\ScoreService;

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

        // Estudiantes inscritos que aún no tienen ninguna nota registrada,
        // agrupados por materia, para que el profesor los detecte fácil.
        $pendingBySubject = $subjects->mapWithKeys(function ($subject) {
            $gradedIds = $subject->scores->pluck('student_id')->unique();

            return [$subject->id => $subject->students->reject(fn ($s) => $gradedIds->contains($s->id))];
        });

        // Alertas de los estudiantes matriculados en las materias de este profesor,
        // sin resolver primero, para que las vea de inmediato en su panel.
        $subjectIds = $subjects->pluck('id');
        $alerts = Alert::whereIn('subject_id', $subjectIds)
            ->with(['student', 'subject'])
            ->orderBy('resolved')
            ->orderByDesc('updated_at')
            ->get();

        return view('dashboard.teacher', compact('user', 'subjects', 'students', 'pendingBySubject', 'alerts'));
    }

    public function storeScore(StoreScoreRequest $request, ScoreService $scoreService, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $score = $scoreService->storeTeacherScore($user, $validated);

        $auditLogService->record($user, 'scores.saved', "Registro nota {$score->value} para {$score->student?->name}.", Score::class, $score->id, [
            'student_id' => $score->student_id,
            'subject_id' => $score->subject_id,
            'label' => $score->label,
            'value' => $score->value,
        ], $request);

        return back()->with('message', 'Nota registrada correctamente');
    }

    public function importScores(ImportScoresRequest $request, BulkImportService $bulkImportService, ScoreService $scoreService, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        $summary = $bulkImportService->importScores($user, $request->file('scores_file'), $scoreService);

        $auditLogService->record($user, 'scores.imported', 'Importo notas desde archivo CSV.', Score::class, null, [
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'errors' => count($summary['errors']),
        ], $request);

        return back()->with('importSummary', [
            'title' => 'Importacion de notas',
            ...$summary,
        ]);
    }

    public function destroyScore(Score $score, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        if ($score->subject->teacher_id !== $user->id) {
            return back()->withErrors(['score' => 'No tienes permiso para eliminar esta nota.']);
        }

        $scoreId = $score->id;
        $studentName = $score->student?->name ?? 'Estudiante';
        $subjectId = $score->subject_id;
        $studentId = $score->student_id;

        $score->delete();

        $auditLogService->record($user, 'scores.deleted', "Elimino una nota de {$studentName}.", Score::class, $scoreId, [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
        ], request());

        return back()->with('message', 'Nota eliminada correctamente');
    }

    public function resolveAlert(Alert $alert, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        if (! $alert->subject || $alert->subject->teacher_id !== $user->id) {
            return back()->withErrors(['alert' => 'No tienes permiso para modificar esta alerta.']);
        }

        $alert->update(['resolved' => true]);

        $auditLogService->record($user, 'alerts.resolved', "Marco alerta resuelta para {$alert->student?->name}.", Alert::class, $alert->id, [
            'subject_id' => $alert->subject_id,
        ], request());

        return back()->with('message', 'Alerta marcada como resuelta');
    }

    public function unresolveAlert(Alert $alert, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isTeacher()) {
            return redirect('/login');
        }

        if (! $alert->subject || $alert->subject->teacher_id !== $user->id) {
            return back()->withErrors(['alert' => 'No tienes permiso para modificar esta alerta.']);
        }

        $alert->update(['resolved' => false]);

        $auditLogService->record($user, 'alerts.unresolved', "Marco alerta no resuelta para {$alert->student?->name}.", Alert::class, $alert->id, [
            'subject_id' => $alert->subject_id,
        ], request());

        return back()->with('message', 'Alerta marcada como no resuelta');
    }
}
