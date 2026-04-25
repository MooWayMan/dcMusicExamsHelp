<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLiveData extends Command
{
    protected $signature = 'data:import {file : Path to the JSON export file} {--force : Skip confirmation}';
    protected $description = 'Import live data from a JSON export (run locally with sail after fresh migrate)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);

        if (! $data) {
            $this->error('Failed to parse JSON file.');
            return Command::FAILURE;
        }

        $this->info("Importing data exported at: {$data['exported_at']}");
        $this->newLine();

        // Show summary
        $tables = ['instruments', 'schools', 'users', 'students', 'orders', 'exam_entries', 'contact_logs'];
        foreach ($tables as $table) {
            $count = count($data[$table] ?? []);
            $this->info("  {$table}: {$count} records");
        }

        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('This will WIPE all existing data and replace it. Continue?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        DB::statement('SET session_replication_role = replica;'); // Disable FK checks (PostgreSQL)

        try {
            // Truncate in reverse dependency order
            $this->info('Clearing existing data...');
            $truncateOrder = [
                'contact_logs', 'exam_entries', 'students', 'orders',
                'schools', 'users', 'instruments',
            ];

            foreach ($truncateOrder as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            // Insert in dependency order
            $insertOrder = [
                'instruments' => 'instruments',
                'schools' => 'schools',
                'users' => 'users',
                'students' => 'students',
                'orders' => 'orders',
                'exam_entries' => 'exam_entries',
                'contact_logs' => 'contact_logs',
            ];

            foreach ($insertOrder as $key => $table) {
                $records = $data[$key] ?? [];
                if (empty($records)) {
                    continue;
                }

                $this->info("Importing {$table}... (" . count($records) . " records)");

                // Insert in chunks to avoid memory issues
                foreach (array_chunk($records, 100) as $chunk) {
                    // Clean up any fields that don't exist in the table
                    $columns = Schema::getColumnListing($table);
                    $cleaned = array_map(function ($record) use ($columns) {
                        return array_intersect_key($record, array_flip($columns));
                    }, $chunk);

                    DB::table($table)->insert($cleaned);
                }
            }

            // Reset auto-increment sequences (PostgreSQL)
            $this->info('Resetting sequences...');
            $sequenceTables = ['instruments', 'schools', 'users', 'students', 'orders', 'exam_entries', 'contact_logs'];
            foreach ($sequenceTables as $table) {
                $max = DB::table($table)->max('id');
                if ($max) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), {$max})");
                }
            }

            $this->newLine();
            $this->info('Import complete! Local database now matches live.');

        } finally {
            DB::statement('SET session_replication_role = DEFAULT;');
        }

        return Command::SUCCESS;
    }
}
