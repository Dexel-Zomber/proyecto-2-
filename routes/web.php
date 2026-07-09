<?php

use App\Http\Controllers\AdminAlertController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminReportExportController;
use App\Http\Controllers\AdminReportXmlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Todo lo de /admin/* requiere sesión activa Y rol admin.
// Antes cada método validaba esto a mano; si alguno lo olvidaba (como pasaba
// con AdminAlertController) la ruta quedaba completamente abierta.
Route::middleware('role:admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->middleware('permission:manage_users')->name('admin.users.store');
    Route::post('/admin/users/import', [AdminController::class, 'importStudents'])->middleware('permission:manage_users')->name('admin.users.import');
    Route::patch('/admin/users/{adminUser}', [AdminController::class, 'updateUser'])->middleware('permission:manage_users')->name('admin.users.update');
    Route::delete('/admin/users/{adminUser}', [AdminController::class, 'deleteUser'])->middleware('permission:manage_users')->name('admin.users.delete');

    Route::patch('/admin/users/{user}/course', [AdminController::class, 'assignCourse'])->middleware('permission:manage_users')->name('admin.users.assign-course');

    Route::post('/admin/courses', [AdminController::class, 'storeCourse'])->middleware('permission:manage_courses')->name('admin.courses.store');
    Route::patch('/admin/courses/{course}', [AdminController::class, 'updateCourse'])->middleware('permission:manage_courses')->name('admin.courses.update');
    Route::delete('/admin/courses/{course}', [AdminController::class, 'deleteCourse'])->middleware('permission:manage_courses')->name('admin.courses.delete');

    Route::post('/admin/subjects', [AdminController::class, 'storeSubject'])->middleware('permission:manage_subjects')->name('admin.subjects.store');
    Route::patch('/admin/subjects/{subject}', [AdminController::class, 'updateSubject'])->middleware('permission:manage_subjects')->name('admin.subjects.update');
    Route::delete('/admin/subjects/{subject}', [AdminController::class, 'deleteSubject'])->middleware('permission:manage_subjects')->name('admin.subjects.delete');

    Route::post('/admin/enrollments', [AdminController::class, 'enrollStudent'])->middleware('permission:manage_enrollments')->name('admin.enrollments.store');
    Route::delete('/admin/enrollments', [AdminController::class, 'removeStudentEnrollment'])->middleware('permission:manage_enrollments')->name('admin.enrollments.delete');

    Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->middleware('permission:manage_settings')->name('admin.settings.update');

    Route::patch('/admin/alerts/{alert}/resolve', [AdminAlertController::class, 'resolve'])->middleware('permission:manage_alerts')->name('admin.alerts.resolve');
    Route::patch('/admin/alerts/{alert}/unresolve', [AdminAlertController::class, 'unresolve'])->middleware('permission:manage_alerts')->name('admin.alerts.unresolve');

    Route::get('/admin/reports/course', [AdminController::class, 'courseReport'])->middleware('permission:view_reports')->name('admin.reports.course');
    Route::get('/admin/reports/teacher', [AdminController::class, 'teacherReport'])->middleware('permission:view_reports')->name('admin.reports.teacher');
    Route::get('/admin/reports/student', [AdminController::class, 'studentReport'])->middleware('permission:view_reports')->name('admin.reports.student');
    Route::get('/admin/reports/course/{course}/xml', [AdminReportXmlController::class, 'course'])->middleware('permission:export_xml')->name('admin.reports.course.xml');
    Route::get('/admin/reports/course/{course}/pdf', [AdminReportExportController::class, 'coursePdf'])->middleware('permission:export_xml')->name('admin.reports.course.pdf');
    Route::get('/admin/reports/course/{course}/excel', [AdminReportExportController::class, 'courseExcel'])->middleware('permission:export_xml')->name('admin.reports.course.excel');
    Route::get('/admin/reports/teacher/{teacher}/xml', [AdminReportXmlController::class, 'teacher'])->middleware('permission:export_xml')->name('admin.reports.teacher.xml');
    Route::get('/admin/reports/teacher/{teacher}/pdf', [AdminReportExportController::class, 'teacherPdf'])->middleware('permission:export_xml')->name('admin.reports.teacher.pdf');
    Route::get('/admin/reports/teacher/{teacher}/excel', [AdminReportExportController::class, 'teacherExcel'])->middleware('permission:export_xml')->name('admin.reports.teacher.excel');
    Route::get('/admin/reports/student/{student}/xml', [AdminReportXmlController::class, 'student'])->middleware('permission:export_xml')->name('admin.reports.student.xml');
    Route::get('/admin/reports/student/{student}/pdf', [AdminReportExportController::class, 'studentPdf'])->middleware('permission:export_xml')->name('admin.reports.student.pdf');
    Route::get('/admin/reports/student/{student}/excel', [AdminReportExportController::class, 'studentExcel'])->middleware('permission:export_xml')->name('admin.reports.student.excel');
});

Route::middleware('role:teacher')->group(function () {
    Route::get('/teacher', [TeacherController::class, 'index'])->name('teacher');
    Route::post('/teacher/scores', [TeacherController::class, 'storeScore'])->middleware('permission:manage_own_scores')->name('teacher.scores.store');
    Route::post('/teacher/scores/import', [TeacherController::class, 'importScores'])->middleware('permission:manage_own_scores')->name('teacher.scores.import');
    Route::delete('/teacher/scores/{score}', [TeacherController::class, 'destroyScore'])->middleware('permission:manage_own_scores')->name('teacher.scores.delete');
    Route::patch('/teacher/alerts/{alert}/resolve', [TeacherController::class, 'resolveAlert'])->middleware('permission:manage_own_alerts')->name('teacher.alerts.resolve');
    Route::patch('/teacher/alerts/{alert}/unresolve', [TeacherController::class, 'unresolveAlert'])->middleware('permission:manage_own_alerts')->name('teacher.alerts.unresolve');
});

Route::middleware('role:student')->group(function () {
    Route::get('/student', [StudentController::class, 'index'])->name('student');
    Route::post('/student/ai-chat', [StudentController::class, 'aiChat'])->middleware('permission:use_ai_assistant')->name('student.ai-chat');
});
