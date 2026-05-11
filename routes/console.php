<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Google Calendar task sync every 30 minutes between 8am and 10pm
// (Europe/London timezone). Production-only — staging and local don't have
// Google OAuth credentials configured, so registering the schedule there
// just produces noisy "Missing OAuth credentials" errors in the log stream
// every 30 minutes. See docs/dev-rules.md "Calendar sync" rule.
if (app()->environment('production')) {
    Schedule::command('calendar:sync-tasks')
        ->everyThirtyMinutes()
        ->between('08:00', '22:00')
        ->timezone('Europe/London')
        ->onOneServer();
}
