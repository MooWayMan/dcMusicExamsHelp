<?php

// app/Services/ContactMergeService.php

namespace App\Services;

use App\Models\ContactMergeDismissal;
use App\Models\ExamContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ContactMergeService
 * -------------------
 * Two jobs:
 *   1. Detect likely-duplicate contacts (fuzzy name match) for the banner.
 *   2. Merge one contact into another, repointing EVERY reference so nothing
 *      is orphaned, then soft-deleting the loser.
 *
 * Teachers routinely sign up under different emails, so the same human ends up
 * as several contacts. This collapses them without losing entries, orders,
 * emails, roles, school/instrument links or logs.
 */
class ContactMergeService
{
    /**
     * Contacts that look like duplicates of $contact, by fuzzy name match,
     * excluding pairs already dismissed as "not the same".
     *
     * @return Collection<int, array{contact: ExamContact, score: int}>
     */
    public function possibleDuplicatesFor(ExamContact $contact, int $threshold = 80): Collection
    {
        $target = $this->normalizeName($contact->name);

        if ($target === '') {
            return collect();
        }

        return ExamContact::where('id', '!=', $contact->id)
            ->get()
            ->map(function (ExamContact $other) use ($target) {
                $percent = 0.0;
                similar_text($target, $this->normalizeName($other->name), $percent);

                return ['contact' => $other, 'score' => (int) round($percent)];
            })
            ->filter(fn ($row) => $row['score'] >= $threshold
                && ! ContactMergeDismissal::isDismissed($contact->id, $row['contact']->id))
            ->sortByDesc('score')
            ->values();
    }

