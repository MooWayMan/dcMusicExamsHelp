<?php

// app/Console/Commands/ImportCandidateDobs.php
//
// Import candidate dates of birth from a CSV. Trinity's bulk Results Enquiry
// CSV doesn't include DOB, so Paul looks each one up manually in the Trinity
// portal, fills in a spreadsheet, and feeds it through this command.
//
// Expected CSV columns (header row required):
//   - candidate_number  (required, e.g. "1-15280057324")
//   - date_of_birth     (required, accepts DD/MM/YYYY or YYYY-MM-DD)
//
// Any other columns are ignored. Idempotent — running twice is a no-op for
// rows whose DOB already matches.
//
// Match key is candidate_number (unique Trinity identifier). All exam_entries
// rows sharing the candidate_number get the same DOB.

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportCandidateDobs extends Command
{
    protected $signature = 'students:import-dobs
        {path : Path to the CSV file (relative to project root or absolute)}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Import candidate dates of birth from a CSV. Matches by candidate_number.';

    public function handle(): int
    {
        $path = $this->argument('path');
        $absolute = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($absolute)) {
            $this->error("CSV not found: {$absolute}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($absolute);
        if ($rows === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $unchanged = 0;
        $missing = 0;
        $invalid = 0;

        foreach ($rows as $i => $row) {
            $candidateNumber = trim((string) ($row['candidate_number'] ?? ''));
            $dobRaw = trim((string) ($row['date_of_birth'] ?? ''));

            if ($candidateNumber === '' || $dobRaw === '') {
                continue; // silently skip blank lines / unfilled DOBs
            }

            try {
                $dob = $this->parseDate($dobRaw);
            } catch (\Throwable $e) {
                $this->warn("Row ".($i + 2).": couldn't parse '{$dobRaw}' for {$candidateNumber} — skipped");
                $invalid++;

                continue;
            }

            $entries = ExamEntry::where('candidate_number', $candidateNumber)->get();
            if ($entries->isEmpty()) {
                $this->warn("No exam_entries match candidate_number {$candidateNumber}");
                $missing++;

                continue;
            }

            $rowChanged = false;
            foreach ($entries as $entry) {
                if ($entry->date_of_birth?->equalTo($dob)) {
                    continue;
                }
                $rowChanged = true;
                if (! $dryRun) {
                    $entry->date_of_birth = $dob;
                    $entry->save();
                }
            }

            if ($rowChanged) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Updated', $updated],
                ['Already correct', $unchanged],
                ['No matching candidate', $missing],
                ['Invalid date format', $invalid],
            ]
        );

        if ($dryRun) {
            $this->info('Dry run — no changes written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, string>>|null
     */
    private function readCsv(string $absolute): ?array
    {
        $handle = fopen($absolute, 'r');
        if ($handle === false) {
            $this->error("Couldn't open CSV: {$absolute}");

            return null;
        }

        // Try up to 3 lines for the header. TablePlus's CSV export puts the
        // filename on line 1 (single-cell pseudo-row), so the real header
        // sits on line 2. We just keep peeling off lines until we find one
        // that contains the required column names.
        $required = ['candidate_number', 'date_of_birth'];
        $header = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $line = fgetcsv($handle);
            if ($line === false) {
                break;
            }

            $candidate = array_map(
                fn ($h) => strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string) $h), '_')),
                $line
            );

            if (empty(array_diff($required, $candidate))) {
                $header = $candidate;
                break;
            }
        }

        if ($header === null) {
            $this->error('CSV is missing required columns (candidate_number, date_of_birth) in the first 3 rows.');
            fclose($handle);

            return null;
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            // Pad/truncate to header length so array_combine works cleanly
            $line = array_pad(array_slice($line, 0, count($header)), count($header), '');
            $rows[] = array_combine($header, $line);
        }
        fclose($handle);

        return $rows;
    }

    private function parseDate(string $raw): Carbon
    {
        $raw = trim($raw);

        // ISO YYYY-MM-DD (TablePlus / database default)
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $raw)) {
            return Carbon::parse($raw)->startOfDay();
        }

        // DD/MM/YYYY or DD-MM-YYYY (Trinity portal display, UK Excel default)
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $raw, $m)) {
            return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        // Last resort — let Carbon auto-detect. Throws on garbage; the
        // command catches that and reports the row as invalid.
        return Carbon::parse($raw)->startOfDay();
    }
}
