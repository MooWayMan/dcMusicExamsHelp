<?php

// app/Support/QuarterLabel.php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * "3rd Quarter 2026", the wording on certificates, emails and admin pages.
 * Written once here; until 27 Sep 2026 it was typed out in five places
 * (tests/Feature/QuarterLabelTest.php fails on a sixth).
 */
final class QuarterLabel
{
    public static function for(int $quarter, int $year): string
    {
        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
            default => '?',
        };

        return "{$suffix} Quarter {$year}";
    }

    /** The quarter a date falls in; today when there is no date. */
    public static function forDate(?CarbonInterface $date): string
    {
        $date ??= now();

        return self::for((int) ceil($date->month / 3), (int) $date->year);
    }
}
