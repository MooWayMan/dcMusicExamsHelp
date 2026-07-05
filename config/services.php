<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'task_api' => [
        'token' => env('TASK_API_TOKEN'),
    ],

    'google_calendar' => [
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_CALENDAR_REFRESH_TOKEN'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    /*
    | HubSpot — Private App token used to auto-sync consented subscribers into
    | the CRM (App\Services\HubSpot\HubSpotClient + App\Jobs\SyncSubscriberToHubSpot).
    |
    | `token` blank  => the sync job no-ops (staging/local + test are meant to
    |                   run with a blank token, exactly like the Mailchimp keys).
    | `consent_property` is the INTERNAL NAME of a boolean HubSpot contact
    |                   property we set to true/false to mirror marketing consent
    |                   so the "All Marketing Subscribers" smart list can filter on
    |                   it. Leave blank until the property exists in HubSpot.
    */
    'hubspot' => [
        'token' => env('HUBSPOT_API_TOKEN'),
        'base_url' => env('HUBSPOT_BASE_URL', 'https://api.hubapi.com'),
        'consent_property' => env('HUBSPOT_CONSENT_PROPERTY'),
    ],

];
