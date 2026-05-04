<?php

// config/lead_magnet.php

return [
    /*
    |--------------------------------------------------------------------------
    | Lead Magnet PDF URL
    |--------------------------------------------------------------------------
    |
    | Public URL to the Trinity Exam Checklist PDF. The Mailable fetches
    | this over HTTP so no AWS credentials are required. Override via .env
    | LEAD_MAGNET_PDF_URL if the asset moves (eg new Canva export).
    |
    */

    'pdf_url' => env('LEAD_MAGNET_PDF_URL'),
];
