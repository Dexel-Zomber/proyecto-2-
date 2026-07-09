<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente sencillo para Groq (API gratuita, compatible con el formato de OpenAI).
 * Toda llamada está protegida con try/catch: si no hay API key configurada o
 * la API falla, el sistema sigue funcionando con el comportamiento anterior
 * (plantillas fijas) en vez de romperse.
 *
 * Para activarlo: crear una cuenta gratis en https://console.groq.com,
 * generar una API key y ponerla en el .env como GROQ_API_KEY=...
 */
class AiAssistantService
{
    protected static function isConfigured(): bool
    {
        return filled(config('services.groq.key'));
    }

    protected static function complete(string $systemPrompt, string $userPrompt, int $maxTokens = 300): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        try {
            $model = config('services.groq.model');

            $payload = [
                'model' => $model,
                // Los modelos "razonadores" (gpt-oss) consumen parte de max_tokens
                // pensando internamente antes de escribir la respuesta visible.
                // Le damos más margen para que no se quede sin espacio y devuelva vacío.
                'max_tokens' => max($maxTokens, 600),
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ];

            // reasoning_effort solo lo soportan los modelos gpt-oss de Groq.
            // Lo ponemos en "low" para que gasten pocos tokens pensando y dejen
            // presupuesto de sobra para la respuesta que sí vemos.
            if (str_contains($model, 'gpt-oss')) {
                $payload['reasoning_effort'] = 'low';
            }

            $http = Http::withToken(config('services.groq.key'))->timeout(20);

            // En Windows, PHP muchas veces no trae el paquete de certificados raíz
            // instalado, lo que rompe TODAS las peticiones HTTPS salientes con
            // "SSL certificate problem: unable to get local issuer certificate".
            // Desactivamos la verificación SOLO en entorno local para no bloquear
            // el desarrollo; en producción esto debe ir siempre activado.
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if ($response->failed()) {
                Log::warning('Groq API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $content = trim($response->json('choices.0.message.content') ?? '');

            if ($content === '') {
                Log::warning('Groq API returned empty content', ['model' => $model, 'response' => $response->json()]);

                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            Log::warning('Groq API exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * 1) Mensaje de alerta más natural y personalizado, en vez de la plantilla fija.
     * Si la IA no está disponible, el llamador debe usar su propio texto de respaldo.
     */
    public static function alertInsight(string $studentName, ?string $subjectName, array $values, float $average, int $warning, int $danger, string $trend): ?string
    {
        $system = 'Eres un asistente pedagógico de un sistema académico ecuatoriano. '
            .'Escribe en español, en un tono cercano pero profesional, máximo 3 frases. '
            .'No inventes datos que no te den; usa solo los números proporcionados.';

        $valuesText = implode(', ', array_map(fn ($v) => number_format((float) $v, 1), $values));

        $user = "Estudiante: {$studentName}\n"
            .'Materia: '.($subjectName ?? 'General')."\n"
            ."Últimas calificaciones (de la más reciente a la más antigua): {$valuesText}\n"
            .'Promedio actual: '.round($average, 1)."\n"
            ."Umbral de advertencia: {$warning}, umbral crítico: {$danger}\n"
            ."Tendencia detectada: {$trend}\n\n"
            .'Redacta una alerta breve y una recomendación concreta y accionable para el estudiante.';

        return self::complete($system, $user, 180);
    }

    /**
     * 2) Chat de ayuda para el estudiante, usando sus propios datos como contexto.
     */
    public static function chatWithStudent(User $student, array $scores, array $alerts, string $question): string
    {
        if (! self::isConfigured()) {
            return 'El asistente de IA no está disponible en este momento (falta configurar la clave de la API). '
                .'Mientras tanto, puedes revisar tus calificaciones y alertas en esta misma página.';
        }

        $system = 'Eres un asistente académico para estudiantes de un instituto en Ecuador. '
            .'Responde en español, de forma breve, clara y motivadora. '
            .'Solo puedes hablar de la información académica que se te da; si te preguntan algo fuera de eso, '
            .'indica amablemente que no tienes esa información.';

        $scoresText = collect($scores)->map(fn ($s) => "- {$s['subject']} ({$s['label']}): {$s['value']}")->implode("\n") ?: 'Sin calificaciones registradas.';
        $alertsText = collect($alerts)->map(fn ($a) => "- {$a['subject']}: {$a['message']}")->implode("\n") ?: 'Sin alertas activas.';

        $user = "Estudiante: {$student->name}\n\n"
            ."Calificaciones:\n{$scoresText}\n\n"
            ."Alertas activas:\n{$alertsText}\n\n"
            ."Pregunta del estudiante: {$question}";

        return self::complete($system, $user, 300)
            ?? 'No pude generar una respuesta en este momento. Intenta de nuevo en unos segundos.';
    }

    /**
     * 3) Resumen ejecutivo en lenguaje natural para los reportes del panel admin.
     */
    public static function reportSummary(string $type, string $title, array $rows): ?string
    {
        if (! self::isConfigured() || empty($rows)) {
            return null;
        }

        $system = 'Eres un analista académico. Redacta en español un resumen ejecutivo breve (máximo 4 frases), '
            .'destacando patrones, riesgos y una recomendación general. No repitas la tabla, solo interprétala.';

        $rowsText = json_encode(array_slice($rows, 0, 30), JSON_UNESCAPED_UNICODE);

        $user = "Tipo de reporte: {$type}\nTítulo: {$title}\nDatos (JSON): {$rowsText}\n\n"
            .'Genera el resumen ejecutivo.';

        return self::complete($system, $user, 220);
    }

    public static function riskSummary(User $student, array $riskProfile): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $system = 'Eres un orientador academico. Redacta en espanol una recomendacion breve, concreta y motivadora. '
            .'Usa solo los datos entregados y no prometas resultados garantizados.';

        $user = "Estudiante: {$student->name}\n"
            .'Promedio: '.$riskProfile['average']."\n"
            .'Nivel de riesgo: '.$riskProfile['level']."\n"
            .'Puntaje de riesgo: '.$riskProfile['risk_score']."/100\n"
            .'Motivos: '.json_encode($riskProfile['reasons'], JSON_UNESCAPED_UNICODE)."\n"
            .'Recomendaciones base: '.json_encode($riskProfile['recommendations'], JSON_UNESCAPED_UNICODE)."\n\n"
            .'Genera una explicacion de maximo 3 frases para el estudiante.';

        return self::complete($system, $user, 220);
    }
}
