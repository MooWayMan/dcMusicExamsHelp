<?php

// app/Console/Commands/BackfillQ1MissingResults.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-shot backfill for the 17 Q1 2026 candidates whose results we pulled
 * from TOL/my-trinity on 30 April 2026 but never wrote to the database.
 * Source of truth: docs/session-handover-2026-04-30.md (Paul's Claude
 * folder).
 *
 * Why a hardcoded one-shot rather than a CSV import:
 *   - Yesterday's CSVs were already deleted.
 *   - Handover file has the precise data — candidate numbers (unambiguous),
 *     scores, results, exam dates.
 *   - This is a recovery for one specific gap; the proper long-term answer
 *     is the admin upload page (separate build).
 *
 * Matching rule: candidate_number is the unique Trinity identifier, so
 * we never risk grabbing the wrong row by name fuzziness.
 *
 * Idempotent: skips any row that already has a score recorded, so it's
 * safe to run twice (or after the upload page also handles the same row).
 *
 * Side fix: corrects Milo's surname Hugh → Lydon, an OCR misread Paul
 * confirmed in yesterday's session.
 *
 * Once Oscar Cain and Otis Frieze come in (currently still blank on TOL),
 * they go through the upload page like every future result — not this command.
 */
class BackfillQ1MissingResults extends Command
{
    protected $signature = 'exam:backfill-q1-missing-results {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill Q1 2026 results for the 17 candidates whose scores we pulled from TOL on 30 Apr 2026';

    /**
     * @return list<array{candidate_number: string, candidate_name: string, score: int, result: string, exam_date: string}>
     */
    private function results(): array
    {
        return [
            // Order 1-15279275724 (Clare Keeling) — all 3 done, exam date 10/03/2026
            ['candidate_number' => '1-15279077954', 'candidate_name' => 'Anugrahchandra Nidhin',     'score' => 77, 'result' => 'Merit',       'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-10567842683', 'candidate_name' => 'Tilly Lamb',                'score' => 78, 'result' => 'Merit',       'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15279278554', 'candidate_name' => 'Yuling Huang',              'score' => 77, 'result' => 'Merit',       'exam_date' => '2026-03-10'],

            // Order 1-15279500444
            ['candidate_number' => '1-15279500414', 'candidate_name' => 'Alfie John Clapson',        'score' => 72, 'result' => 'Pass',        'exam_date' => '2026-03-10'],

            // Order 1-15280573474 (Daniel Rogers / Pulse Music) — all 9 done, exam date 10/03/2026
            ['candidate_number' => '1-15280057324', 'candidate_name' => 'Alexander Campbell',        'score' => 75, 'result' => 'Merit',       'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15280405884', 'candidate_name' => 'Andrew Davies',             'score' => 67, 'result' => 'Pass',        'exam_date' => '2026-03-10'],
            // Clayton — all sections 0, no certificate ID. Recorded as a Fail
            // because that's what TOL has. Per handover this almost certainly
            // means he paid for the slot but never recorded the exam (no-show
            // / tech issue). Daniel's currently unreachable (refurbishment),
            // so we enter the data as TOL has it and let Daniel review later.
            ['candidate_number' => '1-15280254974', 'candidate_name' => 'Clayton Lo',                'score' =>  0, 'result' => 'Fail',        'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15280405934', 'candidate_name' => 'Evie Crawford',             'score' => 75, 'result' => 'Merit',       'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15279928394', 'candidate_name' => 'George Higham',             'score' => 70, 'result' => 'Pass',        'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15280255004', 'candidate_name' => 'Joe Gallagher',             'score' => 78, 'result' => 'Merit',       'exam_date' => '2026-03-10'],
            // Milo — was imported as "Milo Hugh" from a screenshot misread.
            // Real surname is Lydon; we fix the Student row separately below.
            ['candidate_number' => '1-15280573404', 'candidate_name' => 'Milo Lydon',                'score' => 60, 'result' => 'Pass',        'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15280573434', 'candidate_name' => 'Sam Brooks',                'score' => 72, 'result' => 'Pass',        'exam_date' => '2026-03-10'],
            ['candidate_number' => '1-15279928344', 'candidate_name' => 'Thomas Escribano',          'score' => 69, 'result' => 'Pass',        'exam_date' => '2026-03-10'],

            // Order 1-15451163944 (Daniel Rogers / Pulse Music) — 2 of 4 done
            // (Oscar Cain + Otis Frieze still genuinely pending — not in this list)
            ['candidate_number' => '1-15451621464', 'candidate_name' => 'Charlotte Sutton',          'score' => 63, 'result' => 'Pass',        'exam_date' => '2026-03-20'],
            ['candidate_number' => '1-15451163914', 'candidate_name' => 'James Preston',             'score' => 72, 'result' => 'Pass',        'exam_date' => '2026-03-20'],

            // Order 1-15641606604 (Daniel Rogers / Pulse Music)
            ['candidate_number' => '1-15641410114', 'candidate_name' => 'Flynn Munro',               'score' => 60, 'result' => 'Pass',        'exam_date' => '2026-03-25'],

            // Order 1-15899713974
            ['candidate_number' => '1-15899370904', 'candidate_name' => 'Delfina Yelich Battisacchi', 'score' => 88, 'result' => 'Distinction', 'exam_date' => '2026-03-30'],
        ];
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skippedAlreadyScored = 0;
        $missing = [];

        foreach ($this->results() as $r) {
            $entry = ExamEntry::where('candidate_number', $r['candidate_number'])->first();

            if (! $entry) {
                $missing[] = "{$r['candidate_number']} ({$r['candidate_name']})";
                continue;
            }

            // Idempotent skip — if a real score is already there, leave it alone.
            // We treat 0 as "not yet scored" so Clayton can still be backfilled
            // on a later run without weird logic.
            if ($entry->score !== null && $entry->score > 0) {
                $skippedAlreadyScored++;
                continue;
            }

            $payload = [
                'score' => $r['score'],
                'result' => $r['result'],
                'exam_date' => Carbon::parse($r['exam_date']),
            ];

            if ($dryRun) {
                $this->line("Would update {$r['candidate_number']} ({$r['candidate_name']}): score={$r['score']} result={$r['result']} date={$r['exam_date']}");
                $updated++;
                continue;
            }

            $entry->fill($payload)->save();
            $updated++;
        }

        // Side fix — Milo's surname. "Milo Hugh" was an OCR misread; Trinity
        // record is "Lydon". Fixes the Student row if the misnamed one
        // exists. Safe no-op on prod where the name might already be right.
        $miloFix = 0;
        if (! $dryRun) {
            $miloFix = Student::where('first_name', 'Milo')
                ->where('last_name', 'Hugh')
                ->update(['last_name' => 'Lydon']);
        }

        $this->newLine();
        $this->info("Updated entries:        {$updated}");
        $this->info("Skipped (already scored): {$skippedAlreadyScored}");
        $this->info("Milo surname fix:       " . ($dryRun ? 'skipped (dry-run)' : "{$miloFix} row(s) updated"));

        if ($missing) {
            $this->warn('Missing exam_entries (no candidate_number match):');
            foreach ($missing as $m) {
                $this->warn("  - {$m}");
            }
        }

        return self::SUCCESS;
    }
}
