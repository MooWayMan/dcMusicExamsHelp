<?php

// app/Http/Controllers/Admin/ReconciliationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportRun;
use App\Models\Order;
use App\Services\RemittanceParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /admin/reconciliation — drop a Trinity "Remittance Advice" PDF and mark the
 * orders it lists as commission-paid.
 *
 * Preview returns JSON so the Vue page can show a matched/mismatch/not-found
 * table before Paul presses Commit. Commit marks orders paid, stores the PDF
 * for the record, and logs an ImportRun (type=remittance). Mirrors the
 * preview→commit pattern of ImportController.
 */
class ReconciliationController extends Controller
{
    /** Paul's Trinity account code — used to flag a statement that isn't his. */
    private const ACCOUNT_CODE = '71-120';

    public function __construct(private RemittanceParser $parser = new RemittanceParser()) {}

    public function index(): Response
    {
        $recent = ImportRun::with('user:id,name')
            ->where('type', 'remittance')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (ImportRun $r) => [
                'id' => $r->id,
                'filename' => $r->filename,
                'summary' => $r->summary,
                'created_at' => $r->created_at?->format('d M Y H:i'),
                'user_name' => $r->user?->name,
            ]);

        return Inertia::render('admin/Reconciliation/Index', [
            'recent' => $recent,
        ]);
    }

    /**
     * JSON preview — parse the PDF and match each row against orders, without
     * writing anything.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimetypes:application/pdf|max:10240',
        ]);

        try {
            $parsed = $this->parser->parseFile($validated['file']->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not read this PDF. ' . $e->getMessage()], 422);
        }

        if (empty($parsed['rows'])) {
            return response()->json([
                'error' => 'No transaction rows found. Is this a Trinity Remittance Advice PDF?',
            ], 422);
        }

        return response()->json($this->buildPreview($parsed));
    }

    /**
     * Commit — re-parse the uploaded PDF (never trust the client), mark the
     * matched orders paid, store the PDF and log the run.
     */
    public function commit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimetypes:application/pdf|max:10240',
        ]);

        try {
            $parsed = $this->parser->parseFile($validated['file']->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read this PDF. ' . $e->getMessage()]);
        }

        if (empty($parsed['rows'])) {
            return back()->withErrors(['file' => 'No transaction rows found in this PDF.']);
        }

        $paidDate = $parsed['remittance_date'];
        if (! $paidDate) {
            return back()->withErrors(['file' => 'Could not read the Remittance Date from this PDF, so orders cannot be dated as paid.']);
        }

        $marked = 0;
        $alreadyPaid = 0;
        $notFound = [];

        DB::transaction(function () use ($parsed, $paidDate, &$marked, &$alreadyPaid, &$notFound) {
            foreach ($parsed['rows'] as $row) {
                // No unique constraint on trinity_order_number in prod, so mark
                // every matching row (mirrors orders:mark-paid).
                $orders = Order::where('trinity_order_number', $row['order_number'])->get();

                if ($orders->isEmpty()) {
                    $notFound[] = $row['order_number'];
                    continue;
                }

                foreach ($orders as $order) {
                    if ($order->isPaid()) {
                        $alreadyPaid++;
                        continue;
                    }
                    $order->update([
                        'commission_paid_at' => $paidDate,
                        'commission_paid_amount' => $row['gbp_amount'],
                    ]);
                    $marked++;
                }
            }
        });

        // Keep the statement on file. Private disk — financial document.
        $storedPath = null;
        try {
            $original = $validated['file']->getClientOriginalName();
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $original) ?: 'remittance.pdf';
            $storedPath = $validated['file']->storeAs(
                'remittances',
                $paidDate . '-' . $safe,
                'local',
            ) ?: null;
        } catch (\Throwable $e) {
            // Storing the PDF is a nice-to-have; don't fail the reconciliation
            // if the disk write hiccups — the orders are already marked.
            $storedPath = null;
        }

        ImportRun::create([
            'user_id' => $request->user()?->id,
            'type' => 'remittance',
            'filename' => $validated['file']->getClientOriginalName(),
            'summary' => [
                'remittance_date' => $paidDate,
                'account_code' => $parsed['account_code'],
                'total' => $parsed['total'],
                'rows_total' => count($parsed['rows']),
                'marked' => $marked,
                'already_paid' => $alreadyPaid,
                'not_found' => count($notFound),
                'not_found_orders' => $notFound,
                'stored_path' => $storedPath,
            ],
        ]);

        $msg = "Reconciled remittance dated {$paidDate}: {$marked} order(s) marked paid";
        if ($alreadyPaid > 0) {
            $msg .= ", {$alreadyPaid} already paid";
        }
        if (count($notFound) > 0) {
            $msg .= ', ' . count($notFound) . ' not found';
        }
        $msg .= '.';

        return redirect()->route('admin.reconciliation.index')->with('success', $msg);
    }

    /**
     * Shape the parsed statement into a preview payload: each row matched
     * against the orders table with a status the UI colour-codes.
     */
    private function buildPreview(array $parsed): array
    {
        $rows = [];
        $matchedSum = 0.0;
        $counts = ['matched' => 0, 'mismatch' => 0, 'already_paid' => 0, 'not_found' => 0];

        foreach ($parsed['rows'] as $row) {
            $orders = Order::where('trinity_order_number', $row['order_number'])->get();
            $gbp = (float) $row['gbp_amount'];

            if ($orders->isEmpty()) {
                $status = 'not_found';
                $expected = null;
            } else {
                $order = $orders->first();
                $expected = $order->commission_amount !== null ? (float) $order->commission_amount : null;

                if ($orders->every(fn (Order $o) => $o->isPaid())) {
                    $status = 'already_paid';
                } elseif ($expected !== null && abs($expected - $gbp) < 0.01) {
                    $status = 'matched';
                } else {
                    // Found and payable, but the expected commission is missing
                    // or differs from what Trinity paid — worth a human glance.
                    $status = 'mismatch';
                }
            }

            $counts[$status]++;
            if (in_array($status, ['matched', 'mismatch'], true)) {
                $matchedSum += $gbp;
            }

            $rows[] = [
                'order_number' => $row['order_number'],
                'transaction_date' => $row['transaction_date'],
                'description' => $row['description'],
                'paid_amount' => $gbp,
                'expected_amount' => $expected,
                'status' => $status,
                'order_id' => $orders->first()?->id,
                'duplicates' => $orders->count() > 1 ? $orders->count() : null,
            ];
        }

        $warnings = [];
        if ($parsed['account_code'] && $parsed['account_code'] !== self::ACCOUNT_CODE) {
            $warnings[] = "Account code on this statement is {$parsed['account_code']}, not your centre " . self::ACCOUNT_CODE . '. Check it is your remittance.';
        }
        if (! $parsed['remittance_date']) {
            $warnings[] = 'Could not read the Remittance Date — orders cannot be committed until this parses.';
        }

        return [
            'remittance_date' => $parsed['remittance_date'],
            'account_code' => $parsed['account_code'],
            'recipient_email' => $parsed['recipient_email'],
            'statement_total' => $parsed['total'],
            'matched_sum' => round($matchedSum, 2),
            'counts' => $counts,
            'rows' => $rows,
            'warnings' => $warnings,
            'can_commit' => $parsed['remittance_date'] !== null
                && ($counts['matched'] + $counts['mismatch']) > 0,
        ];
    }
}
