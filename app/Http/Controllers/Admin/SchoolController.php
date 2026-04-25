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
        // Count contacts who are flagged as teachers in the unified contacts
        // model (was: legacy `teachers` BelongsToMany via teacher_school).
        $query = School::with(['contacts:id,name,phone'])
            ->withCount([
                'contacts as teachers_count' => fn ($q) => $q->whereExists(function ($s) {
                    $s->select(\DB::raw(1))
                        ->from('contact_types')
                        ->whereColumn('contact_types.exam_contact_id', 'exam_contacts.id')
                        ->where('type', 'teacher');
                }),
                'orders',
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

    public function show(School $school): Response
    {
        $school->load([
            'contacts' => fn ($q) => $q->withCount(['examEntries', 'orders']),
            'orders' => fn ($q) => $q->with(['teacher:id,name', 'createdByContact:id,name'])->latest(),
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
            // "teachers" key kept for the existing Vue table; rows now point
            // at exam_contacts (so row clicks land on /admin/contacts/{id}).
            'teachers' => $school->contacts->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'types' => $c->types,
                'students_count' => 0, // count via contact's students relation if needed later
                'orders_count' => $c->orders_count,
            ]),
            'orders' => $school->orders->map(fn ($o) => [
                'id' => $o->id,
                'trinity_order_number' => $o->trinity_order_number,
                'teacher_name' => $o->createdByContact?->name ?? $o->teacher?->name ?? $o->applicant_name ?? '—',
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

        return null;
    }
}
