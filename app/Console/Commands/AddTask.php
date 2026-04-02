<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class AddTask extends Command
{
    protected $signature = 'task:add
        {title? : The task title (omit to enter multiple tasks)}
        {--detail= : Extra detail or notes}
        {--notes= : Journal notes (context, time spent, decisions)}
        {--priority=medium : high, medium, or low}
        {--category=technical : launch, admin, content, marketing, technical, or other}
        {--assigned=Paul & Spider-Man : Who is assigned}
        {--done : Mark as completed immediately}
        {--batch= : Add multiple tasks at once, separated by :: e.g. "Task one::Task two::Task three"}';

    protected $description = 'Quickly add one or more tasks to the task manager';

    public function handle(): int
    {
        $titles = [];

        if ($this->option('batch')) {
            $titles = array_filter(array_map('trim', explode('::', $this->option('batch'))));
        } elseif ($this->argument('title')) {
            $titles = [$this->argument('title')];
        } else {
            $this->error('Provide a title or use --batch="Task one|Task two"');
            return Command::FAILURE;
        }

        foreach ($titles as $title) {
            $task = Task::create([
                'title' => $title,
                'detail' => $this->option('detail') ?? '',
                'notes' => $this->option('notes') ?? null,
                'priority' => $this->option('priority'),
                'category' => $this->option('category'),
                'assigned_to' => $this->option('assigned'),
                'status' => $this->option('done') ? 'completed' : 'pending',
                'completed_at' => $this->option('done') ? now() : null,
                'sort_order' => Task::max('sort_order') + 1,
            ]);

            $status = $this->option('done') ? 'completed' : 'pending';
            $this->info("#{$task->id}: {$task->title} [{$status}]");
        }

        $this->info(count($titles) . ' task(s) added.');

        return Command::SUCCESS;
    }
}
