<?php

// config/lead_magnet.php

return [
    /*
    |--------------------------------------------------------------------------
    | Lead Magnet PDF — path and fallback URL
    |--------------------------------------------------------------------------
    |
    | The Mailable (App\Mail\LeadMagnetDelivery) resolves the PDF URL via:
    |
    |   1. If AWS credentials are configured (AWS_ACCESS_KEY_ID,
    |      AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET), it generates
    |      a short-lived presigned URL using `pdf_path` — the path WITHIN the
    |      bucket. The S3 object itself should be PRIVATE; the presigned URL
    |      is the only access path and expires after 15 minutes.
    |
    |   2. Otherwise, the Mailable falls back to `pdf_url` — a public HTTP URL
    |      to the PDF (typically the same S3 object accessed via public URL).
    |      Used in local dev when AWS keys aren't available.
    |
    | See docs/dev-rules.md "Lead magnet PDF gating" rule.
    |
    */

    'pdf_path' => env('LEAD_MAGNET_PDF_PATH', 'musicexamshelp/Trinity Exam Checklist.pdf'),
    'pdf_url' => env('LEAD_MAGNET_PDF_URL'),
];
