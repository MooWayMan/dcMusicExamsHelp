<?php

// app/Console/Commands/RetagRockPopDrums.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Historically every drum entry was canonicalised to the single "Drum Kit"
 * instrument. Now that the C&J percussion "Drum Kit" syllabus is a distinct
 * thing, Rock & Pop drum entries must sit on their own "Drums" instrument.
 *
 * This moves exam_entries whose subject area is "Rock and Pop" off "Drum Kit"
 * and onto "Drums", leaving any genuine C&J percussion (subject area "Music")
 * as "Drum Kit". Student instruments follow automatically (they derive from
 * exam_entries); the contact/school instrument pivots are rebuilt so no stale
 * "Drum Kit" links remain.
 *
 * Idempotent and safe to re-run.
 *
 *   sail artisan exam:retag-rp-drums --dry-run
 *   sail artisan exam:retag-rp-drums
 */
class RetagRockPopDrums extends Command
{
    protected $signature = 'exam:retag-rp-drums {--dry-run : Show what would change without saving}';

    protected $description = 'Move Rock & Pop drum entries from "Drum Kit" to "Drums"; leave C&J percussion as "Drum Kit".';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        $drumKit = Instrument::where('name', 'Drum Kit')->first();
        if (! $drumKit) {
            $this->info('No "Drum Kit" instrument found — nothing to retag.');

            return self::SUCCESS;
        }

        // Show the current split so it is obvious what will move and what stays.
        $byArea = ExamEntry::where('instrument_id', $drumKit->id)
            ->selectRaw("COALESCE(subject_area, '(none)') as area, count(*) as n")
            ->groupBy('area')
            ->pluck('n', 'area');

        $this->line('Current "Drum Kit" entries by subject area:');
        foreach ($byArea as $area => $n) {
            $this->line("  {$area}: {$n}");
        }

        // Subject-area spelling varies by import source ("Rock and Pop",
        // "Rock & Pop", "R&P"), so match on a pattern rather than an exact
        // string. Everything else (Music, Classical & Jazz, blank) stays as
        // the C&J "Drum Kit".
        $isRockPop = fn ($q) => $q
            ->whereRaw('LOWER(subject_area) LIKE ?', ['%rock%'])
            ->orWhereRaw('LOWER(subject_area) LIKE ?', ['%r&p%']);

        $rpEntryIds = ExamEntry::where('instrument_id', $drumKit->id)
            ->where($isRockPop)
            ->pluck('id');

        $total = ExamEntry::where('instrument_id', $drumKit->id)->count();
        $stay = $total - $rpEntryIds->count();

        $this->info("Rock & Pop entries to move to \"Drums\": {$rpEntryIds->count()}");
        $this->info("Entries left as \"Drum Kit\" (C&J / unclassified — review if unexpected): {$stay}");

        if ($rpEntryIds->isEmpty() || $dry) {
            $this->info($dry ? 'Dry run complete.' : 'Nothing to move.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($drumKit, $rpEntryIds) {
            $drums = Instrument::firstOrCreate(['name' => 'Drums'], ['family' => 'Percussion']);

            ExamEntry::whereIn('id', $rpEntryIds)->update(['instrument_id' => $drums->id]);

            $this->resyncPivots([$drumKit->id, $drums->id]);
        });

        $this->info('Done — entries retagged and contact/school instrument links rebuilt.');
        $this->warn('Note: PrizeDraw.winner_instrument snapshots are left untouched (historical record).');

        return self::SUCCESS;
    }

    /**
     * Recompute contact_instrument / school_instrument for the given instrument
     * ids straight from exam_entries: detach them everywhere, then re-attach
     * only where an entry earns them. This clears stale "Drum Kit" links that a
     * plain syncWithoutDetaching backfill would leave behind.
     *
     * @param  array<int, int>  $instrumentIds
     */
    private function resyncPivots(array $instrumentIds): void
    {
        $entries = ExamEntry::whereIn('instrument_id', $instrumentIds)
            ->whereNotNull('teacher_contact_id')
            ->get(['teacher_contact_id', 'instrument_id', 'booking_role']);

        // school_admin contact id => [school ids]
        $schoolsByContact = ExamContact::withType('school_admin')
            ->with('schools:id')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->schools->pluck('id')->all()]);

        $contactMap = [];
        $schoolMap = [];
        foreach ($entries as $e) {
            $contactMap[$e->teacher_contact_id][$e->instrument_id] = true;

            if ($e->booking_role === 'school_admin' && isset($schoolsByContact[$e->teacher_contact_id])) {
                foreach ($schoolsByContact[$e->teacher_contact_id] as $sid) {
                    $schoolMap[$sid][$e->instrument_id] = true;
                }
            }
        }

        // Wipe both drum instruments from every pivot first, then re-attach.
        DB::table('contact_instrument')->whereIn('instrument_id', $instrumentIds)->delete();
        DB::table('school_instrument')->whereIn('instrument_id', $instrumentIds)->delete();

        foreach ($contactMap as $contactId => $ids) {
            ExamContact::find($contactId)?->instruments()->syncWithoutDetaching(array_keys($ids));
        }
        foreach ($schoolMap as $schoolId => $ids) {
            School::find($schoolId)?->instruments()->syncWithoutDetaching(array_keys($ids));
        }
    }
}