    /**
     * IDs of every contact that has at least one non-dismissed fuzzy duplicate,
     * computed in a single pass (for the contacts-list "possible duplicate"
     * flag). O(n²) name comparisons, fine for the low-hundreds of contacts.
     *
     * @return array<int, true> keyed by contact id for O(1) lookup
     */
    public function duplicateContactIds(int $threshold = 80): array
    {
        $rows = ExamContact::get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'norm' => $this->normalizeName($c->name)])
            ->filter(fn ($c) => $c['norm'] !== '')
            ->values()
            ->all();

        $dismissed = [];
        foreach (ContactMergeDismissal::get(['low_contact_id', 'high_contact_id']) as $d) {
            $dismissed[$d->low_contact_id.'-'.$d->high_contact_id] = true;
        }

        $flagged = [];
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $percent = 0.0;
                similar_text($rows[$i]['norm'], $rows[$j]['norm'], $percent);
                if ($percent < $threshold) {
                    continue;
                }
                [$low, $high] = ContactMergeDismissal::pair($rows[$i]['id'], $rows[$j]['id']);
                if (isset($dismissed[$low.'-'.$high])) {
                    continue;
                }
                $flagged[$rows[$i]['id']] = true;
                $flagged[$rows[$j]['id']] = true;
            }
        }

        return $flagged;
    }

    /**
     * Merge $drop INTO $keep. $keep survives; $drop is soft-deleted after all
     * its references are repointed. Runs in a transaction — all or nothing.
     */
    public function merge(ExamContact $keep, ExamContact $drop): void
    {
        if ($keep->id === $drop->id) {
            throw new \InvalidArgumentException('Cannot merge a contact into itself.');
        }

        DB::transaction(function () use ($keep, $drop): void {
            // Direct FK columns — a plain repoint, no uniqueness to worry about.
            DB::table('exam_entries')->where('teacher_contact_id', $drop->id)->update(['teacher_contact_id' => $keep->id]);
            DB::table('exam_entries')->where('submitter_contact_id', $drop->id)->update(['submitter_contact_id' => $keep->id]);
            DB::table('students')->where('teacher_contact_id', $drop->id)->update(['teacher_contact_id' => $keep->id]);
            DB::table('orders')->where('created_by_contact_id', $drop->id)->update(['created_by_contact_id' => $keep->id]);
            DB::table('contact_logs')->where('exam_contact_id', $drop->id)->update(['exam_contact_id' => $keep->id]);

            // Pivots with a unique constraint — repoint unless the keeper
            // already has the same row, in which case drop the duplicate.
            $this->foldPivot('order_contacts', $keep->id, $drop->id, fn ($r) => $r->order_id.'|'.$r->role_in_order);
            $this->foldPivot('contact_school', $keep->id, $drop->id, fn ($r) => (string) $r->school_id);
            $this->foldPivot('contact_instrument', $keep->id, $drop->id, fn ($r) => (string) $r->instrument_id);

            // Types are a union.
            foreach (DB::table('contact_types')->where('exam_contact_id', $drop->id)->pluck('type') as $type) {
                DB::table('contact_types')->updateOrInsert(
                    ['exam_contact_id' => $keep->id, 'type' => $type],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
            DB::table('contact_types')->where('exam_contact_id', $drop->id)->delete();

            $this->foldEmails($keep, $drop);
            $this->foldProfile($keep, $drop);

            $drop->delete();
        });
    }

    /**
     * Move drop's rows in a unique-constrained pivot onto keep, skipping any
     * the keeper already has (identified by $signature).
     */
    private function foldPivot(string $table, int $keepId, int $dropId, callable $signature): void
    {
        $existing = DB::table($table)->where('exam_contact_id', $keepId)->get()->map($signature)->all();

        foreach (DB::table($table)->where('exam_contact_id', $dropId)->get() as $row) {
            $sig = $signature($row);
            if (in_array($sig, $existing, true)) {
                DB::table($table)->where('id', $row->id)->delete();
            } else {
                DB::table($table)->where('id', $row->id)->update(['exam_contact_id' => $keepId]);
                $existing[] = $sig;
            }
        }
    }

    /**
     * Fold drop's emails onto keep as secondaries. The keeper's own primary is
     * never demoted — this is how "keep gmail primary, me.com becomes
     * secondary" works: view the gmail contact, merge the me.com one in.
     */
    private function foldEmails(ExamContact $keep, ExamContact $drop): void
    {
        $existing = DB::table('contact_emails')
            ->where('exam_contact_id', $keep->id)
            ->pluck('email')
            ->map(fn ($e) => mb_strtolower($e))
            ->all();

        // If the keeper has no contact_emails rows yet, seed its column email
        // as the primary so the folded ones show up as clearly secondary.
        if (empty($existing) && ! empty($keep->email)) {
            DB::table('contact_emails')->insert([
                'exam_contact_id' => $keep->id,
                'email' => $keep->email,
                'label' => 'primary',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $existing[] = mb_strtolower($keep->email);
        }

        $incoming = collect();
        if (! empty($drop->email)) {
            $incoming->push($drop->email);
        }
        foreach (DB::table('contact_emails')->where('exam_contact_id', $drop->id)->pluck('email') as $e) {
            $incoming->push($e);
        }

        foreach ($incoming->unique() as $email) {
            if (in_array(mb_strtolower($email), $existing, true)) {
                continue;
            }
            DB::table('contact_emails')->insert([
                'exam_contact_id' => $keep->id,
                'email' => $email,
                'label' => 'secondary',
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $existing[] = mb_strtolower($email);
        }

        DB::table('contact_emails')->where('exam_contact_id', $drop->id)->delete();
    }

    /**
     * Copy profile fields from drop only where the keeper is missing them, and
     * append drop's notes so nothing is silently lost.
     */
    private function foldProfile(ExamContact $keep, ExamContact $drop): void
    {
        $dirty = false;

        foreach (['phone', 'how_they_found_us', 'hubspot_contact_id', 'user_id', 'source'] as $field) {
            if (empty($keep->$field) && ! empty($drop->$field)) {
                $keep->$field = $drop->$field;
                $dirty = true;
            }
        }

        foreach (['met_face_to_face', 'spoken_on_phone', 'contacted_by_email'] as $flag) {
            if (! $keep->$flag && $drop->$flag) {
                $keep->$flag = true;
                $dirty = true;
            }
        }

        if (! empty($drop->notes)) {
            if (empty($keep->notes)) {
                $keep->notes = $drop->notes;
                $dirty = true;
            } elseif (! str_contains($keep->notes, $drop->notes)) {
                $keep->notes = trim($keep->notes."\n".$drop->notes);
                $dirty = true;
            }
        }

        if ($dirty) {
            $keep->save();
        }
    }

    private function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = preg_replace('/[^a-z0-9 ]+/u', '', $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);

        return trim((string) $name);
    }
}
