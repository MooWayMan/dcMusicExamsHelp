<?php

// app/Console/Commands/ShowHallOfFame.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;

class ShowHallOfFame extends Command
{
    protected $signature = 'exam:hall-of-fame {--quarter=1} {--year=2026}';
    protected $description = 'Display the Hall of Fame data for a given quarter';

    public function handle(): int
    {
        $quarter = (int) $this->option('quarter');
        $year = (int) $this->option('year');

        $startMonth = ($quarter - 1) * 3 + 1;
        $startDate = sprintf('%d-%02d-01', $year, $startMonth);
        $endDate = sprintf('%d-%02d-31', $year, $startMonth + 2);

        $entries = ExamEntry::whereNotNull('score')
            ->where('exam_date', '>=', $startDate)
            ->where('exam_date', '<=', $endDate)
            ->orderByDesc('score')
            ->get();

        if ($entries->isEmpty()) {
            $this->warn("No results found for Q{$quarter} {$year}.");
            return Command::SUCCESS;
        }

        // ── Top Scorers ──
        $this->newLine();
        $this->info("══════════════════════════════════════════════════");
        $this->info("   🏆  HALL OF FAME — Q{$quarter} {$year}  🏆");
        $this->info("══════════════════════════════════════════════════");
        $this->newLine();

        $topDistinction = $entries->where('score', '>=', 87)->first();
        $topMerit = $entries->where('score', '>=', 75)->where('score', '<', 87)->first();

        if ($topDistinction) {
            $this->info("  ⭐ HIGHEST DISTINCTION");
            $this->line("     {$topDistinction->candidate_name}");
            $this->line("     {$topDistinction->grade} — Score: {$topDistinction->score}");
            $this->line("     Standing Ovation Certificate + Gift Token");
            $this->newLine();
        }

        if ($topMerit) {
            $this->info("  ⭐ HIGHEST MERIT");
            $this->line("     {$topMerit->candidate_name}");
            $this->line("     {$topMerit->grade} — Score: {$topMerit->score}");
            $this->line("     Take a Bow Certificate + Gift Token");
            $this->newLine();
        }

        // ── Distinctions (Standing Ovation) ──
        $distinctions = $entries->where('score', '>=', 87);
        $this->info("──────────────────────────────────────────────────");
        $this->info("  🎖  STANDING OVATION CERTIFICATES (Distinction)");
        $this->info("──────────────────────────────────────────────────");
        $this->table(
            ['Name', 'Instrument', 'Grade', 'Score', 'School'],
            $distinctions->map(fn ($e) => [
                $e->candidate_name,
                $e->instrument?->name ?? '—',
                $e->grade,
                $e->score,
                $e->school_name ?? '—',
            ])->toArray()
        );

        // ── Merits (Take a Bow) ──
        $merits = $entries->where('score', '>=', 75)->where('score', '<', 87);
        $this->newLine();
        $this->info("──────────────────────────────────────────────────");
        $this->info("  🎖  TAKE A BOW CERTIFICATES (Merit)");
        $this->info("──────────────────────────────────────────────────");
        $this->table(
            ['Name', 'Instrument', 'Grade', 'Score', 'School'],
            $merits->map(fn ($e) => [
                $e->candidate_name,
                $e->instrument?->name ?? '—',
                $e->grade,
                $e->score,
                $e->school_name ?? '—',
            ])->toArray()
        );

        // ── Everyone (Thank You page) ──
        $passes = $entries->where('score', '<', 75);
        $this->newLine();
        $this->info("──────────────────────────────────────────────────");
        $this->info("  ⭐ THANK YOU — ALL ENTRIES");
        $this->info("──────────────────────────────────────────────────");
        $this->table(
            ['Name', 'Instrument', 'Grade', 'Score', 'School'],
            $passes->map(fn ($e) => [
                $e->candidate_name,
                $e->instrument?->name ?? '—',
                $e->grade,
                $e->score,
                $e->school_name ?? '—',
            ])->toArray()
        );

        // ── Summary ──
        $this->newLine();
        $this->info("══════════════════════════════════════════════════");
        $this->table(
            ['', 'Count'],
            [
                ['Distinction (Standing Ovation)', $distinctions->count()],
                ['Merit (Take a Bow)', $merits->count()],
                ['Pass (Thank You)', $passes->count()],
                ['Total Entries', $entries->count()],
            ]
        );

        $this->info("  📊 Digital scores still pending (waiting for Jen's export)");
        $this->newLine();

        return Command::SUCCESS;
    }
}
