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
     * Contacts that look like duplicates of $contact, excluding pairs already
     * dismissed as "not the same".
     *
     * @return Collection<int, array{contact: ExamContact, score: int}>
     */
    public function possibleDuplicatesFor(ExamContact $contact, int $threshold = 80): Collection
    {
        if ($this->normalizeName($contact->name) === '') {
            return collect();
        }

        // The subject's own emails and schools are part of the comparison, so
        // make sure they're loaded — signals() reads them off the relation and
        // would otherwise quietly see only the primary address.
        $contact->loadMissing(['emails', 'schools:id']);

        $me = $this->signals($contact);

        return ExamContact::with(['emails', 'schools:id'])
            ->where('id', '!=', $contact->id)
            ->get()
            ->map(fn (ExamContact $other) => [
                'contact' => $other,
                'score' => $this->scoreFor($me, $this->signals($other)),
            ])
            ->filter(fn ($row) => $row['score'] >= $threshold
                && ! ContactMergeDismissal::isDismissed($contact->id, $row['contact']->id))
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Every contact that has at least one non-dismissed likely duplicate,
     * with its BEST match, so the contacts list can name who it means rather
     * than showing a bare "possible duplicate" chip with no counterpart.
     *
     * O(n²) comparisons, fine for the low-hundreds of contacts.
     *
     * @return array<int, array{id: int, name: string, score: int}> keyed by contact id
     */
    public function duplicateContactIds(int $threshold = 80): array
    {
        $rows = ExamContact::with(['emails', 'schools:id'])
            ->get()
            ->map(fn (ExamContact $c) => $this->signals($c))
            ->filter(fn (array $sig) => $sig['norm'] !== '')
            ->values()
            ->all();

        $dismissed = [];
        foreach (ContactMergeDismissal::get(['low_contact_id', 'high_contact_id']) as $d) {
            $dismissed[$d->low_contact_id.'-'.$d->high_contact_id] = true;
        }

        $best = [];
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $score = $this->scoreFor($rows[$i], $rows[$j]);
                if ($score < $threshold) {
                    continue;
                }
                [$low, $high] = ContactMergeDismissal::pair($rows[$i]['id'], $rows[$j]['id']);
                if (isset($dismissed[$low.'-'.$high])) {
                    continue;
                }
                foreach ([[$i, $j], [$j, $i]] as [$self, $other]) {
                    if (($best[$rows[$self]['id']]['score'] ?? -1) < $score) {
                        $best[$rows[$self]['id']] = [
                            'id' => $rows[$other]['id'],
                            'name' => $rows[$other]['name'],
                            'score' => $score,
                        ];
                    }
                }
            }
        }

        return $best;
    }

    /**
     * Everything the matcher looks at for one contact, gathered once so the
     * O(n²) pass isn't re-deriving it on every comparison.
     *
     * @return array{id: int, name: string, norm: string, first: string, last: string,
     *               emails: array<int, string>, phone: string, schools: array<int, int>}
     */
    private function signals(ExamContact $c): array
    {
        $norm = $this->normalizeName($c->name);
        $parts = preg_split('/\s+/', $norm, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $emails = collect([$c->email])
            ->merge($c->relationLoaded('emails') ? $c->emails->pluck('email') : [])
            ->filter()
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->unique()
            ->values()
            ->all();

        // Digits only, so 07584 904 971 and +447584904971 compare equal.
        $phone = preg_replace('/\D+/', '', (string) $c->phone) ?? '';
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        return [
            'id' => $c->id,
            'name' => (string) $c->name,
            'norm' => $norm,
            'first' => $parts[0] ?? '',
            'last' => count($parts) > 1 ? $parts[count($parts) - 1] : '',
            'emails' => $emails,
            'phone' => strlen($phone) >= 7 ? $phone : '',
            'schools' => $c->relationLoaded('schools') ? $c->schools->pluck('id')->all() : [],
        ];
    }

    /**
     * How likely two contacts are the same person, 0–100.
     *
     * The old rule ran similar_text() over the WHOLE name at 80%. That treats
     * a name as a bag of characters, so a shared forename dominates whenever
     * the surnames are short: "claire freeman" vs "claire reed" scores exactly
     * 80.00 — seven characters of "claire " plus the accident that "ree"
     * appears in both "f-ree-man" and "ree-d" — and got flagged. Meanwhile
     * "emily bates" vs "emma bates" scored 76 and did not.
     *
     * So: score the forename and surname SEPARATELY, then let other evidence
     * corroborate. Two rules Paul set (3 Aug 2026) shape this:
     *
     *   - People marry and change surname, so a surname match can NOT be a
     *     hard gate — a renamed teacher must still be findable.
     *   - People use several email addresses, so a shared email is strong
     *     evidence FOR a match, but NOT sharing one is no evidence against.
     *     Corroboration therefore only ever ADDS.
     *
     * A changed surname with no shared email, phone or school is genuinely
     * indistinguishable from two different people, and no threshold can fix
     * that. Those stay a manual catch — the "merge another contact into this
     * one" picker on the contact page lists EVERY contact with a free-text
     * search, independent of this score, so nothing is out of reach.
     *
     * @param  array{norm: string, first: string, last: string, emails: array<int, string>, phone: string, schools: array<int, int>}  $a
     * @param  array{norm: string, first: string, last: string, emails: array<int, string>, phone: string, schools: array<int, int>}  $b
     */
    private function scoreFor(array $a, array $b): int
    {
        if ($a['norm'] === '' || $b['norm'] === '') {
            return 0;
        }

        // Identical names need no corroboration.
        if ($a['norm'] === $b['norm']) {
            return 100;
        }

        $firstStrong = $this->partScore($a['first'], $b['first']) >= 85;
        $lastStrong = $a['last'] !== '' && $b['last'] !== ''
            && $this->partScore($a['last'], $b['last']) >= 85;

        if ($firstStrong && $lastStrong) {
            $name = 95;          // "cheryl ritchie" / "cheryl richie"
        } elseif ($lastStrong) {
            $name = 60;          // same surname, different forename — siblings and
                                 // spouses look exactly like this, so ask for more
        } elseif ($firstStrong) {
            $name = 40;          // the "claire freeman" / "claire reed" case
        } else {
            return 0;
        }

        // Same surname, different forename: far more often a FAMILY than a
        // duplicate. Families share a landline and a school, so those two
        // signals prove much less here than they do elsewhere. A shared email
        // address stays strong either way.
        $familyLike = $lastStrong && ! $firstStrong;

        return min(100, $name + $this->corroboration($a, $b, $familyLike));
    }

    /**
     * Evidence beyond the name. Only ever adds — see scoreFor().
     *
     * A shared email is what rescues a marriage rename: same person, new
     * surname, but one address still in common across contact_emails.
     *
     * $familyLike flips the weighting for the same-surname/different-forename
     * case. A household shares a phone number and a school, so neither says
     * much about two people called Smith — but two records called Claire that
     * share a mobile are almost certainly one person who changed surname.
     *
     * @param  array{emails: array<int, string>, phone: string, schools: array<int, int>}  $a
     * @param  array{emails: array<int, string>, phone: string, schools: array<int, int>}  $b
     */
    private function corroboration(array $a, array $b, bool $familyLike = false): int
    {
        $score = 0;

        // Strong regardless: two people rarely share a mailbox, and this is
        // the signal that survives a change of name.
        if (array_intersect($a['emails'], $b['emails'])) {
            $score += 45;
        }

        // 45, matching email, NOT 40: at 40 the rename case lands on exactly
        // 80 — the same knife-edge that made the old whole-name rule flag
        // Claire Freeman and Claire Reed. Leave headroom.
        if ($a['phone'] !== '' && $a['phone'] === $b['phone']) {
            $score += $familyLike ? 10 : 45;
        }

        if (array_intersect($a['schools'], $b['schools'])) {
            $score += $familyLike ? 5 : 15;
        }

        return $score;
    }

    /**
     * Similarity of one name PART, 0–100. An initial counts as a match for
     * any forename starting with the same letter, so "p sheridan" and
     * "paul sheridan" aren't split apart by the abbreviation.
     */
    private function partScore(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 100.0;
        }

        if ((strlen($a) === 1 || strlen($b) === 1) && $a[0] === $b[0]) {
            return 90.0;
        }

        $percent = 0.0;
        similar_text($a, $b, $percent);

        return $percent;
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
