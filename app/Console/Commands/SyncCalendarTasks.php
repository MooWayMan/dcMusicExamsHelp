<?php

namespace App\Console\Commands;

use App\Models\Task;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCalendarTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync REMINDER events from Google Calendar into tasks table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Starting Google Calendar task sync...');

            // Get OAuth2 credentials
            $clientId = config('services.google_calendar.client_id');
            $clientSecret = config('services.google_calendar.client_secret');
            $refreshToken = config('services.google_calendar.refresh_token');
            $calendarId = config('services.google_calendar.calendar_id');

            if (!$clientId || !$clientSecret || !$refreshToken) {
                $this->error('Google Calendar OAuth credentials not configured');
                Log::error('SyncCalendarTasks: Missing OAuth credentials', [
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret),
                    'has_refresh_token' => !empty($refreshToken),
                ]);

                return Command::FAILURE;
            }

            if (!$calendarId) {
                $this->error('Google Calendar ID not configured');
                Log::error('SyncCalendarTasks: Calendar ID not configured');

                return Command::FAILURE;
            }

            // Initialize Google Calendar client with OAuth2
            $client = new GoogleClient();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setAccessType('offline');
            $client->addScope(GoogleCalendar::CALENDAR);
            $client->fetchAccessTokenWithRefreshToken($refreshToken);

            $calendarService = new GoogleCalendar($client);

            // Get events from last 7 days
            $sevenDaysAgo = now()->subDays(7)->toRfc3339String();
            $now = now()->toRfc3339String();

            $params = [
                'timeMin' => $sevenDaysAgo,
                'timeMax' => $now,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];

            $events = $calendarService->events->listEvents($calendarId, $params);

            $created = 0;
            $skipped = 0;
            $deleted = 0;
            $failed = 0;

            foreach ($events->getItems() as $event) {
                try {
                    $title = $event->getSummary();

                    // Check if title starts with REMINDER (case-insensitive)
                    if (!preg_match('/^reminder\s*:?\s*/i', $title)) {
                        continue;
                    }

                    // Strip REMINDER prefix
                    $cleanTitle = preg_replace('/^reminder\s*:?\s*/i', '', $title);
                    $cleanTitle = trim($cleanTitle);

                    if (empty($cleanTitle)) {
                        $this->warn("Skipping event with empty title after stripping REMINDER prefix: {$event->getId()}");
                        $skipped++;
                        continue;
                    }

                    $eventId = $event->getId();

                    // Check for duplicate by calendar_event_id
                    $existingByEventId = Task::where('calendar_event_id', $eventId)->first();
                    if ($existingByEventId) {
                        $this->line("Task already exists for event {$eventId}: skipping");
                        $skipped++;
                        continue;
                    }

                    // Check for duplicate by title created today
                    $existingByTitle = Task::where('title', $cleanTitle)
                        ->whereDate('created_at', now()->toDateString())
                        ->first();

                    if ($existingByTitle) {
                        $this->line("Task with same title created today: {$cleanTitle} - skipping");
                        $skipped++;
                        continue;
                    }

                    // Create the task
                    $task = Task::create([
                        'title' => $cleanTitle,
                        'detail' => 'Added via voice from Google Calendar',
                        'priority' => 'medium',
                        'assigned_to' => 'Paul',
                        'category' => 'other',
                        'calendar_event_id' => $eventId,
                        'sort_order' => Task::max('sort_order') + 1 ?? 1,
                    ]);

                    $this->info("Created task #{$task->id}: {$cleanTitle}");
                    $created++;

                    // Delete the calendar event
                    try {
                        $calendarService->events->delete($calendarId, $eventId);
                        $this->line("Deleted calendar event: {$eventId}");
                        $deleted++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to delete calendar event {$eventId}: {$e->getMessage()}");
                        Log::warning('SyncCalendarTasks: Failed to delete event', [
                            'event_id' => $eventId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing event: {$e->getMessage()}");
                    Log::error('SyncCalendarTasks: Error processing event', [
                        'event_id' => $event->getId() ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            // Summary
            $this->newLine();
            $this->info('Sync complete:');
            $this->line("  Created: {$created}");
            $this->line("  Skipped: {$skipped}");
            $this->line("  Deleted: {$deleted}");
            if ($failed > 0) {
                $this->line("  Failed: {$failed}");
            }

            Log::info('SyncCalendarTasks: Completed successfully', [
                'created' => $created,
                'skipped' => $skipped,
                'deleted' => $deleted,
                'failed' => $failed,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Fatal error during sync: {$e->getMessage()}");
            Log::error('SyncCalendarTasks: Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
