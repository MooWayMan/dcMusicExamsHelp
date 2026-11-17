<?php

// config/rep.php

// Paul's own details as Trinity's Local Area Representative, printed on the
// payment documents (payment advices and receipts). Kept here, not in a Vue
// page or a Blade view, so there is one place to change them.

return [
    'name' => env('REP_NAME', 'Paul Sheridan'),
    'role' => 'Trinity College London Local Area Representative (Liverpool & Wirral, centre 120)',
    'on_behalf_of' => 'Trinity College London',
    'address' => [
        '22 Gloucester Road',
        'Wallasey',
        'Wirral',
        'CH45 3JT',
    ],
    'phone' => env('REP_PHONE', '07810 872 816'),
    'email' => env('REP_EMAIL', 'musicexams@musicexams.help'),
];
