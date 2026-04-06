<?php

namespace App\Console\Commands;

use App\Models\DevActivityPing;
use App\Models\SessionLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Calculates daily working hours from dev activity pings and git commit timestamps.
 *
 * How it works:
 * 1. Collects all activity timestamps for a day (localhost pings + git commits)
 * 2. Groups them into "sessions" — a gap of 30+ minutes means a new session
 * 3. Each session runs from first ping to last ping (minimum 15 minutes)
 * 4. Sums all sessions for the day to get total hours
 * 5. Creates or updates the session_logs entry for that day
 *
 * Run manually: php artisan session:sync
 * Or schedule it nightly.
 */
class SyncSessionHours extends Command
{
    protected $signature = 'session:sync
        {--days=7 : How many days back to sync}
        {--force : Overwrite existing session log entries}
        {--dry-run : Show calculations without saving}';

    protected $description = 'Calculate session hours from dev activity pings and git commits';

    private const GAP_MINUTES = 30; // gap that splits sessions
    private const MIN_SESSION_MINUTES = 15; // minimum session length

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $this->info("Syncing session hours from {$startDate->toDateString()} to {$endDate->toDateString()}");

        // Get git commit timestamps
        $gitTimestamps = $this->getGitTimestamps($startDate);

        // Get dev activity pings
        $pings = DevActivityPing::where('pinged_at', '>=', $startDate)
            ->where('pinged_at', '<=', $endDate)
            ->orderBy('pinged_at')
            ->pluck('pinged_at')
            ->map(fn ($dt) => Carbon::parse($dt));

        // Group all timestamps by date
        $allTimestamps = $pings->merge($gitTimestamps)->sortBy(fn ($dt) => $dt->timestamp);

        $byDate = $allTimestamps->groupBy(fn (Carbon $dt) => $dt->toDateString());

        $rows = [];

        foreach ($byDate as $date => $timestamps) {
            $timestamps = $timestamps->sortBy(fn ($dt) => $dt->timestamp)->values();

            if ($timestamps->count() < 1) {
                continue;
            }

            // Split into sessions based on gaps
            $sessions = [];
            $sessionStart = $timestamps->first();
            $sessionEnd = $timestamps->first();

            foreach ($timestamps->slice(1) as $ts) {
                $gapMinutes = $sessionEnd->diffInMinutes($ts);

                if ($gapMinutes > self::GAP_MINUTES) {
                    // Save current session, start new one
                    $sessions[] = ['start' => $sessionStart, 'end' => $sessionEnd];
                    $sessionStart = $ts;
                }
                $sessionEnd = $ts;
            }
            // Don't forget the last session
            $sessions[] = ['start' => $sessionStart, 'end' => $sessionEnd];

            // Calculate total hours
            $totalMinutes = 0;
            $sessionDescriptions = [];

            foreach ($sessions as $session) {
                $minutes = max(self::MIN_SESSION_MINUTES, $session['start']->diffInMinutes($session['end']));
                $totalMinutes += $minutes;
                $sessionDescriptions[] = $session['start']->format('H:i') . '–' . $session['end']->format('H:i') . " ({$minutes}m)";
            }

            $hours = round($totalMinutes / 60, 1);
            $pingCount = $timestamps->filter(fn ($t) => $pings->contains(fn ($p) => $p->eq($t)))->count();
            $commitCount = $timestamps->filter(fn ($t) => $gitTimestamps->contains(fn ($g) => $g->eq($t)))->count();

            $notes = 'Auto-synced: ' . implode(', ', $sessionDescriptions)
                . " | {$pingCount} pings, {$commitCount} commits";

            $rows[] = [
                'date' => $date,
                'hours' => $hours,
                'sessions' => count($sessions),
                'notes' => $notes,
            ];

            if (! $dryRun) {
                $existing = SessionLog::where('date', $date)->first();

                if ($existing && ! $force) {
                    $this->line("  <comment>Skip {$date}</comment> — already logged ({$existing->hours}h). Use --force to overwrite.");
                    continue;
                }

                SessionLog::updateOrCreate(
                    ['date' => $date],
                    ['hours' => $hours, 'notes' => $notes],
                );

                $this->line("  <info>{$date}</info>: {$hours}h across " . count($sessions) . ' session(s)');
            }
        }

        if ($dryRun) {
            $this->table(
                ['Date', 'Hours', 'Sessions', 'Notes'],
                collect($rows)->map(fn ($r) => [$r['date'], $r['hours'], $r['sessions'], $r['notes']])->toArray()
            );
            $this->info('Dry run — nothing saved.');
        }

        $total = collect($rows)->sum('hours');
        $this->newLine();
        $this->info("Total: {$total} hours across " . count($rows) . ' day(s)');

        return self::SUCCESS;
    }

    /**
     * Parse git log for commit timestamps.
     */
    private function getGitTimestamps(Carbon $since): \Illuminate\Support\Collection
    {
        $output = [];
        $basePath = base_path();
        exec("cd {$basePath} && git log --format='%aI' --since='{$since->toIso8601String()}' 2>/dev/null", $output);

        return collect($output)
            ->filter()
            ->map(fn (string $line) => Carbon::parse(trim($line)))
            ->values();
    }
}
