<?php
// app/Console/Commands/SyncContactPrimaryEmails.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncContactPrimaryEmails extends Command
{
    protected $signature = 'contacts:sync-primary-emails
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Ensure exam_contacts.email is reflected as is_primary=true in contact_emails. Fixes drift between edit form and show page.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $promoted = 0;
        $created = 0;
        $demoted = 0;
        $skipped = 0;

        $contacts = ExamContact::query()->with('emails')->orderBy('id')->get();

        foreach ($contacts as $contact) {
            $canonical = $contact->email !== null ? trim((string) $contact->email) : '';

            if ($canonical === '') {
                $skipped++;
                continue;
            }

            $wrongPrimaries = $contact->emails->where('is_primary', true)
                ->filter(fn ($e) => $e->email !== $canonical);

            $matching = $contact->emails->firstWhere('email', $canonical);
            $alreadyCorrect = $matching !== null
                && (bool) $matching->is_primary === true
                && $wrongPrimaries->isEmpty();

            if ($alreadyCorrect) {
                continue;
            }

            $this->line("Contact #{$contact->id} ({$contact->name}): set '{$canonical}' as primary"
                . ($wrongPrimaries->isNotEmpty() ? ", demote " . $wrongPrimaries->count() . " other(s)" : '')
                . ($matching === null ? ' (inserting new row)' : ''));

            if ($dryRun) {
                if ($wrongPrimaries->isNotEmpty()) {
                    $demoted += $wrongPrimaries->count();
                }
                if ($matching === null) {
                    $created++;
                } else {
                    $promoted++;
                }
                continue;
            }

            DB::transaction(function () use ($contact, $canonical, $wrongPrimaries, $matching, &$promoted, &$created, &$demoted): void {
                if ($wrongPrimaries->isNotEmpty()) {
                    $contact->emails()
                        ->where('is_primary', true)
                        ->where('email', '!=', $canonical)
                        ->update(['is_primary' => false]);
                    $demoted += $wrongPrimaries->count();
                }

                $row = $contact->emails()->updateOrCreate(
                    ['email' => $canonical],
                    ['is_primary' => true],
                );

                if ($row->wasRecentlyCreated) {
                    $created++;
                } else {
                    $promoted++;
                }
            });
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Primary promoted (existing row)', $promoted],
                ['Primary inserted (new row)', $created],
                ['Conflicting primaries demoted', $demoted],
                ['Skipped (no canonical email)', $skipped],
            ],
        );

        $this->info($dryRun ? 'Dry run complete.' : 'Primary email sync complete.');

        return self::SUCCESS;
    }
}
