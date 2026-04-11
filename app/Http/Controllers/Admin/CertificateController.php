<?php

// app/Http/Controllers/Admin/CertificateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\User;
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
     */
    public function index(Request $request): Response
    {
        // Get exam entries with scores for student certificates
        $students = ExamEntry::whereNotNull('score')
            ->with(['student:id,first_name,last_name', 'instrument:id,name', 'order:id,requested_start_date'])
            ->orderBy('exam_date', 'desc')
            ->get()
            ->map(fn ($entry) => [
                'id'              => $entry->id,
                'candidate_name'  => $entry->candidate_name,
                'instrument'      => $entry->instrument?->name ?? 'Unknown',
                'grade'           => $entry->grade,
                'score'           => $entry->score,
                'result_band'     => $entry->result_band,
                'certificate'     => $entry->certificate_name,
                'exam_date'       => ($entry->exam_date ?? $entry->order?->requested_start_date)?->format('j F Y'),
            ]);

        // Get teachers with entry counts for teacher certificates
        $teachers = User::where('role', 'teacher')
            ->has('orders')
            ->withCount('orders')
            ->orderBy('name')
            ->get()
            ->map(fn ($teacher) => [
                'id'           => $teacher->id,
                'name'         => $teacher->name,
                'orders_count' => $teacher->orders_count,
                'tier'         => match (true) {
                    $teacher->orders_count >= 40 => 'Top Award',
                    $teacher->orders_count >= 30 => 'Gold',
                    $teacher->orders_count >= 20 => 'Silver',
                    $teacher->orders_count >= 10 => 'Bronze',
                    default => null,
                },
            ]);

        return Inertia::render('admin/Certificates/Index', [
            'students'          => $students,
            'teachers'          => $teachers,
            'studentTemplates'  => array_keys(self::STUDENT_TEMPLATES),
            'teacherTemplates'  => array_keys(self::TEACHER_TEMPLATES),
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
        ]);

        $entry = ExamEntry::with(['instrument', 'order:id,requested_start_date'])->findOrFail($validated['entry_id']);
        $templateKey = $validated['template'];

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

            return response((string) $encoded, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'attachment; filename="' . str_replace(' ', '_', $templateKey) . '_' . str_replace(' ', '_', $name) . '.png"',
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
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        $templateKey = $validated['template'];

        if (! isset(self::TEACHER_TEMPLATES[$templateKey])) {
            return back()->withErrors(['template' => 'Invalid template selected.']);
        }

        $name = $validated['custom_name'] ?? $teacher->name;
        $quarter = $validated['quarter'] ?? $this->getQuarterLabel(now());

        $templateUrl = self::S3_BASE . self::TEACHER_TEMPLATES[$templateKey];
        $image = $this->overlayTeacherText($templateUrl, $name, $quarter);

        $encoded = $image->encode(new PngEncoder());

        return response((string) $encoded, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . str_replace(' ', '_', $templateKey) . '_' . str_replace(' ', '_', $name) . '.png"',
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

        // Text sits centre-right (badge is on the left)
        $textX = (int) ($width * 0.55);

        // Font — Georgia preferred, DejaVu as fallback
        $fontPath = resource_path('fonts/Georgia.ttf');
        if (! file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        }
        if (! file_exists($fontPath)) {
            $fontPath = glob('/usr/share/fonts/truetype/*/*.ttf')[0] ?? null;
        }

        // Scale font sizes relative to image width
        $nameSize = (int) ($width * 0.035);
        $quarterSize = (int) ($width * 0.04);

        // Name — positioned at ~50% from top (centred in the empty space)
        $nameY = (int) ($height * 0.50);
        $image->text($name, $textX, $nameY, function (FontFactory $font) use ($fontPath, $nameSize) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($nameSize);
            $font->color('#1e3a5f');
            $font->align('center');
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
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry->candidate_name);
                    $shortCert = str_replace([' Certificate', ' '], ['', '_'], $certName);
                    $filename = "{$teacherDir}/{$safeName}_{$shortCert}.png";

                    Storage::disk('local')->put($filename, (string) $encoded);
                    $certCount++;
                    $totalGenerated++;
                } catch (\Throwable $e) {
                    \Log::error("Batch cert failed for {$entry->candidate_name}: {$e->getMessage()}");
                }
            }

            $teacherSummary[$teacher] = $certCount;
        }

        // Create ZIPs per teacher + master ZIP
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

        // Master ZIP
        $masterZipName = "{$outputDir}/ALL_Q{$quarter}_{$year}_Certificates.zip";
        $masterZipPath = Storage::disk('local')->path($masterZipName);
        $zip = new ZipArchive();

        if ($zip->open($masterZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($grouped as $teacher => $entries) {
                $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
                $teacherDir = "{$outputDir}/{$safeTeacher}";
                foreach (Storage::disk('local')->files($teacherDir) as $file) {
                    $zip->addFile(Storage::disk('local')->path($file), "{$safeTeacher}/" . basename($file));
                }
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
