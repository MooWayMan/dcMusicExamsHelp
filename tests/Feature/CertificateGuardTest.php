<?php

// tests/Feature/CertificateGuardTest.php
//
// Certificates are drawn in one place (CertificateRenderer) and the quarter
// batch is driven from one place (useQuarterCertificateBatch). The drawing
// code had been copied six times and the copies had drifted apart.

test('certificate text is drawn by CertificateRenderer only', function () {
    expect(guardOffenders('/FontFactory|->text\s*\(/', ['app/Services/CertificateRenderer.php']))->toBe([]);
});

test('a certificate is wrapped in a PDF by CertificateRenderer only', function () {
    expect(guardOffenders('/data:image\/png;base64/', ['app/Services/CertificateRenderer.php']))->toBe([]);
});

test('the teacher badge thresholds are written down once', function () {
    expect(guardOffenders('/>=\s*40\s*=>/', ['app/Services/CertificateRenderer.php']))->toBe([]);
});

test('only useQuarterCertificateBatch drives the quarter batch', function () {
    expect(guardOffenders('/certificates\/batch\/(start|step|finish)/', [
        'resources/js/composables/useQuarterCertificateBatch.ts',
        'routes/admin.php',
    ]))->toBe([]);
});
