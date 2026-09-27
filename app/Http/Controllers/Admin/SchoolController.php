<?php

// app/Http/Controllers/Admin/SchoolController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\SchoolLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        // teachers_count = current staff: the contact_school link minus
        // anyone marked as having left. Not exam entries under the school's
        // name, which is Trinity's exam VENUE (see App\Services\SchoolLinks).
        $query = School::query()
            ->select('schools.*')
            ->with(['contacts:id,name,phone'])
            ->withCount([
                'orders',
                'contacts as teachers_count' => fn ($q) => $q->where('contact_school.former', false),
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%")
                  ->orWhere('postcode', 'ilike', "%{$search}%")
                  ->orWhereHas('contacts', fn ($cq) => $cq->where('name', 'ilike', "%{$search}%"));
            });
        }

        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'city', 'teachers_count', 'orders_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $schools = $query->paginate(15)->withQueryString();

        // Build a single "primary contact" display per school, preferring the
        // unified-model contact_school pivot, falling back to the legacy
        // schools.contact_name string for rows that haven't been migrated.
        // Precedence within the pivot: school_admin > teacher > anyone else.
        $schools->through(function ($school) {
            $primaryContact = $this->pickPrimarySchoolContact($school);

            return [
                'id' => $school->id,
                'name' => $school->name,
                'address' => $school->address,
                'city' => $school->city,
                'postcode' => $school->postcode,
                'phone' => $primaryContact?->phone,
                'email' => $school->email,
                'contact_name' => $primaryContact?->name,
                'contact_id' => $primaryContact?->id,
                'contacts' => $school->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                ]),
                'teachers_count' => $school->teachers_count,
                'orders_count' => $school->orders_count,
            ];
        });

        return Inertia::render('admin/Schools/Index', [
            'schools' => $schools,
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Schools/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        School::create($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', "{$validated['name']} has been added.");
    }

    public function show(School $school, SchoolLinks $links): Response
    {
        $school->load([
            // Counts deliberately NOT loaded here. This page renders its
            // `teachers` table from a raw exam_entries join and uses
            // `contacts` only to pick a primary contact by type, so the
            // withCount(['examEntries', 'orders']) that used to sit here was
            // two dead subqueries per contact feeding nothing in the payload.
            'contacts',
            'orders' => fn ($q) => $q->with(['createdByContact:id,name'])->latest(),
            'instruments:id,name,family',
        ]);

        $primary = $this->pickPrimarySchoolContact($school);

        $schoolData = [
            'id' => $school->id,
            'name' => $school->name,
            'address' => $school->address,
            'city' => $school->city,
            'postcode' => $school->postcode,
            'phone' => $primary?->phone,
            'email' => $school->email,
            'contact_name' => $primary?->name,
            'contact_id' => $primary?->id,
            'notes' => $school->notes,
            'created_at' => $school->created_at->format('d M Y'),
            'instruments' => $school->instruments->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'family' => $i->family,
            ]),
            'teachers' => $links->teachers($school),
            'orders' => $school->orders->map(fn ($o) => [
                'id' => $o->id,
                'trinity_order_number' => $o->trinity_order_number,
                'teacher_name' => $o->createdByContact?->name ?? $o->applicant_name ?? '—',
                'teacher_contact_id' => $o->created_by_contact_id,
                'delivery_method' => $o->isDigital() ? 'DG' : 'F2F',
                'candidates' => $o->candidates,
                'commission_amount' => number_format($o->commission_amount, 2),
                'order_status' => $o->order_status,
                'requested_start_date' => $o->requested_start_date?->format('d M Y'),
            ]),
        ];

        return Inertia::render('admin/Schools/Show', [
            'school' => $schoolData,
        ]);
    }

    public function edit(School $school, SchoolLinks $links): Response
    {
        return Inertia::render('admin/Schools/Edit', [
            'teachers' => $links->teachers($school),
            'teacherOptions' => $links->teacherOptions(),
            'instrumentIds' => $links->instrumentIds($school),
            'instrumentOptions' => $links->instrumentOptions(),
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'address' => $school->address,
                'city' => $school->city,
                'postcode' => $school->postcode,
                'email' => $school->email,
                'notes' => $school->notes,
            ],
        ]);
    }

    public function update(Request $request, School $school, SchoolLinks $links): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'teachers' => 'sometimes|array',
            'teachers.*.id' => 'required|integer|exists:exam_contacts,id',
            'teachers.*.former' => 'boolean',
            'instrument_ids' => 'sometimes|array',
            'instrument_ids.*' => 'integer|exists:instruments,id',
        ]);

        $school->update(collect($validated)->except(['teachers', 'instrument_ids'])->all());

        // Only when the form sends them: a save without these keys leaves
        // the school's teachers and instruments exactly as they were.
        if ($request->has('teachers')) {
            $links->saveTeachers($school, $validated['teachers'] ?? []);
        }
        if ($request->has('instrument_ids')) {
            $links->saveInstruments($school, $validated['instrument_ids'] ?? []);
        }

        return redirect()->route('admin.schools.show', $school)
            ->with('success', "{$school->name} has been updated.");
    }

    public function destroy(School $school): RedirectResponse
    {
        $name = $school->name;
        $school->delete(); // Soft delete

        return redirect()->route('admin.schools.index')
            ->with('success', "{$name} has been archived.");
    }

    /**
     * Pick the canonical "primary" contact to display alongside the school
     * row. Precedence: school_admin > teacher > anyone else > none.
     * This stops alphabetically-first teachers (e.g. Tracey Lea) from
     * masquerading as the school's main contact when a real school_admin
     * (e.g. Peter Rainsford) exists.
     */
    private function pickPrimarySchoolContact(School $school): ?\App\Models\ExamContact
    {
        $byPrecedence = [
            fn ($c) => $c->isSchoolAdmin(),
            fn ($c) => $c->isTeacher(),
            fn () => true, // anyone else
        ];

        foreach ($byPrecedence as $matches) {
            $hit = $school->contacts->first($matches);
            if ($hit) {
                return $hit;
            }
        }

        // Fallback: the teacher who has submitted the most students through
        // this school by name match on exam_entries. Means schools without a
        // contact_school pivot row still get a useful "Contact" — the de-facto
        // lead teacher — instead of "—".
        $topTeacherId = \DB::table('exam_entries')
            ->where('school_name', $school->name)
            ->whereNotNull('teacher_contact_id')
            ->groupBy('teacher_contact_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('teacher_contact_id');

        return $topTeacherId
            ? \App\Models\ExamContact::find($topTeacherId)
            : null;
    }
}
