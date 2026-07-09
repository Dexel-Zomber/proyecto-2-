<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ScoreService
{
    public function storeTeacherScore(User $teacher, array $data): Score
    {
        $subject = Subject::with('students')->findOrFail($data['subject_id']);

        if ($subject->teacher_id !== $teacher->id) {
            throw ValidationException::withMessages([
                'subject_id' => 'No tiene permiso para usar esta materia',
            ]);
        }

        if (! $subject->students->contains('id', $data['student_id'])) {
            throw ValidationException::withMessages([
                'student_id' => 'El estudiante no esta inscrito en esta materia.',
            ]);
        }

        $score = Score::updateOrCreate([
            'student_id' => $data['student_id'],
            'subject_id' => $data['subject_id'],
            'label' => $data['label'] ?: 'General',
        ], [
            'value' => $data['value'],
        ]);

        AcademicAlertService::syncScoreAlert($score);

        return $score;
    }
}
