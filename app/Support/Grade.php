<?php

// app/Support/Grade.php

namespace App\Support;

/**
 * Trinity grades are stored three ways depending on which importer wrote the
 * row: "Grade 4", a bare "4", or "Initial". Everything that prints or groups a
 * grade goes through here, so a certificate never reads "Grade Grade 4" or
 * "Grade Initial". The front-end twin is resources/js/lib/grades.ts.
 */
final class Grade
{
    /** "Grade 4", "grade 4" and "4" all become "4"; any casing of Initial becomes "Initial". */
    public static function bare(?string $grade): string
    {
        $bare = (string) preg_replace('/^grade\s+/i', '', trim((string) $grade));

        return strcasecmp($bare, 'initial') === 0 ? 'Initial' : $bare;
    }

    /** "Grade 4" for a numbered grade, "Initial" for Initial, '' when unknown. */
    public static function label(?string $grade): string
    {
        $bare = self::bare($grade);

        if ($bare === '') {
            return '';
        }

        return $bare === 'Initial' ? 'Initial' : "Grade {$bare}";
    }
}
