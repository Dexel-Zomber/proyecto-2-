<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BulkImportService
{
    public function importStudents(UploadedFile $file): array
    {
        $result = $this->emptyResult();

        foreach ($this->csvRows($file) as $line => $row) {
            $data = $this->pick($row, [
                'name' => ['name', 'nombre'],
                'email' => ['email', 'correo'],
                'password' => ['password', 'contraseÃ±a', 'contrasena'],
                'course' => ['course', 'curso'],
            ]);

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'course' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->addError($result, $line, $validator->errors()->first());

                continue;
            }

            $courseId = null;

            if (! empty($data['course'])) {
                $course = Course::firstOrCreate([
                    'name' => $data['course'],
                ], [
                    'description' => 'Creado automaticamente desde importacion de estudiantes.',
                ]);

                if ($course->wasRecentlyCreated) {
                    $result['courses_created']++;
                }

                $courseId = $course->id;
            }

            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => 'student',
                'password' => Hash::make($data['password']),
                'course_id' => $courseId,
            ]);

            $result['created']++;
        }

        return $result;
    }

    public function importScores(User $teacher, UploadedFile $file, ScoreService $scoreService): array
    {
        $result = $this->emptyResult();

        foreach ($this->csvRows($file) as $line => $row) {
            $data = $this->pick($row, [
                'email' => ['email_estudiante', 'email', 'correo'],
                'subject' => ['materia', 'subject', 'asignatura', 'nombre_materia'],
                'course' => ['course', 'curso'],
                'label' => ['parcial', 'label'],
                'value' => ['nota', 'value', 'calificacion'],
            ], [
                'email' => 0,
                'subject' => 1,
                'course' => 2,
                'label' => 3,
                'value' => 4,
            ]);

            $validator = Validator::make($data, [
                'email' => ['required', 'email'],
                'subject' => ['required', 'string', 'max:255'],
                'course' => ['nullable', 'string', 'max:255'],
                'label' => ['required', 'string', 'max:50'],
                'value' => ['required', 'numeric', 'between:0,100'],
            ]);

            if ($validator->fails()) {
                $this->addError($result, $line, $validator->errors()->first());

                continue;
            }

            $student = User::where('role', 'student')->where('email', $data['email'])->first();
            $subjectQuery = Subject::where('teacher_id', $teacher->id)->where('name', $data['subject']);

            if (! empty($data['course'])) {
                $subjectQuery->whereHas('course', fn ($query) => $query->where('name', $data['course']));
            }

            $subject = $subjectQuery->first();

            if (! $student) {
                $this->addError($result, $line, "No existe estudiante con correo {$data['email']}.");

                continue;
            }

            if (! $subject) {
                $courseText = empty($data['course']) ? '' : " en {$data['course']}";
                $availableSubjects = $this->availableSubjectsText($teacher);
                $this->addError($result, $line, "La materia {$data['subject']}{$courseText} no pertenece al profesor. Materias disponibles: {$availableSubjects}.");

                continue;
            }

            $subject->students()->syncWithoutDetaching([$student->id]);

            $alreadyExists = Score::where('student_id', $student->id)
                ->where('subject_id', $subject->id)
                ->where('label', $data['label'])
                ->exists();

            try {
                $scoreService->storeTeacherScore($teacher, [
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'label' => $data['label'],
                    'value' => $data['value'],
                ]);

                $alreadyExists ? $result['updated']++ : $result['created']++;
            } catch (\Throwable $exception) {
                $this->addError($result, $line, $exception->getMessage());
            }
        }

        return $result;
    }

    private function csvRows(UploadedFile $file): array
    {
        $delimiter = $this->delimiter($file);
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle, 0, $delimiter);
        $rows = [];

        if (! $header) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($value) => $this->normalize((string) $value), $header);
        $line = 1;

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if ($values === [null] || $values === false) {
                continue;
            }

            $normalizedValues = array_slice(array_pad($values, count($header), ''), 0, count($header));
            $row = array_combine($header, $normalizedValues) ?: [];
            $row['__columns'] = $normalizedValues;
            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function delimiter(UploadedFile $file): string
    {
        $handle = fopen($file->getRealPath(), 'r');
        $firstLine = $handle ? (fgets($handle) ?: '') : '';

        if ($handle) {
            fclose($handle);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private function pick(array $row, array $map, array $fallbackPositions = []): array
    {
        $data = [];

        foreach ($map as $target => $aliases) {
            $data[$target] = '';

            foreach ($aliases as $alias) {
                $key = $this->normalize($alias);

                if (array_key_exists($key, $row)) {
                    $data[$target] = trim((string) $row[$key]);
                    break;
                }
            }

            if ($data[$target] === '' && isset($fallbackPositions[$target], $row['__columns'][$fallbackPositions[$target]])) {
                $data[$target] = trim((string) $row['__columns'][$fallbackPositions[$target]]);
            }
        }

        return $data;
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = trim(mb_strtolower($value));

        return str_replace([' ', '-', '.', "\t", "\r", "\n"], '_', $value);
    }

    private function emptyResult(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'courses_created' => 0,
            'errors' => [],
        ];
    }

    private function addError(array &$result, int $line, string $message): void
    {
        $result['errors'][] = "Fila {$line}: {$message}";
    }

    private function availableSubjectsText(User $teacher): string
    {
        $subjects = Subject::where('teacher_id', $teacher->id)
            ->with('course')
            ->get()
            ->map(fn (Subject $subject) => $subject->name.($subject->course ? " en {$subject->course->name}" : ''))
            ->implode(', ');

        return $subjects !== '' ? $subjects : 'ninguna';
    }
}

