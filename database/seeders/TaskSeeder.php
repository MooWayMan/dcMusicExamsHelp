<?php

// database/seeders/TaskSeeder.php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Seed launch readiness checklist tasks.
     * Safe to re-run: skips tasks that already exist by title.
     */
    public function run(): void
    {
        $tasks = [
            // HIGH priority — Spider-Man tasks
            [
                'title' => 'Add footer to Welcome page',
                'detail' => 'Homepage has no footer — no copyright, social links, or navigation at the bottom. Every other page has one.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 1,
            ],
            [
                'title' => 'Fix hardcoded colours on Welcome page',
                'detail' => 'Why cards use hex values (#1e3a8a, #dbeafe etc.) and Tailwind blues instead of brand tokens. Breaks re-skinning.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 2,
            ],
            [
                'title' => 'Remove /demo/constructors route',
                'detail' => 'Component demo page is publicly accessible. Remove or protect behind admin auth.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 3,
            ],
            [
                'title' => 'Fix footer navigation (only shows Home)',
                'detail' => 'Footer Explore section has a single Home button — looks unfinished. Add section links or hide.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 4,
            ],

            // MEDIUM priority
            [
                'title' => 'S3 bucket named moowaymusicbucket',
                'detail' => 'Images work fine but bucket name has old MooWay branding in page source. Not visible to users.',
                'priority' => 'medium',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'sort_order' => 5,
            ],
            [
                'title' => 'Update production MAIL_FROM_ADDRESS',
                'detail' => 'Currently hello@example.com on Laravel Cloud. Should be musicexams@musicexams.help.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 6,
            ],
            [
                'title' => "User Dashboard says 'being set up'",
                'detail' => 'Non-admin users see placeholder message. Fine for soft launch but needs content before teacher logins.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'sort_order' => 7,
            ],
            [
                'title' => 'Registration is publicly enabled',
                'detail' => "Anyone can create an account. Consider disabling for launch if teacher logins aren't ready.",
                'priority' => 'medium',
                'assigned_to' => 'Paul + SM',
                'category' => 'launch',
                'sort_order' => 8,
            ],

            // LOW priority
            [
                'title' => 'Create MusicExams.help Facebook page',
                'detail' => 'Needed for social media launch announcement. Create from Paul Sheza personal account.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'marketing',
                'sort_order' => 9,
            ],
            [
                'title' => 'Check Trinity Centre 120 logo colour',
                'detail' => "Filename says 'purple' — check it isn't actually purple (brand guidelines say no purple).",
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'sort_order' => 10,
            ],
            [
                'title' => 'Update Mailchimp newsletter branding',
                'detail' => 'Old teacher mailing list references moowaymusic.com. Update to MusicExams.help when ready.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'marketing',
                'sort_order' => 11,
            ],

            // Already completed tasks
            [
                'title' => 'Add Gmail to Apple Mail',
                'detail' => "musicexams@musicexams.help added to Apple Mail on Mac — all accounts in one place.",
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'sort_order' => 12,
                'status' => 'completed',
                'completed_at' => now(),
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::firstOrCreate(
                ['title' => $taskData['title']],
                $taskData
            );
        }
    }
}
