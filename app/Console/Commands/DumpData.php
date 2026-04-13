<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DumpData extends Command
{
    protected $signature = 'data:dump {--table=all : Table to dump (orders, exam_entries, students, or all)}';
    protected $description = 'Dump table data as SQL INSERT statements (for Cloud Commands console output)';

    public function handle(): int
    {
        $table = $this->option('table');

        $tables = $table === 'all'
            ? ['orders', 'exam_entries', 'students']
            : [trim($table)];

        foreach ($tables as $t) {
            $rows = DB::table($t)->get();

            if ($rows->isEmpty()) {
                $this->warn("-- {$t}: 0 rows (empty)");
                $this->line('');
                continue;
            }

            $this->info("-- {$t}: {$rows->count()} rows");

            // Get column names from first row
            $columns = array_keys((array) $rows->first());
            $colList = '"' . implode('", "', $columns) . '"';

            $this->line("INSERT INTO \"{$t}\" ({$colList}) VALUES");

            $lastIndex = $rows->count() - 1;
            foreach ($rows->values() as $i => $row) {
                $values = [];
                foreach ((array) $row as $val) {
                    if (is_null($val)) {
                        $values[] = 'NULL';
                    } elseif (is_bool($val)) {
                        $values[] = $val ? "'t'" : "'f'";
                    } elseif (is_numeric($val) && !is_string($val)) {
                        $values[] = $val;
                    } else {
                        $values[] = "'" . str_replace("'", "''", $val) . "'";
                    }
                }

                $line = '(' . implode(', ', $values) . ')';
                $line .= ($i === $lastIndex) ? ';' : ',';
                $this->line($line);
            }

            $this->line('');
        }

        // Also output counts summary
        $this->info('-- Summary:');
        $this->info('-- Orders: ' . DB::table('orders')->count());
        $this->info('-- Exam entries: ' . DB::table('exam_entries')->count());
        $this->info('-- Students: ' . DB::table('students')->count());

        return Command::SUCCESS;
    }
}
