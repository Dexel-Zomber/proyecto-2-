<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Score;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrador Colegio',
            'email' => 'admin@colegio.test',
            'role' => 'admin',
        ]);

        $teacher = User::factory()->create([
            'name' => 'Profesor Demo',
            'email' => 'teacher@colegio.test',
            'role' => 'teacher',
        ]);

        $student = User::factory()->create([
            'name' => 'Estudiante Demo',
            'email' => 'student@colegio.test',
            'role' => 'student',
        ]);

        $course = Course::create([
            'name' => 'Matemáticas',
            'description' => 'Curso de matemáticas y pensamiento lógico.',
        ]);

        $subject = Subject::create([
            'name' => 'Álgebra básica',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
        ]);

        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'value' => 62.0,
        ]);

        Alert::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'title' => 'Alerta de promedio bajo',
            'message' => 'Tu calificación en Álgebra básica es baja y necesita refuerzo.',
            'severity' => 'critical',
            'resolved' => false,
        ]);

        Setting::setValue('alert_warning', 70);
        Setting::setValue('alert_danger', 60);
    }
}
