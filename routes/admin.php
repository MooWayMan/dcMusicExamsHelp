<?php

// routes/admin.php

use App\Http\Controllers\Admin\DashboardController;
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
});

// Explicit model binding: 'teacher' param resolves to User model (teachers are users with role=teacher)
Route::bind('teacher', function ($value) {
    return User::where('id', $value)->where('role', 'teacher')->firstOrFail();
});
