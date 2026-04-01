<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Mark completed tasks (using the ACTUAL titles in DB)
        // -------------------------------------------------------
        $completed = [
            // From original TaskSeeder
            'Add footer to Welcome page',
            'Fix hardcoded colours on Welcome page',
            'Remove /demo/constructors route',
            'Fix footer navigation (only shows Home)',
            // From TodayTasksSeeder
            'Build task management system in admin panel',
            'Add musicexams.help email to Apple Mail on Mac',
            'Add smooth toggle animations to task list',
            'Add today-completed highlighting to task list',
            // From previous TaskUpdateSeeder run (different titles)
            'Add branded footer with gradient bar and social links',
            'Apply brand colour tokens to all components',
            'Set up /constructors-demo route for testing',
            'Build responsive nav with mobile hamburger menu',
            'Add page transition animations (fade-up stagger)',
            'Configure Postmark transactional email',
            'Restyle auth pages with brand constructors',
            'Fix auth layout rendering (authConfig composable)',
            'Set up Google Calendar voice-to-task sync',
            'Configure iPhone calendar sync with Google',
            'Build API endpoint for calendar task creation',
            'Add Gmail to Apple Mail',
        ];

        $markedDone = 0;
        foreach ($completed as $title) {
            $updated = Task::where('title', $title)->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $markedDone += $updated;
        }

        // -------------------------------------------------------
        // 2. Remove duplicates created by previous seeder run
        //    (these have different titles for the same work)
        // -------------------------------------------------------
        $duplicates = [
            'Add branded footer with gradient bar and social links',
            'Apply brand colour tokens to all components',
            'Set up /constructors-demo route for testing',
            'Build responsive nav with mobile hamburger menu',
            'Add page transition animations (fade-up stagger)',
            'Configure Postmark transactional email',
            'Restyle auth pages with brand constructors',
            'Fix auth layout rendering (authConfig composable)',
            'Set up Google Calendar voice-to-task sync',
            'Configure iPhone calendar sync with Google',
            'Build API endpoint for calendar task creation',
        ];

        $deleted = Task::whereIn('title', $duplicates)->delete();

        // -------------------------------------------------------
        // 3. Ensure pending tasks exist with correct priorities
        // -------------------------------------------------------
        $pending = [
            ['title' => 'Set up S3 bucket for file uploads', 'priority' => 'high', 'category' => 'technical'],
            ['title' => 'Set MAIL_FROM_ADDRESS in production', 'priority' => 'high', 'category' => 'technical'],
            ['title' => 'Build user dashboard with exam entry tracking', 'priority' => 'high', 'category' => 'launch'],
            ['title' => 'Toggle registration on/off for launch', 'priority' => 'medium', 'category' => 'launch'],
            ['title' => 'Create MusicExams.help Facebook page', 'priority' => 'medium', 'category' => 'marketing'],
            ['title' => 'Add Trinity College London logo (with permission)', 'priority' => 'medium', 'category' => 'content'],
            ['title' => 'Migrate Mailchimp contacts to new list', 'priority' => 'medium', 'category' => 'marketing'],
            ['title' => 'Fix Piano app staging security (no auth on songs)', 'priority' => 'high', 'category' => 'technical'],
            ['title' => 'Sync auth page restyling to dcTemplate repo', 'priority' => 'low', 'category' => 'technical'],
            ['title' => 'Add dashboard quarter/year time filters', 'priority' => 'low', 'category' => 'launch'],
            ['title' => 'Clean up HubSpot contacts (72 mixed records)', 'priority' => 'low', 'category' => 'admin'],
        ];

        foreach ($pending as $task) {
            Task::updateOrCreate(
                ['title' => $task['title']],
                [
                    'status' => 'pending',
                    'priority' => $task['priority'],
                    'category' => $task['category'],
                    'assigned_to' => 'Paul',
                    'detail' => 'Added via launch checklist',
                ]
            );
        }

        $this->command->info("Done: {$markedDone} marked completed, {$deleted} duplicates removed, " . count($pending) . ' pending ensured.');
    }
}
