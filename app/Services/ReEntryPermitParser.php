<?php

// app/Services/ReEntryPermitParser.php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Parses a Trinity "Re-entry Permit" PDF.
 *
 * Trinity issues one when a booked candidate doesn't sit: a voucher at 100%
 * credit, valid twelve months, letting the family re-enter free. The permit
 * is the ONLY place the code lives, and until now it lived nowhere but a PDF
 * in a downloads folder.
 *
 * The layout is fixed and label-driven, so this reads by label rather than by
 * position (unlike RemittanceParser, which has to cope with two row shapes):
 *
 *     Re-entry Permit
 *     Date of issue: 14/07/2026
 *     Candidate Name:   Sam Dobie
 *     Candidate Id:     1-17563392237
 *     Subject:          Rock and Pop
 *     Exam:             Rock and Pop Guitar Grade 4
 *     Valid Until:      14/07/2027
 *     Status:           Valid
 *     Code: 1-18154879067
 *     Credit Discount: 100%
 *
 * `Candidate Id` is the candidate NUMBER — the same value the enrolment list
 * carries and the same one that stays with a person across sittings, so it is
 * what links a permit back to its exam entry.
 */
class ReEntryPermitParser
{
    /**
     * Pure PHP, so this deploys to Laravel Cloud with no poppler binary —
     * same reasoning as RemittanceParser.
     */
    public function extractText(string $path): string
    {
        return (new PdfParser())->parseFile($path)->getText();
    }

    /**
     * @return array{
     *   is_permit: bool, candidate_name: ?string, candidate_number: ?string,
     *   subject: ?string, exam: ?string, code: ?string, status: ?string,
     *   issued_at: ?string, valid_until: ?string, credit_discount: ?string
     * }
     */
    public function parse(string $path): array
    {
        return $this->parseText($this->extractText($path));
    }

    /**
     * Split out so tests can exercise the parsing without a real PDF.
     *
     * ⚠️ The permit is laid out by ABSOLUTE POSITION, not reading order, and
     * that changes everything. Trinity writes the labels and the values as
     * separate text operators, and for the candidate it emits BOTH labels
     * before EITHER value:
     *
     *     (Candidate Name:)Tj
     *     (Candidate Id: )Tj
     *     (Sam Dobie)Tj
     *     (1-17563392237)Tj
     *
     * pdftotext -layout rebuilds that by coordinates and looks tidy. smalot,
     * which is what we actually run, reads stream order — so "Candidate Id:"
     * is followed by "Sam Dobie", and every label-adjacent regex returns the
     * wrong thing or nothing. That is why all three real permits came back as
     * "not a permit" on 3 Aug 2026 while the unit tests passed.
     *
     * So: pair a RUN of bare labels with the RUN of values that follows it,
     * which handles both that case and the ordinary "Label: value" lines.
     */
    public function parseText(string $text): array
    {
        $lines = preg_split('/\R+/', str_replace(["\r\n", "\r"], "\n", (string) $text)) ?: [];
        $lines = array_values(array_filter(
            array_map(fn ($l) => trim(preg_replace('/[ \t]+/', ' ', (string) $l)), $lines),
            fn ($l) => $l !== ''
        ));

        $isBareLabel = fn (string $l) => (bool) preg_match('/^(.{2,40}?):\s*$/', $l);

        $fields = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            // "Label: value" on one line — Date of issue, Code, Credit Discount.
            if (preg_match('/^(.{2,40}?):\s*(\S.*)$/', $line, $m)) {
                $fields[mb_strtolower(trim($m[1]))] = trim($m[2]);
                $i++;
                continue;
            }

            // A run of bare labels, then the run of values belonging to them.
            if ($isBareLabel($line)) {
                $labels = [];
                while ($i < $count && preg_match('/^(.{2,40}?):\s*$/', $lines[$i], $m)) {
                    $labels[] = mb_strtolower(trim($m[1]));
                    $i++;
                }

                $values = [];
                while ($i < $count
                    && count($values) < count($labels)
                    && ! $isBareLabel($lines[$i])
                    && ! preg_match('/^(.{2,40}?):\s*\S/', $lines[$i])) {
                    $values[] = $lines[$i];
                    $i++;
                }

                foreach ($labels as $k => $label) {
                    if (isset($values[$k])) {
                        $fields[$label] = $values[$k];
                    }
                }
                continue;
            }

            $i++;
        }

        $flat = implode("\n", $lines);

        // The code is reliably inline ("Code: 1-18154879067").
        $code = null;
        if (preg_match('/\b(1-\d{6,})\b/', (string) ($fields['code'] ?? ''), $m)) {
            $code = $m[1];
        } elseif (preg_match('/\bCode\s*:\s*(1-\d{6,})\b/i', $flat, $m)) {
            $code = $m[1];
        }

        $candidateNumber = null;
        if (preg_match('/\b(1-\d{6,})\b/', (string) ($fields['candidate id'] ?? ''), $m)) {
            $candidateNumber = $m[1];
        } else {
            // Safety net: the candidate id is the other 1-XXXXXXXX in the file.
            foreach (array_unique(preg_match_all('/\b(1-\d{6,})\b/', $flat, $all) ? $all[1] : []) as $found) {
                if ($found !== $code) {
                    $candidateNumber = $found;
                    break;
                }
            }
        }

        return [
            'is_permit' => stripos($flat, 'Re-entry Permit') !== false,
            'candidate_name' => $fields['candidate name'] ?? null,
            'candidate_number' => $candidateNumber,
            'subject' => $fields['subject'] ?? null,
            'exam' => $fields['exam'] ?? null,
            'code' => $code,
            'status' => $fields['status'] ?? null,
            'issued_at' => $this->date($fields['date of issue'] ?? null),
            'valid_until' => $this->date($fields['valid until'] ?? null),
            'credit_discount' => $fields['credit discount'] ?? null,
        ];
    }

    /** Trinity writes dd/mm/yyyy on these. */
    private function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', trim($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
