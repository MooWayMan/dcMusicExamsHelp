<?php

// app/Services/EntryCertificates.php

namespace App\Services;

use App\Models\ExamEntry;
use App\Support\QuarterLabel;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * A candidate's certificate for their result, as a PDF, and a set of them as
 * a ZIP. The one place an entry becomes a certificate: the admin Certificates
 * page, the quarter batch and a teacher's own dashboard downloads all ask
 * here (tests/Feature/EntryCertificatesTest.php fails on a second copy).
 *
 * Which certificate is the entry's own (ExamEntry::certificate_name, from the
 * score). Top-scorer awards use their own templates and stay in
 * QuarterCertificateBatch.
 */
final class EntryCertificates
{
    public function __construct(private CertificateRenderer $renderer) {}

    /**
     * The certificate PDF, or null when the entry has no result yet or the
     * drawing fails. The quarter printed on it is the entry's own unless a
     * label is given.
     */
    public function pdf(ExamEntry $entry, ?string $quarterLabel = null): ?string
    {
        $file = CertificateRenderer::STUDENT_TEMPLATES[$entry->certificate_name ?? ''] ?? null;

        if (! $file) {
            return null;
        }

        $label = $quarterLabel ?? QuarterLabel::forDate($entry->exam_date ?? $entry->order?->requested_start_date);

        try {
            return $this->renderer->pdf($this->renderer->student(
                $file,
                (string) $entry->candidate_name,
                $entry->instrument?->name ?? '',
                (string) ($entry->grade ?? ''),
                $label,
            ));
        } catch (\Throwable $e) {
            Log::error("Certificate failed for entry {$entry->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Every drawable certificate among these entries, zipped. Null when none
     * could be drawn. Built in a temp folder that is always cleaned up.
     *
     * @param  iterable<ExamEntry>  $entries
     */
    public function zip(iterable $entries): ?string
    {
        $dir = sys_get_temp_dir().'/certs-'.uniqid('', true);
        if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            return null;
        }

        $files = [];
        foreach ($entries as $entry) {
            $pdf = $this->pdf($entry);
            if ($pdf === null) {
                continue;
            }
            $path = $dir.'/'.self::fileName((string) $entry->candidate_name, (string) $entry->certificate_name);
            // Two candidates with the same name get distinct files.
            if (in_array($path, $files, true)) {
                $path = substr($path, 0, -4).'_'.$entry->id.'.pdf';
            }
            file_put_contents($path, $pdf);
            $files[] = $path;
        }

        $bytes = null;
        if ($files) {
            $zipPath = $dir.'/certificates.zip';
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($files as $f) {
                    $zip->addFile($f, basename($f));
                }
                $zip->close();
                $bytes = file_get_contents($zipPath) ?: null;
                @unlink($zipPath);
            }
        }

        foreach ($files as $f) {
            @unlink($f);
        }
        @rmdir($dir);

        return $bytes;
    }

    /** "Megan_Roberts_TakeABow.pdf"-style name for a candidate's certificate. */
    public static function fileName(string $candidate, string $certName): string
    {
        return self::safe($candidate).'_'.str_replace([' Certificate', ' '], ['', '_'], $certName).'.pdf';
    }

    /** A name made safe for a file or folder. */
    public static function safe(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }
}
