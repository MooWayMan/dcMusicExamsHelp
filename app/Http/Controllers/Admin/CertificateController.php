<?php

// app/Http/Controllers/Admin/CertificateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Support\EntryCredit;
use App\Support\TopScorers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Typography\FontFactory;
use ZipArchive;

class CertificateController extends Controller
{
    private const S3_BASE = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/';

    /**
     * Student certificate templates (blank PNGs on S3).
     *
     * Centre Stage / Showstopper here are the LEGACY single-version templates
     * kept as a fallback. The live top-scorer cert generator picks group-
     * specific templates from TOP_SCORER_TEMPLATES below — those are what
     * Paul actually wants attached to the winner emails. The legacy entries
     * stay so any older code path that looks up by certificate name keeps
     * working.
     */
    private const STUDENT_TEMPLATES = [
        'Bravo Certificate'             => 'certStu_1.png',
        'Take a Bow Certificate'        => 'certStu_2.png',
        'Standing Ovation Certificate'  => 'certStu_3.png',
        'Centre Stage Certificate'      => 'certStu_4.png',
        'Showstopper Certificate'       => 'certStu_5.png',
    ];

    /**
     * Group-specific top-scorer cert templates.
     *
     * Each quarter awards FOUR top-scorer certificates — one per
     * (group × tier) combination. Anna in Initial–5 might score 92 (her
     * group's top Distinction) the same quarter Seth scores 93 in Grades
     * 6–8 (his group's top Distinction). Both deserve a cert that says
     * "highest in YOUR group" — not the legacy generic "highest this
     * quarter" that misled the recipient about which slice was won.
     *
     * Index: [tier][group] → S3 filename.
     *   tier  = 'Showstopper' (Distinction) | 'Centre Stage' (Merit)
     *   group = 'initial_5' | '6_8'
     */
    private const TOP_SCORER_TEMPLATES = [
        'Showstopper' => [
            'initial_5' => 'certStu_5_initial5.png',
            '6_8'       => 'certStu_5_g68.png',
        ],
        'Centre Stage' => [
            'initial_5' => 'certStu_4_initial5.png',
            '6_8'       => 'certStu_4_g68.png',
        ],
    ];

    /**
     * Resolve the S3 filename for a (tier × group) top-scorer cert.
     *
     * Public so tests can verify the wiring without reaching into a
     * private constant via reflection.
     *
     * @param  string  $tier   'Showstopper' (Distinction) | 'Centre Stage' (Merit)
     * @param  string  $group  'initial_5' | '6_8'
     * @return string|null     Matching filename, or null for unknown combos.
     */
    public static function topScorerTemplate(string $tier, string $group): ?string
    {
        return self::TOP_SCORER_TEMPLATES[$tier][$group] ?? null;
    }

    /**
     * Teacher certificate templates (blank PNGs on S3).
     */
    private const TEACHER_TEMPLATES = [
        'Bronze Appreciation Certificate'    => 'certTeach_1.png',
        'Silver Appreciation Certificate'    => 'certTeach_2.png',
        'Gold Appreciation Certificate'      => 'certTeach_3.png',
        'Top Award Appreciation Certificate' => 'certTeach_4.png',
    ];

