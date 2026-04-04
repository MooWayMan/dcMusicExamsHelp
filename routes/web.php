<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/faq', 'Faq')->name('faq');
Route::inertia('/for-teachers', 'ForTeachers')->name('for-teachers');
Route::inertia('/for-teachers/faber-discounts', 'FaberDiscounts')->name('faber-discounts');
Route::inertia('/for-parents', 'ForParents')->name('for-parents');
Route::inertia('/for-students', 'ForStudents')->name('for-students');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';