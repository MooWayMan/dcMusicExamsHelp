<?php

// app/Console/Commands/DeleteTask.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class DeleteTask extends Command
{
    protected $signature = 'task:delete
        {ids : Comma-separated task IDs to soft-delete}
        {--force : Permanently delete instead of soft-delete}';

    protected $description = 'Soft-delete (or permanently delete) one or more tasks';

    public function handle(): int
    {
        $ids = array_filter(array_map('trim', explode(',', $this->argument('ids'))));

        if (empty($ids)) {
            $this->error('Provide at least one task ID.');
            return Command::FAILURE;
        }

        $deleted = 0;

        foreach ($ids as $id) {
            $task = Task::withTrashed()->find($id);

            if (! $task) {
                $this->warn("#{$id}: Not found — skipped");
                continue;
            }

            if ($this->option('force')) {
                $this->info("#{$id}: Permanently deleted — {$task->title}");
                $task->forceDelete();
            } else {
                if ($task->trashed()) {
                    $this->warn("#{$id}: Already trashed — {$task->title}");
                    continue;
                }
                $this->info("#{$id}: Soft-deleted — {$task->title}");
                $task->delete();
            }

            $deleted++;
        }

        $method = $this->option('force') ? 'permanently deleted' : 'soft-deleted';
        $this->info("{$deleted} task(s) {$method}.");

        return Command::SUCCESS;
    }
}
