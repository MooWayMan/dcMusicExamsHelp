<?php

// routes/admin.php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\ContactLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PendingResultsController;
use App\Http\Controllers\Admin\QuarterEndController;
use App\Http\Controllers\Admin\RoadmapController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SessionLogController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Middleware\SyncCalendarTasks;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin', SyncCalendarTasks::class])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Teachers CRUD — bind {teacher} to User model
    Route::resource('teachers', TeacherController::class)->parameters([
        'teachers' => 'teacher:id',
    ]);

    // Teacher extras: restore from archive, deletion impact warning
    Route::post('teachers/{id}/restore', [TeacherController::class, 'restore'])->name('teachers.restore');
    Route::get('teachers/{teacher}/deletion-impact', [TeacherController::class, 'deletionImpact'])->name('teachers.deletion-impact');

    // Contact logs for teachers
    Route::post('teachers/{teacher}/contact-logs', [ContactLogController::class, 'store'])->name('teachers.contact-logs.store');
    Route::delete('teachers/{teacher}/contact-logs/{contactLog}', [ContactLogController::class, 'destroy'])->name('teachers.contact-logs.destroy');

    // Schools CRUD
    Route::resource('schools', SchoolController::class);

    // Orders (read-only for now — data comes from Trinity portal)
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Pending Results — candidates awaiting exam scores
    Route::get('pending-results', [PendingResultsController::class, 'index'])->name('pending-results.index');

    // Students (read-only — managed via teacher profiles)
    Route::get('students', [StudentController::class, 'index'])->name('students.index');

    // Tasks — launch checklist and ongoing task management
    Route::resource('tasks', TaskController::class)->except(['show']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::patch('tasks/{task}/notes', [TaskController::class, 'updateNotes'])->name('tasks.notes');

    // AJAX: sync calendar + return fresh active task count (for sidebar polling)
    Route::post('tasks/sync', [TaskController::class, 'sync'])->name('tasks.sync');

    // Quarter End — step-by-step workflow for sending certs, badges and emails
    Route::get('quarter-end', [QuarterEndController::class, 'index'])->name('quarter-end.index');
    Route::post('quarter-end/draw', [QuarterEndController::class, 'runDraw'])->name('quarter-end.draw');

    // Certificates — generate personalised certificates
    Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('certificates/student', [CertificateController::class, 'generateStudent'])->name('certificates.generate-student');
    Route::post('certificates/teacher', [CertificateController::class, 'generateTeacher'])->name('certificates.generate-teacher');
    Route::post('certificates/batch', [CertificateController::class, 'batchGenerate'])->name('certificates.batch');
    Route::get('certificates/download/{filename}', [CertificateController::class, 'downloadZip'])->name('certificates.download')->where('filename', '.*');

    // Roadmap — visual project roadmap
    Route::get('roadmap', [RoadmapController::class, 'index'])->name('roadmap');

    // Session Logs — daily hours tracking
    Route::get('session-logs', [SessionLogController::class, 'index'])->name('session-logs.index');
    Route::post('session-logs', [SessionLogController::class, 'store'])->name('session-logs.store');
    Route::put('session-logs/{sessionLog}', [SessionLogController::class, 'update'])->name('session-logs.update');
    Route::delete('session-logs/{sessionLog}', [SessionLogController::class, 'destroy'])->name('session-logs.destroy');
});

// Explicit model binding: 'teacher' param resolves to User model (teachers are users with role=teacher)
Route::bind('teacher', function ($value) {
    return User::where('id', $value)->where('role', 'teacher')->firstOrFail();
});
