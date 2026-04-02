<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class AddTask extends Command
{
    protected $signature = 'task:add
        {title : The task title}
        {--detail= : Extra detail or notes}
        {--priority=medium : high, medium, or low}
        {--category=technical : launch, admin, content, marketing, technical, or other}
        {--assigned=Paul & Spider-Man : Who is assigned}
        {--done : Mark as completed immediately}';

    protected $description = 'Quickly add a task to the task manager';

    public function handle(): int
    {
        $task = Task::create([
            'title' => $this->argument('title'),
            'detail' => $this->option('detail') ?? '',
            'priority' => $this->option('priority'),
            'category' => $this->option('category'),
            'assigned_to' => $this->option('assigned'),
            'status' => $this->option('done') ? 'completed' : 'pending',
            'completed_at' => $this->option('done') ? now() : null,
            'sort_order' => Task::max('sort_order') + 1,
        ]);

        $status = $this->option('done') ? '✓ completed' : 'pending';
        $this->info("Task #{$task->id}: {$task->title} [{$status}]");

        return Command::SUCCESS;
    }
}
