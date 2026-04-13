<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedProductionData extends Command
{
    protected $signature = 'data:seed-production';

    protected $description = 'Run all data import commands in sequence to rebuild local database with production data. Run AFTER migrate:fresh and pasting local-import.sql.';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('  Seeding local database with production data');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        // Step 1: Import Q1 F2F results (39 entries + 4 orders)
        $this->info('Step 1/4: Importing Q1 F2F results...');
        $this->call('exam:import-q1', ['--fresh' => true]);
        $this->newLine();

        // Step 2: Fix F2F teacher names and link student_ids
        $this->info('Step 2/4: Fixing F2F teacher names and linking students...');
        $this->call('exam:fix-f2f-teachers');
        $this->newLine();

        // Step 3: Import Q1 digital entries (34 entries + 17 orders)
        $this->info('Step 3/4: Importing Q1 digital entries...');
        $this->call('exam:import-q1-digital', ['--fresh' => true]);
        $this->newLine();

        // Step 4: Seed page maintenance rows
        $this->info('Step 4/4: Seeding page maintenance settings...');
        \App\Models\PageMaintenance::seed();
        $this->info('Page maintenance rows created.');
        $this->newLine();

        // Summary
        $this->info('═══════════════════════════════════════════');
        $this->info('  All done! Local database matches production.');
        $this->info('═══════════════════════════════════════════');

        // Quick count check
        $orders = \App\Models\Order::count();
        $entries = \App\Models\ExamEntry::count();
        $students = \App\Models\Student::count();

        $this->table(
            ['Table', 'Count'],
            [
                ['Orders', $orders],
                ['Exam Entries', $entries],
                ['Students', $students],
            ]
        );

        return Command::SUCCESS;
    }
}
