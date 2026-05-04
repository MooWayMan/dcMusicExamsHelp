<?php

// routes/admin.php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContactLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamEntryController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageMaintenanceController;
use App\Http\Controllers\Admin\PendingResultsController;
use App\Http\Controllers\Admin\QuarterEndController;
use App\Http\Controllers\Admin\QuickRepliesController;
use App\Http\Controllers\Admin\RoadmapController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SessionLogController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\SyncCalendarTasks;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin', SyncCalendarTasks::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Contacts
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');

        // Users — registered accounts (auth side, not the wider exam_contacts list)
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

        // Impersonation — admin "Login as user" so Paul can verify what real
        // teachers/parents see on their dashboard. Pair with the leave route
        // in routes/web.php (which lives outside the admin middleware so the
        // impersonated, non-admin user can use it to switch back).
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])
            ->name('users.impersonate');

        // Subscribers — newsletter + lead-magnet sign-ups (separate from users)
        Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Contact logs for any contact
        Route::post('contacts/{contact}/contact-logs', [ContactLogController::class, 'store'])->name('contacts.contact-logs.store');
        Route::delete('contacts/{contact}/contact-logs/{contactLog}', [ContactLogController::class, 'destroy'])->name('contacts.contact-logs.destroy');

        // Schools CRUD
        Route::resource('schools', SchoolController::class);

        // Orders — manual entry form (Trinity has no bulk export)
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');

        // Pending Results — candidates awaiting exam scores
        Route::get('pending-results', [PendingResultsController::class, 'index'])->name('pending-results.index');

        // Exam Entries — imported raw candidate/result data
        Route::get('exam-entries', [ExamEntryController::class, 'index'])->name('exam-entries.index');

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
        Route::post('quarter-end/mark-sent', [QuarterEndController::class, 'markSent'])->name('quarter-end.mark-sent');

        // Certificates — generate personalised certificates
        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::post('certificates/student', [CertificateController::class, 'generateStudent'])->name('certificates.generate-student');
        Route::post('certificates/teacher', [CertificateController::class, 'generateTeacher'])->name('certificates.generate-teacher');
        Route::post('certificates/batch', [CertificateController::class, 'batchGenerate'])->name('certificates.batch');
        Route::get('certificates/download/{filename}', [CertificateController::class, 'downloadZip'])
            ->name('certificates.download')
            ->where('filename', '.*');

        // Roadmap — visual project roadmap
        Route::get('roadmap', [RoadmapController::class, 'index'])->name('roadmap');

        // Quick Replies — phone-friendly template bank for inbound enquiries
        Route::get('quick-replies', [QuickRepliesController::class, 'index'])->name('quick-replies.index');

        // Session Logs — daily hours tracking
        Route::get('session-logs', [SessionLogController::class, 'index'])->name('session-logs.index');
        Route::post('session-logs', [SessionLogController::class, 'store'])->name('session-logs.store');
        Route::put('session-logs/{sessionLog}', [SessionLogController::class, 'update'])->name('session-logs.update');
        Route::delete('session-logs/{sessionLog}', [SessionLogController::class, 'destroy'])->name('session-logs.destroy');

        // Page Maintenance — per-page toggle for data-heavy pages
        Route::get('page-maintenance', [PageMaintenanceController::class, 'index'])->name('page-maintenance.index');
        Route::patch('page-maintenance/{page}/toggle', [PageMaintenanceController::class, 'toggle'])->name('page-maintenance.toggle');
        Route::patch('page-maintenance/{page}/message', [PageMaintenanceController::class, 'updateMessage'])->name('page-maintenance.message');
    });