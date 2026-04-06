<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class ListTasks extends Command
{
    protected $signature = 'task:list
        {--status=pending : Filter by status (pending, in_progress, completed, all)}
        {--category= : Filter by category}
        {--limit=50 : Number of tasks to show}';

    protected $description = 'List tasks from the task manager';

    public function handle(): int
    {
        $query = Task::query();

        $status = $this->option('status');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($this->option('category')) {
            $query->where('category', $this->option('category'));
        }

        $tasks = $query->orderBy('sort_order')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks found.');
            return Command::SUCCESS;
        }

        $rows = $tasks->map(fn ($t) => [
            $t->id,
            $t->title,
            $t->status,
            $t->priority,
            $t->category,
            $t->assigned_to,
        ])->toArray();

        $this->table(
            ['ID', 'Title', 'Status', 'Priority', 'Category', 'Assigned'],
            $rows
        );

        $this->info($tasks->count() . ' task(s) shown.');

        return Command::SUCCESS;
    }
}
