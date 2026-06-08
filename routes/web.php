<?php

use App\Http\Controllers\Admin\ImpersonationController;
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

/**
 * Sitemap — served via Laravel so we can guarantee the right
 * Content-Type and bypass any nginx/Cloudflare static-file quirks that
 * may have been blocking Google Search Console fetches.
 *
 * The XML source lives at resources/seo/sitemap.xml (deliberately NOT
 * in public/ — if it were, nginx would serve it directly and this route
 * would never fire). This route reads + re-serves it with the correct
 * application/xml Content-Type. Mirrors the /robots.txt pattern above.
 *
 * NOTE (9 Jun 2026): GSC still reports "Couldn't fetch" for this endpoint,
 * but this is NOT a Cloudflare/origin block — that theory was investigated
 * and DISPROVEN via a Laravel Cloud support ticket (~20 May): Cloudflare
 * served 47/47 requests from origin incl. a Verified Bot / Google Inspector
 * 200 OK, BingBot fetched the same XML fine (23 URLs), curl returns 200
 * application/xml. It's a Google-side/GSC cosmetic quirk on a young domain.
 * Do NOT chase Cloudflare, re-submit, or re-open a support ticket — all
 * dead ends. Indexing works via internal links + the /sitemap backstop.
 */
Route::get('/sitemap.xml', function () {
    $path = resource_path('seo/sitemap.xml');
    if (! file_exists($path)) {
        abort(404);
    }
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('sitemap-xml');

/**
 * Human-readable HTML sitemap — internal-link backstop so any crawler that
 * hits the homepage can discover the whole site by following links. Linked
 * from the footer. Also useful for human visitors.
 * Added 18 May 2026 (originally to work around a suspected Cloudflare block
 * on /sitemap.xml — that block was later DISPROVEN, see the /sitemap.xml
 * note above; the page is still worth keeping for discovery + humans).
 */
Route::inertia('/sitemap', 'Sitemap')->name('sitemap');

Route::inertia('/faq', 'Faq')->name('faq');
Route::inertia('/for-teachers', 'ForTeachers')->name('for-teachers');
Route::redirect('/for-teachers/faber-discounts', '/books', 301);
Route::inertia('/for-teachers/awards', 'TeacherAwards')->name('teacher-awards');
Route::inertia('/switch-to-centre-120', 'SwitchToCentre120')->name('switch-to-centre-120');
Route::inertia('/trinity-exam-information', 'TrinityExamInformation')->name('trinity-exam-information');
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

// Public form endpoints — rate-limited per IP to stop bot/email-bomb abuse.
// 5 submissions per minute is generous for a real human, hostile for bots.
// Each controller additionally honeypot-checks `website_url`: a hidden form
// field a real visitor never fills but bots routinely do. See dev-rules.md
// "Public forms" rule for the rationale.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/subscribe', [SubscriberController::class, 'store'])->name('subscribe');
    // Lead magnet — captures name + email + optional marketing consent and
    // emails the Trinity Exam Checklist PDF. Distinct from /subscribe so the
    // existing newsletter forms keep working.
    Route::post('/lead-magnet/subscribe', [SubscriberController::class, 'leadMagnet'])->name('lead-magnet.subscribe');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/link-request', [DashboardController::class, 'linkRequest'])
        ->name('dashboard.link-request');
    Route::post('dashboard/entries/{entry}/correction-request', [DashboardController::class, 'correctionRequest'])
        ->name('dashboard.correction-request');

    // Leave impersonation — must live outside the admin middleware because
    // the currently-authenticated user is the non-admin being impersonated.
    Route::post('impersonate/leave', [ImpersonationController::class, 'leave'])
        ->name('impersonate.leave');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';