<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class RiskAssessmentService
{
    public function assessStudent(User $student, Collection $scores, Collection $alerts): array
    {
        $average = $scores->count() ? round($scores->avg('value'), 1) : 0;
        $criticalScores = $scores->where('value', '<', 60)->count();
        $warningScores = $scores->whereBetween('value', [60, 69.99])->count();
        $openAlerts = $alerts->where('resolved', false)->count();

        $riskScore = 0;
        $reasons = [];

        if ($average < 60) {
            $riskScore += 45;
            $reasons[] = 'Promedio general por debajo de 60.';
        } elseif ($average < 70) {
            $riskScore += 30;
            $reasons[] = 'Promedio general por debajo del minimo recomendado.';
        } elseif ($average < 80) {
            $riskScore += 12;
            $reasons[] = 'Promedio aceptable, pero con margen de mejora.';
        }

        if ($criticalScores > 0) {
            $riskScore += min(30, $criticalScores * 10);
            $reasons[] = "Tiene {$criticalScores} calificacion(es) criticas.";
        }

        if ($warningScores > 0) {
            $riskScore += min(15, $warningScores * 5);
            $reasons[] = "Tiene {$warningScores} calificacion(es) bajas.";
        }

        if ($openAlerts > 0) {
            $riskScore += min(25, $openAlerts * 8);
            $reasons[] = "Tiene {$openAlerts} alerta(s) activa(s).";
        }

        $riskScore = min(100, $riskScore);
        $level = $this->levelFromScore($riskScore);
        $recommendations = $this->recommendations($scores, $alerts, $average);

        return [
            'score' => $riskScore,
            'level' => $level['label'],
            'severity' => $level['severity'],
            'average' => $average,
            'reasons' => $reasons ?: ['No se detectan factores fuertes de riesgo.'],
            'recommendations' => $recommendations,
            'ai_summary' => AiAssistantService::riskSummary($student, [
                'average' => $average,
                'risk_score' => $riskScore,
                'level' => $level['label'],
                'reasons' => $reasons,
                'recommendations' => $recommendations,
            ]),
        ];
    }

    private function levelFromScore(int $score): array
    {
        if ($score >= 75) {
            return ['label' => 'Riesgo critico', 'severity' => 'critical'];
        }

        if ($score >= 50) {
            return ['label' => 'Riesgo alto', 'severity' => 'critical'];
        }

        if ($score >= 25) {
            return ['label' => 'Riesgo medio', 'severity' => 'warning'];
        }

        return ['label' => 'Riesgo bajo', 'severity' => 'success'];
    }

    private function recommendations(Collection $scores, Collection $alerts, float $average): array
    {
        $recommendations = [];

        $lowestSubjects = $scores
            ->groupBy('subject_id')
            ->map(fn ($subjectScores) => [
                'name' => $subjectScores->first()->subject?->name ?? 'Materia',
                'average' => round($subjectScores->avg('value'), 1),
            ])
            ->sortBy('average')
            ->take(2);

        foreach ($lowestSubjects as $subject) {
            if ($subject['average'] < 70) {
                $recommendations[] = "Prioriza {$subject['name']}: tu promedio actual es {$subject['average']}.";
            }
        }

        if ($alerts->where('resolved', false)->isNotEmpty()) {
            $recommendations[] = 'Revisa las alertas activas y acuerda una accion concreta con tu profesor.';
        }

        if ($average < 70) {
            $recommendations[] = 'Dedica sesiones cortas de repaso a las materias con menor promedio antes de avanzar a temas nuevos.';
        }

        if ($average >= 80 && $recommendations === []) {
            $recommendations[] = 'Mantiene una rutina estable y revisa tus notas bajas antes de cada evaluacion.';
        }

        return $recommendations ?: ['Sigue revisando tus calificaciones y mantente al dia con las actividades.'];
    }
}
