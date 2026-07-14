<?php

// app/Services/ResultsScanParser.php

namespace App\Services;

/**
 * The brain behind /admin/results-scan — the F2F exam-report checker.
 *
 * Trinity hands Paul his face-to-face results as handwritten "Examination
 * Report" scans, in bulk, one PDF per exam type. On each form the identity
 * block (subject, grade, candidate name, Candidate ID, Order number) is
 * machine-printed, but every MARK is handwritten — and examiners sometimes
 * add up wrong, or write a total that's hard to read.
 *
 * The handwriting → numbers transcription happens upstream (a vision pass in
 * Cowork) and arrives here as a plain structured array, one record per
 * candidate. This service is pure: it never touches a PDF or the database.
 * It runs the checks Paul does by hand with a calculator, so the editable grid
 * can flag exactly the candidates that need a second look:
 *
 *   1. Sum the section marks and compare to the examiner's written total
 *      (catches addition slips, and reconstructs an unreadable total).
 *   2. Optionally compare a third total from a Trinity results export.
 *   3. Bound every mark by the section max printed on the form.
 *   4. Read the exam family (C&J vs R&P) off the subject so Singing/Vocals and
 *      the R&P instrument labels resolve correctly, then map the instrument
 *      through the single source of truth, TrinityCsvImporter::instrumentMap().
 *
 * The section template is NOT hardcoded — whatever section rows and maxes the
 * transcription carries are summed and checked, so a 4-song Grade 6-8 singing
 * form (or any grade variation) works without a code change.
 */
class ResultsScanParser
{
    /** Trinity attainment bands, read off the bottom of every form. */
    public const BAND_MERIT = 75;

    public const BAND_DISTINCTION = 87;

    public const BAND_PASS = 60;

    /**
     * Check every transcribed candidate.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, array<string, mixed>>
     */
    public function parse(array $candidates): array
    {
        return array_values(array_map([$this, 'checkCandidate'], $candidates));
    }

    /**
     * Run the checks for one candidate record and return the shaped result the
     * grid renders (marks, computed sum, the three total sources, band, flags).
     *
     * @param  array<string, mixed>  $c
     * @return array<string, mixed>
     */
    public function checkCandidate(array $c): array
    {
        $subject = trim((string) ($c['subject'] ?? ''));
        $family = self::detectFamily($subject);

        $flags = [];
        $sum = 0;
        $sections = [];

        foreach (($c['sections'] ?? []) as $s) {
            $label = trim((string) ($s['label'] ?? ''));
            $rawMark = $s['mark'] ?? null;
            $mark = ($rawMark === null || $rawMark === '' || ! is_numeric($rawMark)) ? null : (int) $rawMark;
            $max = (isset($s['max']) && is_numeric($s['max'])) ? (int) $s['max'] : null;

            if ($mark === null) {
                $flags[] = "Couldn't read the mark for “{$label}” — type it in.";
            } else {
                $sum += $mark;
                if ($max !== null && $mark > $max) {
                    $flags[] = "“{$label}” reads {$mark} but the section is out of {$max} — likely a misread.";
                }
            }

            // The piece/song name (label) and the examiner's transcribed comment
            // ride through untouched — they're not on the digital results, and
            // legible feedback is the point of capturing them for teachers.
            $sections[] = [
                'label' => $label,
                'mark' => $mark,
                'max' => $max,
                'comment' => trim((string) ($s['comment'] ?? '')),
            ];
        }

        $examinerTotal = (isset($c['examiner_total']) && is_numeric($c['examiner_total'])) ? (int) $c['examiner_total'] : null;
        $tolTotal = (isset($c['tol_total']) && is_numeric($c['tol_total'])) ? (int) $c['tol_total'] : null;

        // The core reconciliation. The section sum is the computed truth; the
        // examiner's written total is the second source. Disagreement is
        // exactly the case Paul currently catches with a calculator.
        $examinerAgrees = $examinerTotal === null || $examinerTotal === $sum;
        if ($examinerTotal !== null && ! $examinerAgrees) {
            $flags[] = "Sections add up to {$sum}, but the examiner's total says {$examinerTotal} — check the addition.";
        }

        $tolAgrees = $tolTotal === null || $tolTotal === $sum;
        if ($tolTotal !== null && ! $tolAgrees) {
            $flags[] = "Trinity's recorded total is {$tolTotal}, but the sections add up to {$sum}.";
        }

        // Sanity-check the band against the total the examiner wrote, in case
        // the total is legible but a section mark wasn't.
        if ($examinerTotal !== null && $examinerAgrees) {
            $verified = $examinerTotal;
        } else {
            $verified = $sum;
        }

        return [
            'candidate_name' => trim((string) ($c['candidate_name'] ?? '')),
            'candidate_id' => trim((string) ($c['candidate_id'] ?? '')),
            'order_number' => trim((string) ($c['order_number'] ?? '')),
            'subject' => $subject,
            'family' => $family,
            'grade' => self::parseGrade((string) ($c['grade'] ?? '')),
            'grade_raw' => trim((string) ($c['grade'] ?? '')),
            'instrument' => self::mapInstrument($subject, $family),
            'exam_date' => trim((string) ($c['exam_date'] ?? '')) ?: null,
            'examiner_number' => trim((string) ($c['examiner_number'] ?? '')),
            'general_comments' => trim((string) ($c['general_comments'] ?? '')),
            'sections' => $sections,
            'section_sum' => $sum,
            'examiner_total' => $examinerTotal,
            'tol_total' => $tolTotal,
            'verified_total' => $verified,
            'checks_pass' => $flags === [] && $examinerTotal !== null,
            'band' => self::band($verified),
            'flags' => $flags,
        ];
    }

