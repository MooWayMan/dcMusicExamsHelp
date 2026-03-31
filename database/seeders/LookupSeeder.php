<?php

// database/seeders/LookupSeeder.php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\SubjectArea;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Seed the lookup tables with standard Trinity instruments and subject areas.
     */
    public function run(): void
    {
        // ──────────────────────────────────────────
        // Subject Areas
        // ──────────────────────────────────────────
        $subjectAreas = ['Music', 'Rock and Pop', 'Jazz'];

        foreach ($subjectAreas as $name) {
            SubjectArea::firstOrCreate(['name' => $name]);
        }

        // ──────────────────────────────────────────
        // Instruments grouped by family
        // ──────────────────────────────────────────
        $instruments = [
            'Keyboard' => ['Piano', 'Organ', 'Electronic Keyboard'],
            'Strings' => ['Violin', 'Viola', 'Cello', 'Double Bass', 'Harp', 'Guitar (Classical)', 'Guitar (Jazz)', 'Guitar (Rock/Pop)', 'Bass Guitar', 'Ukulele'],
            'Brass' => ['Trumpet', 'Cornet', 'Flugelhorn', 'French Horn', 'Trombone', 'Euphonium', 'Tuba'],
            'Woodwind' => ['Flute', 'Oboe', 'Clarinet', 'Bassoon', 'Saxophone', 'Recorder'],
            'Voice' => ['Singing (Classical)', 'Singing (Jazz)', 'Singing (Rock/Pop)', 'Musical Theatre'],
            'Percussion' => ['Drum Kit', 'Tuned Percussion', 'Snare Drum', 'Timpani'],
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
