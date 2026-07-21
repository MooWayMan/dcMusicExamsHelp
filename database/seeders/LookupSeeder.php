<?php

// database/seeders/LookupSeeder.php

namespace Database\Seeders;

use App\Models\Instrument;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Seed the lookup tables with standard Trinity instruments.
     */
    public function run(): void
    {
        // ──────────────────────────────────────────
        // Instruments grouped by family
        // ──────────────────────────────────────────
        $instruments = [
            'Keyboard' => ['Piano', 'Organ', 'Electronic Keyboard', 'Keyboards'],
            'Strings' => ['Violin', 'Viola', 'Cello', 'Double Bass', 'Harp', 'Guitar (Classical)', 'Guitar (Jazz)', 'Guitar (Acoustic)', 'Guitar (Rock/Pop)', 'Bass Guitar', 'Ukulele'],
            'Brass' => ['Trumpet', 'Cornet', 'Flugelhorn', 'French Horn', 'Tenor Horn', 'Trombone', 'Euphonium', 'Tuba'],
            'Woodwind' => ['Flute', 'Oboe', 'Clarinet', 'Bassoon', 'Saxophone', 'Recorder'],
            'Voice' => ['Singing (Classical)', 'Singing (Jazz)', 'Singing (Rock/Pop)', 'Musical Theatre'],
            // "Drums" = Rock & Pop drum kit; "Drum Kit" = Classical & Jazz percussion syllabus.
            'Percussion' => ['Drums', 'Drum Kit', 'Tuned Percussion', 'Snare Drum', 'Timpani'],
        ];

        foreach ($instruments as $family => $names) {
            foreach ($names as $name) {
                Instrument::firstOrCreate(
                    ['name' => $name],
                    ['family' => $family]
                );
            }
        }
    }
}