    /**
     * Exam family from the printed subject. Only R&P forms carry a family word
     * ("Rock & Pop Guitar", "Rock and Pop Vocals"); a bare instrument ("Piano",
     * "Singing", "Clarinet") is Classical & Jazz.
     */
    public static function detectFamily(string $subject): string
    {
        $s = strtolower($subject);

        return (str_contains($s, 'rock') || str_contains($s, 'r&p') || str_contains($s, 'r & p'))
            ? 'R&P'
            : 'C&J';
    }

    /** The instrument token with any R&P family prefix stripped. */
    public static function instrumentToken(string $subject): string
    {
        $token = preg_replace('/^\s*(rock\s*&\s*pop|rock and pop|r\s*&\s*p|r&p)\s+/i', '', trim($subject));

        return trim((string) $token);
    }

    /**
     * Map the form's subject to one of our seeded Instrument names, family-aware
     * so the tokens that collide (Guitar, Singing/Vocals, Drums, Keyboards)
     * resolve correctly. Falls through to the shared Trinity map for everything
     * unambiguous (Piano, brass, woodwind, strings). Returns null when nothing
     * matches — the grid then leaves the instrument for Paul to set.
     */
    public static function mapInstrument(string $subject, string $family): ?string
    {
        $token = strtolower(self::instrumentToken($subject));

        $familyOverrides = $family === 'C&J'
            ? [
                'singing' => 'Singing (Classical)',
                'voice' => 'Singing (Classical)',
                'guitar' => 'Guitar (Classical)',
                'classical guitar' => 'Guitar (Classical)',
                'acoustic guitar' => 'Guitar (Classical)',
                // C&J percussion is "Drum Kit" (distinct from Rock & Pop "Drums").
                'drums' => 'Drum Kit',
                'drum kit' => 'Drum Kit',
            ]
            : [
                'vocals' => 'Singing (Rock/Pop)',
                'singing' => 'Singing (Rock/Pop)',
                'guitar' => 'Guitar (Rock/Pop)',
                'drums' => 'Drums',
                'keyboards' => 'Electronic Keyboard',
            ];

        if (isset($familyOverrides[$token])) {
            return $familyOverrides[$token];
        }

        $map = array_change_key_case(TrinityCsvImporter::instrumentMap(), CASE_LOWER);

        return $map[$token] ?? null;
    }

    /** Trinity attainment band for a /100 total. */
    public static function band(int $total): string
    {
        return match (true) {
            $total >= self::BAND_DISTINCTION => 'Distinction',
            $total >= self::BAND_MERIT => 'Merit',
            $total >= self::BAND_PASS => 'Pass',
            default => 'Below Pass',
        };
    }

    /**
     * Grade string → our grade token. Delegates to the digital importer's parser
     * so "Grade 2", "Grade Initial", diplomas etc. all resolve identically.
     */
    public static function parseGrade(string $grade): ?string
    {
        return TrinityCsvImporter::parseGrade($grade);
    }
}
