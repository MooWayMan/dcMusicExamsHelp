<?php

// app/Http/Controllers/Admin/CertificateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use Barryvdh\DomPDF\Facade\Pdf;
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
     */
    private const STUDENT_TEMPLATES = [
        'Bravo Certificate'            => 'certStu_1.png',
        'Take a Bow Certificate'       => 'certStu_2.png',
        'Standing Ovation Certificate'  => 'certStu_3.png',
        'Centre Stage Certificate'      => 'certStu_4.png',
        'Showstopper Certificate'       => 'certStu_5.png',
    ];

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
            $contact = ExamContact::withType('teacher')
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

        return Inertia::render('admin/Certificates/Index', [
            'students'          => $students,
            'teachers'          => $teachers,
            'studentTemplates'  => array_keys(self::STUDENT_TEMPLATES),
            'teacherTemplates'  => array_keys(self::TEACHER_TEMPLATES),
            'selectedQuarter'   => $quarter,
            'selectedYear'      => $year,
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

        // Get all entries with scores in this quarter
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
            return back()->with('error', "No entries with results found for {$quarterLabel}.");
        }

        // Group by teacher
        $grouped = $entries->groupBy(fn ($e) => $e->teacher_name ?? 'Unassigned');

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

        // Fetch ALL entries for the quarter (including those without scores) for pending counts
        $allQuarterEntries = ExamEntry::where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->with(['instrument:id,name', 'order:id,requested_start_date'])
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });
        $allGrouped = $allQuarterEntries->groupBy(fn ($e) => $e->teacher_name ?? 'Unassigned');

        // Generate teacher report PDFs and CSV spreadsheets
        foreach ($grouped as $teacher => $teacherEntries) {
            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";

            // Count pending entries for this teacher (entries without scores)
            $allTeacherEntries = $allGrouped->get($teacher, collect());
            $pendingEntries = $allTeacherEntries->filter(fn ($e) => $e->score === null);
            $pendingCount = $pendingEntries->count();

            // --- CSV Spreadsheet ---
            $csvRows = [];
            $csvRows[] = ['Student', 'Instrument', 'Grade', 'Score', 'Result', 'Certificate', 'Exam Date'];
            foreach ($teacherEntries->sortByDesc('score') as $entry) {
                $csvRows[] = [
                    $entry->candidate_name,
                    $entry->instrument?->name ?? '',
                    $entry->grade ?? '',
                    $entry->score,
                    $entry->result_band ?? $entry->result ?? '',
                    $entry->certificate_name ?? '',
                    ($entry->exam_date ?? $entry->order?->requested_start_date)?->format('j M Y') ?? '',
                ];
            }

            // Add pending entries to CSV (no score yet)
            if ($pendingCount > 0) {
                $csvRows[] = []; // blank row separator
                $csvRows[] = ['AWAITING RESULTS', '', '', '', '', '', ''];
                foreach ($pendingEntries->sortBy('candidate_name') as $entry) {
                    $csvRows[] = [
                        $entry->candidate_name,
                        $entry->instrument?->name ?? '',
                        $entry->grade ?? '',
                        '',
                        'Awaiting',
                        '',
                        ($entry->exam_date ?? $entry->order?->requested_start_date)?->format('j M Y') ?? '',
                    ];
                }
            }

            $csvContent = '';
            foreach ($csvRows as $row) {
                if (empty($row)) {
                    $csvContent .= "\n";
                    continue;
                }
                $csvContent .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
            }
            Storage::disk('local')->put("{$teacherDir}/{$safeTeacher}_Results.csv", $csvContent);

            // --- Teacher Report PDF ---
            $sorted = $teacherEntries->sortByDesc('score');
            $totalEntries = $sorted->count();
            $distinctions = $sorted->filter(fn ($e) => $e->score >= 87)->count();
            $merits = $sorted->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();
            $passes = $sorted->filter(fn ($e) => $e->score >= 60 && $e->score < 75)->count();

            $tableRows = '';
            foreach ($sorted as $entry) {
                $band = match (true) {
                    $entry->score >= 87 => 'Distinction',
                    $entry->score >= 75 => 'Merit',
                    default => 'Pass',
                };
                $bandColour = match ($band) {
                    'Distinction' => '#7a1f3d',
                    'Merit' => '#2a6e7a',
                    default => '#1e3a5f',
                };
                $tableRows .= '<tr>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;">' . e($entry->candidate_name) . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;">' . e($entry->instrument?->name ?? '') . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;">' . e($entry->grade ?? '') . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;">' . $entry->score . '</td>'
                    . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;color:' . $bandColour . ';font-weight:bold;">' . $band . '</td>'
                    . '</tr>';
            }

            // Add pending entries to table
            if ($pendingCount > 0) {
                $tableRows .= '<tr><td colspan="5" style="padding:12px;background:#fff8e1;font-weight:bold;color:#856404;border-bottom:1px solid #ddd;">Awaiting Results</td></tr>';
                foreach ($pendingEntries->sortBy('candidate_name') as $entry) {
                    $tableRows .= '<tr style="background:#fffdf5;">'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;">' . e($entry->candidate_name) . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;">' . e($entry->instrument?->name ?? '') . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;">' . e($entry->grade ?? '') . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;">—</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #ddd;text-align:center;color:#856404;font-style:italic;">Awaiting</td>'
                        . '</tr>';
                }
            }

            $reportHtml = '
            <html><head><style>
                @page { margin: 30px 40px; }
                body { font-family: Georgia, serif; color: #1e3a5f; font-size: 12px; }
                .header { background: linear-gradient(to right, #0f1b2d, #1a4a7a, #0f1b2d); padding: 20px 30px; text-align: center; margin: -30px -40px 20px -40px; }
                .header h1 { color: white; font-size: 22px; margin: 0; }
                .header p { color: rgba(255,255,255,0.8); font-size: 13px; margin: 5px 0 0; }
                .summary { display: flex; margin-bottom: 20px; }
                .summary-box { display: inline-block; padding: 8px 16px; margin-right: 10px; border-radius: 6px; font-weight: bold; font-size: 13px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background: #0f1b2d; color: white; padding: 10px 12px; text-align: left; font-size: 12px; }
                th:nth-child(3), th:nth-child(4), th:nth-child(5) { text-align: center; }
                tr:nth-child(even) { background: #f5f7fa; }
                .footer { margin-top: 20px; padding-top: 10px; border-top: 2px solid #1a4a7a; font-size: 10px; color: #666; text-align: center; }
            </style></head><body>
                <div class="header">
                    <h1>musicExams.help</h1>
                    <p>' . e($quarterLabel) . ' — Results Report for ' . e($teacher) . '</p>
                </div>

                <div style="margin-bottom:15px;">
                    <span class="summary-box" style="background:#f0e6ea;color:#7a1f3d;">' . $distinctions . ' Distinction' . ($distinctions !== 1 ? 's' : '') . '</span>
                    <span class="summary-box" style="background:#e6f0f2;color:#2a6e7a;">' . $merits . ' Merit' . ($merits !== 1 ? 's' : '') . '</span>
                    <span class="summary-box" style="background:#e8edf2;color:#1e3a5f;">' . $passes . ' Pass' . ($passes !== 1 ? 'es' : '') . '</span>
                    <span class="summary-box" style="background:#f5f5f5;color:#333;">' . $totalEntries . ' Total</span>
                    ' . ($pendingCount > 0 ? '<span class="summary-box" style="background:#fff8e1;color:#856404;">' . $pendingCount . ' Awaiting Results</span>' : '') . '
                </div>

                <table>
                    <thead><tr>
                        <th>Student</th><th>Instrument</th><th>Grade</th><th>Score</th><th>Result</th>
                    </tr></thead>
                    <tbody>' . $tableRows . '</tbody>
                </table>

                <div class="footer">
                    musicExams.help — Trinity College London Exam Centre 120<br>
                    This report was generated automatically. If you have any queries, please get in touch.
                </div>
            </body></html>';

            $reportPdf = Pdf::loadHTML($reportHtml)->setPaper('a4', 'portrait');
            Storage::disk('local')->put("{$teacherDir}/{$safeTeacher}_Report.pdf", $reportPdf->output());
        }

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

            // Badges reset per-quarter — count only this quarter's non-cancelled entries.
            $quarterCandidates = $teacherEntries->count();

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
     */
    private function getQuarterLabel(?\Carbon\Carbon $date): string
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
