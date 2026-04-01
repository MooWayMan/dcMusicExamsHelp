<?php

// database/seeders/TaskUpdateSeeder.php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskUpdateSeeder extends Seeder
{
    /**
     * Fix task statuses and add all work completed across sessions.
     * Uses updateOrCreate so it can be safely re-run.
     */
    public function run(): void
    {
        // ─── COMPLETED TASKS ──────────────────────────────────
        // All work done across our sessions — mark as completed

        $completedTasks = [
            // HIGH priority launch items — all done 1 April 2026
            [
                'title' => 'Add footer to Welcome page',
                'detail' => 'Imported MyFooter.vue and added at bottom of Welcome page. Now has copyright, social links, and navigation.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 14:00:00',
            ],
            [
                'title' => 'Fix hardcoded colours on Welcome page',
                'detail' => 'Replaced all hex values (#1e3a8a, #dbeafe etc.), slate-*, blue-* with brand tokens throughout Welcome.vue.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 14:00:00',
            ],
            [
                'title' => 'Remove /demo/constructors route',
                'detail' => 'Component demo page was publicly accessible. Removed from web.php.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 14:00:00',
            ],
            [
                'title' => 'Fix footer navigation (only shows Home)',
                'detail' => 'Added Why Use This Page, Incentives, FAQ (anchor links) and Book Your Exam (external) to navigation.ts.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 14:00:00',
            ],

            // Task management system
            [
                'title' => 'Build task management system in admin panel',
                'detail' => 'Full CRUD: migration, model, controller, Index/Create/Edit pages, sidebar nav, toggle tick-off, filters, summary cards.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => '2026-04-01 15:00:00',
            ],
            [
                'title' => 'Add smooth toggle animations to task list',
                'detail' => 'Tasks grey out with "Done!" flash, then slide to bottom after 1.5s. Same for reopening with "Reopened!" flash.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => '2026-04-01 16:00:00',
            ],
            [
                'title' => 'Add today-completed highlighting to task list',
                'detail' => 'Tasks completed today show light blue background with blue left border and "Today" badge. Older completions stay grey.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => '2026-04-01 16:00:00',
            ],

            // Email setup
            [
                'title' => 'Add Gmail to Apple Mail',
                'detail' => 'musicexams@musicexams.help Google Workspace account added to Apple Mail via OAuth. All email in one app.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 12:00:00',
            ],

            // Auth pages restyle
            [
                'title' => 'Restyle all auth pages with brand constructors',
                'detail' => 'Login, Register, Forgot Password, Reset Password — all restyled with MyInputConstructor, MyButtonConstructor, brand tokens. AuthSimpleLayout updated with logo and branded card wrapper.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 18:00:00',
            ],

            // Auth layout fix
            [
                'title' => 'Fix auth layout not rendering (missing logo/card/bg)',
                'detail' => 'defineOptions({ layout: { title, description } }) was setting a plain object instead of a component. Created useAuthConfig composable to bridge config from resolver to AuthLayout.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => '2026-04-01 19:00:00',
            ],

            // Google Calendar sync
            [
                'title' => 'Set up Google Calendar voice-to-task sync',
                'detail' => 'CarPlay/Siri: say "REMINDER fix X" → creates event in Google Calendar → scheduled Cowork task syncs to admin panel 3x daily.',
                'priority' => 'medium',
                'assigned_to' => 'Paul + SM',
                'category' => 'technical',
                'status' => 'completed',
                'completed_at' => '2026-04-01 17:00:00',
            ],

            // Google Calendar added to iPhone
            [
                'title' => 'Add Google Calendar to iPhone',
                'detail' => 'musicexams@musicexams.help Google account added to iOS with mail, contacts, and calendars enabled.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
                'status' => 'completed',
                'completed_at' => '2026-04-01 17:30:00',
            ],
        ];

        // ─── STILL PENDING TASKS ──────────────────────────────
        // These remain on the to-do list

        $pendingTasks = [
            [
                'title' => 'S3 bucket named moowaymusicbucket',
                'detail' => 'Images work fine but bucket name has old MooWay branding in page source. Not visible to users but should be tidied.',
                'priority' => 'medium',
                'assigned_to' => 'Paul',
                'category' => 'launch',
            ],
            [
                'title' => 'Update production MAIL_FROM_ADDRESS',
                'detail' => 'Currently hello@example.com on Laravel Cloud. Should be musicexams@musicexams.help.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
            ],
            [
                'title' => "User Dashboard says 'being set up'",
                'detail' => 'Non-admin users see placeholder message. Fine for soft launch but needs content before teacher logins.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'launch',
            ],
            [
                'title' => 'Registration is publicly enabled',
                'detail' => "Anyone can create an account. Consider disabling for launch if teacher logins aren't ready.",
                'priority' => 'medium',
                'assigned_to' => 'Paul + SM',
                'category' => 'launch',
            ],
            [
                'title' => 'Create MusicExams.help Facebook page',
                'detail' => 'Needed for social media launch announcement. Create from Paul Sheza personal account.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'marketing',
            ],
            [
                'title' => 'Check Trinity Centre 120 logo colour',
                'detail' => "Filename says 'purple' — check it isn't actually purple (brand guidelines say no purple).",
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'launch',
            ],
            [
                'title' => 'Update Mailchimp newsletter branding',
                'detail' => 'Old teacher mailing list references moowaymusic.com. Update to MusicExams.help when ready.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'marketing',
            ],
            [
                'title' => 'Add auth protection to Piano app staging',
                'detail' => 'Piano app on staging server has no authentication. Song files are publicly accessible. Add middleware/auth.',
                'priority' => 'high',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
            ],
            [
                'title' => 'Sync auth page restyling to dcTemplate repo',
                'detail' => 'AuthSimpleLayout.vue, Login.vue, Register.vue, ForgotPassword.vue, ResetPassword.vue + useAuthConfig composable need copying to MooWayMan/dcTemplate.',
                'priority' => 'medium',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
            ],
            [
                'title' => 'Add quarter/year time filters to orders dashboard',
                'detail' => 'Orders page needs filtering by quarter and year. Deferred until after logo work.',
                'priority' => 'low',
                'assigned_to' => 'Spider-Man',
                'category' => 'technical',
            ],
            [
                'title' => 'Clean up HubSpot contacts (72 mixed entries)',
                'detail' => 'Contacts are a mix of Trinity staff, teachers, and random business contacts from old Mailchimp import. Need sorting.',
                'priority' => 'low',
                'assigned_to' => 'Paul',
                'category' => 'marketing',
            ],
        ];

        $sortOrder = 1;

        // Process completed tasks
        foreach ($completedTasks as $taskData) {
            $taskData['sort_order'] = $sortOrder++;
            Task::updateOrCreate(
                ['title' => $taskData['title']],
                $taskData
            );
        }

        // Process pending tasks
        foreach ($pendingTasks as $taskData) {
            $taskData['sort_order'] = $sortOrder++;
            $taskData['status'] = $taskData['status'] ?? 'pending';
            Task::updateOrCreate(
                ['title' => $taskData['title']],
                $taskData
            );
        }
    }
}
