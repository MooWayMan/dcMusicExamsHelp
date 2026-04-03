<?php

// database/seeders/SessionLogSeeder.php

namespace Database\Seeders;

use App\Models\SessionLog;
use Illuminate\Database\Seeder;

class SessionLogSeeder extends Seeder
{
    /**
     * Seed the session_logs table with estimated hours from Sessions 1-8.
     *
     * Paul to confirm/adjust hours — these are estimates based on work completed.
     */
    public function run(): void
    {
        $logs = [
            [
                'date' => '2026-03-31',
                'hours' => 11.0,
                'notes' => 'Sessions 1+2: Big Build Day + UI & Constructors. Database schema, admin panel CRUD, brand identity, constructor components, page animations.',
            ],
            [
                'date' => '2026-04-01',
                'hours' => 7.0,
                'notes' => 'Sessions 3+4: Logo design & placements, brand colours (burgundy/teal), PageHeader responsive grid, task manager, launch fixes, accessibility audit.',
            ],
            [
                'date' => '2026-04-02',
                'hours' => 10.0,
                'notes' => 'Sessions 5+6+7: GCal sync fix & automation, landing page overhaul, task notes feature, business plan, bulk task notes population.',
            ],
            [
                'date' => '2026-04-03',
                'hours' => 4.0,
                'notes' => 'Session 8: Hall of Fame banner, certificates (teacher + student), teacher badges (10/20/30+), landing page graphics, rewards programme, FAQ styling.',
            ],
        ];

        foreach ($logs as $log) {
            SessionLog::updateOrCreate(
                ['date' => $log['date']],
                $log
            );
        }
    }
}
