<?php

// database/seeders/TodayTasksSeeder.php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TodayTasksSeeder extends Seeder
{
    /**
     * Seed tasks completed in today's session (1 April 2026).
     * Safe to re-run: skips tasks that already exist by title.
     */
    public function run(): void
    {
        $tasks = [
            [
                'title' => 'Build task management system in admin panel',
                'detail' => 'Full CRUD: migration, model, controller, Index/Create/Edit pages, sidebar nav, toggle tick-off, filters, summary cards.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Remove /demo/constructors route',
                'detail' => 'Component demo page was publicly accessible. Removed from web.php.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Fix hardcoded colours on Welcome page',
                'detail' => 'Replaced all hex values, slate-*, blue-* with brand tokens throughout Welcome.vue.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Fix footer navigation (only shows Home)',
                'detail' => 'Added Why Use This Page, Incentives, FAQ (anchor links) and Book Your Exam (external) to navigation.ts.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Add footer to Welcome page',
                'detail' => 'Imported MyFooter.vue and added at bottom of Welcome page. Now has copyright, social links, and navigation.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Add musicexams.help email to Apple Mail on Mac',
                'detail' => 'Google Workspace account added to Apple Mail via OAuth. All email accounts now in one app.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Add smooth toggle animations to task list',
                'detail' => 'Tasks grey out with "Done!" flash, then slide to bottom after 1.5s. Same for reopening with "Reopened!" flash.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => now(),
            ],
            [
                'title' => 'Add today-completed highlighting to task list',
                'detail' => 'Tasks completed today show light blue background with blue left border and "Today" badge. Older completions stay grey.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => now(),
            ],
        ];

        $sortOrder = Task::max('sort_order') + 1;

        foreach ($tasks as $taskData) {
            $taskData['sort_order'] = $sortOrder++;
            Task::firstOrCreate(
                ['title' => $taskData['title']],
                $taskData
            );
        }
    }
}
