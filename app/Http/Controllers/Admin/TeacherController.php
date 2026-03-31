<?php

// app/Http/Controllers/Admin/TeacherController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrument;
use App\Models\School;
use App\Models\SubjectArea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): Response
    {
        $query = User::where('role', 'teacher')
            ->with(['schools:id,name', 'instruments:id,name,family', 'subjectAreas:id,name'])
            ->withCount(['students', 'orders']);

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'email', 'created_at', 'students_count', 'orders_count'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $teachers = $query->paginate(15)->withQueryString();

        // Transform for the frontend
        $teachers->through(fn ($teacher) => [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'schools' => $teacher->schools->pluck('name')->implode(', '),
            'instruments' => $teacher->instruments->pluck('name')->implode(', '),
            'subject_areas' => $teacher->subjectAreas->pluck('name')->implode(', '),
            'students_count' => $teacher->students_count,
            'orders_count' => $teacher->orders_count,
            'met_face_to_face' => $teacher->met_face_to_face,
            'spoken_on_phone' => $teacher->spoken_on_phone,
            'contacted_by_email' => $teacher->contacted_by_email,
            'how_they_found_us' => $teacher->how_they_found_us,
            'created_at' => $teacher->created_at->format('d M Y'),
        ]);

        return Inertia::render('admin/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Teachers/Create', [
            'instruments' => Instrument::orderBy('family')->orderBy('name')->get(['id', 'name', 'family']),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'subjectAreas' => SubjectArea::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'how_they_found_us' => 'nullable|string|max:255',
            'met_face_to_face' => 'boolean',
            'spoken_on_phone' => 'boolean',
            'contacted_by_email' => 'boolean',
            'instruments' => 'nullable|array',
            'instruments.*' => 'exists:instruments,id',
            'schools' => 'nullable|array',
            'schools.*' => 'exists:schools,id',
            'subject_areas' => 'nullable|array',
            'subject_areas.*' => 'exists:subject_areas,id',
        ]);

        $teacher = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'), // Default password
            'role' => 'teacher',
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'how_they_found_us' => $validated['how_they_found_us'] ?? null,
            'met_face_to_face' => $validated['met_face_to_face'] ?? false,
            'spoken_on_phone' => $validated['spoken_on_phone'] ?? false,
            'contacted_by_email' => $validated['contacted_by_email'] ?? false,
        ]);

        if (!empty($validated['instruments'])) {
            $teacher->instruments()->attach($validated['instruments']);
        }
        if (!empty($validated['schools'])) {
            $teacher->schools()->attach($validated['schools']);
        }
        if (!empty($validated['subject_areas'])) {
            $teacher->subjectAreas()->attach($validated['subject_areas']);
        }

        return redirect()->route('admin.teachers.index')
            ->with('success', "{$teacher->name} has been added.");
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $teacher): Response
    {
        $teacher->load([
            'schools:id,name,city',
            'instruments:id,name,family',
            'subjectAreas:id,name',
            'students.instrument:id,name',
            'orders' => fn ($q) => $q->with('school:id,name')->latest(),
            'contactLogs' => fn ($q) => $q->latest('contacted_at'),
        ]);

        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'notes' => $teacher->notes,
            'how_they_found_us' => $teacher->how_they_found_us,
            'met_face_to_face' => $teacher->met_face_to_face,
            'spoken_on_phone' => $teacher->spoken_on_phone,
            'contacted_by_email' => $teacher->contacted_by_email,
            'created_at' => $teacher->created_at->format('d M Y'),
            'schools' => $teacher->schools,
            'instruments' => $teacher->instruments,
            'subject_areas' => $teacher->subjectAreas,
            'students' => $teacher->students->map(fn ($s) => [
                'id' => $s->id,
                'full_name' => $s->first_name . ' ' . $s->last_name,
                'instrument' => $s->instrument->name ?? '—',
            ]),
            'orders' => $teacher->orders->map(fn ($o) => [
                'id' => $o->id,
                'trinity_order_number' => $o->trinity_order_number,
                'delivery_method' => $o->isDigital() ? 'DG' : 'F2F',
                'candidates' => $o->candidates,
                'commission_amount' => number_format($o->commission_amount, 2),
                'order_status' => $o->order_status,
                'school_name' => $o->school->name ?? '—',
                'requested_start_date' => $o->requested_start_date?->format('d M Y'),
            ]),
            'contact_logs' => $teacher->contactLogs->map(fn ($cl) => [
                'id' => $cl->id,
                'contact_type' => $cl->contact_type,
                'direction' => $cl->direction,
                'subject' => $cl->subject,
                'summary' => $cl->summary,
                'contacted_at' => $cl->contacted_at?->format('d M Y'),
            ]),
        ];

        return Inertia::render('admin/Teachers/Show', [
            'teacher' => $teacherData,
        ]);
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher): Response
    {
        $teacher->load(['schools', 'instruments', 'subjectAreas']);

        return Inertia::render('admin/Teachers/Edit', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'notes' => $teacher->notes,
                'how_they_found_us' => $teacher->how_they_found_us,
                'met_face_to_face' => $teacher->met_face_to_face,
                'spoken_on_phone' => $teacher->spoken_on_phone,
                'contacted_by_email' => $teacher->contacted_by_email,
                'instruments' => $teacher->instruments->pluck('id'),
                'schools' => $teacher->schools->pluck('id'),
                'subject_areas' => $teacher->subjectAreas->pluck('id'),
            ],
            'instruments' => Instrument::orderBy('family')->orderBy('name')->get(['id', 'name', 'family']),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'subjectAreas' => SubjectArea::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, User $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'how_they_found_us' => 'nullable|string|max:255',
            'met_face_to_face' => 'boolean',
            'spoken_on_phone' => 'boolean',
            'contacted_by_email' => 'boolean',
            'instruments' => 'nullable|array',
            'instruments.*' => 'exists:instruments,id',
            'schools' => 'nullable|array',
            'schools.*' => 'exists:schools,id',
            'subject_areas' => 'nullable|array',
            'subject_areas.*' => 'exists:subject_areas,id',
        ]);

        $teacher->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'how_they_found_us' => $validated['how_they_found_us'] ?? null,
            'met_face_to_face' => $validated['met_face_to_face'] ?? false,
            'spoken_on_phone' => $validated['spoken_on_phone'] ?? false,
            'contacted_by_email' => $validated['contacted_by_email'] ?? false,
        ]);

        $teacher->instruments()->sync($validated['instruments'] ?? []);
        $teacher->schools()->sync($validated['schools'] ?? []);
        $teacher->subjectAreas()->sync($validated['subject_areas'] ?? []);

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', "{$teacher->name} has been updated.");
    }

    /**
     * Soft-delete the specified teacher.
     * Relationships are preserved — the teacher can be restored later.
     */
    public function destroy(User $teacher): RedirectResponse
    {
        $name = $teacher->name;
        $teacher->delete(); // Soft delete — sets deleted_at timestamp

        return redirect()->route('admin.teachers.index')
            ->with('success', "{$name} has been archived. You can restore them from the archived list.");
    }

    /**
     * Restore a soft-deleted teacher.
     */
    public function restore(int $id): RedirectResponse
    {
        $teacher = User::onlyTrashed()->where('role', 'teacher')->findOrFail($id);
        $teacher->restore();

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', "{$teacher->name} has been restored.");
    }

    /**
     * Return the deletion impact summary for a teacher (used by the frontend warning).
     */
    public function deletionImpact(User $teacher): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'name' => $teacher->name,
            'students_count' => $teacher->students()->count(),
            'orders_count' => $teacher->orders()->count(),
            'contact_logs_count' => $teacher->contactLogs()->count(),
            'schools_count' => $teacher->schools()->count(),
        ]);
    }
}
