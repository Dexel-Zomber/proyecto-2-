<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Score;
use App\Models\Setting;

class AcademicAlertService
{
    public static function syncScoreAlert(Score $score): void
    {
        $window = (int) Setting::getValue('alert_window', 3);
        $warning = (int) Setting::getValue('alert_warning', 70);
        $danger = (int) Setting::getValue('alert_danger', 60);

        $studentId = $score->student_id;
        $subjectId = $score->subject_id;
        $title = 'Alerta de promedio bajo';

        $values = self::lastNValues($studentId, $subjectId, $window);
        $avg = $values !== null ? (array_sum($values) / count($values)) : null;

        // If we don't have enough data, fallback to the single score
        $valueToUse = $avg !== null ? $avg : $score->value;

        $severity = self::getSeverity($valueToUse, $warning, $danger);
        $resolved = $valueToUse >= $warning;
        $trend = self::trendLabel($values ?? [$score->value]);

        $message = \App\Services\AiAssistantService::alertInsight(
            $score->student?->name ?? 'Estudiante',
            $score->subject?->name,
            $values ?? [$score->value],
            $valueToUse,
            $warning,
            $danger,
            $trend
        ) ?? self::buildMessage($valueToUse, $warning, $danger, $score->subject?->name, $window);

        $reason = self::buildReason($valueToUse, $warning, $danger, $score->subject?->name, $window, $values ?? [$score->value]);

        $meta = [
            'window' => $window,
            'values' => $values ?? [$score->value],
            'avg' => round($valueToUse, 2),
            'trend' => $trend,
            'reason' => $reason,
            'thresholds' => [
                'warning' => $warning,
                'danger' => $danger,
            ],
        ];

        if ($resolved) {
            Alert::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('title', $title)
                ->update(['resolved' => true, 'meta' => $meta]);

            return;
        }

        Alert::updateOrCreate([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'title' => $title,
        ], [
            'message' => $message,
            'severity' => $severity,
            'resolved' => false,
            'meta' => $meta,
        ]);
    }

    public static function recalculateAll(): void
    {
        $window = (int) Setting::getValue('alert_window', 3);
        $warning = (int) Setting::getValue('alert_warning', 70);
        $danger = (int) Setting::getValue('alert_danger', 60);

        // iterate all distinct student/subject pairs that have scores
        $pairs = Score::select('student_id', 'subject_id')
            ->distinct()
            ->with(['student', 'subject'])
            ->get();

        foreach ($pairs as $p) {
            $values = self::lastNValues($p->student_id, $p->subject_id, $window);
            if ($values === null) {
                continue;
            }

            $avg = array_sum($values) / count($values);

            $title = 'Alerta de promedio bajo';
            $subjectName = optional($p->subject)->name;

            $severity = self::getSeverity($avg, $warning, $danger);
            $resolved = $avg >= $warning;
            $trend = self::trendLabel($values);

            $message = \App\Services\AiAssistantService::alertInsight(
                optional($p->student)->name ?? 'Estudiante',
                $subjectName,
                $values,
                $avg,
                $warning,
                $danger,
                $trend
            ) ?? self::buildMessage($avg, $warning, $danger, $subjectName, $window);

            $reason = self::buildReason($avg, $warning, $danger, $subjectName, $window, $values);

            $meta = [
                'window' => $window,
                'values' => $values,
                'avg' => round($avg, 2),
                'trend' => $trend,
                'reason' => $reason,
                'thresholds' => [
                    'warning' => $warning,
                    'danger' => $danger,
                ],
            ];

            if ($resolved) {
                Alert::where('student_id', $p->student_id)
                    ->where('subject_id', $p->subject_id)
                    ->where('title', $title)
                    ->update(['resolved' => true, 'meta' => $meta]);

                continue;
            }

            Alert::updateOrCreate([
                'student_id' => $p->student_id,
                'subject_id' => $p->subject_id,
                'title' => $title,
            ], [
                'message' => $message,
                'severity' => $severity,
                'resolved' => false,
                'meta' => $meta,
            ]);
        }
    }

    protected static function movingAverage(int $studentId, int $subjectId, int $window): ?float
    {
        $values = self::lastNValues($studentId, $subjectId, $window);

        if ($values === null) {
            return null;
        }

        return array_sum($values) / count($values);
    }

    protected static function lastNValues(int $studentId, int $subjectId, int $window): ?array
    {
        $values = Score::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->limit(max(1, $window))
            ->pluck('value')
            ->toArray();

        if (empty($values)) {
            return null;
        }

        return $values;
    }

    public static function getSeverity(float $value, int $warning, int $danger): string
    {
        return $value < $danger ? 'critical' : 'warning';
    }

    public static function buildMessage(float $value, int $warning, int $danger, ?string $subjectName, int $window = 1): string
    {
        $rounded = round($value, 1);
        if ($value < $danger) {
            return "(últimas {$window}) Promedio crítico de {$rounded} en {$subjectName}. Requiere intervención inmediata.";
        }

        return "(últimas {$window}) Promedio bajo de {$rounded} en {$subjectName}. Recomendamos apoyo adicional.";
    }

    protected static function buildReason(float $value, int $warning, int $danger, ?string $subjectName, int $window, array $values): string
    {
        $rounded = round($value, 1);
        $trend = self::trendLabel($values);
        $name = $subjectName ? "en {$subjectName} " : '';

        if ($value < $danger) {
            return "El promedio de las últimas {$window} calificaciones {$name}es {$rounded}, por debajo del umbral crítico ({$danger}). La tendencia es {$trend}. Requiere intervención inmediata.";
        }

        if ($value < $warning) {
            return "El promedio de las últimas {$window} calificaciones {$name}es {$rounded}, por debajo del umbral de advertencia ({$warning}). La tendencia es {$trend}. Recomendamos apoyo adicional.";
        }

        return "El promedio de las últimas {$window} calificaciones {$name}es {$rounded}. La tendencia es {$trend}. El desempeño se mantiene dentro de los límites aceptables.";
    }

    /**
     * $values viene ordenado de la nota MÁS RECIENTE a la MÁS ANTIGUA
     * (ver lastNValues, que ordena por created_at DESC).
     */
    protected static function trendLabel(array $values): string
    {
        if (count($values) < 2) {
            return 'estable';
        }

        $newest = reset($values);
        $oldest = end($values);
        $delta = $newest - $oldest;

        if ($delta <= -5) {
            return 'descendente';
        }

        if ($delta >= 5) {
            return 'ascendente';
        }

        return 'estable';
    }
}
