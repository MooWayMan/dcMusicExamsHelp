<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Typography\FontFactory;
use ZipArchive;

class GenerateQ1Certificates extends Command
{
    protected $signature = 'certificates:generate-q1
                            {--quarter=1 : Quarter number (1-4)}
                            {--year=2026 : Year}
                            {--teacher= : Generate only for a specific teacher name}
                            {--dry-run : Show what would be generated without creating files}';

    protected $description = 'Batch generate all student certificates for a quarter, grouped by teacher in ZIP files';

    private const S3_BASE = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/';

    private const STUDENT_TEMPLATES = [
        'Bravo Certificate'            => 'certStu_1.png',
        'Take a Bow Certificate'       => 'certStu_2.png',
        'Standing Ovation Certificate'  => 'certStu_3.png',
    ];

    /** Cache downloaded template images to avoid re-downloading. */
    private array $templateCache = [];

    public function handle(): int
    {
        $quarter = (int) $this->option('quarter');
        $year = (int) $this->option('year');
        $teacherFilter = $this->option('teacher');
        $dryRun = $this->option('dry-run');

        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
        };
        $quarterLabel = "{$suffix} Quarter {$year}";

        // Work out date range for the quarter
        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = "{$year}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = \Carbon\Carbon::parse($startDate)->addMonths(3)->subDay()->toDateString();

        $this->info("Generating certificates for {$quarterLabel}");
        $this->info("Date range: {$startDate} to {$endDate}");
        $this->newLine();

