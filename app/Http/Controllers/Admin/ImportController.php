<?php

// app/Http/Controllers/Admin/ImportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportRun;
use App\Services\TrinityCsvImporter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /admin/imports — two CSV-driven workflows:
 *   • Section 1: bulk orders for a chosen quarter (idempotent on Order #).
 *   • Section 2: per-candidate triple (Enrolment + Summary + Marksheet).
 *
 * Preview routes return JSON so the Vue page can show a confirm panel
 * before Paul presses Commit. Commit routes redirect with flash.
 */
class ImportController extends Controller
{
    public function __construct(private TrinityCsvImporter $importer = new TrinityCsvImporter()) {}

    public function index(): Response
    {
        $defaultQuarter = (int) ceil(now()->month / 3);
        $defaultYear = (int) now()->year;

        $recent = ImportRun::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (ImportRun $r) => [
                'id' => $r->id,
                'type' => $r->type,
                'filename' => $r->filename,
                'summary' => $r->summary,
                'created_at' => $r->created_at?->format('d M Y H:i'),
                'user_name' => $r->user?->name,
            ]);

        return Inertia::render('admin/Imports/Index', [
            'defaults' => [
                'year' => $defaultYear,
                'quarter' => $defaultQuarter,
            ],
            'recent' => $recent,
        ]);
    }

    /**
     * Section 1 — JSON preview of the bulk-orders CSV.
     */
    public function previewOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'year' => 'required|integer|min:2025|max:2030',
            'quarter' => 'required|integer|min:1|max:4',
        ]);

        $contents = file_get_contents($validated['file']->getRealPath());
        if ($contents === false) {
            return response()->json(['error' => 'Could not read uploaded file.'], 422);
        }

        try {
            $preview = $this->importer->previewOrders(
                $contents,
                (int) $validated['year'],
                (int) $validated['quarter'],
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // The preview's row date objects need to become strings for JSON.
        $shape = function (array $row) {
            return [
                'order_number' => $row['order_number'],
                'requested_start_date' => $row['requested_start_date']?->toDateString(),
                'delivery_method' => $row['delivery_method'],
                'subject_area' => $row['subject_area'],
                'candidates' => $row['candidates'],
                'venue' => $row['venue'],
                'order_status' => $row['order_status'],
                'commission_rate' => $row['commission_rate'],
            ];
        };

        return response()->json([
            'totals' => $preview['totals'],
            'toCreate' => array_map($shape, $preview['toCreate']),
            'toUpdate' => array_map($shape, $preview['toUpdate']),
        ]);
    }

    /**
     * Section 1 — commit the bulk-orders CSV.
     */
    public function commitOrders(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'year' => 'required|integer|min:2025|max:2030',
            'quarter' => 'required|integer|min:1|max:4',
        ]);

        $contents = file_get_contents($validated['file']->getRealPath());
        if ($contents === false) {
            return back()->withErrors(['file' => 'Could not read uploaded file.']);
        }

        try {
            $run = $this->importer->commitOrders(
                $contents,
                (int) $validated['year'],
                (int) $validated['quarter'],
                $request->user()?->id,
                $validated['file']->getClientOriginalName(),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $created = $run->summary['created'] ?? 0;
        $updated = $run->summary['updated'] ?? 0;

        return redirect()->route('admin.imports.index')
            ->with('success', "Imported orders for Q{$validated['quarter']} {$validated['year']}: {$created} created, {$updated} updated.");
    }

    /**
     * Section 2 — JSON preview of one candidate's triple.
     */
    public function previewCandidate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enrolment' => 'required|file|max:10240',
            'summary' => 'required|file|max:10240',
            'marksheet' => 'required|file|max:10240',
            // Plain string — we accept DD/MM/YYYY or YYYY-MM-DD (Trinity uses
            // DD/MM/YYYY). Don't use Laravel's |date rule because strtotime is
            // ambiguous on UK dates (10/01/2015 → Oct 1, not Jan 10).
            'date_of_birth' => 'nullable|string|max:32',
            'applicant_email' => 'nullable|email',
        ]);

        $dob = $this->normaliseDob($validated['date_of_birth'] ?? null);

        try {
            $enrol = $this->importer->parseEnrolment(
                file_get_contents($validated['enrolment']->getRealPath())
            );
            $summary = $this->importer->parseSummary(
                file_get_contents($validated['summary']->getRealPath())
            );
            $score = $this->importer->parseMarksheet(
                file_get_contents($validated['marksheet']->getRealPath())
            );

            $preview = $this->importer->previewCandidate(
                $enrol,
                $summary,
                $score,
                $dob,
                $validated['applicant_email'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($preview);
    }

    /**
     * Section 2 — commit a single candidate.
     */
    public function commitCandidate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrolment' => 'required|file|max:10240',
            'summary' => 'required|file|max:10240',
            'marksheet' => 'required|file|max:10240',
            // Plain string — we accept DD/MM/YYYY or YYYY-MM-DD (Trinity uses
            // DD/MM/YYYY). Don't use Laravel's |date rule because strtotime is
            // ambiguous on UK dates (10/01/2015 → Oct 1, not Jan 10).
            'date_of_birth' => 'nullable|string|max:32',
            'applicant_email' => 'nullable|email',
        ]);

        $dob = $this->normaliseDob($validated['date_of_birth'] ?? null);

        try {
            $enrol = $this->importer->parseEnrolment(
                file_get_contents($validated['enrolment']->getRealPath())
            );
            $summary = $this->importer->parseSummary(
                file_get_contents($validated['summary']->getRealPath())
            );
            $score = $this->importer->parseMarksheet(
                file_get_contents($validated['marksheet']->getRealPath())
            );

            $run = $this->importer->commitCandidate(
                $enrol,
                $summary,
                $score,
                $dob,
                $validated['applicant_email'] ?? null,
                $request->user()?->id,
                $validated['enrolment']->getClientOriginalName(),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['enrolment' => $e->getMessage()]);
        }

        $verb = ($run->summary['created_entry'] ?? false) ? 'Created' : 'Updated';
        $name = $run->summary['candidate_name'] ?? 'candidate';

        return redirect()->route('admin.imports.index')
            ->with('success', "{$verb} exam entry for {$name}.");
    }

    /**
     * Normalise a Date of Birth string to ISO YYYY-MM-DD so Eloquent's
     * date cast doesn't have to guess. Trinity gives us DD/MM/YYYY (UK
     * format) which Carbon::parse misreads as US (Oct 1 vs Jan 10).
     * Returns null if the input is empty or doesn't match a known shape.
     */
    private function normaliseDob(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // Already ISO — return as-is.
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $raw)) {
            return $raw;
        }

        // DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY (UK formats).
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $raw, $m)) {
            try {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])
                    ->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }
}
