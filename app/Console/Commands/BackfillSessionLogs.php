<?php

namespace App\Console\Commands;

use App\Models\SessionLog;
use Illuminate\Console\Command;

class BackfillSessionLogs extends Command
{
    protected $signature = 'session:backfill-easter';
    protected $description = 'Backfill missing session logs for Easter week (8-10 Apr 2026)';

    public function handle(): int
    {
        $sessions = [
            [
                'date' => '2026-04-08',
                'hours' => 5,
                'notes' => 'Session 15-16: Certificate generator admin page, Bravo wording fix across all pages, parent-in-room FAQ, blue-on-blue link fixes, dark button fixes, cookie banner sequencing, comprehensive Pest test suite',
            ],
            [
                'date' => '2026-04-09',
                'hours' => 10,
                'notes' => 'Sessions 17-18: 88 site review notes (all fixed), theory added to recognition/prize draw, FAQ accordion links, ThankYou template fix, site corrections (exam order, pathway names, results process, syllabus links), syllabuses page, terms of use page, Google Calendar shared link, nav alignment fixes',
            ],
            [
                'date' => '2026-04-10',
                'hours' => 17.5,
                'notes' => 'Sessions 19-20: 9:30am to 3:04am. Coming soon page with logo, mobile booking modal fix, OG meta tags for social sharing, social links across site, shared Google Calendar, nav scroll fixes, hero logo centering, prize draw wording (both teacher + student draws, £50 each), content accuracy fixes',
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($sessions as $session) {
            if (SessionLog::where('date', $session['date'])->exists()) {
                $this->line("Skipped {$session['date']} — already exists.");
                $skipped++;
                continue;
            }

            SessionLog::create($session);
            $this->info("Added {$session['date']}: {$session['hours']}h");
            $created++;
        }

        $this->newLine();
        $this->info("Done: {$created} added, {$skipped} skipped.");

        return Command::SUCCESS;
    }
}