        // Get all entries with scores for this quarter
        $entries = ExamEntry::whereNotNull('score')
            ->where('score', '>=', 60) // Only pass and above get certificates
            ->where('notes', '!=', 'CANCELLED')
            ->orWhereNull('notes')
            ->whereNotNull('score')
            ->where('score', '>=', 60)
            ->with(['instrument:id,name', 'order:id,requested_start_date'])
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                // Use exam_date or fall back to order's requested_start_date
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                if (! $date) {
                    return false;
                }
                return $date->between($startDate, $endDate);
            });

        if ($teacherFilter) {
            $entries = $entries->filter(fn ($e) => stripos($e->teacher_name ?? '', $teacherFilter) !== false);
        }

        if ($entries->isEmpty()) {
            $this->warn('No entries found for this quarter.');
            return Command::SUCCESS;
        }

        // Group by teacher
        $grouped = $entries->groupBy(fn ($e) => $e->teacher_name ?? 'Unassigned');

        $this->info("Found {$entries->count()} entries across {$grouped->count()} teacher(s):");
        $this->newLine();

        foreach ($grouped as $teacher => $teacherEntries) {
            $this->line("  {$teacher}: {$teacherEntries->count()} certificate(s)");
        }
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run — no files created.');
            $this->newLine();

            $this->table(
                ['Teacher', 'Student', 'Instrument', 'Grade', 'Score', 'Certificate'],
                $entries->map(fn ($e) => [
                    $e->teacher_name ?? 'Unassigned',
                    $e->candidate_name,
                    $e->instrument?->name ?? '?',
                    $e->grade,
                    $e->score,
                    $e->certificate_name,
                ])->toArray()
            );

            return Command::SUCCESS;
        }

        // Create output directory
        $outputDir = "certificates/{$year}-Q{$quarter}";
        Storage::disk('local')->makeDirectory($outputDir);

        $totalGenerated = 0;

        foreach ($grouped as $teacher => $teacherEntries) {
            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";
            Storage::disk('local')->makeDirectory($teacherDir);

            $this->info("Generating for {$teacher}...");
            $bar = $this->output->createProgressBar($teacherEntries->count());
            $bar->start();

            foreach ($teacherEntries as $entry) {
                $certName = $entry->certificate_name;

                if (! $certName || ! isset(self::STUDENT_TEMPLATES[$certName])) {
                    $bar->advance();
                    continue;
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
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry->candidate_name);
                    $filename = "{$teacherDir}/{$safeName}_{$certName}.png";
                    $filename = str_replace(' ', '_', $filename);

                    Storage::disk('local')->put($filename, (string) $encoded);
                    $totalGenerated++;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->error("  Failed: {$entry->candidate_name} — {$e->getMessage()}");
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("Generated {$totalGenerated} certificates in {$grouped->count()} teacher folder(s).");

        // Now create ZIPs per teacher
        $this->newLine();
        $this->info('Creating ZIP files per teacher...');

        $zipDir = "{$outputDir}/zips";
        Storage::disk('local')->makeDirectory($zipDir);
        $zipPaths = [];

        foreach ($grouped as $teacher => $teacherEntries) {
            $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
            $teacherDir = "{$outputDir}/{$safeTeacher}";
            $zipFilename = "{$zipDir}/{$safeTeacher}_Q{$quarter}_{$year}_Certificates.zip";
            $zipFullPath = Storage::disk('local')->path($zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error("Could not create ZIP for {$teacher}");
                continue;
            }

            $files = Storage::disk('local')->files($teacherDir);
            foreach ($files as $file) {
                $zip->addFile(
                    Storage::disk('local')->path($file),
                    basename($file)
                );
            }

            $zip->close();
            $zipPaths[$teacher] = $zipFullPath;
            $this->line("  Created: {$zipFilename}");
        }

        // Also create one master ZIP with all teacher folders
        $masterZip = "{$outputDir}/ALL_Certificates_Q{$quarter}_{$year}.zip";
        $masterZipPath = Storage::disk('local')->path($masterZip);
        $zip = new ZipArchive();

        if ($zip->open($masterZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($grouped as $teacher => $teacherEntries) {
                $safeTeacher = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teacher);
                $teacherDir = "{$outputDir}/{$safeTeacher}";
                $files = Storage::disk('local')->files($teacherDir);
                foreach ($files as $file) {
                    $zip->addFile(
                        Storage::disk('local')->path($file),
                        "{$safeTeacher}/" . basename($file)
                    );
                }
            }
            $zip->close();
            $this->newLine();
            $this->info("Master ZIP: {$masterZip}");
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Teacher', 'Certificates', 'ZIP'],
            $grouped->map(fn ($entries, $teacher) => [
                $teacher,
                $entries->count(),
                isset($zipPaths[$teacher]) ? 'Yes' : 'No',
            ])->values()->toArray()
        );

        $this->newLine();
        $this->info("All files stored in: storage/app/private/{$outputDir}/");
        $this->info("Download the master ZIP or individual teacher ZIPs from the server.");

        return Command::SUCCESS;
    }

    /**
     * Overlay student text onto certificate template.
     */
    private function overlayStudentText(string $templateUrl, string $name, string $instrument, string $grade, string $quarter)
    {
        // Cache templates to avoid re-downloading for each student
        if (! isset($this->templateCache[$templateUrl])) {
            $response = Http::get($templateUrl);
            if (! $response->successful()) {
                throw new \RuntimeException("Failed to download template: {$templateUrl}");
            }
            $this->templateCache[$templateUrl] = $response->body();
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->decode($this->templateCache[$templateUrl]);

        $width = $image->width();
        $height = $image->height();

        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $boldFontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

        $nameSize = (int) ($width * 0.038);
        $detailSize = (int) ($width * 0.028);
        $quarterSize = (int) ($width * 0.042);

        $rightTextX = (int) ($width * 0.92);

        // Name at 47%, right-aligned
        $nameY = (int) ($height * 0.47);
        $image->text($name, $rightTextX, $nameY, function (FontFactory $font) use ($fontPath, $nameSize) {
            $font->filename($fontPath);
            $font->size($nameSize);
            $font->color('#1e3a5f');
            $font->align('right');
        });

        // Instrument & Grade at 52%, right-aligned
        $detail = trim("{$instrument} Grade {$grade}");
        $detailY = (int) ($height * 0.52);
        $image->text($detail, $rightTextX, $detailY, function (FontFactory $font) use ($fontPath, $detailSize) {
            $font->filename($fontPath);
            $font->size($detailSize);
            $font->color('#1e3a5f');
            $font->align('right');
        });

        // Quarter at 96%, centre-aligned, bold
        $quarterX = (int) ($width * 0.50);
        $quarterY = (int) ($height * 0.96);
        $image->text($quarter, $quarterX, $quarterY, function (FontFactory $font) use ($boldFontPath, $quarterSize) {
            $font->filename($boldFontPath);
            $font->size($quarterSize);
            $font->color('#1e3a5f');
            $font->align('center');
        });

        return $image;
    }
}
