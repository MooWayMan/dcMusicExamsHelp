<?php

// app/Console/Commands/RetagRockPopKeyboards.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Historically the importer collapsed the Rock & Pop keyboard exam onto the
 * Classical & Jazz "Electronic Keyboard" instrument, so the two were
 * indistinguishable. R&P keyboard is its own exam ("Keyboards"); the C&J
 * keyboard exam stays "Electronic Keyboard".
 *
 * This moves exam_entries whose subject area is Rock & Pop off "Electronic
 * Keyboard" and onto "Keyboards", leaving any genuine C&J entries as
 * "Electronic Keyboard". Student instruments follow automatically (they derive
 * from exam_entries); the contact/school instrument pivots are rebuilt so no
 * stale "Electronic Keyboard" links remain on R&P teachers.
 *
 * Idempotent and safe to re-run.
 *
 *   sail artisan exam:retag-rp-keyboards --dry-run
 *   sail artisan exam:retag-rp-keyboards
 */
class RetagRockPopKeyboards extends Command
{
    protected $signature = 'exam:retag-rp-keyboards {--dry-run : Show what would change without saving}';

    protected $description = 'Move Rock & Pop keyboard entries from "Electronic Keyboard" to "Keyboards"; leave C&J as "Electronic Keyboard".';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        $ek = Instrument::where('name', 'Electronic Keyboard')->first();
        if (! $ek) {
            $this->info('No "Electronic Keyboard" instrument found — nothing to retag.');

            return self::SUCCESS;
        }

        // Show the current split so it is obvious what will move and what stays.
        $byArea = ExamEntry::where('instrument_id', $ek->id)
            ->selectRaw("COALESCE(subject_area, '(none)') as area, count(*) as n")
            ->groupBy('area')
            ->pluck('n', 'area');

        $this->line('Current "Electronic Keyboard" entries by subject area:');
        foreach ($byArea as $area => $n) {
            $this->line("  {$area}: {$n}");
        }

        // Subject-area spelling varies by import source ("Rock and Pop",
        // "Rock & Pop", "R&P"), so match on a pattern rather than an exact
        // string. Everything else (Music, Classical & Jazz, blank) stays as
        // the C&J "Electronic Keyboard".
        $isRockPop = fn ($q) => $q
            ->whereRaw('LOWER(subject_area) LIKE ?', ['%rock%'])
            ->orWhereRaw('LOWER(subject_area) LIKE ?', ['%r&p%']);

        $rpEntryIds = ExamEntry::where('instrument_id', $ek->id)
            ->where($isRockPop)
            ->pluck('id');

        $total = ExamEntry::where('instrument_id', $ek->id)->count();
        $stay = $total - $rpEntryIds->count();

        $this->info("Rock & Pop entries to move to \"Keyboards\": {$rpEntryIds->count()}");
        $this->info("Entries left as \"Electronic Keyboard\" (C&J / unclassified — review if unexpected): {$stay}");

        if ($rpEntryIds->isEmpty() || $dry) {
            $this->info($dry ? 'Dry run complete.' : 'Nothing to move.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ek, $rpEntryIds) {
            $keyboards = Instrument::firstOrCreate(['name' => 'Keyboards'], ['family' => 'Keyboard']);

            ExamEntry::whereIn('id', $rpEntryIds)->update(['instrument_id' => $keyboards->id]);

            $this->resyncPivots([$ek->id, $keyboards->id]);
        });

        $this->info('Done — entries retagged and contact/school instrument links rebuilt.');
        $this->warn('Note: PrizeDraw.winner_instrument snapshots are left untouched (historical record).');

        return self::SUCCESS;
    }

    /**
     * Recompute contact_instrument / school_instrument for the given instrument
     * ids straight from exam_entries: detach them everywhere, then re-attach
     * only where an entry earns them. This clears stale "Electronic Keyboard"
     * links that a plain syncWithoutDetaching backfill would leave behind.
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

        // Wipe both keyboard instruments from every pivot first, then re-attach.
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
