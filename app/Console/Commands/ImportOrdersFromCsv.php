<?php
// app/Console/Commands/ImportOrdersFromCsv.php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportOrdersFromCsv extends Command
{
    protected $signature = 'orders:import-csv
                            {file=enrolementsNow.csv : Path to the orders export file}
                            {--year=2026 : Only import rows from this year}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Import orders from a Trinity export file, filtered by year';

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

        // Convert UTF-16 / UTF-16LE / UTF-16BE to UTF-8
        $encoding = mb_detect_encoding($raw, ['UTF-8', 'UTF-16', 'UTF-16LE', 'UTF-16BE'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }

        // Remove UTF-8 BOM if present
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
            'requested start date',
            'delivery method',
            'order #',
            'subject area',
            'candidates',
            'venue',
            'order status',
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

        foreach (array_slice($contents, 1) as $lineNumber => $line) {
            $row = str_getcsv($line, $delimiter);

            if (count($row) !== count($headers)) {
                $this->warn('Skipping malformed row at data line '.($lineNumber + 2));
                $skipped++;
                continue;
            }

            $data = array_combine($headers, $row);

            $orderNumber = trim((string) ($data['order #'] ?? ''));
            $rawDate = trim((string) ($data['requested start date'] ?? ''));

            if ($orderNumber === '' || $rawDate === '') {
                $skipped++;
                continue;
            }

            $parsedDate = $this->parseDate($rawDate);

            if (! $parsedDate) {
                $this->warn("Skipping row with unparseable date '{$rawDate}' for order {$orderNumber}");
                $skipped++;
                continue;
            }

            if ((int) $parsedDate->format('Y') !== $year) {
                $filteredOut++;
                continue;
            }

            $payload = [
                'trinity_order_number' => $orderNumber,
                'requested_start_date' => $parsedDate->toDateString(),
                'delivery_method' => trim((string) ($data['delivery method'] ?? '')),
                'subject_area' => trim((string) ($data['subject area'] ?? '')),
                'candidates' => is_numeric($data['candidates'] ?? null) ? (int) $data['candidates'] : null,
                'venue' => trim((string) ($data['venue'] ?? '')),
                'order_status' => trim((string) ($data['order status'] ?? '')),
            ];

            $existing = Order::where('trinity_order_number', $orderNumber)->first();

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would update order: {$orderNumber}");
                    $updated++;
                } else {
                    $this->line("Would create order: {$orderNumber}");
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
                Order::create($payload);
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
            ]
        );

        $this->info("Orders CSV import complete for year {$year}.");

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