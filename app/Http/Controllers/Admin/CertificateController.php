<?php

// app/Http/Controllers/Admin/CertificateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Services\CertificateRenderer;
use App\Services\QuarterCertificateBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class CertificateController extends Controller
{
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
                'tier'             => CertificateRenderer::teacherBadge($count),
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
            'studentTemplates'  => array_keys(CertificateRenderer::STUDENT_TEMPLATES),
            'teacherTemplates'  => array_keys(CertificateRenderer::TEACHER_TEMPLATES),
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
    private function renderStudentCertPdfBytes(CertificateRenderer $renderer, ExamEntry $entry, string $quarterLabel): ?string
    {
        $file = CertificateRenderer::STUDENT_TEMPLATES[$entry->certificate_name] ?? null;
        if (! $file) {
            return null;
        }

        try {
            return $renderer->pdf($renderer->student(
                $file,
                $entry->candidate_name,
                $entry->instrument?->name ?? '',
                (string) ($entry->grade ?? ''),
                $quarterLabel,
            ));
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

        $renderer = new CertificateRenderer();
        $writtenFiles = [];
        foreach ($entries as $entry) {
            $pdfBytes = $this->renderStudentCertPdfBytes($renderer, $entry, $quarterLabel);
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

        if (! isset(CertificateRenderer::STUDENT_TEMPLATES[$templateKey])) {
            return back()->withErrors(['template' => 'Invalid template selected.']);
        }

        $name = $validated['custom_name'] ?? $entry->candidate_name;
        $instrument = $entry->instrument?->name ?? '';
        $grade = $entry->grade ?? '';

        // Auto-detect quarter from exam date, falling back to order date
        $effectiveDate = $entry->exam_date ?? $entry->order?->requested_start_date;
        $quarter = $validated['quarter'] ?? $this->getQuarterLabel($effectiveDate);

        try {
            $renderer = new CertificateRenderer();
            $image = $renderer->student(CertificateRenderer::STUDENT_TEMPLATES[$templateKey], $name, $instrument, (string) $grade, $quarter);

            return $this->certificateResponse($renderer, $image, $templateKey, $name, $format);
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
            'teacher_id'   => 'required|integer|exists:exam_contacts,id',
            'template'     => 'required|string',
            'custom_name'  => 'nullable|string|max:100',
            'quarter'      => 'nullable|string|max:30',
            'format'       => 'nullable|in:png,pdf',
        ]);

        $contact = ExamContact::with('schools:id,name')->findOrFail($validated['teacher_id']);
        $templateKey = $validated['template'];
        $format = $validated['format'] ?? 'pdf';

        if (! isset(CertificateRenderer::TEACHER_TEMPLATES[$templateKey])) {
            return back()->withErrors(['template' => 'Invalid template selected.']);
        }

        // The page sends the teacher's contact id. Certificates show the
        // school when one is linked, otherwise the teacher's own name.
        $name = $validated['custom_name'] ?? $contact->schools->first()?->name ?? $contact->name;
        $quarter = $validated['quarter'] ?? $this->getQuarterLabel(now());

        $renderer = new CertificateRenderer();
        $image = $renderer->teacher(CertificateRenderer::TEACHER_TEMPLATES[$templateKey], $name, $quarter);

        return $this->certificateResponse($renderer, $image, $templateKey, $name, $format);
    }

    /**
     * A single certificate as an inline PNG (the preview) or a PDF download.
     */
    private function certificateResponse(CertificateRenderer $renderer, $image, string $templateKey, string $name, string $format)
    {
        $safeBase = str_replace(' ', '_', $templateKey).'_'.str_replace(' ', '_', $name);

        if ($format === 'png') {
            return response($renderer->png($image), 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'inline; filename="'.$safeBase.'.png"',
            ]);
        }

        return response($renderer->pdf($image), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safeBase.'.pdf"',
        ]);
    }

    /**
     * Quarter batch, step 1 of 3: clear the quarter's folder and return the
     * list of steps for the page to run. See QuarterCertificateBatch for why
     * the work is split.
     */
    public function batchStart(Request $request, QuarterCertificateBatch $batch): JsonResponse
    {
        ['quarter' => $quarter, 'year' => $year] = $this->quarterInput($request);

        $plan = $batch->start($quarter, $year);
        if ($plan === null) {
            return response()->json(['error' => 'No entries with results found for '.QuarterCertificateBatch::label($quarter, $year).'.'], 422);
        }

        return response()->json($plan);
    }

    /**
     * Quarter batch, step 2 of 3, called once per step start() listed.
     */
    public function batchStep(Request $request, QuarterCertificateBatch $batch): JsonResponse
    {
        ['quarter' => $quarter, 'year' => $year] = $this->quarterInput($request);
        $step = $request->validate([
            'teacher' => 'required|string|max:255',
            'part' => 'required|integer|min:1',
        ]);

        return response()->json([
            'written' => $batch->step($quarter, $year, $step['teacher'], (int) $step['part']),
        ]);
    }

    /**
     * Quarter batch, step 3 of 3: the ZIPs and the download links.
     */
    public function batchFinish(Request $request, QuarterCertificateBatch $batch): JsonResponse
    {
        ['quarter' => $quarter, 'year' => $year] = $this->quarterInput($request);

        return response()->json($batch->finish($quarter, $year));
    }

    /** @return array{quarter: int, year: int} */
    private function quarterInput(Request $request): array
    {
        $validated = $request->validate([
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2025|max:2030',
        ]);

        return ['quarter' => (int) $validated['quarter'], 'year' => (int) $validated['year']];
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
    public function generateTopScorers(Request $request, QuarterCertificateBatch $batch): JsonResponse
    {
        ['quarter' => $quarter, 'year' => $year] = $this->quarterInput($request);
        $label = QuarterCertificateBatch::label($quarter, $year);

        if ($batch->entries($quarter, $year)->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => "No scored entries in {$label}.",
            ], 422);
        }

        $certs = $batch->topScorersOnly($quarter, $year);

        return response()->json([
            'success' => true,
            'count' => count($certs),
            'quarter_label' => $label,
            'certs' => $certs,
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
