<?php

// app/Services/TrinityCsvImporter.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\ImportRun;
use App\Models\Instrument;
use App\Models\Order;
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
            'Drums' => 'Drum Kit',
            'Guitar' => 'Guitar (Rock/Pop)',
            'Keyboards' => 'Electronic Keyboard',
            'Singing' => 'Singing (Classical)',
            'R&P Vocals' => 'Singing (Rock/Pop)',
            'R&P Guitar' => 'Guitar (Rock/Pop)',
            'R&P Drums' => 'Drum Kit',
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
    public function commitCandidate(array $enrol, array $summary, int $score, ?string $dob, ?string $applicantEmail, ?int $userId, ?string $filename = null): ImportRun
    {
        $preview = $this->previewCandidate($enrol, $summary, $score, $dob, $applicantEmail);

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
            &$createdEntry, &$updatedEntry, &$createdContact
        ) {
            // Submitter contact — lookup by email, create if missing.
            $submitterContact = null;
            if ($enrol['submitter_email'] !== '') {
                $submitterContact = ExamContact::whereRaw('LOWER(email) = ?', [strtolower($enrol['submitter_email'])])->first();
                if (! $submitterContact) {
                    $submitterContact = ExamContact::create([
                        'name' => $enrol['submitter_name'] ?: $enrol['submitter_email'],
                        'email' => $enrol['submitter_email'],
                        'source' => 'trinity_csv_import',
                    ]);
                    $submitterContact->addType('parent');
                    $createdContact = true;
                }
            }

            // Applicant contact — when role is parent and the applicant differs from the submitter,
            // ensure we have a contact for them too.
            $applicantContact = null;
            if ($preview['derivedRole'] === 'parent' && $enrol['applicant_name'] !== $enrol['submitter_name']) {
                $email = $preview['derivedEmail'] ?: $applicantEmail;
                $applicantContact = $email
                    ? ExamContact::whereRaw('LOWER(email) = ?', [strtolower($email)])->first()
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
                'teacher_name' => $this->resolveTeacherName($summary, $enrol, $preview['derivedRole']),
                'school_name' => $summary['school'],
                'booking_role' => $preview['derivedRole'],
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
            } else {
                ExamEntry::create($payload);
                $createdEntry = true;
            }
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
                'derived_role' => $preview['derivedRole'],
                'derived_email' => $preview['derivedEmail'],
                'score' => $score,
            ],
        ]);
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
     * Booking-role auto-derivation per spec:
     *   1. Applicant name == Candidate name (Enrolment) → 'self'
     *   2. Else Summary teacher matches Applicant name → 'teacher'
     *   3. Else look up Applicant in exam_contacts:
     *        type teacher / school_admin → 'teacher'
     *        type parent only            → 'parent'
     *        type trinity_admin AND Summary has separate teacher → 'teacher'
     *   4. Default → 'parent'
     */
    private function deriveBookingRole(array $enrol, array $summary, ?string $derivedEmail): string
    {
        $applicantName = $enrol['applicant_name'];
        $candidateName = $enrol['candidate_name'];
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
            $contact = ExamContact::whereRaw('LOWER(email) = ?', [strtolower($derivedEmail)])->first();
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

        // 4. Default — parent
        return 'parent';
    }
}
