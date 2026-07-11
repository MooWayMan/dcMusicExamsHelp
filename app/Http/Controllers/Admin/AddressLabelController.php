<?php

// app/Http/Controllers/Admin/AddressLabelController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AddressLabelParser;
use App\Services\AddressLabelPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * /admin/labels — the F2F address-label converter.
 *
 * Paul drops Trinity's messy 8-up label PDFs (or a CSV, or types freestyle),
 * this cleans + de-duplicates them into an editable grid, and then reflows the
 * confirmed labels onto his Avery L7173 10-up sheets as a print-ready PDF.
 *
 * preview() returns JSON so the Vue grid can be edited before anything is
 * printed; pdf() takes the final, Paul-approved labels and streams the sheet.
 */
class AddressLabelController extends Controller
{
    public function __construct(
        private AddressLabelParser $parser = new AddressLabelParser(),
        private AddressLabelPdf $pdf = new AddressLabelPdf(),
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/Labels/Index');
    }

    /**
     * Parse uploaded PDFs and/or a CSV into a cleaned, de-duplicated set of
     * labels. Nothing is stored — the result feeds the editable grid.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'file|mimetypes:application/pdf|max:10240',
            'spreadsheet' => 'nullable|file|max:10240',
        ]);

        $labels = [];

        // PDFs — keyed by original filename so the grid can show the source.
        if (! empty($validated['files'])) {
            $paths = [];
            foreach ($request->file('files') as $file) {
                $paths[$file->getClientOriginalName()] = $file->getRealPath();
            }

            try {
                $labels = $this->parser->parseFiles($paths);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Could not read one of those PDFs. '.$e->getMessage()], 422);
            }
        }

        // Spreadsheet — CSV read directly; XLSX asks for a CSV to avoid a
        // heavyweight parser (see handover note).
        if ($request->hasFile('spreadsheet')) {
            $file = $request->file('spreadsheet');
            $ext = strtolower($file->getClientOriginalExtension());

            if (! in_array($ext, ['csv', 'txt'], true)) {
                return response()->json([
                    'error' => 'Please upload a CSV file (in Excel: File → Save As → CSV). One address per row, one address part per column.',
                ], 422);
            }

            $csvLabels = $this->parser->parseCsv((string) file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            $labels = $this->parser->dedupe(array_merge($labels, $csvLabels));
        }

        if ($labels === []) {
            return response()->json(['error' => 'No addresses found. Upload a Trinity label PDF or a CSV, or add labels by hand.'], 422);
        }

        return response()->json([
            'labels' => array_values($labels),
            'count' => count($labels),
            'flagged' => count(array_filter($labels, static fn (array $l): bool => $l['flag'] !== '')),
        ]);
    }

    /**
     * Render the final, edited labels onto L7173 sheets and stream the PDF.
     */
    public function pdf(Request $request): Response
    {
        $validated = $request->validate([
            'labels' => 'nullable|array',
            'labels.*' => 'array',
            'labels.*.*' => 'nullable|string|max:200',
        ]);

        $labels = [];
        foreach ($validated['labels'] ?? [] as $lines) {
            $lines = array_values(array_filter(
                array_map(static fn ($l): string => trim((string) $l), $lines),
                static fn (string $l): bool => $l !== '',
            ));
            if ($lines !== []) {
                $labels[] = $lines;
            }
        }

        if ($labels === []) {
            abort(422, 'Every label was empty.');
        }

        $bytes = $this->pdf->render($labels);
        $filename = 'address-labels-'.now()->format('Y-m-d').'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
