<?php

// app/Console/Commands/CompleteTask.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class CompleteTask extends Command
{
    protected $signature = 'task:complete
        {ids : Comma-separated task IDs to mark as completed, e.g. 65,60,62}
        {--reopen : Reopen completed tasks instead of completing them}';

    protected $description = 'Mark one or more tasks as completed (or reopened with --reopen)';

    public function handle(): int
    {
        $ids = array_filter(array_map('trim', explode(',', $this->argument('ids'))));

        if (empty($ids)) {
            $this->error('Provide at least one task ID.');
            return Command::FAILURE;
        }

        $action = $this->option('reopen') ? 'reopen' : 'complete';
        $completed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $task = Task::find($id);

            if (! $task) {
                $this->warn("#{$id}: Not found — skipped");
                $skipped++;
                continue;
            }

            if ($action === 'complete') {
                if ($task->isCompleted()) {
                    $this->warn("#{$id}: Already done — {$task->title}");
                    $skipped++;
                    continue;
                }
                $task->markCompleted();
                $this->info("#{$id}: Completed — {$task->title}");
            } else {
                if (! $task->isCompleted()) {
                    $this->warn("#{$id}: Already active — {$task->title}");
                    $skipped++;
                    continue;
                }
                $task->markPending();
                $this->info("#{$id}: Reopened — {$task->title}");
            }

            $completed++;
        }

        $this->info("{$completed} task(s) {$action}d, {$skipped} skipped.");

        return Command::SUCCESS;
    }
}
