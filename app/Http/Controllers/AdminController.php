<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends BaseController
{
    public function index(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $students = User::where('role', 'student')->with('course')->get();
        $teachers = User::where('role', 'teacher')->get();
        $courses = Course::all();
        $subjects = Subject::with(['course', 'teacher', 'students'])->get();

        // Filters
        $filterCourse = $request->query('filter_course');
        $filterSubject = $request->query('filter_subject');
        $filterStatus = $request->query('filter_status'); // 'all'|'resolved'|'open'

        $alertsQuery = Alert::with(['student', 'subject']);

        if ($filterCourse) {
            $alertsQuery->whereHas('subject', function ($q) use ($filterCourse) {
                $q->where('course_id', $filterCourse);
            });
        }

        if ($filterSubject) {
            $alertsQuery->where('subject_id', $filterSubject);
        }

        if ($filterStatus === 'resolved') {
            $alertsQuery->where('resolved', true);
        } elseif ($filterStatus === 'open') {
            $alertsQuery->where('resolved', false);
        }

        $alerts = $alertsQuery->latest('updated_at')->paginate(15)->withQueryString();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'alertWarning',
            'alertDanger'
        ));
    }

    public function storeUser(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:teacher,student'],
            'password' => ['required', 'string', 'min:6'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'course_id' => $validated['course_id'] ?? null,
        ]);

        return back()->with('message', 'Usuario creado correctamente');
    }

    public function updateUser(Request $request, User $adminUser)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $adminUser->id],
            'role' => ['required', 'in:teacher,student'],
            'password' => ['nullable', 'string', 'min:6'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $adminUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'course_id' => $validated['course_id'] ?? null,
            'password' => isset($validated['password']) && $validated['password'] ? Hash::make($validated['password']) : $adminUser->password,
        ]);

        return back()->with('message', 'Usuario actualizado correctamente');
    }

    public function deleteUser(User $adminUser)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        if ($user->id === $adminUser->id) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta desde el panel.']);
        }

        $adminUser->delete();

        return back()->with('message', 'Usuario eliminado correctamente');
    }

    public function assignCourse(Request $request, User $user)
    {
        $current = $this->currentUser();

        if (! $current || ! $current->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $user->update([
            'course_id' => $validated['course_id'] ?? null,
        ]);

        return back()->with('message', 'Curso asignado correctamente');
    }

    public function storeCourse(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Course::create($validated);

        return back()->with('message', 'Curso creado correctamente');
    }

    public function updateCourse(Request $request, Course $course)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $course->update($validated);

        return back()->with('message', 'Curso actualizado correctamente');
    }

    public function deleteCourse(Course $course)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $course->delete();

        return back()->with('message', 'Curso eliminado correctamente');
    }

    public function storeSubject(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        Subject::create($validated);

        return back()->with('message', 'Materia creada y asignada correctamente');
    }

    public function updateSubject(Request $request, Subject $subject)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $subject->update($validated);

        return back()->with('message', 'Materia actualizada correctamente');
    }

    public function deleteSubject(Subject $subject)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $subject->delete();

        return back()->with('message', 'Materia eliminada correctamente');
    }

    public function enrollStudent(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $student = User::find($validated['student_id']);
        $subject = Subject::find($validated['subject_id']);

        if ($student->role !== 'student') {
            return back()->withErrors(['student_id' => 'Solo los estudiantes pueden ser inscritos en materias.']);
        }

        $subject->students()->syncWithoutDetaching([$student->id]);

        return back()->with('message', 'Estudiante inscrito en la materia correctamente');
    }

    public function removeStudentEnrollment(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $subject = Subject::find($validated['subject_id']);
        $subject->students()->detach($validated['student_id']);

        return back()->with('message', 'Inscripción eliminada correctamente');
    }

    public function courseReport(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $courses = Course::all();
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->with('course')->get();
        $subjects = Subject::with(['course', 'teacher'])->get();
        $alerts = Alert::with(['student', 'subject'])->latest()->limit(30)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = null;
        $course = Course::with('subjects')->find($request->query('course_id'));

        if ($course) {
            $subjectIds = $course->subjects->pluck('id')->all();
            $studentsInCourse = $course->students()->with(['scores' => function ($query) use ($subjectIds) {
                $query->whereIn('subject_id', $subjectIds)->with('subject');
            }])->get();

            $rows = $studentsInCourse->map(function ($student) use ($subjectIds) {
                $scores = $student->scores->pluck('value');
                return [
                    'student' => $student,
                    'average' => $scores->count() ? round($scores->avg(), 1) : 0,
                    'subjects' => $student->scores,
                ];
            });

            $report = [
                'type' => 'course',
                'title' => "Reporte por curso: {$course->name}",
                'rows' => $rows,
                'ai_summary' => AiAssistantService::reportSummary('curso', $course->name, $rows->map(fn ($r) => [
                    'estudiante' => $r['student']->name,
                    'promedio' => $r['average'],
                ])->all()),
            ];
        }

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function teacherReport(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $courses = Course::all();
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->with('course')->get();
        $subjects = Subject::with(['course', 'teacher'])->get();
        $alerts = Alert::with(['student', 'subject'])->latest()->limit(30)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = null;
        $teacher = User::where('role', 'teacher')->with(['subjects.scores.student'])->find($request->query('teacher_id'));

        if ($teacher) {
            $rows = $teacher->subjects->map(function ($subject) {
                $scores = $subject->scores->pluck('value');
                return [
                    'subject' => $subject,
                    'average' => $scores->count() ? round($scores->avg(), 1) : 0,
                    'studentCount' => $subject->scores->pluck('student_id')->unique()->count(),
                ];
            });

            $report = [
                'type' => 'teacher',
                'title' => "Reporte por profesor: {$teacher->name}",
                'rows' => $rows,
                'ai_summary' => AiAssistantService::reportSummary('profesor', $teacher->name, $rows->map(fn ($r) => [
                    'materia' => $r['subject']->name,
                    'promedio' => $r['average'],
                    'estudiantes' => $r['studentCount'],
                ])->all()),
            ];
        }

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function studentReport(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $courses = Course::all();
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->with(['course', 'scores.subject.course'])->get();
        $subjects = Subject::with(['course', 'teacher'])->get();
        $alerts = Alert::with(['student', 'subject'])->latest()->limit(30)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = null;
        $student = User::where('role', 'student')->with(['scores.subject.course', 'alerts'])->find($request->query('student_id'));

        if ($student) {
            $scores = $student->scores;
            $average = $scores->count() ? round($scores->avg('value'), 1) : 0;
            $report = [
                'type' => 'student',
                'title' => "Reporte por estudiante: {$student->name}",
                'student' => $student,
                'average' => $average,
                'scores' => $scores,
                'alerts' => $student->alerts,
                'ai_summary' => AiAssistantService::reportSummary('estudiante', $student->name, $scores->map(fn ($s) => [
                    'materia' => $s->subject?->name,
                    'parcial' => $s->label,
                    'nota' => $s->value,
                ])->all()),
            ];
        }

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function updateSettings(Request $request)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'alert_warning' => ['required', 'integer', 'between:0,100'],
            'alert_danger' => ['required', 'integer', 'between:0,100'],
        ]);

        Setting::setValue('alert_warning', $validated['alert_warning']);
        Setting::setValue('alert_danger', $validated['alert_danger']);

        return back()->with('message', 'Parámetros de IA actualizados');
    }
}
