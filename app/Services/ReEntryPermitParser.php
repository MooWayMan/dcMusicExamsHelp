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

    /** Split out so tests can exercise the parsing without a real PDF. */
    public function parseText(string $text): array
    {
        // Normalise the whitespace the extractor leaves between label and
        // value — it varies with the PDF's internal spacing.
        $flat = preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $text));

        $grab = function (string $label) use ($flat): ?string {
            // Value runs to end of line; permits put one field per line.
            if (preg_match('/'.preg_quote($label, '/').'\s*:?\s*(.+)/i', $flat, $m)) {
                return trim($m[1]) !== '' ? trim($m[1]) : null;
            }

            return null;
        };

        // "Code" is deliberately matched last and anchored, because the
        // candidate id and the voucher code share the 1-XXXXXXXX shape.
        $code = null;
        if (preg_match('/\bCode\s*:\s*(1-\d{6,})\b/i', $flat, $m)) {
            $code = $m[1];
        }

        $candidateNumber = null;
        if (preg_match('/Candidate\s+Id\s*:?\s*(1-\d{6,})\b/i', $flat, $m)) {
            $candidateNumber = $m[1];
        }

        return [
            'is_permit' => stripos($flat, 'Re-entry Permit') !== false,
            'candidate_name' => $grab('Candidate Name'),
            'candidate_number' => $candidateNumber,
            'subject' => $grab('Subject'),
            'exam' => $grab('Exam'),
            'code' => $code,
            'status' => $grab('Status'),
            'issued_at' => $this->date($grab('Date of issue')),
            'valid_until' => $this->date($grab('Valid Until')),
            'credit_discount' => $grab('Credit Discount'),
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
