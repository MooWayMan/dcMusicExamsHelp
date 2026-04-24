<?php

namespace App\Console\Commands;

use App\Models\ExamContact;
use Illuminate\Console\Command;

/**
 * Quick-add an ExamContact from the command line.
 *
 * Use when a teacher_name appears on exam_entries (e.g. "Claire Reed",
 * "Adrian O'Malley") but no corresponding ExamContact exists yet, so the
 * admin Parent-booking / email workflows can't find them.
 *
 * Usage:
 *   contacts:add "Claire Reed" parent reed@example.com
 *   contacts:add "Seth Barraclough" self seth@example.com
 *   contacts:add "New Teacher Name" teacher teacher@example.com
 *
 * Email is optional — pass '-' or leave blank to create without one.
 */
class AddExamContact extends Command
{
    protected $signature = 'contacts:add
                            {name : Full name (use quotes if it has spaces or apostrophes)}
                            {role : parent | self | teacher | applicant | admin}
                            {email? : Email address (optional)}';

    protected $description = 'Add a new ExamContact row (for parents/self/teachers stamped on exam_entries but missing from contacts)';

    public function handle(): int
    {
        $name = trim($this->argument('name'));
        $role = strtolower(trim($this->argument('role')));
        $email = $this->argument('email');
        if ($email === '-' || $email === '') {
            $email = null;
        }

        $validRoles = ['parent', 'self', 'teacher', 'applicant', 'admin'];
        if (! in_array($role, $validRoles, true)) {
            $this->error("Invalid role '{$role}'. Valid: " . implode(', ', $validRoles));
            return Command::FAILURE;
        }

        $existing = ExamContact::where('name', $name)->where('role', $role)->first();
        if ($existing) {
            $this->warn("{$name} already exists as {$role} (id={$existing->id}). Updating email only.");
            if ($email !== null) {
                $existing->update(['email' => $email]);
                $this->info("Updated email to {$email}.");
            }
            return Command::SUCCESS;
        }

        $contact = ExamContact::create([
            'name' => $name,
            'role' => $role,
            'email' => $email,
            'source' => 'manual',
        ]);

        $this->info("Created ExamContact id={$contact->id}: {$name} ({$role})" . ($email ? " — {$email}" : ' — no email'));
        return Command::SUCCESS;
    }
}
