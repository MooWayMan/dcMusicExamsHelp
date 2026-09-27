<?php

// app/Support/PublicName.php

namespace App\Support;

use App\Models\ExamContact;
use App\Models\School;

/**
 * How a name is shown to anyone other than its owner. The GDPR rule for
 * people is first name + surname initial ("Megan R") unless they opted in to
 * their full name; that shortening lives here and nowhere else
 * (tests/Feature/PublicNameTest.php fails on a second copy).
 *
 * A school or business is not a person, so its name is never shortened.
 * On 27 Sep 2026 Learn Music Ltd won the Q2 teacher draw (a school admin's
 * entries are pooled under the school) and the dashboard showed "Learn L",
 * because the winner lookup only knew about people.
 */
final class PublicName
{
    /** "Megan Roberts" → "Megan R". A single word comes back as it was. */
    public static function short(?string $name): string
    {
        $name = (string) $name;
        $parts = preg_split('/\s+/', trim($name));

        if (count($parts) < 2) {
            return $name;
        }

        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1));
    }

    /**
     * The name to show for a teacher-draw winner. Quarter end credits a
     * school admin's school bookings to the SCHOOL, so a school winner is
     * stored under the school's name and shown in full. Any other name is a
     * person (their own pupils, even if they also work at a school): full
     * name if they opted in, otherwise short. A name matching nobody is
     * shortened, as it may be a person.
     */
    public static function drawWinner(?string $winnerName): string
    {
        $key = mb_strtolower(trim((string) $winnerName));

        if ($key === '') {
            return '';
        }

        $school = School::query()->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();

        if ($school) {
            return $school->name;
        }

        $contact = ExamContact::query()->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();

        return $contact ? $contact->displayName() : self::short($winnerName);
    }
}
