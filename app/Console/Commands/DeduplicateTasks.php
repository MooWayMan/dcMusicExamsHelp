<?php

// app/Console/Commands/DeduplicateTasks.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class DeduplicateTasks extends Command
{
    protected $signature = 'task:dedupe {--dry-run : Show duplicates without deleting}';

    protected $description = 'Find and soft-delete duplicate tasks (keeps the oldest, removes newer copies)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $duplicates = 0;

        // Group tasks by title (case-insensitive)
        $tasks = Task::orderBy('created_at', 'asc')->get();
        $seen = [];

        foreach ($tasks as $task) {
            $key = strtolower(trim($task->title));

            if (isset($seen[$key])) {
                $duplicates++;
                if ($dryRun) {
                    $this->line("  DUPLICATE #{$task->id}: {$task->title} (keeping #{$seen[$key]})");
                } else {
                    $task->delete();
                    $this->line("  Removed #{$task->id}: {$task->title} (kept #{$seen[$key]})");
                }
            } else {
                $seen[$key] = $task->id;
            }
        }

        if ($duplicates === 0) {
            $this->info('No duplicates found.');
        } elseif ($dryRun) {
            $this->newLine();
            $this->info("{$duplicates} duplicate(s) found. Run without --dry-run to remove them.");
        } else {
            $this->newLine();
            $this->info("{$duplicates} duplicate(s) removed (soft-deleted).");
        }

        return Command::SUCCESS;
    }
}
