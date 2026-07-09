<?php
// app/Http/Controllers/Admin/ContactController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMergeDismissal;
use App\Models\ExamContact;
use App\Services\ContactMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $family = $request->input('family');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'email', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = ExamContact::query()
            ->with([
                'emails',
                // Eager-load instruments per teacher so the chips column can
                // render without N+1 queries. examEntries() is the
                // teacher_contact_id relation; instrument is per-entry.
                'examEntries.instrument:id,name,family',
                // Persisted instruments (contact_instrument) — survive entry
                // deletion. The chips show the union of these + derived.
                'instruments:id,name,family',
            ])
            ->withCount(['examEntries', 'students', 'orders']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhereHas('emails', function ($eq) use ($search) {
                        $eq->where('email', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($type) {
            $query->withType($type);
        }

        // Family filter — show only contacts who have submitted exam entries
        // for at least one instrument in this family. Effectively limits the
        // listing to teachers (and school admins) since they're the ones with
        // teacher_contact_id links, which is exactly the syllabus-broadcast
        // use case Paul wants.
        if ($family) {
            $query->whereHas('examEntries.instrument', fn ($iq) => $iq->where('family', $family));
        }

        $contacts = $query
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        // Fall back to related contact_emails table when the direct email column is empty,
        // so legacy-imported rows (e.g. Roxanne) show their email in the list view.
        // `role` is kept for back-compat with the existing Vue pages — derived
        // from the new types[] array using a fixed precedence.
        // `instruments` is the chips data — distinct instruments derived from
        // the contact's exam entries (only meaningful for teachers / school
        // admins who have a teacher_contact_id link to entries).
        $contacts->through(function ($contact) {
            // Union of persisted (contact_instrument) + derived (from current
            // entries), distinct by id — so the chips survive entry deletion
            // but also show instruments from entries not yet backfilled.
            $instruments = $contact->instruments
                ->concat($contact->examEntries->pluck('instrument')->filter())
                ->unique('id')
                ->values()
                ->map(fn ($i) => [
                    'id'     => $i->id,
                    'name'   => $i->name,
                    'family' => $i->family,
                ]);

            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->primary_email,
                'phone' => $contact->phone,
                'types' => $contact->types,
                'role' => $this->primaryType($contact),
                'instruments' => $instruments,
                'exam_entries_count' => $contact->exam_entries_count,
                'students_count' => $contact->students_count,
                'orders_count' => $contact->orders_count,
            ];
        });

        // Summary stats. `total` reflects the active search/type/family filter
        // (matching the dynamic-total behaviour on /admin/students). The
        // per-type counters stay global so the type pills always show the
        // full population — they answer "how many teachers exist", not "how
        // many teachers match the current filter".
        $filteredQuery = ExamContact::query();
        if ($search) {
            $filteredQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhereHas('emails', fn ($eq) => $eq->where('email', 'ilike', "%{$search}%"));
            });
        }
        if ($type) {
            $filteredQuery->withType($type);
        }
        if ($family) {
            $filteredQuery->whereHas('examEntries.instrument', fn ($iq) => $iq->where('family', $family));
        }

        $summary = [
            'total' => $filteredQuery->count(),
            'teachers' => ExamContact::withType('teacher')->count(),
            'parents' => ExamContact::withType('parent')->count(),
            'candidates' => ExamContact::withType('candidate')->count(),
            'school_admins' => ExamContact::withType('school_admin')->count(),
            'trinity_admins' => ExamContact::withType('trinity_admin')->count(),
            'subscribers' => ExamContact::withType('subscriber')->count(),
        ];

        return Inertia::render('admin/Contacts/Index', [
            'contacts' => $contacts,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'type' => $type,
                // back-compat: existing Vue page reads filters.role
                'role' => $type,
                'family' => $family,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Single-string "primary" type for a contact, derived from its types[] pivot.
     * Precedence: teacher > school_admin > trinity_admin > parent > candidate > subscriber.
     * Used for back-compat with Vue pages that show a single role chip.
     */
    private function primaryType(ExamContact $contact): string
    {
        foreach (['teacher', 'school_admin', 'trinity_admin', 'parent', 'candidate', 'subscriber'] as $candidate) {
            if (in_array($candidate, $contact->types, true)) {
                return $candidate;
            }
        }

        return 'unknown';
    }

    public function show(ExamContact $contact, ContactMergeService $merges)
    {
        $entrySelect = fn ($q) => $q->select(
            'id', 'order_id', 'candidate_name', 'candidate_number',
            'grade', 'subject_area', 'delivery_method', 'result',
            'score', 'exam_date', 'teacher_contact_id', 'submitter_contact_id', 'fee', 'student_id'
        )
            ->with([
                'order:id,trinity_order_number,requested_start_date',
                'student:id,first_name,last_name',
            ])
            ->latest('exam_date');

        $contact->load([
            'emails',
            'students' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'teacher_contact_id'),
            // Entries where this contact is the TEACHER…
            'examEntries' => $entrySelect,
            // …and entries they merely SUBMITTED (parents / self-bookers). A
            // parent isn't a teacher, so without this their page reads blank.
            'submittedExamEntries' => $entrySelect,
            // Orders via the role pivot…
            'orders' => fn ($q) => $q->select(
                'orders.id', 'trinity_order_number', 'delivery_method',
                'subject_area', 'candidates', 'order_status', 'requested_start_date'
            ),
            // …and orders they SUBMITTED (created_by), which the pivot often
            // doesn't carry — this is why an applicant's Orders read 0.
            'createdOrders' => fn ($q) => $q->select(
                'id', 'created_by_contact_id', 'trinity_order_number', 'delivery_method',
                'subject_area', 'candidates', 'order_status', 'requested_start_date'
            ),
        ]);

        $contact->loadCount('students');

        // Entries: union of taught + submitted, one row per entry. Tag how the
        // contact relates to each so the UI can label parent-submitted rows.
        $allEntries = $contact->examEntries
            ->concat($contact->submittedExamEntries)
            ->unique('id')
            ->map(function ($entry) use ($contact) {
                $entry->setAttribute(
                    'relationship',
                    $entry->teacher_contact_id === $contact->id ? 'teacher' : 'submitted',
                );

                return $entry;
            })
            ->values();

        // Orders: union of pivot-linked + submitted (created_by), one row per
        // order, collecting every role the contact holds on it.
        $uniqueOrders = $contact->orders
            ->groupBy('id')
            ->map(function ($rows) {
                $first = $rows->first();
                $first->setAttribute(
                    'roles_in_order',
                    $rows->pluck('pivot.role_in_order')->filter()->unique()->values()->all(),
                );

                return $first;
            });

        foreach ($contact->createdOrders as $order) {
            if ($uniqueOrders->has($order->id)) {
                $roles = $uniqueOrders->get($order->id)->getAttribute('roles_in_order');
                $uniqueOrders->get($order->id)->setAttribute(
                    'roles_in_order',
                    array_values(array_unique([...$roles, 'applicant'])),
                );
            } else {
                $order->setAttribute('roles_in_order', ['applicant']);
                $uniqueOrders->put($order->id, $order);
            }
        }

        $uniqueOrders = $uniqueOrders->values();

        // Likely duplicates (fuzzy name), for the merge banner. Plus a light
        // list of every other contact for the manual "merge another contact
        // into this one" picker (catches aliases with a different surname).
        $possibleDuplicates = $merges->possibleDuplicatesFor($contact)
            ->map(fn ($row) => [
                'id' => $row['contact']->id,
                'name' => $row['contact']->name,
                'email' => $row['contact']->primary_email,
                'types' => $row['contact']->types,
                'score' => $row['score'],
            ]);

        $mergeCandidates = ExamContact::where('id', '!=', $contact->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
            ]);

        return Inertia::render('admin/Contacts/Show', [
            'possibleDuplicates' => $possibleDuplicates,
            'mergeCandidates' => $mergeCandidates,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'types' => $contact->types,
                'source' => $contact->source,
                'notes' => $contact->notes,
                'primary_email' => $contact->primary_email,
                'created_at' => $contact->created_at->format('d M Y'),
                'emails' => $contact->emails->map(fn ($e) => [
                    'id' => $e->id,
                    'email' => $e->email,
                    'label' => $e->label,
                    'is_primary' => $e->is_primary,
                ]),
                'students_count' => $contact->students_count,
                'exam_entries_count' => $allEntries->count(),
                'orders_count' => $uniqueOrders->count(),
                'exam_entries' => $allEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'order_id' => $entry->order_id,
                    'order_number' => $entry->order?->trinity_order_number ?? '—',
                    // 'teacher' = they taught it, 'submitted' = they booked it
                    // (parent / self-booker). Lets the UI flag parent rows.
                    'relationship' => $entry->getAttribute('relationship'),
                    // Fall back to the linked student's name when the per-entry
                    // candidate_name string is empty (e.g. older imports that
                    // didn't stamp it). Trinity always provides one on real
                    // imports so this is mostly a safety net.
                    'candidate_name' => $entry->candidate_name
                        ?: ($entry->student
                            ? trim($entry->student->first_name.' '.$entry->student->last_name)
                            : null),
                    'candidate_number' => $entry->candidate_number,
                    'grade' => $entry->grade,
                    'subject_area' => $entry->subject_area,
                    'delivery_method' => $entry->delivery_method,
                    'result' => $entry->result,
                    'score' => $entry->score,
                    // Fall back to the order's requested_start_date when the
                    // entry's exam_date is null (in-progress / submitted-but-
                    // not-sat entries don't have an actual exam_date until
                    // results come in).
                    'exam_date' => $entry->exam_date?->format('d M Y')
                        ?? $entry->order?->requested_start_date?->format('d M Y'),
                    'fee' => $entry->fee,
                ]),
                'students' => $contact->students->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->first_name . ' ' . substr($s->last_name ?? '', 0, 1) . '.',
                ]),
                'orders' => $uniqueOrders->map(fn ($o) => [
                    'id' => $o->id,
                    'trinity_order_number' => $o->trinity_order_number,
                    'delivery_method' => $o->delivery_method === 'Digital' ? 'DG' : 'F2F',
                    'subject_area' => $o->subject_area,
                    'candidates' => $o->candidates,
                    'order_status' => $o->order_status,
                    'requested_start_date' => $o->requested_start_date,
                    'roles_in_order' => $o->getAttribute('roles_in_order') ?? [],
                ]),
            ],
        ]);
    }

    /**
     * Merge another contact INTO this one. $contact survives (its primary
     * email is kept); the other is folded in and soft-deleted.
     */
    public function merge(Request $request, ExamContact $contact, ContactMergeService $merges): RedirectResponse
    {
        $validated = $request->validate([
            'drop_id' => ['required', 'integer', Rule::notIn([$contact->id]), 'exists:exam_contacts,id'],
        ]);

        $drop = ExamContact::findOrFail($validated['drop_id']);
        $dropName = $drop->name;

        $merges->merge($contact, $drop);

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', "Merged {$dropName} into {$contact->name}.");
    }

    /**
     * Record a "not the same person" decision so the pair is never flagged
     * as a possible duplicate again.
     */
    public function dismissDuplicate(Request $request, ExamContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'other_id' => ['required', 'integer', Rule::notIn([$contact->id]), 'exists:exam_contacts,id'],
        ]);

        ContactMergeDismissal::dismiss($contact->id, $validated['other_id']);

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', 'Dismissed — these two won\'t be flagged as duplicates again.');
    }

    public function edit(ExamContact $contact)
    {
        return Inertia::render('admin/Contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'types' => $contact->types,
                'source' => $contact->source,
                'notes' => $contact->notes,
                'show_full_name' => (bool) $contact->show_full_name,
                'excluded_from_prize_draw' => (bool) $contact->excluded_from_prize_draw,
            ],
            'allTypes' => ExamContact::TYPES,
        ]);
    }

    public function update(Request $request, ExamContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'types' => ['array'],
            'types.*' => ['string', 'in:' . implode(',', ExamContact::TYPES)],
            'notes' => ['nullable', 'string'],
            'show_full_name' => ['boolean'],
            'excluded_from_prize_draw' => ['boolean'],
        ]);

        DB::transaction(function () use ($contact, $validated): void {
            // Update plain fields (name, email, phone, notes) — exclude `types`
            // because that lives in the contact_types pivot, not on the row.
            $contact->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'show_full_name' => (bool) ($validated['show_full_name'] ?? false),
                'excluded_from_prize_draw' => (bool) ($validated['excluded_from_prize_draw'] ?? false),
            ]);
            $this->syncPrimaryEmail($contact, $validated['email'] ?? null);
            $this->syncTypes($contact, $validated['types'] ?? []);
        });

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', 'Contact updated.');
    }

    /**
     * Sync the contact_types pivot to exactly match the submitted array.
     */
    private function syncTypes(ExamContact $contact, array $types): void
    {
        $current = DB::table('contact_types')
            ->where('exam_contact_id', $contact->id)
            ->pluck('type')
            ->all();

        foreach (array_diff($types, $current) as $add) {
            $contact->addType($add);
        }
        foreach (array_diff($current, $types) as $remove) {
            $contact->removeType($remove);
        }
    }

    /**
     * Keep contact_emails in sync with the canonical exam_contacts.email.
     *
     * Ensures the saved email exists in contact_emails and is the ONLY row
     * flagged is_primary = true, so the show page's primary_email accessor
     * always surfaces the same value shown in the edit form.
     */
    private function syncPrimaryEmail(ExamContact $contact, ?string $email): void
    {
        $email = $email !== null ? trim($email) : null;

        // Demote every existing primary flag on this contact first.
        $contact->emails()->where('is_primary', true)->update(['is_primary' => false]);

        if ($email === null || $email === '') {
            return;
        }

        // Upsert this email as the primary. Preserve existing label if any.
        $contact->emails()->updateOrCreate(
            ['email' => $email],
            ['is_primary' => true],
        );
    }
}
