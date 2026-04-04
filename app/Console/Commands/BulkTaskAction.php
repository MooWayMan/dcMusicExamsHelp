<?php

// app/Console/Commands/BulkTaskAction.php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class BulkTaskAction extends Command
{
    protected $signature = 'task:bulk
        {--json= : JSON array of actions}';

    protected $description = 'Bulk task operations - complete, update, delete in one command';

    public function handle(): int
    {
        $actions = json_decode($this->option('json'), true);

        if (! is_array($actions)) {
            $this->error('Invalid JSON. Provide an array of action objects.');
            $this->line('Example: [{"id":65,"action":"complete","notes":"Done"}]');
            return Command::FAILURE;
        }

        $completed = 0;
        $failed = 0;

        foreach ($actions as $i => $item) {
            $id = $item['id'] ?? null;
            $action = $item['action'] ?? 'update';

            if (! $id) {
                $this->warn("Item {$i}: Missing ID -skipped");
                $failed++;
                continue;
            }

            $task = Task::withTrashed()->find($id);

            if (! $task) {
                $this->warn("#{$id}: Not found -skipped");
                $failed++;
                continue;
            }

            // Build updates array from any provided fields
            $updates = [];
            foreach (['title', 'detail', 'priority', 'category', 'assigned_to'] as $field) {
                if (isset($item[$field])) {
                    $updates[$field] = $item[$field];
                }
            }

            // Handle notes (append or replace)
            if (isset($item['notes'])) {
                if (isset($item['append']) && $item['append']) {
                    $existing = $task->notes ? $task->notes . "\n\n" : '';
                    $updates['notes'] = $existing . $item['notes'];
                } else {
                    $updates['notes'] = $item['notes'];
                }
            }

            // Apply field updates first
            if (! empty($updates)) {
                $task->update($updates);
            }

            // Then handle the action
            switch ($action) {
                case 'complete':
                    if (! $task->isCompleted()) {
                        $task->markCompleted();
                    }
                    $this->info("#{$id}: Completed -{$task->fresh()->title}");
                    break;

                case 'delete':
                    $task->delete();
                    $this->info("#{$id}: Deleted -{$task->title}");
                    break;

                case 'reopen':
                    if ($task->isCompleted()) {
                        $task->markPending();
                    }
                    $this->info("#{$id}: Reopened -{$task->fresh()->title}");
                    break;

                case 'update':
                default:
                    $this->info("#{$id}: Updated -{$task->fresh()->title}");
                    break;
            }

            $completed++;
        }

        $this->info("Done: {$completed} processed, {$failed} skipped.");

        return Command::SUCCESS;
    }
}
