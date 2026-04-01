<?php

// routes/admin.php

use App\Http\Controllers\Admin\ContactLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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

    // Students (read-only — managed via teacher profiles)
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
});

// Explicit model binding: 'teacher' param resolves to User model (teachers are users with role=teacher)
Route::bind('teacher', function ($value) {
    return User::where('id', $value)->where('role', 'teacher')->firstOrFail();
});
