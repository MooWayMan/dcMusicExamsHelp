<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Instrument;
use Illuminate\Console\Command;

/**
 * One-shot backfill of instrument_id on Q1 2026 exam_entries.
 *
 * Context: the 17 Apr schema refactor migration partially wiped instrument_id
 * on ~38 Q1 F2F rows. The scores and candidate names survived but the
 * instrument link didn't. The admin now shows "Unknown" for those candidates,
 * which is embarrassing when Paul opens a teacher's row.
 *
 * This command is non-destructive: it only updates rows where instrument_id
 * is currently NULL. Any row that already has an instrument is left alone.
 *
 * Mapping data sourced from:
 *  - project_q1_results_complete.md (authoritative list of 39 Q1 F2F entries)
 *  - the email Paul sent Clare Keeling on 13 Apr, which confirmed the instruments
 *
 * Usage:  sail artisan backfill:q1-instruments          # runs the update
 *         sail artisan backfill:q1-instruments --dry-run # preview only
 */
class BackfillQ1Instruments extends Command
{
    protected $signature = 'backfill:q1-instruments {--dry-run : Show what would change without updating}';
    protected $description = 'Backfill instrument_id on Q1 2026 exam_entries that lost the link during the 17 Apr migration';

    /**
     * candidate_name => instruments.name
     *
     * Trinity terminology mapped to our instruments table:
     *  - "Acoustic Guitar"   => Guitar (Acoustic)      [classical/jazz examiner, own syllabus]
     *  - "Guitar" (classical/jazz order) => Guitar (Classical)
     *  - "R&P Guitar"        => Guitar (Rock/Pop)
     *  - "Singing" (no qualifier on classical order) => Singing (Classical)
     *  - "R&P Vocals"        => Singing (Rock/Pop)
     *  - "R&P Drums"         => Drums
     *
     * "Acoustic Guitar" and "Tenor Horn" are added in LookupSeeder; run
     * `sail artisan db:seed --class=LookupSeeder` first to ensure they exist.
     */
    private const MAPPING = [
        // Order 1-11508172910 — Learn Music Ltd, 5 Mar (Clare Keeling)
        'Aria Maddison Chambers'        => 'Singing (Classical)',
        'Ravi Michael Steff'            => 'Trombone',
        'Solomon Elliot David Wetherall' => 'Tenor Horn',  // not in seeder, created below
        'Primrose Nancy Gannon'         => 'Singing (Classical)',
        'Maya Ghali'                    => 'Piano',
        'Elise Florence Scott'          => 'Flute',
        'Dean Gwyther'                  => 'Clarinet',
        'Imogen Mayes'                  => 'Guitar (Acoustic)',
        'Niamh Keyna Anakin'            => 'Clarinet',
        'Isaac Pover'                   => 'Piano',
        'Farrah Harper Fennell'         => 'Piano',
        'Kate Leyland'                  => 'Guitar (Acoustic)',

        // Order 1-11508308070 — Wirral School of Music, 6 Mar
        'Seth James Barraclough'        => 'Trombone',
        'Anna Martin'                   => 'Piano',
        'Julia Zamirska'                => 'Oboe',
        'Sam Williamson'                => 'Piano',
        'Maya Parkinson'                => 'Piano',
        'Imogen Hughes'                 => 'Piano',
        'Krystian Debek'                => 'Violin',
        'Florence Cookson'              => 'Piano',
        'Alice Jun Mei Khoo'            => 'Cornet',
        'Henry Rodway'                  => 'Piano',
        'Megan Parkinson'               => 'Piano',
        'Lucas Hassall'                 => 'Piano',

        // Order 1-12208881501 — Learn Music R&P, 7 Mar (Pulse Music / Hillside)
        'Amy Norcott'                   => 'Singing (Rock/Pop)',
        'Mia Mason'                     => 'Singing (Rock/Pop)',
        'Pearl Fay'                     => 'Singing (Rock/Pop)',
        'Charlotte McVey'               => 'Singing (Rock/Pop)',
        'Zachary Beswick'               => 'Guitar (Rock/Pop)',
        'Zach Hughes'                   => 'Guitar (Rock/Pop)',
        'Lilly-Mae Dibbert'             => 'Singing (Rock/Pop)',

        // Order 1-11478141779 — Learn Music R&P, 7 Mar (School of Rox + parents)
        'Thomas Gander'                 => 'Guitar (Rock/Pop)',
        'Alfie Coburn'                  => 'Guitar (Rock/Pop)',
        'Francesca Lee'                 => 'Guitar (Rock/Pop)',
        'Jacob Thomas Leslie'           => 'Guitar (Rock/Pop)',
        'Jasper Christian O\'Malley'    => 'Guitar (Rock/Pop)',
        'Jemima Claire Reed'            => 'Singing (Rock/Pop)',
        'Daniel Carty'                  => 'Drums',
        'Philip Martin Gazdecki'        => 'Guitar (Rock/Pop)',

        // Extras that appeared in Clare Keeling's 13 Apr email but not
        // in the memory breakdown (likely Q1 2026 additions):
        'Naomi Ruth Maher'              => 'Piano',
        'Mira Ghali'                    => 'Piano',
        'George John Canning Yates'     => 'Piano',
        'Harrison John Burslem'         => 'Piano',
        'George Ghali'                  => 'Drums',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Ensure any instruments we map to exist in the DB. These were added
        // to LookupSeeder but prod was seeded before the addition.
        $requiredInstruments = [
            'Tenor Horn'         => 'Brass',
            'Guitar (Acoustic)'  => 'Strings',
        ];

        foreach ($requiredInstruments as $instrumentName => $family) {
            if (Instrument::where('name', $instrumentName)->exists()) {
                continue;
            }

            if ($dryRun) {
                $this->line("  (would create) Instrument '{$instrumentName}' in {$family} family");
            } else {
                Instrument::create(['name' => $instrumentName, 'family' => $family]);
                $this->info("Created Instrument '{$instrumentName}' ({$family}).");
            }
        }

        // Cache instrument name → id
        $instruments = Instrument::pluck('id', 'name');

        $updated = 0;
        $skippedAlreadySet = 0;
        $notFound = [];
        $missingInstrument = [];

        foreach (self::MAPPING as $candidateName => $instrumentName) {
            $instrumentId = $instruments[$instrumentName] ?? null;

            if (! $instrumentId && ! $dryRun) {
                // Refresh cache in case we created Tenor Horn above
                $instruments = Instrument::pluck('id', 'name');
                $instrumentId = $instruments[$instrumentName] ?? null;
            }

            if (! $instrumentId) {
                $missingInstrument[] = "{$candidateName} → {$instrumentName}";
                continue;
            }

            $entry = ExamEntry::where('candidate_name', $candidateName)->first();

            if (! $entry) {
                $notFound[] = $candidateName;
                continue;
            }

            if ($entry->instrument_id !== null) {
                $skippedAlreadySet++;
                continue;
            }

            if ($dryRun) {
                $this->line("  (would update) {$candidateName} → {$instrumentName}");
            } else {
                $entry->update(['instrument_id' => $instrumentId]);
                $this->line("  ✓ {$candidateName} → {$instrumentName}");
            }
            $updated++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d entries, skipped %d already set.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $skippedAlreadySet
        ));

        if (! empty($notFound)) {
            $this->warn('Candidates not found in exam_entries:');
            foreach ($notFound as $n) {
                $this->line("    - {$n}");
            }
        }

        if (! empty($missingInstrument)) {
            $this->error('Instrument lookups that failed (add to DB and retry):');
            foreach ($missingInstrument as $m) {
                $this->line("    - {$m}");
            }
        }

        return Command::SUCCESS;
    }
}
