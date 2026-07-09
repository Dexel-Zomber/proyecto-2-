<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignCourseRequest;
use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\ImportStudentsRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BulkImportService;
use App\Services\ReportService;
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
        $auditLogs = AuditLog::with('user')->latest()->limit(12)->get();

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
            'auditLogs',
            'alertWarning',
            'alertDanger'
        ));
    }

    public function storeUser(StoreUserRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $createdUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'course_id' => $validated['course_id'] ?? null,
        ]);

        $auditLogService->record($user, 'users.created', "Creo el usuario {$createdUser->name}.", User::class, $createdUser->id, [
            'created_role' => $createdUser->role,
        ], $request);

        return back()->with('message', 'Usuario creado correctamente');
    }

    public function importStudents(ImportStudentsRequest $request, BulkImportService $bulkImportService, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $summary = $bulkImportService->importStudents($request->file('students_file'));

        $auditLogService->record($user, 'students.imported', 'Importo estudiantes desde archivo CSV.', User::class, null, [
            'created' => $summary['created'],
            'courses_created' => $summary['courses_created'] ?? 0,
            'errors' => count($summary['errors']),
        ], $request);

        return back()->with('importSummary', [
            'title' => 'Importacion de estudiantes',
            ...$summary,
        ]);
    }

    public function updateUser(UpdateUserRequest $request, User $adminUser, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $adminUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'course_id' => $validated['course_id'] ?? null,
            'password' => isset($validated['password']) && $validated['password'] ? Hash::make($validated['password']) : $adminUser->password,
        ]);

        $auditLogService->record($user, 'users.updated', "Actualizo el usuario {$adminUser->name}.", User::class, $adminUser->id, [
            'updated_role' => $adminUser->role,
        ], $request);

        return back()->with('message', 'Usuario actualizado correctamente');
    }

    public function deleteUser(Request $request, User $adminUser, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        if ($user->id === $adminUser->id) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta desde el panel.']);
        }

        $deletedName = $adminUser->name;
        $deletedRole = $adminUser->role;
        $deletedId = $adminUser->id;

        $adminUser->delete();

        $auditLogService->record($user, 'users.deleted', "Elimino el usuario {$deletedName}.", User::class, $deletedId, [
            'deleted_role' => $deletedRole,
        ], $request);

        return back()->with('message', 'Usuario eliminado correctamente');
    }

    public function assignCourse(AssignCourseRequest $request, User $user, AuditLogService $auditLogService)
    {
        $current = $this->currentUser();

        if (! $current || ! $current->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $user->update([
            'course_id' => $validated['course_id'] ?? null,
        ]);

        $auditLogService->record($current, 'users.course_assigned', "Asigno curso al usuario {$user->name}.", User::class, $user->id, [
            'course_id' => $validated['course_id'] ?? null,
        ], $request);

        return back()->with('message', 'Curso asignado correctamente');
    }

    public function storeCourse(StoreCourseRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $course = Course::create($validated);

        $auditLogService->record($user, 'courses.created', "Creo el curso {$course->name}.", Course::class, $course->id, [], $request);

        return back()->with('message', 'Curso creado correctamente');
    }

    public function updateCourse(StoreCourseRequest $request, Course $course, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $course->update($validated);

        $auditLogService->record($user, 'courses.updated', "Actualizo el curso {$course->name}.", Course::class, $course->id, [], $request);

        return back()->with('message', 'Curso actualizado correctamente');
    }

    public function deleteCourse(Request $request, Course $course, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $deletedName = $course->name;
        $deletedId = $course->id;

        $course->delete();

        $auditLogService->record($user, 'courses.deleted', "Elimino el curso {$deletedName}.", Course::class, $deletedId, [], $request);

        return back()->with('message', 'Curso eliminado correctamente');
    }

    public function storeSubject(StoreSubjectRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $subject = Subject::create($validated);

        $auditLogService->record($user, 'subjects.created', "Creo la materia {$subject->name}.", Subject::class, $subject->id, [
            'course_id' => $subject->course_id,
            'teacher_id' => $subject->teacher_id,
        ], $request);

        return back()->with('message', 'Materia creada y asignada correctamente');
    }

    public function updateSubject(StoreSubjectRequest $request, Subject $subject, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $subject->update($validated);

        $auditLogService->record($user, 'subjects.updated', "Actualizo la materia {$subject->name}.", Subject::class, $subject->id, [
            'course_id' => $subject->course_id,
            'teacher_id' => $subject->teacher_id,
        ], $request);

        return back()->with('message', 'Materia actualizada correctamente');
    }

    public function deleteSubject(Request $request, Subject $subject, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $deletedName = $subject->name;
        $deletedId = $subject->id;

        $subject->delete();

        $auditLogService->record($user, 'subjects.deleted', "Elimino la materia {$deletedName}.", Subject::class, $deletedId, [], $request);

        return back()->with('message', 'Materia eliminada correctamente');
    }

    public function enrollStudent(EnrollmentRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $student = User::find($validated['student_id']);
        $subject = Subject::find($validated['subject_id']);

        if ($student->role !== 'student') {
            return back()->withErrors(['student_id' => 'Solo los estudiantes pueden ser inscritos en materias.']);
        }

        $subject->students()->syncWithoutDetaching([$student->id]);

        $auditLogService->record($user, 'enrollments.created', "Inscribio a {$student->name} en {$subject->name}.", Subject::class, $subject->id, [
            'student_id' => $student->id,
        ], $request);

        return back()->with('message', 'Estudiante inscrito en la materia correctamente');
    }

    public function removeStudentEnrollment(EnrollmentRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        $subject = Subject::find($validated['subject_id']);
        $student = User::find($validated['student_id']);
        $subject->students()->detach($validated['student_id']);

        $auditLogService->record($user, 'enrollments.deleted', "Quito a {$student->name} de {$subject->name}.", Subject::class, $subject->id, [
            'student_id' => $student->id,
        ], $request);

        return back()->with('message', 'Inscripción eliminada correctamente');
    }

    public function courseReport(Request $request, ReportService $reportService)
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
        $auditLogs = AuditLog::with('user')->latest()->limit(12)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = $reportService->courseReport($request->query('course_id'));

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'auditLogs',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function teacherReport(Request $request, ReportService $reportService)
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
        $auditLogs = AuditLog::with('user')->latest()->limit(12)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = $reportService->teacherReport($request->query('teacher_id'));

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'auditLogs',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function studentReport(Request $request, ReportService $reportService)
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
        $auditLogs = AuditLog::with('user')->latest()->limit(12)->get();
        $alertWarning = Setting::getValue('alert_warning', 70);
        $alertDanger = Setting::getValue('alert_danger', 60);

        $report = $reportService->studentReport($request->query('student_id'));

        return view('dashboard.admin', compact(
            'students',
            'teachers',
            'courses',
            'subjects',
            'alerts',
            'auditLogs',
            'alertWarning',
            'alertDanger',
            'report'
        ));
    }

    public function updateSettings(UpdateSettingsRequest $request, AuditLogService $auditLogService)
    {
        $user = $this->currentUser();

        if (! $user || ! $user->isAdmin()) {
            return redirect('/login');
        }

        $validated = $request->validated();

        Setting::setValue('alert_warning', $validated['alert_warning']);
        Setting::setValue('alert_danger', $validated['alert_danger']);

        $auditLogService->record($user, 'settings.updated', 'Actualizo los parametros de alertas IA.', Setting::class, null, $validated, $request);

        return back()->with('message', 'Parámetros de IA actualizados');
    }
}
