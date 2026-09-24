<?php

// app/Console/Commands/GenerateQuarterCertificates.php

namespace App\Console\Commands;

use App\Services\QuarterCertificateBatch;
use Illuminate\Console\Command;

/**
 * The same quarter batch as the "Generate All Certificates" button, from the
 * command line. A command has no web timeout, so this is the fallback if the
 * button ever struggles.
 */
class GenerateQuarterCertificates extends Command
{
    protected $signature = 'certificates:generate
                            {--quarter= : Quarter number (1-4)}
                            {--year= : Year}';

    protected $description = "Generate a quarter's certificates, one ZIP per teacher plus a master ZIP";

    public function handle(QuarterCertificateBatch $batch): int
    {
        $quarter = (int) $this->option('quarter');
        $year = (int) $this->option('year');

        if ($quarter < 1 || $quarter > 4 || $year < 2025) {
            $this->error('Give --quarter=1..4 and --year, e.g. --quarter=2 --year=2026');

            return self::FAILURE;
        }

        $plan = $batch->start($quarter, $year);
        if ($plan === null) {
            $this->warn('No entries with results found for '.QuarterCertificateBatch::label($quarter, $year).'.');

            return self::SUCCESS;
        }

        $this->info("Generating certificates for {$plan['quarter_label']}");
        $bar = $this->output->createProgressBar(count($plan['steps']));
        foreach ($plan['steps'] as $step) {
            $batch->step($quarter, $year, $step['teacher'], $step['part']);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $result = $batch->finish($quarter, $year);
        $this->table(['Teacher', 'Certificates', 'ZIP'], collect($result['teachers'])
            ->map(fn ($count, $teacher) => [$teacher, $count, $result['download_links'][$teacher] ?? '-'])
            ->values()
            ->all());
        $this->info("{$result['total']} certificates. Master ZIP: ".($result['master_zip'] ?? 'none'));

        return self::SUCCESS;
    }
}
