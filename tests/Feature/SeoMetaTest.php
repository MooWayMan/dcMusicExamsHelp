<?php

// ──────────────────────────────────────────
// SEO meta guards for public pages
// Each public page must render exactly one <h1> (via the MyTextConstructor
// titleTag="h1" prop or plain markup) and pass a title + description to the
// shared Head component. Guards the fixes for the missing-h1 / duplicate-title
// issues Bing flagged on 5 Jul 2026.
// ──────────────────────────────────────────

$publicPages = [
    'Welcome', 'Syllabus', 'TopTen', 'ForParents', 'ForTeachers', 'ForStudents',
    'About', 'Faq', 'ExamGuide', 'ExamGuideGrades', 'ExamGuideDigital',
    'ExamGuideExpect', 'ExamGuideUcas', 'ExamGuideSyllabuses', 'Books',
    'TrinityExamInformation', 'SwitchToCentre120', 'TeacherAwards', 'Incentives',
    'ExamFees', 'Contact', 'ThankYou', 'PrivacyPolicy', 'TermsOfUse',
    'CookiePolicy', 'Sitemap',
];

test('MyTextConstructor supports a titleTag prop', function () {
    $src = file_get_contents(resource_path('js/components/reusables/MyTextConstructor.vue'));

    expect($src)->toContain('titleTag')
        ->and($src)->toContain(':is="titleTag"');
});

it('renders exactly one h1 on each public page', function (string $page) {
    $src = file_get_contents(resource_path("js/pages/{$page}.vue"));

    $viaTitleTag = preg_match_all('/titleTag=(["\'])h1\1/', $src);
    $plainH1 = preg_match_all('/<h1[\s>]/', $src);

    expect($viaTitleTag + $plainH1)->toBe(1);
})->with($publicPages);

it('passes a title and description to Head on each public page', function (string $page) {
    $src = file_get_contents(resource_path("js/pages/{$page}.vue"));

    expect($src)->toMatch('/[\s:]title=/')
        ->and($src)->toMatch('/[\s:]description=/');
})->with($publicPages);
