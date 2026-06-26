<?php

// app/Services/RemittanceParser.php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Parses a Trinity College London "Remittance Advice" PDF into structured
 * transaction rows so they can be reconciled against Orders (marking
 * commission_paid_at / commission_paid_amount).
 *
 * Why token-based rather than column-based: Trinity uses two row layouts on
 * the SAME statement.
 *
 *   • Digital (DGD) rows put the order number in the MyTrinity Reference
 *     column:   31 May 2026  CEB012013  DGD  1-17510214884  GBP  9.80  9.80
 *
 *   • Face-to-face / centre rows put the order number in the Reference
 *     column and the venue where the digital order number would sit:
 *               6 December 2025  1-6053029943  CET000447  Liverpool  GBP 124.25 124.25
 *
 * A statement can mix both, with different transaction dates. So instead of
 * trusting column position we pull, from every line that contains a Trinity
 * order number (1-XXXXXXXXX), the order number + the trailing money amounts.
 * That survives PDF-extraction quirks (smalot vs pdftotext spacing) too.
 */
class RemittanceParser
{
    /** Trinity order numbers look like 1- followed by many digits. */
    private const ORDER_RE = '/\b(1-\d{6,})\b/';

    /** A money amount: 124.25 or 1,234.50 */
    private const MONEY_RE = '/\d{1,3}(?:,\d{3})*\.\d{2}/';

    /** A long-form date like "23 June 2026". */
    private const DATE_RE = '/\b(\d{1,2}\s+[A-Za-z]+\s+\d{4})\b/';

    /**
     * Extract the raw text of a PDF using smalot/pdfparser (pure PHP, so it
     * deploys cleanly to Laravel Cloud without a poppler binary).
     */
    public function extractText(string $path): string
    {
        $parser = new PdfParser();

        return $parser->parseFile($path)->getText();
    }

    /**
     * Parse a PDF file straight to the structured shape.
     */
    public function parseFile(string $path): array
    {
        return $this->parseText($this->extractText($path));
    }

    /**
     * Parse already-extracted remittance text. Pure (no PDF dependency) so it
     * is cheap to unit-test against captured Trinity layouts.
     *
     * @return array{
     *     remittance_date: ?string,
     *     account_code: ?string,
     *     recipient_email: ?string,
     *     total: ?float,
     *     rows: array<int, array{
     *         transaction_date: ?string,
     *         order_number: string,
     *         description: string,
     *         transaction_amount: float,
     *         gbp_amount: float
     *     }>
     * }
     */
    public function parseText(string $text): array
    {
        // NB: header fields are matched without relying on a space after the
        // label or a fixed column order — PDF text extractors (smalot vs
        // pdftotext) disagree, e.g. smalot emits "Remittance Date2 April 2026"
        // (no space) and reverses "71-120Your Account code with us:" /
        // "236.45Total Amount (GBP):" (value before label).
        $moneyPat = substr(self::MONEY_RE, 1, -1);

        $remittanceDate = null;
        if (preg_match('/Remittance Date\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})/', $text, $m)) {
            $remittanceDate = $this->normaliseDate($m[1]);
        }

        $accountCode = null;
        if (preg_match('/Your Account code with us:\s*(\d{2}-\d{3})/', $text, $m)
            || preg_match('/(\d{2}-\d{3})\s*Your Account code with us:/', $text, $m)) {
            $accountCode = trim($m[1]);
        }

        $recipientEmail = null;
        if (preg_match('/emailed this advice to:\s*(\S+@\S+)/', $text, $m)) {
            $recipientEmail = trim($m[1]);
        }

        $total = null;
        if (preg_match('/Total Amount\s*\(GBP\):\s*(' . $moneyPat . ')/', $text, $m)
            || preg_match('/(' . $moneyPat . ')\s*Total Amount\s*\(GBP\)/', $text, $m)) {
            $total = $this->money($m[1]);
        }

        // Two extraction strategies, because PDF text extractors disagree on
        // how they lay this table out: pdftotext keeps each transaction on one
        // line; smalot/pdfparser can fragment a row across text objects. Run
        // both and keep whichever recovered more rows (ties → the line-based
        // one, which yields tidier descriptions). Both pull the same critical
        // data: order number + the GBP amount that was actually paid.
        $lineRows = $this->parseRowsLineBased($text);
        $globalRows = $this->parseRowsGlobal($text);
        $rows = count($globalRows) > count($lineRows) ? $globalRows : $lineRows;

        return [
            'remittance_date' => $remittanceDate,
            'account_code' => $accountCode,
            'recipient_email' => $recipientEmail,
            'total' => $total,
            'rows' => $rows,
        ];
    }

