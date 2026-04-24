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
                            {email? : Email address (optional)}
                            {--candidate= : Candidate name to also re-assign on exam_entries (optional, one-step workflow for orphans)}';

    protected $description = 'Add a new ExamContact row and optionally re-link a candidate\'s exam entry to it in one step';

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

        // Create or update the ExamContact (idempotent).
        $existing = ExamContact::where('name', $name)->where('role', $role)->first();
        if ($existing) {
            $this->warn("{$name} already exists as {$role} (id={$existing->id}).");
            if ($email !== null && $existing->email !== $email) {
                $existing->update(['email' => $email]);
                $this->info("Updated email to {$email}.");
            }
        } else {
            $contact = ExamContact::create([
                'name' => $name,
                'role' => $role,
                'email' => $email,
                'source' => 'manual',
            ]);
            $this->info("Created ExamContact id={$contact->id}: {$name} ({$role})" . ($email ? " — {$email}" : ' — no email'));
        }

        // Optional: also re-link a candidate's exam_entries.teacher_name to
        // this contact. Runs whether the contact was newly created or already
        // existed — useful if you're linking a second sibling to the same
        // parent, for example.
        $candidate = $this->option('candidate');
        if ($candidate) {
            $candidate = trim($candidate);
            $entries = \App\Models\ExamEntry::where('candidate_name', $candidate)->get();

            if ($entries->isEmpty()) {
                $this->warn("Candidate '{$candidate}' not found in exam_entries — no entries linked.");
            } else {
                foreach ($entries as $entry) {
                    $old = $entry->teacher_name ?? 'NULL';
                    $entry->update(['teacher_name' => $name]);
                    $this->line("  ✓ {$candidate} (entry #{$entry->id}) — teacher_name '{$old}' → '{$name}'");
                }
                $this->info("Linked {$entries->count()} entry/entries to {$name}.");
            }
        }

        return Command::SUCCESS;
    }
}
