<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\ThankYouController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/faq', 'Faq')->name('faq');
Route::inertia('/for-teachers', 'ForTeachers')->name('for-teachers');
Route::inertia('/for-teachers/faber-discounts', 'FaberDiscounts')->name('faber-discounts');
Route::inertia('/for-teachers/awards', 'TeacherAwards')->name('teacher-awards');
Route::inertia('/for-parents', 'ForParents')->name('for-parents');
Route::inertia('/for-students', 'ForStudents')->name('for-students');
Route::get('/recognition', ThankYouController::class)->name('recognition');
Route::inertia('/exam-guide', 'ExamGuide')->name('exam-guide');
Route::inertia('/exam-guide/ucas-points', 'ExamGuideUcas')->name('exam-guide.ucas');
Route::inertia('/exam-guide/what-to-expect', 'ExamGuideExpect')->name('exam-guide.expect');
Route::inertia('/exam-guide/digital-exams', 'ExamGuideDigital')->name('exam-guide.digital');
Route::inertia('/exam-guide/grades-explained', 'ExamGuideGrades')->name('exam-guide.grades');
Route::inertia('/exam-guide/syllabuses', 'ExamGuideSyllabuses')->name('exam-guide.syllabuses');
Route::inertia('/exam-fees', 'ExamFees')->name('exam-fees');
Route::inertia('/contact', 'Contact')->name('contact');
Route::inertia('/incentives', 'Incentives')->name('incentives');
Route::inertia('/about', 'About')->name('about');
Route::inertia('/privacy', 'PrivacyPolicy')->name('privacy');
Route::inertia('/cookies', 'CookiePolicy')->name('cookies');
Route::inertia('/terms', 'TermsOfUse')->name('terms');

Route::post('/subscribe', [SubscriberController::class, 'store'])->name('subscribe');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';