    /**
     * Show the certificate generator page.
     *
     * Both the student list and the teacher list are scoped to the
     * selected quarter. Teacher badge tier (Bronze/Silver/Gold/Top Award)
     * is calculated PER QUARTER — counts reset every quarter, so a
     * teacher who hit Gold in Q1 but only has 3 entries in Q2 won't
     * earn a Q2 badge.
     */
    public function index(Request $request): Response
    {
        $quarter = (int) $request->query('quarter', (int) ceil(now()->month / 3));
        $year = (int) $request->query('year', (int) now()->year);

        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = \Carbon\Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        $inQuarter = function ($entry) use ($startDate, $endDate) {
            $date = $entry->exam_date ?? $entry->order?->requested_start_date;
            return $date && $date->between($startDate, $endDate);
        };

        // ────────────── Students (scored, in selected quarter) ──────────────
        $students = ExamEntry::whereNotNull('score')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->with(['student:id,first_name,last_name', 'instrument:id,name', 'order:id,requested_start_date'])
            ->orderBy('exam_date', 'desc')
            ->get()
            ->filter($inQuarter)
            ->map(fn ($entry) => [
                'id'              => $entry->id,
                'candidate_name'  => $entry->candidate_name,
                'instrument'      => $entry->instrument?->name ?? 'Unknown',
                'grade'           => $entry->grade,
                'score'           => $entry->score,
                'result_band'     => $entry->result_band,
                'certificate'     => $entry->certificate_name,
                'exam_date'       => ($entry->exam_date ?? $entry->order?->requested_start_date)?->format('j F Y'),
                // Drives the Sent ✓ pill in the flat Student Certificates
                // list — lets the bottom tab double as a master view of
                // who's already had their weekly cert email.
                'sent'            => $entry->certificate_sent_at !== null,
                'sent_at'         => $entry->certificate_sent_at?->format('j M Y'),
            ])
            ->values();

        // ────────────── Teachers (per-quarter counts + tier) ──────────────
        // Group ALL entries in the quarter by teacher_name string,
        // then count per teacher (non-cancelled only).
        $quarterEntriesByTeacher = ExamEntry::with('order:id,requested_start_date')
            ->whereNotNull('teacher_name')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get()
            ->filter($inQuarter)
            ->groupBy('teacher_name');

        $teachers = $quarterEntriesByTeacher->map(function ($entries, $teacherName) {
            // Include school_admin type alongside teacher — school admins
            // (e.g. Daniel Rogers / Pulse Music) book on behalf of the
            // school's teachers and earn the appreciation cert + badge for
            // their volume too. Mirrors how /admin/quarter-end treats them.
            $contact = ExamContact::withType(['teacher', 'school_admin'])
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($teacherName)])
                ->first();
            $count = $entries->count();

            return [
                'id'               => $contact?->id,
                'name'             => $teacherName,
                'candidates_count' => $count,
                'tier'             => match (true) {
                    $count >= 40 => 'Top Award',
                    $count >= 30 => 'Gold',
                    $count >= 20 => 'Silver',
                    $count >= 10 => 'Bronze',
                    default      => null,
                },
            ];
        })->sortByDesc('candidates_count')->values();

        // ────────────── Weekly Send groups ──────────────
        // Teachers (or parent-bookers) with results in this quarter whose
        // weekly cert email hasn't been sent yet. Drives the Send This Week's
        // Results accordion. Mirrors QuarterEnd Step 2's teacher-group shape
        // so the Vue can reuse the same accordion + button cluster.
        $weeklyGroups = $this->buildWeeklyGroups($startDate, $endDate);

        return Inertia::render('admin/Certificates/Index', [
            'students'          => $students,
            'teachers'          => $teachers,
            'studentTemplates'  => array_keys(self::STUDENT_TEMPLATES),
            'teacherTemplates'  => array_keys(self::TEACHER_TEMPLATES),
            'selectedQuarter'   => $quarter,
            'selectedYear'      => $year,
            'weeklyGroups'      => $weeklyGroups,
        ]);
    }

    /**
     * Build the Weekly Send accordion payload.
     *
     * Scope: scored entries in the selected quarter whose
     * certificate_sent_at is still NULL. Grouped by teacher_name (or
     * "Parent Bookings (no teacher assigned)" for orphans), with the
     * applicant_email resolved via the same ExamContact lookup the
     * QuarterEnd Step 2 page uses — so the Open in Gmail button routes
     * to the teacher's real address, not Paul's submitter email.
     *
     * Returns an array of teacher groups, each with:
     *   - teacher_name, applicant_email, is_parent_booking, booking_role
     *   - unsent_count, students[] (id, name, instrument, grade, score, result, certificate)
     *
     * Empty array when nothing's unsent — the Vue hides the section then.
     */
    private function buildWeeklyGroups(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $unsentEntries = ExamEntry::with([
                'instrument:id,name',
                'order:id,requested_start_date,delivery_method,applicant_name,applicant_email',
            ])
            // Any scored entry is owed a certificate — a Below Pass gets a
            // Bravo. CANCELLED / NO_SHOW are still excluded below: Trinity
            // never issues a result for those, so there is nothing to award.
            ->whereNotNull('score')
            ->certNotSent()
            ->where(function ($q) {
                $q->whereNull('notes')->orWhereNotIn('notes', ExamEntry::NOTES_NO_RESULT);
            })
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        if ($unsentEntries->isEmpty()) {
            return [];
        }

        // Parent / self-booker lookup — matches QuarterEnd Step 2 behaviour.
        $parentOrSelfLookup = ExamContact::with('emails')
            ->withType(['parent', 'candidate'])
            ->get()
            ->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

        $grouped = $unsentEntries->groupBy(function ($e) {
            $name = trim((string) ($e->teacher_name ?? ''));
            return $name === '' ? 'Parent Bookings (no teacher assigned)' : $e->teacher_name;
        });

        return $grouped->map(function ($entries, $teacherName) use ($parentOrSelfLookup) {
            // Resolve booking role — explicit per-entry override wins, else
            // infer from the contact type. Same precedence as QuarterEnd.
            $parentContact = $parentOrSelfLookup->get(mb_strtolower(trim($teacherName)));
            $entryRoles = $entries->pluck('booking_role')->filter()->unique();
            $explicitRole = $entryRoles->count() === 1 ? $entryRoles->first() : null;
            $contactInferredRole = match (true) {
                $parentContact === null     => null,
                $parentContact->isParent()  => 'parent',
                $parentContact->isCandidate() => 'self',
                default                     => null,
            };
            $bookingRole = $explicitRole ?? $contactInferredRole;
            $isParentBooking = $bookingRole === 'parent' || $bookingRole === 'self';

            $firstOrder = $entries->first()?->order;
            $ownOrder = $entries->first(fn ($e) =>
                $e->order
                && mb_strtolower(trim($e->order->applicant_name ?? '')) === mb_strtolower(trim($teacherName))
            )?->order;

            if ($isParentBooking) {
                $teacherEmail = $parentContact?->primary_email ?? $ownOrder?->applicant_email;
            } else {
                $teacherRecord = ExamContact::with('emails')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($teacherName)])
                    ->first();
                $teacherEmail = $teacherRecord?->primary_email
                    ?? $ownOrder?->applicant_email
                    ?? $firstOrder?->applicant_email;
            }

            // Orphaned bucket has no real recipient — null the email so the
            // UI hides the Copy / Open Gmail buttons.
            if ($teacherName === 'Parent Bookings (no teacher assigned)') {
                $teacherEmail = null;
            }

            return [
                'teacher_name'      => $teacherName,
                'applicant_email'   => $teacherEmail,
                'is_parent_booking' => $isParentBooking,
                'booking_role'      => $bookingRole,
                'unsent_count'      => $entries->count(),
                'students'          => $entries->map(fn ($e) => [
                    'id'          => $e->id,
                    'name'        => $e->candidate_name,
                    'instrument'  => $e->instrument?->name ?? 'Unknown',
                    'grade'       => $e->grade,
                    'score'       => $e->score,
                    'result'      => $e->result_band,
                    'certificate' => $e->certificate_name,
                ])->values()->toArray(),
            ];
        })->sortByDesc('unsent_count')->values()->toArray();
    }

    /**
     * Flip certificate_sent_at to now() on a set of entry IDs.
     *
     * Used by the Weekly Send "Mark as Sent" button — when Paul finishes
     * emailing a teacher their batch of unsent certs, this hides the row
     * so the teacher doesn't pop back into next week's list.
     */
    public function markSent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_ids'   => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:exam_entries,id',
        ]);

        $count = ExamEntry::whereIn('id', $validated['entry_ids'])
            ->update(['certificate_sent_at' => now()]);

        return response()->json([
            'success' => true,
            'marked'  => $count,
        ]);
    }

    /**
     * Undo a "Mark as Sent" — sets certificate_sent_at back to NULL so the
     * entries reappear in the Weekly Send list. Used when Paul ticks the
     * box by accident or wants to re-send (e.g. teacher said the email
     * didn't arrive).
     */
    public function unmarkSent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_ids'   => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:exam_entries,id',
        ]);

        $count = ExamEntry::whereIn('id', $validated['entry_ids'])
            ->update(['certificate_sent_at' => null]);

        return response()->json([
            'success'   => true,
            'unmarked'  => $count,
        ]);
    }

    /**
     * Render one student's cert PDF and return the bytes.
     *
     * Shared helper used by batchByEntries — keeps the cert-rendering
     * recipe (S3 template fetch → overlay text → encode PNG → wrap in
     * DomPDF) in one place instead of duplicating the inline blocks from
     * generateStudent / batchGenerate. Returns null on any failure
     * (template missing, S3 unreachable, encode error) so the caller
     * can skip the entry rather than 500-ing the whole batch.
     */
    private function renderStudentCertPdfBytes(ExamEntry $entry, string $quarterLabel): ?string
    {
        $certName = $entry->certificate_name;
        if (! $certName || ! isset(self::STUDENT_TEMPLATES[$certName])) {
            return null;
        }

        try {
            $templateUrl = self::S3_BASE . self::STUDENT_TEMPLATES[$certName];
            $image = $this->overlayStudentText(
                $templateUrl,
                $entry->candidate_name,
                $entry->instrument?->name ?? '',
                $entry->grade ?? '',
                $quarterLabel,
            );

            $encoded = $image->encode(new PngEncoder());
            $base64 = base64_encode((string) $encoded);
            $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
                . '<img src="data:image/png;base64,' . $base64 . '" style="width:210mm;height:297mm;display:block;">'
                . '</body></html>';

            return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
        } catch (\Throwable $e) {
            \Log::error("Cert render failed for entry {$entry->id}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Bundle certs for an arbitrary list of entry IDs into a single ZIP
     * and stream it back. Drives the "Download All Certs" button in the
     * Weekly Send accordion when a teacher has 2+ students — one ZIP
     * attachment to drag into Gmail beats N separate PDFs the browser
     * would have to be granted multi-download permission for.
     *
     * Why server-side ZIP not client-side: the cert rendering needs
     * Intervention Image + S3 + DomPDF, all server-only. Browser would
     * need to fetch each PDF individually anyway, then pack with JSZip —
     * extra round-trips and a JS dep we don't carry. Better to do it
     * here, return one binary response.
     */
    public function batchByEntries(Request $request)
    {
        set_time_limit(120);

        $validated = $request->validate([
            'entry_ids'   => 'required|array|min:1|max:100',
            'entry_ids.*' => 'integer|exists:exam_entries,id',
        ]);

        $entries = ExamEntry::with(['instrument', 'order:id,requested_start_date'])
            ->whereIn('id', $validated['entry_ids'])
            ->whereNotNull('score')
            ->get();

        if ($entries->isEmpty()) {
            return response()->json(['error' => 'No matching scored entries.'], 422);
        }

        // Use the first entry's exam/order date as the quarter label.
        // The Vue groups by teacher's unsent batch so all entries are
        // typically the same quarter; if they ever diverged, the label
        // would still pick a reasonable Q for the cert footer text.
        $firstEntry = $entries->first();
        $effectiveDate = $firstEntry->exam_date ?? $firstEntry->order?->requested_start_date;
        $quarterLabel = $this->getQuarterLabel($effectiveDate);

        // Temp working dir for PDFs + the ZIP. Cleaned up before return
        // so we don't accumulate junk under /tmp on the box.
        $tempDir = sys_get_temp_dir() . '/cert-batch-' . uniqid('', true);
        if (! mkdir($tempDir, 0700, true) && ! is_dir($tempDir)) {
            return response()->json(['error' => 'Could not create temp dir.'], 500);
        }

        $writtenFiles = [];
        foreach ($entries as $entry) {
            $pdfBytes = $this->renderStudentCertPdfBytes($entry, $quarterLabel);
            if (! $pdfBytes) {
                continue;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry->candidate_name);
            $shortCert = str_replace([' Certificate', ' '], ['', '_'], $entry->certificate_name ?? 'Cert');
            $pdfPath = "{$tempDir}/{$safeName}_{$shortCert}.pdf";
            file_put_contents($pdfPath, $pdfBytes);
            $writtenFiles[] = $pdfPath;
        }

        if (empty($writtenFiles)) {
            @rmdir($tempDir);
            return response()->json(['error' => 'No certs could be generated.'], 500);
        }

        $zipPath = "{$tempDir}/certs.zip";
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            foreach ($writtenFiles as $f) @unlink($f);
            @rmdir($tempDir);
            return response()->json(['error' => 'Could not create ZIP.'], 500);
        }
        foreach ($writtenFiles as $f) {
            $zip->addFile($f, basename($f));
        }
        $zip->close();

        $zipBytes = file_get_contents($zipPath);

        // Cleanup temp files before returning.
        foreach ($writtenFiles as $f) @unlink($f);
        @unlink($zipPath);
        @rmdir($tempDir);

        $downloadName = 'certs_' . now()->format('Y-m-d_His') . '.zip';

        return response($zipBytes, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'Content-Length'      => (string) strlen($zipBytes),
        ]);
    }

    /**
     * Generate a student certificate.
     */
    public function generateStudent(Request $request)
    {
        $validated = $request->validate([
            'entry_id'     => 'required|exists:exam_entries,id',
            'template'     => 'required|string',
            'custom_name'  => 'nullable|string|max:100',
            'quarter'      => 'nullable|string|max:30',
            'format'       => 'nullable|in:png,pdf',
        ]);

        $entry = ExamEntry::with(['instrument', 'order:id,requested_start_date'])->findOrFail($validated['entry_id']);
        $templateKey = $validated['template'];
        $format = $validated['format'] ?? 'pdf';

        if (! isset(self::STUDENT_TEMPLATES[$templateKey])) {
            return back()->withErrors(['template' => 'Invalid template selected.']);
        }

        $name = $validated['custom_name'] ?? $entry->candidate_name;
        $instrument = $entry->instrument?->name ?? '';
        $grade = $entry->grade ?? '';

        // Auto-detect quarter from exam date, falling back to order date
        $effectiveDate = $entry->exam_date ?? $entry->order?->requested_start_date;
        $quarter = $validated['quarter'] ?? $this->getQuarterLabel($effectiveDate);

        try {
            $templateUrl = self::S3_BASE . self::STUDENT_TEMPLATES[$templateKey];
            $image = $this->overlayStudentText($templateUrl, $name, $instrument, $grade, $quarter);

            $encoded = $image->encode(new PngEncoder());
            $safeBase = str_replace(' ', '_', $templateKey) . '_' . str_replace(' ', '_', $name);

            // PNG mode — return the raw image so the browser can render an inline preview
            if ($format === 'png') {
                return response((string) $encoded, 200, [
                    'Content-Type'        => 'image/png',
                    'Content-Disposition' => 'inline; filename="' . $safeBase . '.png"',
                ]);
            }

            // PDF mode — wrap the PNG in an A4 page for download
            $base64 = base64_encode((string) $encoded);
            $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
                . '<img src="data:image/png;base64,' . $base64 . '" style="width:210mm;height:297mm;display:block;">'
                . '</body></html>';

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $safeBase . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a teacher certificate.
     */
    public function generateTeacher(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'   => 'required|exists:users,id',
            'template'     => 'required|string',
            'custom_name'  => 'nullable|string|max:100',
            'quarter'      => 'nullable|string|max:30',
            'format'       => 'nullable|in:png,pdf',
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        $templateKey = $validated['template'];
        $format = $validated['format'] ?? 'pdf';

        if (! isset(self::TEACHER_TEMPLATES[$templateKey])) {
            return back()->withErrors(['template' => 'Invalid template selected.']);
        }

        // Use custom name if provided, otherwise look up the teacher's school
        // via the unified ExamContact → contact_school pivot. Falls back to the
        // teacher's display name if no school is linked.
        $schoolName = \App\Models\ExamContact::query()
            ->where('user_id', $teacher->id)
            ->with('schools:id,name')
            ->first()
            ?->schools->first()?->name;
        $name = $validated['custom_name'] ?? $schoolName ?? $teacher->name;
        $quarter = $validated['quarter'] ?? $this->getQuarterLabel(now());

        $templateUrl = self::S3_BASE . self::TEACHER_TEMPLATES[$templateKey];
        $image = $this->overlayTeacherText($templateUrl, $name, $quarter);

        $encoded = $image->encode(new PngEncoder());
        $safeBase = str_replace(' ', '_', $templateKey) . '_' . str_replace(' ', '_', $name);

        if ($format === 'png') {
            return response((string) $encoded, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'inline; filename="' . $safeBase . '.png"',
            ]);
        }

        $base64 = base64_encode((string) $encoded);
        $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
            . '<img src="data:image/png;base64,' . $base64 . '" style="width:210mm;height:297mm;display:block;">'
            . '</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeBase . '.pdf"',
        ]);
    }

    /**
     * Overlay student name and instrument/grade onto certificate template.
     *
     * Text sits in the empty space between "Proudly Presented To" and the body text.
     * Positioned centre-right to avoid the badge on the left.
     * Uses percentage-based Y positioning so it works at any resolution.
     */
    private function overlayStudentText(string $templateUrl, string $name, string $instrument, string $grade, string $quarter)
    {
        $response = Http::get($templateUrl);
        if (! $response->successful()) {
            throw new \RuntimeException("Failed to download template from: {$templateUrl}");
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->decode($response->body());

        $width = $image->width();
        $height = $image->height();

        // Text shifted right to avoid badge on the left
        $textX = (int) ($width * 0.60);

        // Font — Georgia preferred, DejaVu as fallback (GD built-in only supports size 1-5)
        $fontPath = resource_path('fonts/Georgia.ttf');
        if (! file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        }
        if (! file_exists($fontPath)) {
            $fontPath = glob('/usr/share/fonts/truetype/*/*.ttf')[0] ?? null;
        }

        // Scale font sizes relative to image width (designed for ~2480px wide A4)
        $nameSize = (int) ($width * 0.038);
        $detailSize = (int) ($width * 0.028);
        $quarterSize = (int) ($width * 0.042);

        // Right-side text X — right edge anchor, aligned with body text area
        $rightTextX = (int) ($width * 0.92);

        // Bold font for date (try Bold variant, fall back to regular)
        $boldFontPath = resource_path('fonts/Georgia-Bold.ttf');
        if (! file_exists($boldFontPath)) {
            $boldFontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }
        if (! file_exists($boldFontPath)) {
            $boldFontPath = $fontPath; // fall back to regular
        }

        // Name — positioned at ~47% from top, right-aligned
        $nameY = (int) ($height * 0.47);
        $image->text($name, $rightTextX, $nameY, function (FontFactory $font) use ($fontPath, $nameSize) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($nameSize);
            $font->color('#1e3a5f');
            $font->align('right');
        });

        // Instrument & Grade — positioned at ~52% from top, right-aligned to same anchor
        $detail = trim("$instrument Grade $grade");
        $detailY = (int) ($height * 0.52);
        $image->text($detail, $rightTextX, $detailY, function (FontFactory $font) use ($fontPath, $detailSize) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($detailSize);
            $font->color('#1e3a5f');
            $font->align('right');
        });

        // Quarter — at the bottom (~96% from top, bold and bigger)
        $quarterX = (int) ($width * 0.50);
        $quarterY = (int) ($height * 0.96);
        $image->text($quarter, $quarterX, $quarterY, function (FontFactory $font) use ($boldFontPath, $quarterSize) {
            if ($boldFontPath) {
                $font->filename($boldFontPath);
            }
            $font->size($quarterSize);
            $font->color('#1e3a5f');
            $font->align('center');
        });

        return $image;
    }

    /**
     * Overlay teacher name and quarter onto certificate template.
     *
     * Same layout as student but only needs the name (no instrument/grade).
     */
    private function overlayTeacherText(string $templateUrl, string $name, string $quarter)
    {
        $response = Http::get($templateUrl);
        if (! $response->successful()) {
            throw new \RuntimeException("Failed to download template from: {$templateUrl}");
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->decode($response->body());

        $width = $image->width();
        $height = $image->height();

        // Right-side text X — right edge anchor, matching student certificate layout
        $rightTextX = (int) ($width * 0.92);

        // Font — Georgia preferred, DejaVu as fallback
        $fontPath = resource_path('fonts/Georgia.ttf');
        if (! file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        }
        if (! file_exists($fontPath)) {
            $fontPath = glob('/usr/share/fonts/truetype/*/*.ttf')[0] ?? null;
        }

        // Scale font sizes relative to image width
        $nameSize = (int) ($width * 0.038);
        $quarterSize = (int) ($width * 0.042);

        // Name — positioned at ~47% from top, right-aligned (matching student layout)
        $nameY = (int) ($height * 0.47);
        $image->text($name, $rightTextX, $nameY, function (FontFactory $font) use ($fontPath, $nameSize) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($nameSize);
            $font->color('#1e3a5f');
            $font->align('right');
        });

        // Quarter — bold, at the very bottom (~94% from top)
        $quarterX = (int) ($width * 0.50);
        $quarterY = (int) ($height * 0.94);
        $image->text($quarter, $quarterX, $quarterY, function (FontFactory $font) use ($fontPath, $quarterSize) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($quarterSize);
            $font->color('#1e3a5f');
            $font->align('center');
        });

        return $image;
    }

    /**
     * Batch generate all certificates for a quarter, grouped by teacher in ZIPs.
     */
    public function batchGenerate(Request $request)
    {
        set_time_limit(120); // Certificate generation can take a while with many entries

        $validated = $request->validate([
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2025|max:2030',
        ]);

        $quarter = $validated['quarter'];
        $year = $validated['year'];

        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
        };
        $quarterLabel = "{$suffix} Quarter {$year}";

        // Date range for the quarter
        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = "{$year}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = \Carbon\Carbon::parse($startDate)->addMonths(3)->subDay()->toDateString();

        // Get all SCORED entries in this quarter — drives the per-student
        // certificate generation. Every candidate who sat the exam gets a
        // certificate based on their result: Standing Ovation for a
        // Distinction, Take a Bow for a Merit, Bravo for a Pass OR a Below
        // Pass. That last case is the point of the scheme — see ForTeachers
        // ("even if they don't pass"). CANCELLED entries are excluded because
        // no exam was sat.
        $entries = ExamEntry::whereNotNull('score')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->with(['instrument:id,name', 'order:id,requested_start_date'])
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        if ($entries->isEmpty()) {
            return back()->with('error', "No entries with results found for {$quarterLabel}.");
        }

        // Credit-name resolution for the ZIP folders and the report PDFs.
        //
        // Entries created by the Section 1b enrolment-list import carry
        // `teacher_name = null` on purpose — Trinity doesn't tell us the
        // teacher until the per-candidate results triple arrives — and their
        // only link to a person is `submitter_contact_id`. Grouping on the raw
        // string alone therefore dropped every not-yet-resulted candidate out
        // of their teacher's bucket, which is why the "Awaiting Results"
        // section silently never rendered for them. (Penelope Jane Mitchell,
        // Q2 2026 — Paul's report said 6 Total with no pending line, while
        // Quarter End correctly showed 1 pending.)
        //
        // This mirrors the submitter fallback in
        // QuarterEndController::creditNameFor(). It deliberately does NOT
        // adopt that method's school-admin rollup: Quarter End rolls Daniel
        // Rogers up into "Pulse Music School", but the ZIP is built per person
        // (Daniel_Rogers_Report.pdf), and changing that here would rename
        // existing folders.
        //
        // See App\Support\EntryCredit for the rule itself.
        $submitterNameById = EntryCredit::submitterNames($entries);

        $creditName = fn (ExamEntry $e) => EntryCredit::nameFor($e, $submitterNameById);

        // Group by teacher
        $grouped = $entries->groupBy($creditName);

        // ── Teacher badge volume counts ──────────────────────────────────────
        // The Bronze/Silver/Gold/Top-Award badge counts EVERY non-CANCELLED
        // entry in the quarter — including NO_SHOW and Fails — because the
        // booking itself earns the teacher their volume tally. Using the
        // passing-scores-only $grouped count (the previous behaviour) under-
        // counted teachers near a threshold and shipped them the wrong cert
        // (e.g. Daniel Rogers Q1 2026: 19 passes + 2 NO_SHOW + 1 Fail = 22
        // entries → Silver, but the old logic said 19 → Bronze, which then
        // disagreed with the email body's "22+ candidates / Silver" line).
        // Mirrors the inclusion rule on /admin/quarter-end so the cert
        // generator and the email body always agree on which badge to award.
        $teacherBadgeCounts = ExamEntry::query()
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->whereNotNull('teacher_name')
            ->where('teacher_name', '!=', '')
            ->with('order:id,requested_start_date')
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            })
            ->groupBy('teacher_name')
            ->map->count();

        // Template image cache
        $templateImageCache = [];

        // Create output directory
        $outputDir = "certificates/{$year}-Q{$quarter}";
        Storage::disk('local')->deleteDirectory($outputDir); // Clean previous run
        Storage::disk('local')->makeDirectory($outputDir);

        $totalGenerated = 0;
        $teacherSummary = [];

        foreach ($grouped as $teacher => $teacherEntries) {
            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";
            Storage::disk('local')->makeDirectory($teacherDir);

            $certCount = 0;

            foreach ($teacherEntries as $entry) {
                $certName = $entry->certificate_name;
                if (! $certName || ! isset(self::STUDENT_TEMPLATES[$certName])) {
                    continue;
                }

                try {
                    $templateUrl = self::S3_BASE . self::STUDENT_TEMPLATES[$certName];

                    // Cache template downloads
                    if (! isset($templateImageCache[$templateUrl])) {
                        $response = Http::get($templateUrl);
                        if (! $response->successful()) {
                            continue;
                        }
                        $templateImageCache[$templateUrl] = $response->body();
                    }

                    $manager = new ImageManager(new Driver());
                    $image = $manager->decode($templateImageCache[$templateUrl]);

                    $width = $image->width();
                    $height = $image->height();

                    $fontPath = resource_path('fonts/Georgia.ttf');
                    if (! file_exists($fontPath)) {
                        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
                    }
                    $boldFontPath = resource_path('fonts/Georgia-Bold.ttf');
                    if (! file_exists($boldFontPath)) {
                        $boldFontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
                    }

                    $nameSize = (int) ($width * 0.038);
                    $detailSize = (int) ($width * 0.028);
                    $quarterSize = (int) ($width * 0.042);
                    $rightTextX = (int) ($width * 0.92);

                    // Name at 47%
                    $image->text($entry->candidate_name, $rightTextX, (int) ($height * 0.47), function (FontFactory $font) use ($fontPath, $nameSize) {
                        if ($fontPath) $font->filename($fontPath);
                        $font->size($nameSize);
                        $font->color('#1e3a5f');
                        $font->align('right');
                    });

                    // Instrument & Grade at 52%
                    $detail = trim(($entry->instrument?->name ?? '') . ' Grade ' . ($entry->grade ?? ''));
                    $image->text($detail, $rightTextX, (int) ($height * 0.52), function (FontFactory $font) use ($fontPath, $detailSize) {
                        if ($fontPath) $font->filename($fontPath);
                        $font->size($detailSize);
                        $font->color('#1e3a5f');
                        $font->align('right');
                    });

                    // Quarter at 96%, bold, centre
                    $image->text($quarterLabel, (int) ($width * 0.50), (int) ($height * 0.96), function (FontFactory $font) use ($boldFontPath, $quarterSize) {
                        if ($boldFontPath) $font->filename($boldFontPath);
                        $font->size($quarterSize);
                        $font->color('#1e3a5f');
                        $font->align('center');
                    });

                    $encoded = $image->encode(new PngEncoder());

                    // Convert PNG to PDF using DomPDF
                    $base64 = base64_encode((string) $encoded);
                    $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
                        . '<img src="data:image/png;base64,' . $base64 . '" style="width:210mm;height:297mm;display:block;">'
                        . '</body></html>';

                    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry->candidate_name);
                    $shortCert = str_replace([' Certificate', ' '], ['', '_'], $certName);
                    $filename = "{$teacherDir}/{$safeName}_{$shortCert}.pdf";

                    Storage::disk('local')->put($filename, $pdf->output());
                    $certCount++;
                    $totalGenerated++;
                } catch (\Throwable $e) {
                    \Log::error("Batch cert failed for {$entry->candidate_name}: {$e->getMessage()}");
                }
            }

            $teacherSummary[$teacher] = $certCount;
        }

        // Top-Scorer certificates — extracted into a shared helper so the
        // standalone "Generate top-scorer certs only" endpoint can reuse
        // the exact same rendering pipeline.
        $topScorerResult = $this->renderTopScorerCertificates(
            $entries,
            $quarter,
            $year,
            $quarterLabel,
            $templateImageCache,
            true // alsoIntoTeacherFolder — bundle into the teacher's ZIP
        );
        $topScorerCount = $topScorerResult['count'];
        $topScorerLog = $topScorerResult['log'];
        $totalGenerated += $topScorerCount;

        // NOTE: the per-teacher results CSV and report PDF used to be built
        // here. They duplicated the teacher dashboard, which now carries every
        // candidate's exam details — including the ones still awaiting a
        // result — and offers its own dated CSV / PDF download. Dropping them
        // takes two renders per teacher out of this already slow batch, so it
        // now produces certificates only.

        // Generate teacher badge certificates for qualifying teachers
        foreach ($grouped as $teacher => $teacherEntries) {
            if ($teacher === 'Unassigned') continue;

            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";

            // Look up school name for this teacher — certificates show school, not personal name
            $teacherContact = ExamContact::withType('teacher')
                ->with('schools')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($teacher)])
                ->first();
            $certDisplayName = $teacherContact?->schools->first()?->name ?? $teacher;

            // Badges reset per-quarter — count this quarter's non-CANCELLED
            // entries (NO_SHOW + Fails included, see $teacherBadgeCounts
            // build above). Falls back to the passing-scores group count
            // only if the teacher somehow isn't in the badge-count map
            // (defensive — shouldn't happen since the badge query is broader).
            $quarterCandidates = $teacherBadgeCounts->get($teacher, $teacherEntries->count());

            $badgeTier = match (true) {
                $quarterCandidates >= 40 => 'Top Award Appreciation Certificate',
                $quarterCandidates >= 30 => 'Gold Appreciation Certificate',
                $quarterCandidates >= 20 => 'Silver Appreciation Certificate',
                $quarterCandidates >= 10 => 'Bronze Appreciation Certificate',
                default => null,
            };

            if ($badgeTier && isset(self::TEACHER_TEMPLATES[$badgeTier])) {
                try {
                    $templateUrl = self::S3_BASE . self::TEACHER_TEMPLATES[$badgeTier];

                    if (! isset($templateImageCache[$templateUrl])) {
                        $response = Http::get($templateUrl);
                        if ($response->successful()) {
                            $templateImageCache[$templateUrl] = $response->body();
                        }
                    }

                    if (isset($templateImageCache[$templateUrl])) {
                        $image = (new ImageManager(new Driver()))->decode($templateImageCache[$templateUrl]);
                        $w = $image->width();
                        $h = $image->height();

                        $fontPath = resource_path('fonts/Georgia.ttf');
                        if (! file_exists($fontPath)) $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
                        $boldFontPath = resource_path('fonts/Georgia-Bold.ttf');
                        if (! file_exists($boldFontPath)) $boldFontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

                        $image->text($certDisplayName, (int) ($w * 0.92), (int) ($h * 0.47), function (FontFactory $font) use ($fontPath, $w) {
                            if ($fontPath) $font->filename($fontPath);
                            $font->size((int) ($w * 0.038));
                            $font->color('#1e3a5f');
                            $font->align('right');
                        });

                        $image->text($quarterLabel, (int) ($w * 0.50), (int) ($h * 0.94), function (FontFactory $font) use ($boldFontPath, $w) {
                            if ($boldFontPath) $font->filename($boldFontPath);
                            $font->size((int) ($w * 0.04));
                            $font->color('#1e3a5f');
                            $font->align('center');
                        });

                        $encoded = $image->encode(new PngEncoder());
                        $base64 = base64_encode((string) $encoded);
                        $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
                            . '<img src="data:image/png;base64,' . $base64 . '" style="width:210mm;height:297mm;display:block;">'
                            . '</body></html>';

                        $badgePdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                        $shortBadge = str_replace([' Certificate', ' '], ['', '_'], $badgeTier);
                        Storage::disk('local')->put("{$teacherDir}/{$safeTeacher}_{$shortBadge}.pdf", $badgePdf->output());
                    }
                } catch (\Throwable $e) {
                    \Log::error("Badge cert failed for {$teacher}: {$e->getMessage()}");
                }

                // Download the badge PNG from S3 for social media / website use
                try {
                    $badgePngMap = [
                        'Bronze Appreciation Certificate' => 'awardTA10.png',
                        'Silver Appreciation Certificate' => 'awardTA20.png',
                        'Gold Appreciation Certificate'   => 'awardTA30.png',
                        'Top Award Appreciation Certificate' => 'awardTA40.png',
                    ];

                    if (isset($badgePngMap[$badgeTier])) {
                        $badgePngUrl = self::S3_BASE . $badgePngMap[$badgeTier];
                        $badgePngResponse = Http::get($badgePngUrl);

                        if ($badgePngResponse->successful()) {
                            $shortTier = str_replace([' Appreciation Certificate', ' '], ['', '_'], $badgeTier);
                            Storage::disk('local')->put(
                                "{$teacherDir}/{$safeTeacher}_{$shortTier}_Badge.png",
                                $badgePngResponse->body()
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error("Badge PNG download failed for {$teacher}: {$e->getMessage()}");
                }
            }
        }

        // Create ZIPs per teacher
        $zipDir = "{$outputDir}/zips";
        Storage::disk('local')->makeDirectory($zipDir);
        $downloadLinks = [];

        foreach ($grouped as $teacher => $teacherEntries) {
            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";
            $zipFilename = "{$zipDir}/{$safeTeacher}_Q{$quarter}_{$year}.zip";
            $zipFullPath = Storage::disk('local')->path($zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                continue;
            }

            foreach (Storage::disk('local')->files($teacherDir) as $file) {
                $zip->addFile(Storage::disk('local')->path($file), basename($file));
            }
            $zip->close();

            $downloadLinks[$teacher] = $zipFilename;
        }

        // Master ZIP — contains the individual teacher ZIPs (not loose folders)
        $masterZipName = "{$outputDir}/ALL_Q{$quarter}_{$year}_Certificates.zip";
        $masterZipPath = Storage::disk('local')->path($masterZipName);
        $zip = new ZipArchive();

        if ($zip->open($masterZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($downloadLinks as $teacher => $teacherZipPath) {
                $fullPath = Storage::disk('local')->path($teacherZipPath);
                $zip->addFile($fullPath, basename($teacherZipPath));
            }
            $zip->close();
        }

        return back()->with('batch_result', [
            'total' => $totalGenerated,
            'quarter_label' => $quarterLabel,
            'teachers' => $teacherSummary,
            'download_links' => $downloadLinks,
            'master_zip' => $masterZipName,
            // Top-scorer cert manifest — Paul attaches these standalone PDFs
            // to the per-winner congratulations emails on QuarterEnd Step 3.
            'top_scorer_certs'  => $topScorerLog,
            'top_scorer_count'  => $topScorerCount,
        ]);
    }

    /**
     * Render Showstopper / Centre Stage PDFs for the four top-scorer
     * winners (more if ties). Two output locations:
     *
     *   1. Standalone in `certificates/{year}-Q{quarter}/top-scorers/` —
     *      always written. Each PDF is a single attachable file for the
     *      per-winner congratulations email Paul sends from QuarterEnd
     *      Step 3.
     *
     *   2. Optionally also into the winner's teacher folder — only when
     *      `$alsoIntoTeacherFolder` is true (i.e. when called as part of
     *      the full batch). Skipped for the standalone "top-scorer certs
     *      only" endpoint, since regenerating individual teacher folders
     *      without their other certs would make confusing partial ZIPs.
     *
     * @param  Collection<int, ExamEntry>  $entries
     * @param  array  $templateImageCache  Pass-by-ref so the per-student
     *                                     loop and this method can share
     *                                     fetched template PNGs.
     * @return array{count: int, log: array, dir: string}
     */
    private function renderTopScorerCertificates(
        \Illuminate\Support\Collection $entries,
        int $quarter,
        int $year,
        string $quarterLabel,
        array &$templateImageCache = [],
        bool $alsoIntoTeacherFolder = false
    ): array {
        $outputDir = "certificates/{$year}-Q{$quarter}";
        $topScorersDir = "{$outputDir}/top-scorers";
        Storage::disk('local')->makeDirectory($outputDir);
        Storage::disk('local')->makeDirectory($topScorersDir);

        $log = [];
        $count = 0;

        // Bucket the actual ExamEntry models ourselves rather than going
        // through TopScorers::calculate, because that helper's ->toArray()
        // call converts Eloquent models to plain associative arrays, which
        // breaks the `$entry->candidate_name` / `$entry->instrument?->name`
        // / `$entry->teacher_name` access we need below.
        $awards = [];
        foreach (['initial_5', '6_8'] as $group) {
            foreach (['distinction', 'merit'] as $band) {
                $bucket = $entries->filter(fn ($e) =>
                    $e->score !== null
                    && TopScorers::groupOf((string) $e->grade) === $group
                    && TopScorers::bandOf((int) $e->score) === $band
                );
                if ($bucket->isEmpty()) continue;
                $topScore = $bucket->max('score');
                foreach ($bucket->where('score', $topScore) as $entry) {
                    $awards[] = [
                        'entry' => $entry,
                        'group' => $group,
                        'band'  => $band,
                        'certificate' => $band === 'distinction' ? 'Showstopper' : 'Centre Stage',
                    ];
                }
            }
        }

        foreach ($awards as $award) {
            /** @var ExamEntry $entry */
            $entry = $award['entry'];
            $certName = $award['certificate'].' Certificate'; // 'Showstopper Certificate' | 'Centre Stage Certificate'

            // Pick the group-specific template (Initial–5 vs 6–8) so the cert
            // text reflects which slice was won. Falls back to the legacy
            // single template by certificate name if the group-specific
            // file isn't mapped — defensive only, the map should always hit.
            $tier  = $award['certificate']; // 'Showstopper' | 'Centre Stage'
            $group = $award['group'];       // 'initial_5'   | '6_8'
            $templateFile = self::topScorerTemplate($tier, $group)
                ?? self::STUDENT_TEMPLATES[$certName]
                ?? null;

            if (! $templateFile) {
                continue;
            }

            try {
                $templateUrl = self::S3_BASE.$templateFile;

                if (! isset($templateImageCache[$templateUrl])) {
                    $response = Http::get($templateUrl);
                    if (! $response->successful()) {
                        \Log::warning("Top-scorer template fetch failed: {$templateUrl}");
                        continue;
                    }
                    $templateImageCache[$templateUrl] = $response->body();
                }

                $manager = new ImageManager(new Driver());
                $image = $manager->decode($templateImageCache[$templateUrl]);

                $width = $image->width();
                $height = $image->height();

                $fontPath = resource_path('fonts/Georgia.ttf');
                if (! file_exists($fontPath)) {
                    $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
                }
                $boldFontPath = resource_path('fonts/Georgia-Bold.ttf');
                if (! file_exists($boldFontPath)) {
                    $boldFontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
                }

                $nameSize = (int) ($width * 0.038);
                $detailSize = (int) ($width * 0.028);
                $quarterSize = (int) ($width * 0.042);
                $rightTextX = (int) ($width * 0.92);

                $image->text($entry->candidate_name, $rightTextX, (int) ($height * 0.47), function (FontFactory $font) use ($fontPath, $nameSize) {
                    if ($fontPath) $font->filename($fontPath);
                    $font->size($nameSize);
                    $font->color('#1e3a5f');
                    $font->align('right');
                });

                $detail = trim(($entry->instrument?->name ?? '').' Grade '.($entry->grade ?? ''));
                $image->text($detail, $rightTextX, (int) ($height * 0.52), function (FontFactory $font) use ($fontPath, $detailSize) {
                    if ($fontPath) $font->filename($fontPath);
                    $font->size($detailSize);
                    $font->color('#1e3a5f');
                    $font->align('right');
                });

                $image->text($quarterLabel, (int) ($width * 0.50), (int) ($height * 0.96), function (FontFactory $font) use ($boldFontPath, $quarterSize) {
                    if ($boldFontPath) $font->filename($boldFontPath);
                    $font->size($quarterSize);
                    $font->color('#1e3a5f');
                    $font->align('center');
                });

                $encoded = $image->encode(new PngEncoder());

                $base64 = base64_encode((string) $encoded);
                $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
                    .'<img src="data:image/png;base64,'.$base64.'" style="width:210mm;height:297mm;display:block;">'
                    .'</body></html>';

                $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry->candidate_name);
                $shortCert = str_replace([' Certificate', ' '], ['', '_'], $certName);
                $pdfBytes = $pdf->output();

                $standalonePath = "{$topScorersDir}/{$safeName}_{$shortCert}.pdf";
                Storage::disk('local')->put($standalonePath, $pdfBytes);

                if ($alsoIntoTeacherFolder) {
                    $teacherForWinner = $entry->teacher_name ?? 'Unassigned';
                    $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacherForWinner);
                    $teacherDir = "{$outputDir}/{$safeTeacher}";
                    Storage::disk('local')->makeDirectory($teacherDir);
                    Storage::disk('local')->put("{$teacherDir}/{$safeName}_{$shortCert}.pdf", $pdfBytes);
                }

                $count++;
                $log[] = [
                    'name'             => $entry->candidate_name,
                    'short_name'       => $this->shortDisplayName($entry->candidate_name),
                    'certificate'      => $certName,
                    'group'            => $award['group'],
                    'band'             => $award['band'],
                    'score'            => $entry->score,
                    'instrument'       => $entry->instrument?->name,
                    'grade'            => $entry->grade,
                    'standalone_path'  => $standalonePath,
                    'download_url'     => '/admin/certificates/download/'.$standalonePath,
                ];
            } catch (\Throwable $e) {
                \Log::error("Top-scorer cert failed for {$entry->candidate_name}: {$e->getMessage()}");
            }
        }

        return ['count' => $count, 'log' => $log, 'dir' => $topScorersDir];
    }

    /**
     * GDPR display name: "Anna M". Mirrors ThankYouController.
     */
    private function shortDisplayName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if (count($parts) <= 1) return $fullName;
        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1));
    }

    /**
     * Standalone "Generate top-scorer certs only" endpoint.
     *
     * Produces ONLY the four (or more, with ties) Showstopper / Centre
     * Stage PDFs and drops them in `certificates/{year}-Q{quarter}/top-
     * scorers/`. Doesn't touch the per-student certs, teacher reports, or
     * ZIPs — much faster than re-running the full batch when Paul just
     * wants the four PDFs to attach to congratulations emails.
     */
    public function generateTopScorers(Request $request): JsonResponse
    {
        set_time_limit(60);

        $validated = $request->validate([
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2025|max:2030',
        ]);

        $quarter = $validated['quarter'];
        $year = $validated['year'];
        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
        };
        $quarterLabel = "{$suffix} Quarter {$year}";

        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = \Carbon\Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        $entries = ExamEntry::whereNotNull('score')
            ->where('score', '>=', 60)
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->with(['instrument:id,name', 'order:id,requested_start_date'])
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        if ($entries->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => "No scored entries in {$quarterLabel}.",
            ], 422);
        }

        $cache = [];
        $result = $this->renderTopScorerCertificates(
            $entries,
            $quarter,
            $year,
            $quarterLabel,
            $cache,
            false // standalone — DON'T duplicate into teacher folders
        );

        return response()->json([
            'success' => true,
            'count' => $result['count'],
            'quarter_label' => $quarterLabel,
            'certs' => $result['log'],
        ]);
    }

    /**
     * Download a generated ZIP file.
     */
    public function downloadZip(string $filename)
    {
        $path = Storage::disk('local')->path($filename);

        if (! file_exists($path)) {
            return back()->withErrors(['download' => 'File not found. Please generate certificates first.']);
        }

        return response()->download($path);
    }

    /**
     * Get a quarter label from a date (e.g. "1st Quarter 2026").
     *
     * Accepts both Carbon and CarbonImmutable — Laravel's date casts can
     * hand either back depending on the cast definition and Carbon
     * version, and a narrower hint here used to crash the cert generator
     * with a TypeError on local seed data.
     */
    private function getQuarterLabel(?\Carbon\CarbonInterface $date): string
    {
        $date = $date ?? now();
        $quarter = (int) ceil($date->month / 3);
        $suffix = match ($quarter) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            4 => '4th',
        };

        return "{$suffix} Quarter {$date->year}";
    }
}
