<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskUpdateSeeder extends Seeder
{
    public function run(): void
    {
        $completed = [
            'Add branded footer with gradient bar and social links',
            'Apply brand colour tokens to all components',
            'Set up /constructors-demo route for testing',
            'Build responsive nav with mobile hamburger menu',
            'Build task management system in admin panel',
            'Add page transition animations (fade-up stagger)',
            'Configure Postmark transactional email',
            'Restyle auth pages with brand constructors',
            'Fix auth layout rendering (authConfig composable)',
            'Set up Google Calendar voice-to-task sync',
            'Configure iPhone calendar sync with Google',
            'Build API endpoint for calendar task creation',
        ];

        foreach ($completed as $title) {
            Task::updateOrCreate(
                ['title' => $title],
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'assigned_to' => 'Paul',
                ]
            );
        }

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

        $this->command->info('Task statuses updated: ' . count($completed) . ' completed, ' . count($pending) . ' pending.');
    }
}
