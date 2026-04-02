<?php

// app/Console/Commands/UpdateTaskNotes.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class UpdateTaskNotes extends Command
{
    protected $signature = 'task:notes
        {id : The task ID}
        {--notes= : The notes to set (replaces existing notes)}
        {--append= : Append to existing notes instead of replacing}
        {--clear : Clear the notes field}';

    protected $description = 'Update notes on an existing task';

    public function handle(): int
    {
        $task = Task::find($this->argument('id'));

        if (! $task) {
            $this->error("Task #{$this->argument('id')} not found.");
            return Command::FAILURE;
        }

        if ($this->option('clear')) {
            $task->update(['notes' => null]);
            $this->info("#{$task->id}: Notes cleared — {$task->title}");
            return Command::SUCCESS;
        }

        if ($this->option('append')) {
            $existing = $task->notes ? $task->notes . "\n\n" : '';
            $task->update(['notes' => $existing . $this->option('append')]);
            $this->info("#{$task->id}: Notes appended — {$task->title}");
            return Command::SUCCESS;
        }

        if ($this->option('notes')) {
            $task->update(['notes' => $this->option('notes')]);
            $this->info("#{$task->id}: Notes updated — {$task->title}");
            return Command::SUCCESS;
        }

        // No option given — show current notes
        $this->info("#{$task->id}: {$task->title}");
        $this->info("Status: {$task->status}");
        $this->line($task->notes ?? '(no notes)');

        return Command::SUCCESS;
    }
}
