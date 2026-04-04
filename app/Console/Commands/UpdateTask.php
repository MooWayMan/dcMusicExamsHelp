<?php

// app/Console/Commands/UpdateTask.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class UpdateTask extends Command
{
    protected $signature = 'task:update
        {id : The task ID to update}
        {--title= : New title}
        {--detail= : New detail text}
        {--priority= : high, medium, or low}
        {--category= : launch, admin, content, marketing, technical, or other}
        {--assigned= : Who is assigned}
        {--status= : pending, in_progress, or completed}';

    protected $description = 'Update any field on an existing task';

    public function handle(): int
    {
        $task = Task::find($this->argument('id'));

        if (! $task) {
            $this->error("Task #{$this->argument('id')} not found.");
            return Command::FAILURE;
        }

        $fields = ['title', 'detail', 'priority', 'category', 'assigned_to' => 'assigned', 'status'];
        $updates = [];

        foreach ($fields as $column => $option) {
            if (is_int($column)) {
                $column = $option;
            }
            $value = $this->option($option);
            if ($value !== null) {
                $updates[$column] = $value;
            }
        }

        if ($updates['status'] ?? null === 'completed') {
            $updates['completed_at'] = now();
        }

        if (empty($updates)) {
            $this->warn("No updates provided. Use --title, --detail, --priority, --category, --assigned, or --status.");
            $this->info("Current: #{$task->id} [{$task->status}] {$task->title}");
            return Command::FAILURE;
        }

        $task->update($updates);

        $this->info("#{$task->id}: Updated — {$task->fresh()->title}");
        foreach ($updates as $field => $value) {
            $this->line("  {$field}: {$value}");
        }

        return Command::SUCCESS;
    }
}
