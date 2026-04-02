<?php

// app/Console/Commands/UpdateTaskNotes.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class UpdateTaskNotes extends Command
{
    protected $signature = 'task:notes
        {id? : The task ID (optional if using --search)}
        {--notes= : The notes to set (replaces existing notes)}
        {--append= : Append to existing notes instead of replacing}
        {--search= : Find task by title (case-insensitive partial match)}
        {--clear : Clear the notes field}
        {--list : List all tasks with their IDs}
        {--bulk= : JSON string of title:notes pairs to bulk-update}';

    protected $description = 'Update notes on existing tasks — single or bulk';

    public function handle(): int
    {
        // List mode — show all tasks with IDs for reference
        if ($this->option('list')) {
            $tasks = Task::orderBy('id')->get(['id', 'title', 'status', 'notes']);
            foreach ($tasks as $t) {
                $hasNotes = $t->notes ? ' [HAS NOTES]' : '';
                $this->line("#{$t->id} [{$t->status}] {$t->title}{$hasNotes}");
            }
            $this->info($tasks->count() . ' tasks total.');
            return Command::SUCCESS;
        }

        // Bulk mode — JSON string of {"title search": "notes", ...}
        if ($this->option('bulk')) {
            $pairs = json_decode($this->option('bulk'), true);
            if (! is_array($pairs)) {
                $this->error('Invalid JSON. Use: --bulk=\'{"task title": "notes text", ...}\'');
                return Command::FAILURE;
            }

            $updated = 0;
            $notFound = 0;
            foreach ($pairs as $search => $notes) {
                $task = Task::where('title', 'ilike', "%{$search}%")->first();
                if ($task) {
                    $task->update(['notes' => $notes]);
                    $this->info("#{$task->id}: Updated — {$task->title}");
                    $updated++;
                } else {
                    $this->warn("Not found: \"{$search}\"");
                    $notFound++;
                }
            }
            $this->info("{$updated} updated, {$notFound} not found.");
            return Command::SUCCESS;
        }

        // Find task by ID or search
        $task = null;
        if ($this->argument('id')) {
            $task = Task::find($this->argument('id'));
        } elseif ($this->option('search')) {
            $task = Task::where('title', 'ilike', '%' . $this->option('search') . '%')->first();
        }

        if (! $task) {
            $this->error('Task not found. Use --list to see all tasks, or --search="partial title"');
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

        // No option given — show current state
        $this->info("#{$task->id}: {$task->title}");
        $this->info("Status: {$task->status}");
        $this->line($task->notes ?? '(no notes)');

        return Command::SUCCESS;
    }
}
