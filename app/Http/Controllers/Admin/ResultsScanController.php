<?php

// app/Http/Controllers/Admin/ResultsScanController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\ImportRun;
use App\Models\Instrument;
use App\Models\Order;
use App\Services\ResultsScanParser;
use App\Services\ResultsScanTranscriber;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * /admin/results-scan — the F2F exam-report checker + importer.
 *
 * Paul's face-to-face results come as handwritten "Examination Report" scans.
 * A vision pass in Cowork transcribes each form to a small JSON (identity block
 * + section marks + the examiner's total); this screen then runs the checks he
 * used to do with a calculator, shows an editable grid, and — once he's happy —
 * fills the verified score/result/exam date onto the matching exam entries.
 *
 * preview() is read-only: it runs the checks and reports, per candidate,
 * whether the order and entry already exist so the grid can show what an import
 * would do. commit() is the only method that writes, and it is deliberately
 * non-destructive — it never overwrites a score/result an entry already has.
 */
class ResultsScanController extends Controller
{
    public function __construct(private ResultsScanParser $parser = new ResultsScanParser()) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/ResultsScan/Index', [
            'transcribeEnabled' => (new ResultsScanTranscriber())->enabled(),
        ]);
    }

    /**
     * Turn an uploaded handwritten Trinity report PDF into candidate records via
     * the Anthropic vision pass, so Paul can upload the scan directly instead of
     * transcribing it by hand first. Returns the same raw records the JSON upload
     * produces; the page then runs them through preview() exactly as before.
     * Writes nothing.
     */
    public function transcribe(Request $request, ResultsScanTranscriber $transcriber): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.(ResultsScanTranscriber::MAX_BYTES / 1024)],
        ]);

        try {
            $candidates = $transcriber->transcribe($request->file('file'));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'candidates' => $candidates,
            'count' => count($candidates),
        ]);
    }

    /**
     * Run the checks on the transcribed candidates and report their match
     * status against existing orders/entries. Nothing is written.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidates' => 'required|array|min:1',
            'candidates.*' => 'array',
        ]);

        $checked = $this->parser->parse($validated['candidates']);

        foreach ($checked as $i => $c) {
            $checked[$i]['match'] = $this->matchStatus($c);
        }

        return response()->json([
            'candidates' => $checked,
            'count' => count($checked),
            'flagged' => count(array_filter($checked, static fn (array $c): bool => $c['flags'] !== [])),
        ]);
    }

    /**
     * Fill the verified results onto the matching exam entries. For each
     * candidate: find the order by its Trinity order number, find the entry by
     * candidate number (then name) within that order, and set score / result /
     * exam date ONLY where they're currently empty. When the order exists but
     * the candidate has no entry yet, create a minimal F2F entry. Orders we
     * can't find are reported, never guessed.
     */
    public function commit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidates' => 'required|array|min:1',
            'candidates.*' => 'array',
        ]);

        $checked = $this->parser->parse($validated['candidates']);

        $updated = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        DB::transaction(function () use ($checked, &$updated, &$created, &$skipped, &$warnings) {
            foreach ($checked as $c) {
                // Never import a candidate whose checks haven't cleared — a
                // failed total or an unreadable mark means the score can't be
                // trusted yet. Paul fixes it in the grid and re-imports.
                if (! $c['checks_pass']) {
                    $warnings[] = "{$c['candidate_name']}: still needs review — not imported.";
                    $skipped++;

                    continue;
                }

                $orderNumber = $c['order_number'];
                if ($orderNumber === '') {
                    $warnings[] = "{$c['candidate_name']}: no order number on the form — skipped.";
                    $skipped++;

                    continue;
                }

                $order = Order::where('trinity_order_number', $orderNumber)->first();
                if (! $order) {
                    $warnings[] = "Order {$orderNumber} ({$c['candidate_name']}) not found — import it on the Imports page first.";
                    $skipped++;

                    continue;
                }

                $instrumentId = $c['instrument']
                    ? Instrument::where('name', $c['instrument'])->value('id')
                    : null;

                $score = $c['verified_total'];
                $result = $c['band'];
                $examDate = $c['exam_date'] ? $this->parseDate($c['exam_date']) : null;

                // The full report — piece names, per-section marks and the
                // examiner's transcribed comments — stored for teachers to see
                // later (none of this is on the digital results).
                $report = [
                    'subject' => $c['subject'],
                    'family' => $c['family'],
                    'grade' => $c['grade'],
                    'examiner_number' => $c['examiner_number'],
                    'exam_date' => $c['exam_date'],
                    'total' => $c['verified_total'],
                    'band' => $c['band'],
                    'general_comments' => $c['general_comments'],
                    'sections' => $c['sections'],
                    'source' => 'f2f_results_scan',
                    'captured_at' => now()->toDateString(),
                ];

                $entry = ExamEntry::where('order_id', $order->id)
                    ->where(function ($q) use ($c) {
                        $q->where('candidate_number', $c['candidate_id']);
                        if ($c['candidate_name'] !== '') {
                            $q->orWhereRaw('LOWER(candidate_name) = ?', [strtolower($c['candidate_name'])]);
                        }
                    })
                    ->first();

                if ($entry) {
                    // Already scored → leave it entirely alone (never overwrite
                    // a result). Only result-less entries get filled.
                    if ($entry->score !== null) {
                        $warnings[] = "{$c['candidate_name']}: already has a result — left untouched.";
                        $skipped++;

                        continue;
                    }

                    $fill = ['score' => $score, 'result' => $result, 'report' => $report];
                    if (empty($entry->exam_date) && $examDate) {
                        $fill['exam_date'] = $examDate;
                    }
                    if (empty($entry->grade) && $c['grade']) {
                        $fill['grade'] = $c['grade'];
                    }
                    if (empty($entry->instrument_id) && $instrumentId) {
                        $fill['instrument_id'] = $instrumentId;
                    }
                    if (empty($entry->delivery_method)) {
                        $fill['delivery_method'] = 'Default';
                    }

                    $entry->fill($fill)->save();
                    $updated++;
                } else {
                    ExamEntry::create([
                        'order_id' => $order->id,
                        'candidate_number' => $c['candidate_id'] ?: null,
                        'candidate_name' => $c['candidate_name'],
                        'instrument_id' => $instrumentId,
                        'grade' => $c['grade'],
                        'subject_area' => $c['family'] === 'R&P' ? 'Rock and Pop' : 'Classical and Jazz',
                        'delivery_method' => 'Default',
                        'result' => $result,
                        'score' => $score,
                        'exam_date' => $examDate,
                        'report' => $report,
                        'source' => 'f2f_results_scan',
                    ]);
                    $created++;
                }
            }
        });

        ImportRun::create([
            'user_id' => $request->user()?->id,
            'type' => 'f2f_results_scan',
            'filename' => null,
            'summary' => [
                'candidates' => count($checked),
                'updated' => $updated,
                'created' => $created,
                'skipped' => $skipped,
            ],
        ]);

        return response()->json([
            'updated' => $updated,
            'created' => $created,
            'skipped' => $skipped,
            'warnings' => array_values(array_unique($warnings)),
        ]);
    }

    /**
     * Read-only lookup of whether a candidate's order/entry already exist, so
     * the grid can show "will update" vs "will create" vs "order missing".
     *
     * @param  array<string, mixed>  $c
     * @return array{order_found: bool, entry_found: bool, has_result: bool}
     */
    private function matchStatus(array $c): array
    {
        $order = $c['order_number'] !== ''
            ? Order::where('trinity_order_number', $c['order_number'])->first()
            : null;

        if (! $order) {
            return ['order_found' => false, 'entry_found' => false, 'has_result' => false];
        }

        $entry = ExamEntry::where('order_id', $order->id)
            ->where(function ($q) use ($c) {
                $q->where('candidate_number', $c['candidate_id']);
                if ($c['candidate_name'] !== '') {
                    $q->orWhereRaw('LOWER(candidate_name) = ?', [strtolower($c['candidate_name'])]);
                }
            })
            ->first();

        return [
            'order_found' => true,
            'entry_found' => (bool) $entry,
            'has_result' => $entry ? $entry->score !== null : false,
        ];
    }

    private function parseDate(string $raw): ?string
    {
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
