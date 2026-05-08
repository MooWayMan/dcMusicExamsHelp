<?php

// tests/Feature/Admin/TopScorerCertTemplateTest.php
//
// Each quarter awards FOUR top-scorer certificates — one per
// (group × tier). Anna in Initial–5 might score 92 the same quarter
// Seth scores 93 in Grades 6–8; both are "highest in their group" but
// only one is highest overall. The cert text needs to specify the
// group so neither cert misleads the recipient.
//
// These tests guard the (tier, group) → S3 filename mapping. If a
// filename is renamed without updating the upload to S3, or vice
// versa, the cert generator silently picks the legacy generic
// template — which is the exact bug we just fixed.

use App\Http\Controllers\Admin\CertificateController;

it('returns the correct Showstopper template for Initial–5', function () {
    expect(CertificateController::topScorerTemplate('Showstopper', 'initial_5'))
        ->toBe('certStu_5_initial5.png');
});

it('returns the correct Showstopper template for Grades 6–8', function () {
    expect(CertificateController::topScorerTemplate('Showstopper', '6_8'))
        ->toBe('certStu_5_g68.png');
});

it('returns the correct Centre Stage template for Initial–5', function () {
    expect(CertificateController::topScorerTemplate('Centre Stage', 'initial_5'))
        ->toBe('certStu_4_initial5.png');
});

it('returns the correct Centre Stage template for Grades 6–8', function () {
    expect(CertificateController::topScorerTemplate('Centre Stage', '6_8'))
        ->toBe('certStu_4_g68.png');
});

it('returns null for an unknown tier', function () {
    expect(CertificateController::topScorerTemplate('Standing Ovation', 'initial_5'))
        ->toBeNull();
});

it('returns null for an unknown group', function () {
    expect(CertificateController::topScorerTemplate('Showstopper', 'grade_3'))
        ->toBeNull();
});

it('produces four unique filenames across the full mapping', function () {
    $files = collect([
        CertificateController::topScorerTemplate('Showstopper', 'initial_5'),
        CertificateController::topScorerTemplate('Showstopper', '6_8'),
        CertificateController::topScorerTemplate('Centre Stage', 'initial_5'),
        CertificateController::topScorerTemplate('Centre Stage', '6_8'),
    ]);

    expect($files->unique()->count())->toBe(4);
    expect($files->filter())->toHaveCount(4); // none null
});
