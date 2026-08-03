<?php

// app/Http/Controllers/Admin/ReEntryPermitController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\ImportRun;
use App\Services\ReEntryPermitParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /admin/re-entry-permits — drop Trinity "Re-entry Permit" PDFs and mark the
 * candidates who didn't sit.
 *
 * Trinity issues a permit when a booked candidate withdraws: a voucher at 100%
 * credit, valid twelve months. Until this existed the app never heard about
 * them, so four July 2026 orders sat permanently short — 53 entries against
 * 59 booked candidates — and the codes lived only in PDFs in a downloads
 * folder.
 *
 * Preview returns JSON so the page can show a matched/unmatched table before
 * Commit. Mirrors ReconciliationController's preview-then-commit shape.
 *
 * ORDER OF OPERATIONS: a permit matches an exam entry by candidate number, so
 * the entry has to exist first. If the enrolment list has not been imported
 * for that order the withdrawn candidate has no row, and the permit reports
 * "not found" — which is why the preview says so in as many words.
 */
class ReEntryPermitController extends Controller
{
    public function __construct(private ReEntryPermitParser $parser = new ReEntryPermitParser()) {}

    public function index(): Response
    {
        return Inertia::render('admin/ReEntryPermits/Index', [
            'recent' => ImportRun::with('user:id,name')
                ->where('type', 're_entry_permit')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn (ImportRun $r) => [
                    'id' => $r->id,
                    'filename' => $r->filename,
                    'summary' => $r->summary,
                    'user' => $r->user?->name,
                    'created_at' => $r->created_at?->format('d M Y, H:i'),
                ]),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:25'],
            'files.*' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        return response()->json(['rows' => $this->rowsFor($validated['files'])]);
    }

    /**
     * Re-parse every upload (never trust what the client sends back), stamp
     * the matched entries and log the run.
     */
    public function commit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:25'],
            'files.*' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $rows = $this->rowsFor($validated['files']);

        $marked = 0;
        $alreadyMarked = 0;

        foreach ($rows as $row) {
            if ($row['status'] === 'already') {
                $alreadyMarked++;
                continue;
            }
            if ($row['status'] !== 'ready' || ! $row['entry_id']) {
                continue;
            }

            ExamEntry::whereKey($row['entry_id'])->update([
                'notes' => ExamEntry::NOTE_RE_ENTRY,
                're_entry_code' => $row['code'],
            ]);
            $marked++;
        }

        // Keeping the PDFs is a nice-to-have — a disk hiccup must not undo
        // work already committed to the database.
        foreach ($validated['files'] as $file) {
            try {
                Storage::disk('local')->putFileAs(
                    're-entry-permits/'.now()->format('Y-m'),
                    $file,
                    $file->getClientOriginalName(),
                );
            } catch (\Throwable) {
                // deliberately ignored
            }
        }

        ImportRun::create([
            'user_id' => $request->user()?->id,
            'type' => 're_entry_permit',
            'filename' => count($validated['files']).' permit'.(count($validated['files']) === 1 ? '' : 's'),
            'summary' => [
                'files' => count($validated['files']),
                'marked' => $marked,
                'already_marked' => $alreadyMarked,
                'not_found' => count(array_filter($rows, fn ($r) => $r['status'] === 'not_found')),
                'not_a_permit' => count(array_filter($rows, fn ($r) => $r['status'] === 'not_a_permit')),
                'candidates' => array_values(array_filter(array_map(
                    fn ($r) => $r['status'] === 'ready' ? $r['candidate_name'] : null,
                    $rows
                ))),
            ],
        ]);

        $message = $marked === 1
            ? '1 candidate marked as withdrawn with a re-entry permit.'
            : "{$marked} candidates marked as withdrawn with re-entry permits.";

        return redirect()
            ->route('admin.re-entry-permits.index')
            ->with('success', $message);
    }

    /**
     * Parse each upload and resolve it to an exam entry.
     *
     * @param  array<int,\Illuminate\Http\UploadedFile>  $files
     * @return array<int,array<string,mixed>>
     */
    private function rowsFor(array $files): array
    {
        $rows = [];

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();

            try {
                $p = $this->parser->parse($file->getRealPath());
            } catch (\Throwable) {
                $rows[] = $this->row($name, 'not_a_permit', ['note' => 'Could not read this PDF.']);
                continue;
            }

            if (! $p['is_permit'] || ! $p['candidate_number'] || ! $p['code']) {
                $rows[] = $this->row($name, 'not_a_permit', [
                    'note' => 'This does not look like a Trinity Re-entry Permit.',
                ]);
                continue;
            }

            $entry = $this->matchEntry($p['candidate_number']);

            if (! $entry) {
                $rows[] = $this->row($name, 'not_found', [
                    'candidate_name' => $p['candidate_name'],
                    'candidate_number' => $p['candidate_number'],
                    'exam' => $p['exam'],
                    'code' => $p['code'],
                    'note' => 'No exam entry with this candidate number. Import the enrolment list for its order first.',
                ]);
                continue;
            }

            $rows[] = $this->row($name, $entry->notes === ExamEntry::NOTE_RE_ENTRY ? 'already' : 'ready', [
                'candidate_name' => $p['candidate_name'],
                'candidate_number' => $p['candidate_number'],
                'exam' => $p['exam'],
                'code' => $p['code'],
                'valid_until' => $p['valid_until'],
                'entry_id' => $entry->id,
                'order_number' => $entry->order?->trinity_order_number,
                'current_notes' => $entry->notes,
                'note' => $entry->score !== null
                    ? 'Careful — this entry already has a score, so it looks like it WAS sat.'
                    : null,
            ]);
        }

        return $rows;
    }

    /**
     * A candidate number stays with a person across sittings, so it can match
     * more than one entry. Prefer the one that looks un-sat and unmarked —
     * that is the withdrawal the permit was issued for — then fall back to
     * the most recent.
     */
    private function matchEntry(string $candidateNumber): ?ExamEntry
    {
        $candidates = ExamEntry::with('order:id,trinity_order_number,requested_start_date')
            ->where('candidate_number', $candidateNumber)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $sortKey = fn (ExamEntry $e) => $e->exam_date ?? $e->order?->requested_start_date;

        return $candidates->first(fn (ExamEntry $e) => $e->score === null && $e->notes === null)
            ?? $candidates->sortByDesc($sortKey)->first();
    }

    /** @return array<string,mixed> */
    private function row(string $filename, string $status, array $extra = []): array
    {
        return array_merge([
            'filename' => $filename,
            'status' => $status,
            'candidate_name' => null,
            'candidate_number' => null,
            'exam' => null,
            'code' => null,
            'valid_until' => null,
            'entry_id' => null,
            'order_number' => null,
            'current_notes' => null,
            'note' => null,
        ], $extra);
    }
}
