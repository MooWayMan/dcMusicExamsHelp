<?php

// app/Services/TrinityCsvImporter.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\ImportRun;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The brain behind /admin/imports.
 *
 * Section 1 — Bulk Orders CSV (one Trinity export, many orders, filter
 * by quarter on `Requested Start Date`. Idempotent on Trinity Order #).
 *
 * Section 2 — Per-candidate triple (Enrolment + Summary + Marksheet),
 * one candidate per upload. Auto-derives booking_role, applicant_email,
 * instrument, grade, delivery method, commission rate, and score.
 *
 * All Trinity exports are UTF-16 LE with a BOM and CRLF line endings.
 */
class TrinityCsvImporter
{
    // ──────────────────────────────────────────────────────────────────
    // Static helpers — usable from tests without instantiating the class
    // ──────────────────────────────────────────────────────────────────

    /**
     * Decode a raw file_get_contents() byte string to UTF-8.
     * Trinity exports as UTF-16 LE with BOM. We also handle UTF-8
     * input transparently so unit tests can pass plain strings.
     */
    public static function decodeUtf16(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        // UTF-16 LE BOM
        if (str_starts_with($raw, "\xFF\xFE")) {
            $raw = substr($raw, 2);
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($raw, "\xFE\xFF")) {
            $raw = substr($raw, 2);
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
        } else {
            // Detect — Trinity occasionally exports without a BOM.
            $detected = mb_detect_encoding($raw, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16'], true);
            if ($detected && $detected !== 'UTF-8') {
                $raw = mb_convert_encoding($raw, 'UTF-8', $detected);
            }
        }

        // Strip a UTF-8 BOM if present.
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);

        return (string) $raw;
    }

    /**
     * Parse a Trinity Examination string into one of our allowed grades.
     *
     * Examples:
     *   "Classical and Jazz Technical Grade 2 (Digital)"  → "2"
     *   "Rock and Pop Grade Initial (Digital)"            → "Initial"
     *   "Classical and Jazz Technical Grade IN (Digital)" → "Initial"
     *   "Music Performers ATCL Diploma"                   → "ATCL"
     *
     * "Grade IN" is Trinity's abbreviation for Initial grade — confirmed
     * 16 May 2026 from a real Theo Curtis Classical and Jazz Technical
     * entry that was failing to parse. The "IN" form only counts as
     * Initial when prefixed with "Grade" (a bare "in" is too ambiguous).
     *
     * Returns null when nothing matches — caller stores the raw string
     * in `notes` so a human can sort it out.
     */
    public static function parseGrade(string $examination): ?string
    {
        $exam = trim($examination);
        if ($exam === '') {
            return null;
        }

        // Diploma codes first — they don't follow the "Grade X" pattern.
        foreach (['ATCL', 'LTCL', 'FTCL'] as $diploma) {
            if (stripos($exam, $diploma) !== false) {
                return $diploma;
            }
        }

        // "Grade Initial", "Grade IN" (Trinity abbreviation), or "Grade <number>"
        if (preg_match('/grade\s+(initial|in|[1-8])\b/i', $exam, $m)) {
            $val = strtolower($m[1]);
            return ($val === 'initial' || $val === 'in') ? 'Initial' : (string) (int) $m[1];
        }

        // Naked "Initial"
        if (preg_match('/\binitial\b/i', $exam)) {
            return 'Initial';
        }

        // Last-ditch: a bare digit 1-8 anywhere in the string.
        if (preg_match('/\b([1-8])\b/', $exam, $m)) {
            return (string) (int) $m[1];
        }

        return null;
    }

    /**
     * Map Trinity Examination string → our delivery_method tokens.
     * Mirrors the OrderController dropdown values.
     */
    public static function parseDeliveryMethod(string $examination): string
    {
        $exam = strtolower($examination);

        if (str_contains($exam, '(digital theory)')) {
            return 'DigitalTheory';
        }
        if (str_contains($exam, '(digital)')) {
            return 'Digital';
        }
        return 'Default';
    }

    /**
     * Default commission rate per delivery method. Matches the dropdown
     * defaults in OrderController::create() but we *use* lower numbers
     * (Paul's net rate after Trinity overheads):
     *   Digital       → 20%
     *   DigitalTheory → 12.5%
     *   Default (F2F) → 10%
     */
    public static function commissionRateForDelivery(string $deliveryMethod): float
    {
        return match ($deliveryMethod) {
            'Digital' => 20.00,
            'DigitalTheory' => 12.50,
            default => 10.00,
        };
    }

    /**
     * Parse a Trinity-formatted price string into a float.
     *
     *   "£61.00"   → 61.0
     *   "(£12.20)" → -12.2  (parens = negative, e.g. centre commission)
     *   "61.00"    → 61.0
     *   ""         → 0.0
     */
    public static function parsePrice(string $raw): float
    {
        $s = trim($raw);
        if ($s === '') {
            return 0.0;
        }

        $negative = false;
        if (str_starts_with($s, '(') && str_ends_with($s, ')')) {
            $negative = true;
            $s = substr($s, 1, -1);
        }

        // Strip currency, spaces, thousands separators
        $s = str_replace(['£', '$', '€', ',', ' '], '', $s);
        if ($s === '' || ! is_numeric($s)) {
            return 0.0;
        }

        $val = (float) $s;
        return $negative ? -$val : $val;
    }

    /**
     * Trinity instrument name → our seeded Instrument.name.
     *
     * Combined map: ImportQ1Digital + ImportQ1Results, plus the brass /
     * woodwind / R&P additions Paul flagged. Names not in this map are
     * kept as-is in the row's notes field with `instrument_id = null`.
     */
    public static function instrumentMap(): array
    {
        return [
            // Digital R&P shorthands
            'Drums' => 'Drums',
            'Guitar' => 'Guitar (Rock/Pop)',
            'Keyboards' => 'Electronic Keyboard',
            'Singing' => 'Singing (Classical)',
            'R&P Vocals' => 'Singing (Rock/Pop)',
            'R&P Guitar' => 'Guitar (Rock/Pop)',
            'R&P Drums' => 'Drums',
            // Trinity also exports just 'Vocals' for R&P singing entries.
            'Vocals' => 'Singing (Rock/Pop)',
            'Voice' => 'Singing (Classical)',

            // Classical names
            'Piano' => 'Piano',
            'Flute' => 'Flute',
            'Oboe' => 'Oboe',
            'Clarinet' => 'Clarinet',
            'Jazz Clarinet' => 'Clarinet',
            'Bassoon' => 'Bassoon',
            'Saxophone' => 'Saxophone',
            'Recorder' => 'Recorder',
            'Violin' => 'Violin',
            'Viola' => 'Viola',
            'Cello' => 'Cello',
            'Double Bass' => 'Double Bass',
            'Acoustic Guitar' => 'Guitar (Classical)',
            'Classical Guitar' => 'Guitar (Classical)',

            // Brass
            'Trumpet' => 'Trumpet',
            'Cornet' => 'Cornet',
            'Trombone' => 'Trombone',
            'Tenor Horn' => 'Tenor Horn',
            'French Horn' => 'French Horn',
            'Euphonium' => 'Euphonium',
            'Tuba' => 'Tuba',
            'Flugelhorn' => 'Flugelhorn',
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Section 1 — Bulk Orders
    // ──────────────────────────────────────────────────────────────────

    /**
     * Required headers for the bulk orders export.
     */
    private const ORDERS_HEADERS = [
        'Requested Start Date',
        'Delivery Method',
        'Order #',
        'Subject Area',
        'Candidates',
        'Venue',
        'Order Status',
    ];

    /**
     * Parse the bulk orders CSV into normalised rows.
     * Each row carries the original raw fields plus a `requested_start_date`
     * Carbon instance so callers can filter by quarter without re-parsing.
     *
     * @return array<int,array<string,mixed>>
     */
    public function parseOrdersCsv(string $contents): array
    {
        [$headers, $rows] = $this->extractRows($contents, self::ORDERS_HEADERS);

        $out = [];
        foreach ($rows as $i => $row) {
            $data = array_combine($headers, $row);
            $orderNumber = trim((string) ($data['Order #'] ?? ''));
            if ($orderNumber === '') {
                continue;
            }

            $rawDate = trim((string) ($data['Requested Start Date'] ?? ''));
            $date = $this->parseDate($rawDate);

            $delivery = trim((string) ($data['Delivery Method'] ?? ''));
            // Trinity uses "Digital" / "Default" already — pass through.
            // Normalise capitalisation since some exports use lower case.
            if (strcasecmp($delivery, 'digital') === 0) {
                $delivery = 'Digital';
            } elseif (strcasecmp($delivery, 'default') === 0) {
                $delivery = 'Default';
            } elseif (strcasecmp($delivery, 'digital theory') === 0) {
                $delivery = 'DigitalTheory';
            }

            $out[] = [
                'order_number' => $orderNumber,
                'requested_start_date' => $date,
                'delivery_method' => $delivery,
                'subject_area' => trim((string) ($data['Subject Area'] ?? '')),
                'candidates' => (int) trim((string) ($data['Candidates'] ?? 0)),
                'venue' => trim((string) ($data['Venue'] ?? '')) ?: null,
                'order_status' => trim((string) ($data['Order Status'] ?? '')) ?: null,
                'commission_rate' => self::commissionRateForDelivery($delivery),
            ];
        }

        return $out;
    }

    /**
     * Build a preview of what the bulk-orders CSV would do, scoped to
     * the (year, quarter) Paul picked. Does NOT write to the DB.
     */
    public function previewOrders(string $contents, int $year, int $quarter): array
    {
        $rows = $this->parseOrdersCsv($contents);

        [$startDate, $endDate] = $this->quarterRange($year, $quarter);

        $inQuarter = [];
        $filteredOut = 0;
        foreach ($rows as $row) {
            $date = $row['requested_start_date'];
            if (! $date || ! $date->between($startDate, $endDate)) {
                $filteredOut++;
                continue;
            }
            $inQuarter[] = $row;
        }

        $existingNumbers = Order::withTrashed()
            ->whereIn('trinity_order_number', array_column($inQuarter, 'order_number'))
            ->pluck('trinity_order_number')
            ->all();

        $toCreate = [];
        $toUpdate = [];
        foreach ($inQuarter as $row) {
            if (in_array($row['order_number'], $existingNumbers, true)) {
                $toUpdate[] = $row;
            } else {
                $toCreate[] = $row;
            }
        }

        return [
            'year' => $year,
            'quarter' => $quarter,
            'toCreate' => $toCreate,
            'toUpdate' => $toUpdate,
            'filteredOut' => $filteredOut,
            'rows' => $inQuarter,
            'totals' => [
                'rows_in_csv' => count($rows),
                'in_quarter' => count($inQuarter),
                'filtered_out' => $filteredOut,
                'to_create' => count($toCreate),
                'to_update' => count($toUpdate),
            ],
        ];
    }

    /**
     * Persist the bulk-orders CSV. Idempotent on Order #.
     * Returns the resulting ImportRun row.
     */
    public function commitOrders(string $contents, int $year, int $quarter, ?int $userId, ?string $filename = null): ImportRun
    {
        $preview = $this->previewOrders($contents, $year, $quarter);

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($preview, &$created, &$updated) {
            foreach ($preview['rows'] as $row) {
                $existing = Order::withTrashed()
                    ->where('trinity_order_number', $row['order_number'])
                    ->first();

                $payload = [
                    'delivery_method' => $row['delivery_method'],
                    'subject_area' => $row['subject_area'] ?: null,
                    'candidates' => $row['candidates'],
                    'venue' => $row['venue'],
                    'order_status' => $row['order_status'],
                    'requested_start_date' => $row['requested_start_date']?->toDateString(),
                    'commission_rate' => $row['commission_rate'],
                ];

                if ($existing) {
                    $existing->fill($payload);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $updated++;
                    }
                } else {
                    Order::create(array_merge($payload, [
                        'trinity_order_number' => $row['order_number'],
                    ]));
                    $created++;
                }
            }
        });

        return ImportRun::create([
            'user_id' => $userId,
            'type' => 'bulk_orders',
            'filename' => $filename,
            'summary' => [
                'year' => $year,
                'quarter' => $quarter,
                'rows_in_csv' => $preview['totals']['rows_in_csv'],
                'in_quarter' => $preview['totals']['in_quarter'],
                'filtered_out' => $preview['totals']['filtered_out'],
                'created' => $created,
                'updated' => $updated,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Section 2 — Per-candidate triple
    // ──────────────────────────────────────────────────────────────────

    private const ENROLMENT_HEADERS = [
        'Examination', 'Subject', 'Candidate Number', 'Candidate Name',
        'Enrolment Date', 'Price', 'Submitter Last Name', 'Submitter First Name',
        'Submitter Email Address', 'Applicant Id', 'Applicant Last Name',
        'Applicant First Name',
    ];

    private const SUMMARY_HEADERS = [
        'Subject Area', 'Syllabus', 'Examination Date', 'Examination',
        'Candidate Number', 'Candidate', 'School', 'Teacher First Name',
        'Teacher Last Name', 'Status', 'Result', 'Digital Certificate ID',
        'Order Number', 'Examiner',
    ];

    private const MARKSHEET_HEADERS = ['Section #', 'Mark', 'Section', 'Max'];

    /**
     * Parse the single-candidate Enrolment CSV. Skips the
     * "Centre Commission - …" row that follows the candidate row
     * (it has an empty Candidate Number).
     */
    public function parseEnrolment(string $contents): array
    {
        [$headers, $rows] = $this->extractRows($contents, self::ENROLMENT_HEADERS);

        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            $candNumber = trim((string) ($data['Candidate Number'] ?? ''));
            if ($candNumber === '') {
                // The Centre Commission row — skip it.
                continue;
            }

            $applicantFirst = trim((string) ($data['Applicant First Name'] ?? ''));
            $applicantLast = trim((string) ($data['Applicant Last Name'] ?? ''));
            $submitterFirst = trim((string) ($data['Submitter First Name'] ?? ''));
            $submitterLast = trim((string) ($data['Submitter Last Name'] ?? ''));

            return [
                'examination' => trim((string) ($data['Examination'] ?? '')),
                'subject' => trim((string) ($data['Subject'] ?? '')),
                'candidate_number' => $candNumber,
                'candidate_name' => trim((string) ($data['Candidate Name'] ?? '')),
                'enrolment_date' => $this->parseDate(trim((string) ($data['Enrolment Date'] ?? ''))),
                'price' => self::parsePrice((string) ($data['Price'] ?? '')),
                'submitter_first' => $submitterFirst,
                'submitter_last' => $submitterLast,
                'submitter_name' => trim($submitterFirst . ' ' . $submitterLast),
                'submitter_email' => trim((string) ($data['Submitter Email Address'] ?? '')),
                'applicant_id' => trim((string) ($data['Applicant Id'] ?? '')),
                'applicant_first' => $applicantFirst,
                'applicant_last' => $applicantLast,
                'applicant_name' => trim($applicantFirst . ' ' . $applicantLast),
            ];
        }

        throw new RuntimeException('Enrolment CSV had no candidate row.');
    }

    /**
     * Parse the single-candidate Summary CSV. Returns the first data row.
     */
    public function parseSummary(string $contents): array
    {
        [$headers, $rows] = $this->extractRows($contents, self::SUMMARY_HEADERS);

        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            $candNumber = trim((string) ($data['Candidate Number'] ?? ''));
            if ($candNumber === '') {
                continue;
            }

            $teacherFirst = trim((string) ($data['Teacher First Name'] ?? ''));
            $teacherLast = trim((string) ($data['Teacher Last Name'] ?? ''));

            return [
                'subject_area' => trim((string) ($data['Subject Area'] ?? '')),
                'syllabus' => trim((string) ($data['Syllabus'] ?? '')),
                'examination_date' => $this->parseDate(trim((string) ($data['Examination Date'] ?? ''))),
                'examination' => trim((string) ($data['Examination'] ?? '')),
                'candidate_number' => $candNumber,
                'candidate' => trim((string) ($data['Candidate'] ?? '')),
                'school' => trim((string) ($data['School'] ?? '')) ?: null,
                'teacher_first' => $teacherFirst,
                'teacher_last' => $teacherLast,
                'teacher_name' => trim($teacherFirst . ' ' . $teacherLast),
                'status' => trim((string) ($data['Status'] ?? '')),
                'result' => trim((string) ($data['Result'] ?? '')) ?: null,
                'digital_certificate_id' => trim((string) ($data['Digital Certificate ID'] ?? '')) ?: null,
                'order_number' => trim((string) ($data['Order Number'] ?? '')),
                'examiner' => trim((string) ($data['Examiner'] ?? '')) ?: null,
            ];
        }

        throw new RuntimeException('Summary CSV had no data row.');
    }

    /**
     * Parse the Marksheet CSV and return the integer score (sum of Mark column).
     * Each Mark is cast to int — Trinity sometimes outputs blanks for unscored
     * sections, those become 0.
     */
    public function parseMarksheet(string $contents): int
    {
        [$headers, $rows] = $this->extractRows($contents, self::MARKSHEET_HEADERS);

        $sum = 0;
        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            $mark = trim((string) ($data['Mark'] ?? ''));
            if ($mark === '') {
                continue;
            }
            $sum += (int) $mark;
        }
        return $sum;
    }

    // ──────────────────────────────────────────────────────────────────
    // Section 3 — Enrolment list (pre-results, before the triple)
    //
    // Trinity's "Generate Summary of Entries" export lists every candidate
    // on an order (names, subject, grade, the booking submitter), but NOT
    // the order number (that lives in the page header) and NOT results.
    // This lets Paul load the list early — so the candidates and the
    // submitter show against the order before exam day — and the later
    // per-candidate triple fills in the scores on the SAME entries (matched
    // by order_id + candidate_number), no duplicates.
    //
    // Deliberately does NOT tag the submitter as a teacher: the submitter is
    // often a parent, and minting parents as teachers pollutes the prize
    // draw (the bug fixed 13 Jun 2026). Role is still confirmed at triple
    // time. We only link the submitter as the order's contact so the name
    // is visible on /admin/orders.
    // ──────────────────────────────────────────────────────────────────

    /**
     * Parse the multi-candidate Enrolment list CSV (same columns as the
     * single-candidate Enrolment file, many rows). Skips the
     * "Centre Commission - …" rows (empty Candidate Number).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseEnrolmentList(string $contents): array
    {
        [$headers, $rows] = $this->extractRows($contents, self::ENROLMENT_HEADERS);

        $candidates = [];
        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            $candNumber = trim((string) ($data['Candidate Number'] ?? ''));
            if ($candNumber === '') {
                continue; // Centre Commission row
            }

            $submitterFirst = trim((string) ($data['Submitter First Name'] ?? ''));
            $submitterLast = trim((string) ($data['Submitter Last Name'] ?? ''));
            $applicantFirst = trim((string) ($data['Applicant First Name'] ?? ''));
            $applicantLast = trim((string) ($data['Applicant Last Name'] ?? ''));

            $candidates[] = [
                'examination' => trim((string) ($data['Examination'] ?? '')),
                'subject' => trim((string) ($data['Subject'] ?? '')),
                'candidate_number' => $candNumber,
                'candidate_name' => trim((string) ($data['Candidate Name'] ?? '')),
                'price' => self::parsePrice((string) ($data['Price'] ?? '')),
                'submitter_name' => trim($submitterFirst . ' ' . $submitterLast),
                'submitter_email' => trim((string) ($data['Submitter Email Address'] ?? '')),
                'applicant_name' => trim($applicantFirst . ' ' . $applicantLast),
            ];
        }

        if ($candidates === []) {
            throw new RuntimeException('Enrolment list had no candidate rows.');
        }

        return $candidates;
    }

    /**
     * Shape one enrolment-list candidate into the derived fields we'd store.
     */
    private function shapeEnrolmentCandidate(array $c): array
    {
        $instrumentMap = array_change_key_case(self::instrumentMap(), CASE_LOWER);
        $mappedName = $instrumentMap[strtolower(trim($c['subject']))] ?? null;
        $instrument = $mappedName ? Instrument::where('name', $mappedName)->first() : null;

        return [
            'candidate_number' => $c['candidate_number'],
            'candidate_name' => $c['candidate_name'],
            'grade' => self::parseGrade($c['examination']),
            'delivery_method' => self::parseDeliveryMethod($c['examination']),
            'subject_area' => self::subjectAreaFromExamination($c['examination']),
            'instrument' => $instrument ? ['id' => $instrument->id, 'name' => $instrument->name] : null,
            'instrument_raw' => $c['subject'],
            'fee' => abs($c['price']),
        ];
    }

    /**
     * Subject area = the Examination string with the grade and the
     * "(Digital)" / "(Default)" suffix stripped, e.g.
     * "Rock and Pop Grade 1 (Digital)" → "Rock and Pop".
     */
    private static function subjectAreaFromExamination(string $exam): ?string
    {
        $area = preg_replace('/\s*(Grade\s.*|Initial.*|\(.*\)).*$/i', '', $exam) ?? $exam;
        $area = trim($area);

        return $area !== '' ? $area : null;
    }

    /**
     * JSON preview of an enrolment-list import: match the order by the
     * pasted number, and split candidates into to-create vs already-present.
     */
    public function previewEnrolmentList(string $contents, string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        $candidates = $this->parseEnrolmentList($contents);

        $order = Order::where('trinity_order_number', $orderNumber)->first();

        $existingNumbers = $order
            ? ExamEntry::where('order_id', $order->id)->pluck('candidate_number')->all()
            : [];

        $warnings = [];
        if (! $order) {
            $warnings[] = "Order {$orderNumber} not found — import it on Section 1 (Bulk Orders) first.";
        }

        $submitter = [
            'name' => $candidates[0]['submitter_name'] ?? '',
            'email' => $candidates[0]['submitter_email'] ?? '',
        ];

        $toCreate = [];
        $toUpdate = [];
        $totalFees = 0.0;
        foreach ($candidates as $c) {
            $shaped = $this->shapeEnrolmentCandidate($c);
            $totalFees += $shaped['fee'];
            if (in_array($c['candidate_number'], $existingNumbers, true)) {
                $toUpdate[] = $shaped;
            } else {
                $toCreate[] = $shaped;
            }
            if (! $shaped['instrument']) {
                $warnings[] = "Instrument '{$c['subject']}' (candidate {$c['candidate_name']}) not in our map — stored in notes.";
            }
        }

        $rate = ($order && $order->commission_rate !== null) ? (float) $order->commission_rate : 20.0;
        $commissionEstimate = round($totalFees * $rate / 100, 2);

        return [
            'order' => $order ? [
                'id' => $order->id,
                'trinity_order_number' => $order->trinity_order_number,
                'candidates' => $order->candidates,
            ] : null,
            'submitter' => $submitter,
            'totals' => [
                'rows' => count($candidates),
                'to_create' => count($toCreate),
                'to_update' => count($toUpdate),
                'total_fees' => round($totalFees, 2),
                'commission_estimate' => $commissionEstimate,
            ],
            'toCreate' => $toCreate,
            'toUpdate' => $toUpdate,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Commit an enrolment-list import. Creates the candidate entries with
     * results blank (filled later by the triple), links the submitter as the
     * order's contact for visibility, and never overwrites results or a
     * confirmed teacher on an entry the triple has already populated.
     */
    public function commitEnrolmentList(string $contents, string $orderNumber, ?int $userId, ?string $filename = null): ImportRun
    {
        $orderNumber = trim($orderNumber);
        $candidates = $this->parseEnrolmentList($contents);

        $order = Order::where('trinity_order_number', $orderNumber)->first();
        if (! $order) {
            throw new InvalidArgumentException("Order {$orderNumber} not found — import it on Section 1 (Bulk Orders) first.");
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($candidates, $order, &$created, &$updated) {
            // Submitter contact — find by email or create. NO type tagging
            // (don't mint a teacher; the submitter may be a parent).
            $submitterEmail = trim((string) ($candidates[0]['submitter_email'] ?? ''));
            $submitterName = trim((string) ($candidates[0]['submitter_name'] ?? ''));
            $submitterContact = null;
            if ($submitterEmail !== '') {
                $submitterContact = ExamContact::findByEmail($submitterEmail);
                if (! $submitterContact) {
                    $submitterContact = ExamContact::create([
                        'name' => $submitterName ?: $submitterEmail,
                        'email' => $submitterEmail,
                        'source' => 'trinity_csv_import',
                    ]);
                }
            }

            // Link the order to the submitter for visibility — only if not
            // already linked (never overwrite a human/triple-set link).
            if ($submitterContact && empty($order->created_by_contact_id)) {
                $order->update(['created_by_contact_id' => $submitterContact->id]);
            }

            $totalFees = 0.0;
            foreach ($candidates as $c) {
                $shaped = $this->shapeEnrolmentCandidate($c);
                $totalFees += $shaped['fee'];

                $notes = $shaped['instrument'] ? null : ('Instrument (raw Trinity name): ' . $shaped['instrument_raw']);

                $existing = ExamEntry::where('order_id', $order->id)
                    ->where('candidate_number', $c['candidate_number'])
                    ->first();

                if ($existing) {
                    // Backfill only empty descriptive fields — never touch a
                    // score/result/exam_date/teacher the triple has set.
                    $fill = [];
                    if (empty($existing->candidate_name)) $fill['candidate_name'] = $c['candidate_name'];
                    if (empty($existing->instrument_id) && $shaped['instrument']) $fill['instrument_id'] = $shaped['instrument']['id'];
                    if (empty($existing->grade)) $fill['grade'] = $shaped['grade'];
                    if (empty($existing->subject_area)) $fill['subject_area'] = $shaped['subject_area'];
                    if (empty($existing->delivery_method)) $fill['delivery_method'] = $shaped['delivery_method'];
                    if (empty($existing->fee)) $fill['fee'] = $shaped['fee'];
                    if (empty($existing->submitter_contact_id) && $submitterContact) $fill['submitter_contact_id'] = $submitterContact->id;
                    if ($fill !== []) {
                        $existing->fill($fill)->save();
                        $updated++;
                    }
                } else {
                    ExamEntry::create([
                        'order_id' => $order->id,
                        'candidate_number' => $c['candidate_number'],
                        'candidate_name' => $c['candidate_name'],
                        'instrument_id' => $shaped['instrument']['id'] ?? null,
                        'grade' => $shaped['grade'],
                        'subject_area' => $shaped['subject_area'],
                        'delivery_method' => $shaped['delivery_method'],
                        'fee' => $shaped['fee'],
                        'score' => null,
                        'result' => null,
                        'exam_date' => null,
                        'teacher_name' => null,
                        'teacher_contact_id' => null,
                        'booking_role' => null,
                        'submitter_contact_id' => $submitterContact?->id,
                        'notes' => $notes,
                        'source' => 'trinity_enrolment_list',
                    ]);
                    $created++;
                }
            }

            // The enrolment file carries the per-candidate fees, so we can set
            // the order's commission now (summed fees × rate) and stop it
            // reading "Awaiting £0" pre-results. Only when not already set, so
            // a real remittance figure is never overwritten.
            if (empty((float) $order->commission_amount) && $totalFees > 0) {
                $rate = $order->commission_rate !== null ? (float) $order->commission_rate : 20.0;
                $order->update(['commission_amount' => round($totalFees * $rate / 100, 2)]);
            }
        });

        return ImportRun::create([
            'user_id' => $userId,
            'type' => 'enrolment_list',
            'filename' => $filename,
            'summary' => [
                'order_number' => $orderNumber,
                'rows' => count($candidates),
                'created' => $created,
                'updated' => $updated,
                'submitter' => $candidates[0]['submitter_name'] ?? null,
            ],
        ]);
    }

    /**
     * Build a preview for a per-candidate import — runs all the auto-
     * derivation rules and reports what would be written without writing.
     */
    public function previewCandidate(array $enrol, array $summary, int $score, ?string $dob, ?string $applicantEmail): array
    {
        $warnings = [];

        // Cross-check: same Candidate Number across Enrolment + Summary.
        if ($enrol['candidate_number'] !== $summary['candidate_number']) {
            $warnings[] = "Candidate Number mismatch — Enrolment {$enrol['candidate_number']} vs Summary {$summary['candidate_number']}.";
        }

        // Match Order by Summary.Order Number → Order.trinity_order_number.
        $order = Order::where('trinity_order_number', $summary['order_number'])->first();
        if (! $order) {
            $warnings[] = "Order {$summary['order_number']} not found — run Section 1 first.";
        }

        $delivery = self::parseDeliveryMethod($enrol['examination']);
        $grade = self::parseGrade($enrol['examination']);
        if ($grade === null) {
            $warnings[] = "Could not parse grade from Examination: '{$enrol['examination']}'.";
        }

        // Case-insensitive lookup so 'Vocals' / 'vocals' / 'VOCALS' all map.
        $instrumentMap = self::instrumentMap();
        $caseInsensitive = array_change_key_case($instrumentMap, CASE_LOWER);
        $mappedInstrumentName = $caseInsensitive[strtolower(trim($enrol['subject']))] ?? null;
        $instrument = $mappedInstrumentName
            ? Instrument::where('name', $mappedInstrumentName)->first()
            : null;
        if (! $instrument) {
            $warnings[] = "Instrument '{$enrol['subject']}' not in our map — will be stored in notes.";
        }

        $derivedEmail = $this->deriveApplicantEmail($enrol, $applicantEmail);
        $derivedRole = $this->deriveBookingRole($enrol, $summary, $derivedEmail);
        $roleSuggestion = $this->suggestRole($enrol, $summary, $derivedEmail);

        // Email is required when names differ and the user didn't supply one.
        $namesMatch = $this->namesMatch($enrol['submitter_name'], $enrol['applicant_name']);
        if (! $namesMatch && empty($applicantEmail)) {
            $warnings[] = 'Applicant Email is required because Submitter and Applicant names differ.';
        }

        $fee = abs($enrol['price']);

        return [
            'warnings' => $warnings,
            'candidate' => [
                'candidate_number' => $enrol['candidate_number'],
                'candidate_name' => $enrol['candidate_name'],
                'applicant_name' => $enrol['applicant_name'],
                'applicant_email' => $derivedEmail,
                'submitter_name' => $enrol['submitter_name'],
                'submitter_email' => $enrol['submitter_email'],
                'date_of_birth' => $dob,
            ],
            'order' => $order ? [
                'id' => $order->id,
                'trinity_order_number' => $order->trinity_order_number,
                'applicant_name' => $order->applicant_name,
                'applicant_email' => $order->applicant_email,
            ] : null,
            'derivedRole' => $derivedRole,
            'roleSuggestion' => $roleSuggestion,
            'derivedEmail' => $derivedEmail,
            'fee' => $fee,
            'instrument' => $instrument ? [
                'id' => $instrument->id,
                'name' => $instrument->name,
            ] : null,
            'grade' => $grade,
            'delivery_method' => $delivery,
            'score' => $score,
            'result' => $summary['result'],
            'exam_date' => $summary['examination_date']?->toDateString(),
            'teacher_name' => $this->resolveTeacherName($summary, $enrol, $derivedRole),
            'school_name' => $summary['school'],
            'subject_area' => $summary['subject_area'] ?: null,
            'digital_certificate_id' => $summary['digital_certificate_id'],
        ];
    }

    /**
     * Persist the per-candidate import (all three CSVs, plus DOB and
     * an Applicant Email override when names differ). Idempotent on
     * candidate_number within the matched order.
     */
    public function commitCandidate(array $enrol, array $summary, int $score, ?string $dob, ?string $applicantEmail, ?int $userId, ?string $filename = null, ?array $roleOverride = null): ImportRun
    {
        $preview = $this->previewCandidate($enrol, $summary, $score, $dob, $applicantEmail);

        // The human-confirmed role from the import page wins over the
        // heuristic. When absent (legacy callers / tests), fall back to the
        // derived suggestion so existing behaviour is unchanged.
        $role = $roleOverride['role'] ?? $preview['derivedRole'];

        // Hard-stops: missing order or candidate-number mismatch.
        if ($enrol['candidate_number'] !== $summary['candidate_number']) {
            throw new InvalidArgumentException(
                "Candidate Number mismatch — Enrolment {$enrol['candidate_number']} vs Summary {$summary['candidate_number']}."
            );
        }
        $order = Order::where('trinity_order_number', $summary['order_number'])->first();
        if (! $order) {
            throw new InvalidArgumentException(
                "Order {$summary['order_number']} not found — run Section 1 first."
            );
        }

        $namesMatch = $this->namesMatch($enrol['submitter_name'], $enrol['applicant_name']);
        if (! $namesMatch && empty($applicantEmail)) {
            throw new InvalidArgumentException('Applicant Email is required because Submitter and Applicant names differ.');
        }

        $createdEntry = false;
        $updatedEntry = false;
        $createdContact = false;

        DB::transaction(function () use (
            $enrol, $summary, $score, $dob, $applicantEmail, $preview, $order,
            $role, $roleOverride,
            &$createdEntry, &$updatedEntry, &$createdContact
        ) {
            // Submitter contact — lookup by email, create if missing.
            //
            // NOTE: we no longer tag the submitter teacher/parent off a
            // guessed role here. That guess (rule-4 shape default) was
            // minting parents as teachers and polluting the prize draw
            // (Mark Vincent-Smith, Helen Khoo, … — 13 Jun 2026). Typing now
            // happens against the ACTUAL resolved teacher/parent below, from
            // the human-confirmed role.
            $submitterContact = null;
            if ($enrol['submitter_email'] !== '') {
                $submitterContact = ExamContact::findByEmail($enrol['submitter_email']);
                if (! $submitterContact) {
                    $submitterContact = ExamContact::create([
                        'name' => $enrol['submitter_name'] ?: $enrol['submitter_email'],
                        'email' => $enrol['submitter_email'],
                        'source' => 'trinity_csv_import',
                    ]);
                    $createdContact = true;
                }
            }

            // Applicant contact — when role is parent and the applicant differs from the submitter,
            // ensure we have a contact for them too.
            $applicantContact = null;
            if ($role === 'parent' && $enrol['applicant_name'] !== $enrol['submitter_name']) {
                $email = $preview['derivedEmail'] ?: $applicantEmail;
                $applicantContact = $email
                    ? ExamContact::findByEmail($email)
                    : ExamContact::whereRaw('LOWER(name) = ?', [strtolower($enrol['applicant_name'])])->first();
                if (! $applicantContact) {
                    $applicantContact = ExamContact::create([
                        'name' => $enrol['applicant_name'],
                        'email' => $email,
                        'source' => 'trinity_csv_import',
                    ]);
                    $applicantContact->addType('parent');
                    $createdContact = true;
                }
            }

            // Backfill order's applicant_name / applicant_email if not set,
            // AND link the order to the applicant contact via
            // `created_by_contact_id` so the applicant name renders as a
            // clickable link on /admin/orders. When the applicant IS the
            // submitter (same person — the typical case for teacher /
            // self-applicant), the submitter contact IS the applicant
            // contact. When they differ (parent submitted by a teacher,
            // etc.), use the `$applicantContact` resolved just above.
            // Only set the link if it's not already in place — never
            // overwrite a manual link a human may have set in TablePlus.
            //
            // Bug fix (16 May 2026): the legacy importers set
            // `created_by_contact_id`; the new TrinityCsvImporter (built
            // 8 May 2026) was missing this step, leaving every post-8-May
            // import with a null link and a plain-text-only applicant
            // name on the orders list. Backfill SQL covers existing rows.
            $orderUpdates = [];
            if (empty($order->applicant_name) && $enrol['applicant_name'] !== '') {
                $orderUpdates['applicant_name'] = $enrol['applicant_name'];
            }
            if (empty($order->applicant_email) && $preview['derivedEmail']) {
                $orderUpdates['applicant_email'] = $preview['derivedEmail'];
            }
            if (empty($order->created_by_contact_id)) {
                $contactForOrder = ($enrol['applicant_name'] === $enrol['submitter_name'])
                    ? $submitterContact
                    : $applicantContact;
                if ($contactForOrder) {
                    $orderUpdates['created_by_contact_id'] = $contactForOrder->id;
                }
            }
            if (! empty($orderUpdates)) {
                $order->update($orderUpdates);
            }

            // Build notes — concat Digital Certificate ID if we have one.
            $notes = null;
            $noteParts = [];
            if (! empty($summary['digital_certificate_id'])) {
                $noteParts[] = 'Digital Certificate ID: ' . $summary['digital_certificate_id'];
            }
            // If the instrument couldn't be mapped, store the raw Trinity name in notes
            // so a human can reconcile later.
            if (! $preview['instrument'] && $enrol['subject'] !== '') {
                $noteParts[] = 'Instrument (raw Trinity name): ' . $enrol['subject'];
            }
            if (! empty($noteParts)) {
                $notes = implode(' | ', $noteParts);
            }

            // Teacher FK + type tagging, driven by the confirmed role.
            //
            //   teacher / school_admin → resolve a teacher contact and tag it
            //     with the matching type. Resolution order:
            //       1. explicit teacher_contact_id from the import page,
            //       2. explicit teacher name (+ email) → find-or-create
            //          (email is the precise key, so an existing teacher like
            //          Clare Keeling is reused, never duplicated),
            //       3. fall back to the legacy heuristic (Summary teacher /
            //          submitter) for callers that pass no override.
            //   parent → tag the parent (applicant, else submitter). No
            //     teacher FK — Paul attributes the teacher later.
            //   self → no teacher, no tags.
            // school_name defaults to whatever Trinity gave us; a School-admin
            // role can override it with the confirmed school below.
            $schoolName = $summary['school'];
            $school = null;
            $teacherContact = null;
            if (in_array($role, ['teacher', 'school_admin'], true)) {
                $explicitId = $roleOverride['teacher_contact_id'] ?? null;
                $explicitName = trim((string) ($roleOverride['teacher_name'] ?? ''));
                $explicitEmail = trim((string) ($roleOverride['teacher_email'] ?? ''));

                if ($explicitId) {
                    $teacherContact = ExamContact::find($explicitId);
                } elseif ($explicitName !== '') {
                    if ($explicitEmail !== '') {
                        $teacherContact = ExamContact::findByEmail($explicitEmail);
                    }
                    if (! $teacherContact) {
                        $teacherContact = ExamContact::whereRaw('LOWER(name) = ?', [strtolower($explicitName)])->first();
                    }
                    if (! $teacherContact) {
                        $teacherContact = ExamContact::create([
                            'name' => $explicitName,
                            'email' => $explicitEmail ?: null,
                            'source' => 'trinity_csv_import',
                        ]);
                        $createdContact = true;
                    }
                } else {
                    // Legacy / no-override path — Maria-shape + Trinity-named.
                    $resolvedId = $this->resolveTeacherContactId($summary, 'teacher', $submitterContact);
                    $teacherContact = $resolvedId ? ExamContact::find($resolvedId) : null;
                }

                if ($teacherContact) {
                    $type = $role === 'school_admin' ? 'school_admin' : 'teacher';
                    if (! $teacherContact->hasType($type)) {
                        $teacherContact->addType($type);
                    }
                }

                // School-admin → resolve the school this entry rolls up to
                // (pick existing by id, else find-or-create by name) and link
                // the admin contact to it via contact_school. The Phase-2 draw
                // credits the SCHOOL for school_admin entries, read off this
                // link — so Emily Bates' Learn Music entries roll up to Learn
                // Music while her private-teacher entries stay personal.
                if ($role === 'school_admin') {
                    $school = $this->resolveSchool($roleOverride);
                    if ($school) {
                        $schoolName = $school->name;
                        $teacherContact?->schools()->syncWithoutDetaching([$school->id]);
                    }
                }

                // Persist this entry's instrument on the teacher/school-admin
                // contact (and the school) so the instrument profile survives
                // deletion of the entry it came from.
                $instrumentId = $preview['instrument']['id'] ?? null;
                if ($instrumentId) {
                    $teacherContact?->instruments()->syncWithoutDetaching([$instrumentId]);
                    $school?->instruments()->syncWithoutDetaching([$instrumentId]);
                }
            } elseif ($role === 'parent') {
                $parentContact = $applicantContact ?? $submitterContact;
                if ($parentContact && ! $parentContact->hasType('parent')) {
                    $parentContact->addType('parent');
                }
            }

            $teacherContactId = $teacherContact?->id;
            // teacher_name is a denormalised cache for search. Prefer the
            // resolved contact's name; for a teacher/school-admin role with no
            // contact resolved, fall back to the Summary/applicant string
            // (old behaviour). Parent/self carry no teacher_name.
            $teacherName = $teacherContact?->name
                ?: (in_array($role, ['teacher', 'school_admin'], true)
                    ? $this->resolveTeacherName($summary, $enrol, 'teacher')
                    : null);

            // Note: exam_entries has no `applicant_name` column — that lives
            // on `orders` only (the per-entry applicant lives on the linked
            // order). The ExamEntry payload deliberately omits it.
            $payload = [
                'order_id' => $order->id,
                'candidate_number' => $enrol['candidate_number'],
                'candidate_name' => $enrol['candidate_name'],
                'instrument_id' => $preview['instrument']['id'] ?? null,
                'grade' => $preview['grade'],
                'subject_area' => $summary['subject_area'] ?: null,
                'delivery_method' => $preview['delivery_method'],
                'fee' => abs($enrol['price']),
                'score' => $score > 0 ? $score : null,
                'result' => $summary['result'],
                'exam_date' => $summary['examination_date']?->toDateString(),
                'date_of_birth' => $dob ?: null,
                'teacher_name' => $teacherName,
                'teacher_contact_id' => $teacherContactId,
                'school_name' => $schoolName,
                'booking_role' => $role,
                'applicant_email' => $preview['derivedEmail'],
                'submitter_contact_id' => $submitterContact?->id,
                'notes' => $notes,
                'source' => 'trinity_csv_import',
            ];

            $existing = ExamEntry::where('order_id', $order->id)
                ->where('candidate_number', $enrol['candidate_number'])
                ->first();

            if ($existing) {
                $existing->fill($payload);
                if ($existing->isDirty()) {
                    $existing->save();
                    $updatedEntry = true;
                }
                $entryModel = $existing;
            } else {
                $entryModel = ExamEntry::create($payload);
                $createdEntry = true;
            }

            // Create/link the Student for this candidate inline, so the
            // candidate is visible on /admin/students immediately. Imports
            // used to leave exam_entries.student_id null until
            // `data:populate-from-entries` was run by hand — that gap is why
            // a candidate (Isaac Ellison, 13 Jun 2026) had a certificate but
            // no student row.
            $this->linkStudentForEntry($entryModel, $teacherContactId);
        });

        return ImportRun::create([
            'user_id' => $userId,
            'type' => 'candidate_triple',
            'filename' => $filename,
            'summary' => [
                'order_number' => $summary['order_number'],
                'candidate_number' => $enrol['candidate_number'],
                'candidate_name' => $enrol['candidate_name'],
                'created_entry' => $createdEntry,
                'updated_entry' => $updatedEntry,
                'created_contact' => $createdContact,
                'derived_role' => $role,
                'derived_email' => $preview['derivedEmail'],
                'score' => $score,
            ],
        ]);
    }

    /**
     * Resolve the School for a School-admin import: an explicit school_id
     * wins, else find-or-create by trimmed name (case-insensitive, so we
     * reuse "Learn Music Ltd" rather than duplicate it). Returns null when
     * no school was supplied.
     */
    private function resolveSchool(?array $roleOverride): ?School
    {
        $id = $roleOverride['school_id'] ?? null;
        if ($id) {
            $school = School::find($id);
            if ($school) {
                return $school;
            }
        }

        $name = trim((string) ($roleOverride['school_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return School::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
            ?? School::create(['name' => $name]);
    }

    /**
     * Find or create the Student for a committed exam entry and link it via
     * exam_entries.student_id. Matches on lowercased "first last" exactly as
     * `data:populate-from-entries` does, so the two stay consistent and a
     * re-import can't create a second Student. Sets the student's teacher
     * contact when we have one and the student doesn't already.
     */
    private function linkStudentForEntry(ExamEntry $entry, ?int $teacherContactId): void
    {
        $name = trim((string) $entry->candidate_name);
        if ($name === '') {
            return;
        }

        $parts = preg_split('/\s+/', $name);
        $firstName = $parts[0];
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        $student = Student::whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($name)])->first();

        if (! $student) {
            $student = Student::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'teacher_contact_id' => $teacherContactId,
            ]);
        } elseif ($teacherContactId && ! $student->teacher_contact_id) {
            $student->update(['teacher_contact_id' => $teacherContactId]);
        }

        if ($entry->student_id !== $student->id) {
            $entry->update(['student_id' => $student->id]);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Decode + split a Trinity CSV into [headers, rows].
     * Verifies the required headers are present (order-insensitive).
     *
     * @param array<int,string> $required
     * @return array{0: array<int,string>, 1: array<int,array<int,string>>}
     */
    private function extractRows(string $rawOrUtf8, array $required): array
    {
        $contents = self::decodeUtf16($rawOrUtf8);
        $contents = preg_replace('/\r\n|\r/', "\n", $contents);

        $lines = preg_split('/\n/', (string) $contents);
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));

        if (count($lines) < 1) {
            throw new RuntimeException('CSV is empty.');
        }

        $headerLine = array_shift($lines);
        $delimiter = str_contains($headerLine, "\t") ? "\t" : ',';

        $headers = str_getcsv((string) $headerLine, $delimiter, '"', '\\');
        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        // Normalise the BOM if it slipped through on the first column.
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $missing = array_diff($required, $headers);
        if (! empty($missing)) {
            throw new RuntimeException('CSV missing required columns: ' . implode(', ', $missing));
        }

        $rows = [];
        foreach ($lines as $line) {
            $row = str_getcsv((string) $line, $delimiter, '"', '\\');
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }
            $rows[] = $row;
        }

        return [$headers, $rows];
    }

    private function parseDate(string $raw): ?Carbon
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y',
            'Y-m-d',
            'd-m-Y',
            'j M Y',
            'j F Y',
        ];

        foreach ($formats as $format) {
            try {
                $d = Carbon::createFromFormat($format, $raw);
                if ($d !== false) {
                    return $d->startOfDay();
                }
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function quarterRange(int $year, int $quarter): array
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException("Invalid quarter: $quarter");
        }
        $startMonth = (($quarter - 1) * 3) + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();
        return [$start, $end];
    }

    /**
     * Case-insensitive whitespace-collapsed name comparison.
     */
    private function namesMatch(string $a, string $b): bool
    {
        $norm = fn (string $s) => strtolower(trim(preg_replace('/\s+/', ' ', $s)));
        return $norm($a) !== '' && $norm($a) === $norm($b);
    }

    /**
     * Implements the email rule from the spec:
     *   - Submitter name == Applicant name → use Submitter Email Address
     *   - Else use the Applicant Email the user typed in the form.
     */
    private function deriveApplicantEmail(array $enrol, ?string $applicantEmail): ?string
    {
        if ($this->namesMatch($enrol['submitter_name'], $enrol['applicant_name'])) {
            return $enrol['submitter_email'] ?: null;
        }
        $email = trim((string) $applicantEmail);
        return $email !== '' ? $email : null;
    }

    /**
     * Suggest a booking role for the import PREVIEW, based on evidence we
     * actually hold — never an auto-commit. Trinity gives us no teacher
     * field, so the only reliable signal is whether the applicant or
     * submitter is already a registered contact. The human confirms the
     * role at commit; this just pre-selects the dropdown and shows WHY.
     *
     * Order of confidence:
     *   1. Applicant == candidate → 'self'.
     *   2. Applicant / submitter matches an existing contact (by email,
     *      then name) typed teacher / school_admin / parent → suggest that.
     *      teacher / school_admin beat parent; an applicant match beats a
     *      submitter match.
     *   3. Summary CSV names a teacher who exists as a teacher contact
     *      → 'teacher'.
     *   4. Otherwise null — no confident suggestion, the human must choose.
     *
     * @return array{role: ?string, reason: string, matched_contact: ?array{id:int,name:string,types:array<int,string>,matched_by:string,who:string}}
     */
    private function suggestRole(array $enrol, array $summary, ?string $derivedEmail): array
    {
        // 1. Self — the applicant is the candidate.
        if ($this->namesMatch($enrol['applicant_name'], $enrol['candidate_name'])) {
            return [
                'role' => 'self',
                'reason' => 'Applicant is the candidate — looks like a self-entry.',
                'matched_contact' => null,
            ];
        }

        // 2. Look the applicant, then the submitter, up against existing
        //    contacts. Email is the precise key; fall back to exact name.
        $probes = [
            ['email' => $derivedEmail, 'name' => $enrol['applicant_name'] ?? '', 'who' => 'applicant'],
            ['email' => $enrol['submitter_email'] ?? null, 'name' => $enrol['submitter_name'] ?? '', 'who' => 'submitter'],
        ];

        $matches = [];
        foreach ($probes as $probe) {
            $contact = null;
            $by = null;

            $email = trim((string) ($probe['email'] ?? ''));
            if ($email !== '') {
                $contact = ExamContact::findByEmail($email);
                if ($contact) {
                    $by = 'email';
                }
            }
            if (! $contact && trim((string) $probe['name']) !== '') {
                $contact = ExamContact::whereRaw('LOWER(name) = ?', [strtolower(trim($probe['name']))])->first();
                if ($contact) {
                    $by = 'name';
                }
            }
            if ($contact) {
                $matches[] = ['contact' => $contact, 'by' => $by, 'who' => $probe['who']];
            }
        }

        // Prefer a teacher/school_admin match (draw-eligible) over parent,
        // and an applicant match over a submitter match (probe order does
        // the latter for us).
        foreach (['teacher', 'school_admin', 'parent'] as $wantType) {
            foreach ($matches as $m) {
                if ($m['contact']->hasType($wantType)) {
                    $label = $wantType === 'school_admin' ? 'school admin' : $wantType;

                    return [
                        'role' => $wantType,
                        'reason' => "Matches registered {$label} {$m['contact']->name} — by {$m['by']} ({$m['who']}).",
                        'matched_contact' => [
                            'id' => $m['contact']->id,
                            'name' => $m['contact']->name,
                            'types' => $m['contact']->types,
                            'matched_by' => $m['by'],
                            'who' => $m['who'],
                        ],
                    ];
                }
            }
        }

        // 3. Summary CSV names a teacher who already exists as a teacher.
        $summaryTeacher = trim((string) ($summary['teacher_name'] ?? ''));
        if ($summaryTeacher !== '') {
            $teacher = ExamContact::whereRaw('LOWER(name) = ?', [strtolower($summaryTeacher)])
                ->get()
                ->first(fn (ExamContact $c) => $c->isTeacher() || $c->isSchoolAdmin());
            if ($teacher) {
                return [
                    'role' => $teacher->isSchoolAdmin() && ! $teacher->isTeacher() ? 'school_admin' : 'teacher',
                    'reason' => "Trinity Summary names teacher {$summaryTeacher}, who is a registered teacher.",
                    'matched_contact' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'types' => $teacher->types,
                        'matched_by' => 'summary',
                        'who' => 'teacher',
                    ],
                ];
            }
        }

        // 4. No confident signal — the human must choose.
        return [
            'role' => null,
            'reason' => 'No existing teacher, school or parent match — please choose the role.',
            'matched_contact' => null,
        ];
    }

    /**
     * Resolve teacher_name with a fallback: Trinity Digital Summary CSVs
     * usually leave Teacher fields empty (the teacher submits via the
     * Enrolment as the Applicant, not as a tagged teacher on the result).
     * So when Summary teacher is blank AND the derived booking_role is
     * 'teacher', we fall back to the Applicant name from the Enrolment.
     */
    private function resolveTeacherName(array $summary, array $enrol, string $derivedRole): ?string
    {
        $fromSummary = trim((string) ($summary['teacher_name'] ?? ''));
        if ($fromSummary !== '') {
            return $fromSummary;
        }
        if ($derivedRole === 'teacher') {
            $applicantName = trim((string) ($enrol['applicant_name'] ?? ''));
            return $applicantName !== '' ? $applicantName : null;
        }
        return null;
    }

    /**
     * Resolve teacher_contact_id (FK to exam_contacts) for the exam_entries
     * row. This is the proper, queryable source of truth — the denormalised
     * `teacher_name` string is kept in sync for search but the FK is what
     * the UI relies on going forward.
     *
     * Priority:
     *   1. Summary CSV has a Teacher Name → match an existing teacher
     *      contact by case-insensitive name. (Trinity-confirmed teacher.)
     *   2. derivedRole='teacher' → submitter contact (Maria-Nielsen
     *      pattern: submitter == applicant != candidate).
     *   3. Else null. (Parent-submitter without a Summary teacher; Paul
     *      flags the right contact manually via the admin.)
     */
    private function resolveTeacherContactId(
        array $summary,
        string $derivedRole,
        ?ExamContact $submitterContact,
    ): ?int {
        $fromSummary = trim((string) ($summary['teacher_name'] ?? ''));
        if ($fromSummary !== '') {
            $matched = ExamContact::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($fromSummary)])
                ->get()
                ->first(fn (ExamContact $c) => $c->isTeacher());
            if ($matched) {
                return $matched->id;
            }
            // Fall through — if no existing teacher contact matched the
            // Summary name, still prefer the submitter when role=teacher.
        }

        if ($derivedRole === 'teacher' && $submitterContact) {
            return $submitterContact->id;
        }

        return null;
    }

    /**
     * Booking-role auto-derivation per spec:
     *   1. Applicant name == Candidate name (Enrolment) → 'self'
     *   2. Else Summary teacher matches Applicant name → 'teacher'
     *   3. Else look up Applicant in exam_contacts:
     *        type teacher / school_admin → 'teacher'
     *        type parent only            → 'parent'
     *        type trinity_admin AND Summary has separate teacher → 'teacher'
     *   4. Shape-based default — submitter == applicant != candidate →
     *      'teacher' (the dominant LAR-centre pattern: a teacher booking on
     *      behalf of a student). True 'parent' submitters (Adrian-O'Malley
     *      shape) require an existing parent-tagged contact, or a manual
     *      retag via the contacts admin.
     *   5. Else default → 'parent'
     */
    private function deriveBookingRole(array $enrol, array $summary, ?string $derivedEmail): string
    {
        $applicantName = $enrol['applicant_name'];
        $candidateName = $enrol['candidate_name'];
        $submitterName = $enrol['submitter_name'];
        $summaryTeacher = trim($summary['teacher_name']);

        // 1. Self
        if ($this->namesMatch($applicantName, $candidateName)) {
            return 'self';
        }

        // 2. Teacher (from Summary)
        if ($summaryTeacher !== '' && $this->namesMatch($applicantName, $summaryTeacher)) {
            return 'teacher';
        }

        // 3. Contact lookup
        $contact = null;
        if ($derivedEmail) {
            $contact = ExamContact::findByEmail($derivedEmail);
        }
        if (! $contact && $applicantName !== '') {
            $contact = ExamContact::whereRaw('LOWER(name) = ?', [strtolower(trim($applicantName))])->first();
        }
        if ($contact) {
            $hasTeacherType = $contact->isTeacher() || $contact->isSchoolAdmin();
            if ($hasTeacherType) {
                return 'teacher';
            }
            // Pure parent
            if ($contact->isParent() && ! $contact->isTrinityAdmin()) {
                return 'parent';
            }
            if ($contact->isTrinityAdmin() && $summaryTeacher !== '') {
                return 'teacher';
            }
        }

        // 4. Shape-based default: submitter == applicant, candidate is a
        //    different person — submitter is acting as the teacher.
        //    Resolves the Maria-Nielsen case (5 May 2026, Lily Jago).
        if ($this->namesMatch($submitterName, $applicantName)
            && ! $this->namesMatch($applicantName, $candidateName)) {
            return 'teacher';
        }

        // 5. Default — parent
        return 'parent';
    }
}