    /**
     * Strategy A — one transaction per line. Works on tidy extractor output
     * (e.g. pdftotext / poppler), where each row sits on its own line.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseRowsLineBased(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '' || ! preg_match(self::ORDER_RE, $line, $om)) {
                continue;
            }
            if (! preg_match_all(self::MONEY_RE, $line, $mm) || count($mm[0]) === 0) {
                continue;
            }

            $orderNumber = $om[1];
            $amounts = array_map([$this, 'money'], $mm[0]);

            // Find the row date AFTER stripping money tokens — extractors can
            // glue the date to a neighbouring amount ("15.6028 February 2026"),
            // which defeats word-boundary anchors. No \b here for the same
            // reason ("2026CEB012010").
            $dateSearch = preg_replace(self::MONEY_RE, ' ', $line) ?? $line;
            $rawDate = preg_match('/(\d{1,2}\s+[A-Za-z]+\s+\d{4})/', $dateSearch, $dm) ? $dm[1] : null;

            $description = $line;
            $description = preg_replace(self::DATE_RE, '', $description, 1) ?? $description;
            $description = str_replace($orderNumber, '', $description);

            $rows[] = $this->makeRow($orderNumber, $amounts, $rawDate, $description);
        }

        return $rows;
    }

    /**
     * Strategy B — scan the whole text for "<order> … GBP <txn> <gbp>",
     * tolerating newlines between the order number and its amounts so a
     * fragmented extraction still pairs them correctly. The negative
     * look-ahead stops one row's match from swallowing the next.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseRowsGlobal(string $text): array
    {
        $pattern = '/(1-\d{6,})((?:(?!1-\d{6,}).)*?)GBP\s*(' . substr(self::MONEY_RE, 1, -1) . ')\s*(' . substr(self::MONEY_RE, 1, -1) . ')/su';
        if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $rows = [];
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $orderNumber = $matches[1][$i][0];
            $orderOffset = $matches[1][$i][1];
            $afterText = $matches[2][$i][0]; // between order number and "GBP"
            $amounts = [$this->money($matches[3][$i][0]), $this->money($matches[4][$i][0])];

            // Text from the end of the previous row to this order number holds
            // this row's date and (for digital rows) its description prefix.
            $prevEnd = $i === 0 ? 0 : $matches[0][$i - 1][1] + strlen($matches[0][$i - 1][0]);
            $preText = substr($text, $prevEnd, $orderOffset - $prevEnd);

            // Only the last line of the lead-in, so the first row doesn't drag
            // in the page header.
            $segments = preg_split('/\r\n|\r|\n/', $preText) ?: [''];
            $lead = trim(end($segments));
            if (strlen($lead) > 60) {
                $lead = substr($lead, -60);
            }

            $date = null;
            if (preg_match_all(self::DATE_RE, $lead, $dm) && count($dm[1])) {
                $date = end($dm[1]); // nearest date before the order number
            }

            $leadNoDate = preg_replace(self::DATE_RE, '', $lead) ?? $lead;

            $rows[] = $this->makeRow($orderNumber, $amounts, $date, $leadNoDate . ' ' . $afterText);
        }

        return $rows;
    }

    /**
     * Assemble one normalised row from raw parts. Trinity prints the
     * Transaction Amount then the GBP Amount (usually identical); the GBP
     * Amount — what actually landed in the account — is the last.
     *
     * @param  array<int, float>  $amounts
     */
    private function makeRow(string $orderNumber, array $amounts, ?string $rawDate, string $descriptionSource): array
    {
        $gbpAmount = end($amounts);
        $transactionAmount = count($amounts) >= 2 ? $amounts[count($amounts) - 2] : $gbpAmount;

        // Description = source text with order number, currency and amounts
        // stripped. Captures "DGD" (digital) or "CET000447 Liverpool" (F2F).
        $description = str_replace($orderNumber, '', $descriptionSource);
        $description = preg_replace(self::MONEY_RE, '', $description) ?? $description;
        $description = preg_replace('/\bGBP\b/', '', $description) ?? $description;
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');

        return [
            'transaction_date' => $rawDate ? $this->normaliseDate($rawDate) : null,
            'order_number' => $orderNumber,
            'description' => $description,
            'transaction_amount' => $transactionAmount,
            'gbp_amount' => $gbpAmount,
        ];
    }

    /**
     * "23 June 2026" → "2026-06-23". Returns null if unparseable.
     */
    private function normaliseDate(string $raw): ?string
    {
        $raw = trim($raw);
        foreach (['j F Y', 'd F Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw)->toDateString();
            } catch (\Throwable $e) {
                // try next format
            }
        }

        return null;
    }

    /** "1,234.50" → 1234.50 */
    private function money(string $raw): float
    {
        return (float) str_replace(',', '', $raw);
    }
}
