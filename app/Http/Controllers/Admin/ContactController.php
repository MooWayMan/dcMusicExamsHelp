<?php
// app/Http/Controllers/Admin/ContactController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public const ROLES = ['teacher', 'parent', 'self', 'applicant', 'admin', 'unknown'];


    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'email', 'role', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = ExamContact::query()
            ->with('emails')
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

        if ($role) {
            $query->where('role', $role);
        }

        $contacts = $query
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        // Fall back to related contact_emails table when the direct email column is empty,
        // so legacy-imported rows (e.g. Roxanne) show their email in the list view.
        $contacts->through(fn ($contact) => [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->primary_email,
            'phone' => $contact->phone,
            'role' => $contact->role,
            'exam_entries_count' => $contact->exam_entries_count,
            'students_count' => $contact->students_count,
            'orders_count' => $contact->orders_count,
        ]);

        // Summary stats (unfiltered totals)
        $summary = [
            'total' => ExamContact::count(),
            'teachers' => ExamContact::where('role', 'teacher')->count(),
            'parents' => ExamContact::where('role', 'parent')->count(),
            'applicants' => ExamContact::where('role', 'applicant')->count(),
        ];

        return Inertia::render('admin/Contacts/Index', [
            'contacts' => $contacts,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function show(ExamContact $contact)
    {
        $contact->load([
            'emails',
            'students' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'teacher_contact_id'),
            'examEntries' => fn ($q) => $q->select(
                'id', 'order_id', 'candidate_name', 'candidate_number',
                'grade', 'subject_area', 'delivery_method', 'result',
                'score', 'exam_date', 'teacher_contact_id', 'fee'
            )->with('order:id,trinity_order_number')->latest('exam_date'),
            'orders' => fn ($q) => $q->select(
                'orders.id', 'trinity_order_number', 'delivery_method',
                'subject_area', 'candidates', 'order_status', 'requested_start_date'
            ),
        ]);

        $contact->loadCount(['examEntries', 'students', 'orders']);

        return Inertia::render('admin/Contacts/Show', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'role' => $contact->role,
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
                'exam_entries_count' => $contact->exam_entries_count,
                'orders_count' => $contact->orders_count,
                'exam_entries' => $contact->examEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'order_id' => $entry->order_id,
                    'order_number' => $entry->order?->trinity_order_number ?? '—',
                    'candidate_name' => $entry->candidate_name,
                    'candidate_number' => $entry->candidate_number,
                    'grade' => $entry->grade,
                    'subject_area' => $entry->subject_area,
                    'delivery_method' => $entry->delivery_method,
                    'result' => $entry->result,
                    'score' => $entry->score,
                    'exam_date' => $entry->exam_date?->format('d M Y'),
                    'fee' => $entry->fee,
                ]),
                'students' => $contact->students->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->first_name . ' ' . substr($s->last_name ?? '', 0, 1) . '.',
                ]),
                'orders' => $contact->orders->map(fn ($o) => [
                    'id' => $o->id,
                    'trinity_order_number' => $o->trinity_order_number,
                    'delivery_method' => $o->delivery_method === 'Digital' ? 'DG' : 'F2F',
                    'subject_area' => $o->subject_area,
                    'candidates' => $o->candidates,
                    'order_status' => $o->order_status,
                    'requested_start_date' => $o->requested_start_date,
                    'role_in_order' => $o->pivot->role_in_order ?? '—',
                ]),
            ],
        ]);
    }

    public function edit(ExamContact $contact)
    {
        return Inertia::render('admin/Contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'role' => $contact->role,
                'source' => $contact->source,
                'notes' => $contact->notes,
            ],
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, ExamContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:' . implode(',', self::ROLES)],
            'notes' => ['nullable', 'string'],
        ]);

        $contact->update($validated);

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', 'Contact updated.');
    }
}
