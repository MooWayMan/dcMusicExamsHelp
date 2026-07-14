<?php

// tests/Unit/InstrumentMappingTest.php

use App\Services\ResultsScanParser;
use App\Services\TrinityCsvImporter;

test('Rock & Pop drums map to "Drums", not "Drum Kit"', function () {
    $map = TrinityCsvImporter::instrumentMap();

    expect($map['Drums'])->toBe('Drums')
        ->and($map['R&P Drums'])->toBe('Drums');
});

test('the results-scan parser splits R&P "Drums" from C&J "Drum Kit"', function () {
    // Rock & Pop drummer → "Drums"
    expect(ResultsScanParser::mapInstrument('Rock & Pop Drums', 'R&P'))->toBe('Drums');

    // Classical & Jazz percussion → "Drum Kit"
    expect(ResultsScanParser::mapInstrument('Drum Kit', 'C&J'))->toBe('Drum Kit')
        ->and(ResultsScanParser::mapInstrument('Drums', 'C&J'))->toBe('Drum Kit');
});
