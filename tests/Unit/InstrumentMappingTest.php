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

test('Rock & Pop keyboards map to "Keyboards", not the C&J "Electronic Keyboard"', function () {
    $map = TrinityCsvImporter::instrumentMap();

    expect($map['Keyboards'])->toBe('Keyboards')
        ->and($map['R&P Keyboards'])->toBe('Keyboards')
        ->and($map['Electronic Keyboard'])->toBe('Electronic Keyboard');
});

test('Rock & Pop bass maps to "Bass Guitar"', function () {
    $map = TrinityCsvImporter::instrumentMap();

    expect($map['Bass'])->toBe('Bass Guitar')
        ->and($map['R&P Bass'])->toBe('Bass Guitar');
});

test('the results-scan parser splits R&P "Keyboards" from C&J "Electronic Keyboard"', function () {
    expect(ResultsScanParser::mapInstrument('Rock & Pop Keyboards', 'R&P'))->toBe('Keyboards')
        ->and(ResultsScanParser::mapInstrument('Electronic Keyboard', 'C&J'))->toBe('Electronic Keyboard');
});

test('the results-scan parser maps R&P bass to "Bass Guitar"', function () {
    expect(ResultsScanParser::mapInstrument('Rock & Pop Bass', 'R&P'))->toBe('Bass Guitar');
});
