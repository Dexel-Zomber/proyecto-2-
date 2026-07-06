<?php

use App\Http\Controllers\AdminAlertController;
use App\Http\Controllers\AdminController;
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
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::patch('/admin/users/{adminUser}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{adminUser}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

    Route::patch('/admin/users/{user}/course', [AdminController::class, 'assignCourse'])->name('admin.users.assign-course');

    Route::post('/admin/courses', [AdminController::class, 'storeCourse'])->name('admin.courses.store');
    Route::patch('/admin/courses/{course}', [AdminController::class, 'updateCourse'])->name('admin.courses.update');
    Route::delete('/admin/courses/{course}', [AdminController::class, 'deleteCourse'])->name('admin.courses.delete');

    Route::post('/admin/subjects', [AdminController::class, 'storeSubject'])->name('admin.subjects.store');
    Route::patch('/admin/subjects/{subject}', [AdminController::class, 'updateSubject'])->name('admin.subjects.update');
    Route::delete('/admin/subjects/{subject}', [AdminController::class, 'deleteSubject'])->name('admin.subjects.delete');

    Route::post('/admin/enrollments', [AdminController::class, 'enrollStudent'])->name('admin.enrollments.store');
    Route::delete('/admin/enrollments', [AdminController::class, 'removeStudentEnrollment'])->name('admin.enrollments.delete');

    Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');

    Route::patch('/admin/alerts/{alert}/resolve', [AdminAlertController::class, 'resolve'])->name('admin.alerts.resolve');
    Route::patch('/admin/alerts/{alert}/unresolve', [AdminAlertController::class, 'unresolve'])->name('admin.alerts.unresolve');

    Route::get('/admin/reports/course', [AdminController::class, 'courseReport'])->name('admin.reports.course');
    Route::get('/admin/reports/teacher', [AdminController::class, 'teacherReport'])->name('admin.reports.teacher');
    Route::get('/admin/reports/student', [AdminController::class, 'studentReport'])->name('admin.reports.student');
});

Route::middleware('role:teacher')->group(function () {
    Route::get('/teacher', [TeacherController::class, 'index'])->name('teacher');
    Route::post('/teacher/scores', [TeacherController::class, 'storeScore'])->name('teacher.scores.store');
    Route::delete('/teacher/scores/{score}', [TeacherController::class, 'destroyScore'])->name('teacher.scores.delete');
});

Route::middleware('role:student')->group(function () {
    Route::get('/student', [StudentController::class, 'index'])->name('student');
    Route::post('/student/ai-chat', [StudentController::class, 'aiChat'])->name('student.ai-chat');
});
