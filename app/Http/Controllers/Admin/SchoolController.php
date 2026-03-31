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
        $query = School::withCount(['teachers', 'orders']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('postcode', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'city', 'teachers_count', 'orders_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $schools = $query->paginate(15)->withQueryString();

        $schools->through(fn ($school) => [
            'id' => $school->id,
            'name' => $school->name,
            'address' => $school->address,
            'city' => $school->city,
            'postcode' => $school->postcode,
            'phone' => $school->phone,
            'email' => $school->email,
            'contact_name' => $school->contact_name,
            'teachers_count' => $school->teachers_count,
            'orders_count' => $school->orders_count,
        ]);

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
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'contact_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        School::create($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', "{$validated['name']} has been added.");
    }

    public function show(School $school): Response
    {
        $school->load([
            'teachers' => fn ($q) => $q->select('users.id', 'users.name', 'users.email', 'users.phone')
                ->withCount(['students', 'orders']),
            'orders' => fn ($q) => $q->with('teacher:id,name')->latest(),
        ]);

        $schoolData = [
            'id' => $school->id,
            'name' => $school->name,
            'address' => $school->address,
            'city' => $school->city,
            'postcode' => $school->postcode,
            'phone' => $school->phone,
            'email' => $school->email,
            'contact_name' => $school->contact_name,
            'notes' => $school->notes,
            'created_at' => $school->created_at->format('d M Y'),
            'teachers' => $school->teachers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'phone' => $t->phone,
                'students_count' => $t->students_count,
                'orders_count' => $t->orders_count,
            ]),
            'orders' => $school->orders->map(fn ($o) => [
                'id' => $o->id,
                'trinity_order_number' => $o->trinity_order_number,
                'teacher_name' => $o->teacher->name ?? '—',
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
                'phone' => $school->phone,
                'email' => $school->email,
                'contact_name' => $school->contact_name,
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
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'contact_name' => 'nullable|string|max:255',
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
}
