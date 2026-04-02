<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class ManageTrashedTasks extends Command
{
    protected $signature = 'task:trashed
        {action=list : list, restore, or purge}
        {--id= : Task ID to restore or purge (use "all" to restore all)}';

    protected $description = 'View, restore, or permanently delete soft-deleted tasks';

    public function handle(): int
    {
        $action = $this->argument('action');

        if ($action === 'list') {
            return $this->listTrashed();
        }

        if ($action === 'restore') {
            return $this->restoreTasks();
        }

        if ($action === 'purge') {
            return $this->purgeTasks();
        }

        $this->error("Unknown action: {$action}. Use list, restore, or purge.");

        return Command::FAILURE;
    }

    private function listTrashed(): int
    {
        $tasks = Task::onlyTrashed()->orderBy('deleted_at', 'desc')->get();

        if ($tasks->isEmpty()) {
            $this->info('No trashed tasks found.');

            return Command::SUCCESS;
        }

        $this->info($tasks->count() . ' trashed task(s):');
        $this->newLine();

        foreach ($tasks as $task) {
            $this->line("  #{$task->id}: {$task->title}");
            $this->line("    Category: {$task->category} | Priority: {$task->priority} | Deleted: {$task->deleted_at->format('d M Y H:i')}");
            $this->newLine();
        }

        $this->info('To restore: task:trashed restore --id=123');
        $this->info('To restore all: task:trashed restore --id=all');

        return Command::SUCCESS;
    }

    private function restoreTasks(): int
    {
        $id = $this->option('id');

        if (! $id) {
            $this->error('Provide --id=123 or --id=all');

            return Command::FAILURE;
        }

        if ($id === 'all') {
            $count = Task::onlyTrashed()->count();
            Task::onlyTrashed()->restore();
            $this->info("Restored {$count} task(s).");

            return Command::SUCCESS;
        }

        $task = Task::onlyTrashed()->find($id);

        if (! $task) {
            $this->error("No trashed task found with ID {$id}.");

            return Command::FAILURE;
        }

        $task->restore();
        $this->info("Restored #{$task->id}: {$task->title}");

        return Command::SUCCESS;
    }

    private function purgeTasks(): int
    {
        $id = $this->option('id');

        if (! $id) {
            $this->error('Provide --id=123 or --id=all');

            return Command::FAILURE;
        }

        if ($id === 'all') {
            $count = Task::onlyTrashed()->count();
            Task::onlyTrashed()->forceDelete();
            $this->info("Permanently deleted {$count} task(s).");

            return Command::SUCCESS;
        }

        $task = Task::onlyTrashed()->find($id);

        if (! $task) {
            $this->error("No trashed task found with ID {$id}.");

            return Command::FAILURE;
        }

        $title = $task->title;
        $task->forceDelete();
        $this->info("Permanently deleted #{$id}: {$title}");

        return Command::SUCCESS;
    }
}
