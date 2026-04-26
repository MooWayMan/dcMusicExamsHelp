<?php

// app/Http/Controllers/Admin/SchoolController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        // teachers_count = distinct teachers who have actually submitted exam
        // entries via this school's name. Counts canonical teacher_contact_id
        // when present, falling back to a normalised teacher_name string for
        // entries without an FK link. This is more useful than counting the
        // sparse contact_school pivot (which only has manually-linked rows).
        $query = School::query()
            ->select('schools.*')
            ->with(['contacts:id,name,phone'])
            ->withCount(['orders'])
            ->selectSub(
                \DB::table('exam_entries')
                    ->whereColumn('exam_entries.school_name', 'schools.name')
                    ->whereNotNull('exam_entries.teacher_name')
                    ->selectRaw(
                        'COUNT(DISTINCT COALESCE(exam_entries.teacher_contact_id::text, LOWER(TRIM(exam_entries.teacher_name))))'
                    ),
                'teachers_count'
            );

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

    public function show(School $school): Response
    {
        $school->load([
            'contacts' => fn ($q) => $q->withCount(['examEntries', 'orders']),
            'orders' => fn ($q) => $q->with(['createdByContact:id,name'])->latest(),
        ]);

        $primary = $this->pickPrimarySchoolContact($school);

        // Derive teachers from exam_entries — same source the index page uses
        // for `teachers_count`. The contact_school pivot is sparse on prod;
        // this gives a complete list of who's actually submitted via this
        // school, ordered by entry volume so the most active teachers surface
        // first.
        $derivedTeachers = \DB::table('exam_entries')
            ->join('exam_contacts', 'exam_contacts.id', '=', 'exam_entries.teacher_contact_id')
            ->where('exam_entries.school_name', $school->name)
            ->whereNotNull('exam_entries.teacher_contact_id')
            ->groupBy('exam_contacts.id', 'exam_contacts.name', 'exam_contacts.email', 'exam_contacts.phone')
            ->select(
                'exam_contacts.id',
                'exam_contacts.name',
                'exam_contacts.email',
                'exam_contacts.phone',
                \DB::raw('COUNT(DISTINCT exam_entries.student_id) as students_count'),
                \DB::raw('COUNT(DISTINCT exam_entries.order_id) as orders_count'),
                \DB::raw('COUNT(*) as entries_count'),
            )
            ->orderByRaw('COUNT(*) DESC')
            ->get();

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
            'teachers' => $derivedTeachers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'phone' => $t->phone,
                'students_count' => (int) $t->students_count,
                'orders_count' => (int) $t->orders_count,
                'entries_count' => (int) $t->entries_count,
            ]),
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

    public function edit(School $school): Response
    {
        return Inertia::render('admin/Schools/Edit', [
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

    public function update(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $school->update($validated);

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
