<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\ThankYouController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('/robots.txt', function () {
    if (app()->environment('production')) {
        $body = "User-agent: *\n"
            ."Disallow: /admin\n"
            ."Disallow: /dashboard\n"
            ."Disallow: /settings\n"
            ."Disallow: /login\n"
            ."Disallow: /register\n"
            ."Disallow: /forgot-password\n"
            ."Disallow: /reset-password\n"
            ."\n"
            ."Sitemap: https://musicexams.help/sitemap.xml\n";
    } else {
        // Non-prod (local, staging, testing): block all crawlers entirely.
        $body = "User-agent: *\nDisallow: /\n";
    }

    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::inertia('/faq', 'Faq')->name('faq');
Route::inertia('/for-teachers', 'ForTeachers')->name('for-teachers');
Route::redirect('/for-teachers/faber-discounts', '/books', 301);
Route::inertia('/for-teachers/awards', 'TeacherAwards')->name('teacher-awards');
Route::inertia('/for-parents', 'ForParents')->name('for-parents');
Route::inertia('/for-students', 'ForStudents')->name('for-students');
Route::inertia('/books', 'Books')->name('books');
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
// Lead magnet — captures name + email + optional marketing consent and
// emails the Trinity Exam Checklist PDF. Distinct from /subscribe so the
// existing newsletter forms keep working.
Route::post('/lead-magnet/subscribe', [SubscriberController::class, 'leadMagnet'])->name('lead-magnet.subscribe');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/link-request', [DashboardController::class, 'linkRequest'])
        ->name('dashboard.link-request');
    Route::post('dashboard/entries/{entry}/correction-request', [DashboardController::class, 'correctionRequest'])
        ->name('dashboard.correction-request');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';