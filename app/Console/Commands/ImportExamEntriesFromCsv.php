<?php
// app/Console/Commands/ImportExamEntriesFromCsv.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportExamEntriesFromCsv extends Command
{
    protected $signature = 'exam-entries:import-csv
                            {file=imports/ResultsEnquiry.CSV : Path to the Trinity results enquiry export}
                            {--year=2026 : Only import rows from this year}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Import raw exam entries from a Trinity results enquiry CSV export';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));
        $year = (int) $this->option('year');
        $dryRun = (bool) $this->option('dry-run');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $raw = file_get_contents($file);

        if ($raw === false) {
            $this->error("Unable to read file: {$file}");
            return self::FAILURE;
        }

        $encoding = mb_detect_encoding($raw, ['UTF-8', 'UTF-16', 'UTF-16LE', 'UTF-16BE'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $contents = preg_split("/\r\n|\n|\r/", $raw);
        $contents = array_values(array_filter($contents, fn ($line) => trim((string) $line) !== ''));

        if (! $contents || count($contents) < 2) {
            $this->error('File is empty or does not contain enough rows.');
            return self::FAILURE;
        }

        $headerLine = $contents[0];
        $delimiter = str_contains($headerLine, "\t") ? "\t" : ',';

        $headers = str_getcsv($headerLine, $delimiter);
        $headers = array_map(function ($h) {
            $h = (string) $h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = str_replace('"', '', $h);
            $h = trim($h);
            $h = preg_replace('/\s+/', ' ', $h);

            return strtolower($h);
        }, $headers);

        $required = [
            'subject area',
            'examination date',
            'examination',
            'candidate number',
            'candidate',
            'school',
            'teacher first name',
            'teacher last name',
            'status',
            'result',
            'order number',
        ];

        foreach ($required as $column) {
            if (! in_array($column, $headers, true)) {
                $this->error("Missing required column: {$column}");
                return self::FAILURE;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $filteredOut = 0;
        $missingOrders = 0;

        foreach (array_slice($contents, 1) as $lineNumber => $line) {
            $row = str_getcsv($line, $delimiter);

            if (count($row) !== count($headers)) {
                $this->warn('Skipping malformed row at data line '.($lineNumber + 2));
                $skipped++;
                continue;
            }

            $data = array_combine($headers, $row);

            $orderNumber = trim((string) ($data['order number'] ?? ''));
            $candidateNumber = trim((string) ($data['candidate number'] ?? ''));
            $candidateName = trim((string) ($data['candidate'] ?? ''));
            $rawDate = trim((string) ($data['examination date'] ?? ''));

            if ($orderNumber === '' || $candidateNumber === '' || $candidateName === '' || $rawDate === '') {
                $skipped++;
                continue;
            }

            $examDate = $this->parseDate($rawDate);

            if (! $examDate) {
                $this->warn("Skipping row with unparseable exam date '{$rawDate}' for candidate {$candidateNumber}");
                $skipped++;
                continue;
            }

            if ((int) $examDate->format('Y') !== $year) {
                $filteredOut++;
                continue;
            }

            $order = Order::where('trinity_order_number', $orderNumber)->first();

            if (! $order) {
                $missingOrders++;
                $this->warn("Order not found in refactor DB: {$orderNumber}");
                continue;
            }

            $teacherFirst = trim((string) ($data['teacher first name'] ?? ''));
            $teacherLast = trim((string) ($data['teacher last name'] ?? ''));
            $teacherNameRaw = trim(trim($teacherFirst.' '.$teacherLast));

            $payload = [
                'order_id' => $order->id,
                'candidate_number' => $candidateNumber,
                'candidate_name' => $candidateName,
                'subject_area' => trim((string) ($data['subject area'] ?? '')),
                'delivery_method' => $order->delivery_method,
                'exam_date' => $examDate->toDateString(),
                'teacher_name' => $teacherNameRaw !== '' ? $teacherNameRaw : null,
                'school_name' => trim((string) ($data['school'] ?? '')) ?: null,
                'result' => trim((string) ($data['result'] ?? '')) ?: null,
                'notes' => trim((string) ($data['examination'] ?? '')) ?: null,
            ];

            $existing = ExamEntry::where('order_id', $order->id)
                ->where('candidate_number', $candidateNumber)
                ->first();

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would update entry: {$candidateNumber} ({$candidateName})");
                    $updated++;
                } else {
                    $this->line("Would create entry: {$candidateNumber} ({$candidateName})");
                    $created++;
                }
                continue;
            }

            if ($existing) {
                $existing->fill($payload);

                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                ExamEntry::create($payload);
                $created++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped/Unchanged', $skipped],
                ['Filtered Out (not target year)', $filteredOut],
                ['Missing Orders', $missingOrders],
            ]
        );

        $this->info("Exam entries CSV import complete for year {$year}.");

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s',
            'j/n/Y H:i:s',
            'd/m/Y',
            'j/n/Y',
            'Y-m-d',
            'd-m-Y',
            'j M Y',
            'j F Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}