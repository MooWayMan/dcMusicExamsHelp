<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Read-only admin viewer for registered users (the auth/login side).
 *
 * Distinct from Admin\ContactController, which lists everyone in the wider
 * exam_contacts people system. This page only surfaces users who have
 * actually registered to log in (rows in the `users` table).
 *
 * The show page links each registered user back to their matching
 * exam_contact row (by email) so we can see what candidates / exam entries
 * they'd be shown on first login. That mirrors the registration design we
 * agreed: register with email → match against exam_contacts → show their
 * stuff.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['name', 'email', 'role', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        $users->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->format('d M Y'),
            'created_at' => $user->created_at->format('d M Y'),
        ]);

        // Per-role counters stay global (the population, not the filtered
        // subset) so the role pills always show the full headcount.
        // `total` reflects the active filter so the user sees the size of
        // the current view at a glance.
        $filteredQuery = User::query();
        if ($search) {
            $filteredQuery->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }
        if ($role) {
            $filteredQuery->where('role', $role);
        }

        $summary = [
            'total' => $filteredQuery->count(),
            'admins' => User::where('role', 'admin')->count(),
            'school_admins' => User::where('role', 'school_admin')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'parents' => User::where('role', 'parent')->count(),
            'selves' => User::where('role', 'self')->count(),
        ];

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'roles' => User::ROLES,
        ]);
    }

    public function show(User $user)
    {
        // Try to link this registered user to their exam_contacts row by
        // email. This is the same matching rule the registration flow will
        // use on first login — if no contact matches, the user would land
        // on the "we couldn't find any exams under this email" fallback,
        // which is exactly what we want to surface here for debugging.
        $contact = $user->email
            ? ExamContact::query()
                ->with([
                    'students:id,first_name,last_name,teacher_contact_id',
                    'examEntries' => fn ($q) => $q
                        ->select(
                            'id', 'order_id', 'candidate_name', 'grade',
                            'subject_area', 'delivery_method', 'result',
                            'score', 'exam_date'
                        )
                        ->latest('exam_date'),
                ])
                ->withCount(['examEntries', 'students'])
                ->where('email', $user->email)
                ->orWhereHas('emails', fn ($eq) => $eq->where('email', $user->email))
                ->first()
            : null;

        return Inertia::render('admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'notes' => $user->notes,
                'how_they_found_us' => $user->how_they_found_us,
                'met_face_to_face' => $user->met_face_to_face,
                'spoken_on_phone' => $user->spoken_on_phone,
                'contacted_by_email' => $user->contacted_by_email,
                'hubspot_contact_id' => $user->hubspot_contact_id,
                'email_verified_at' => $user->email_verified_at?->format('d M Y'),
                'two_factor_enabled' => ! is_null($user->two_factor_confirmed_at ?? null),
                'created_at' => $user->created_at->format('d M Y'),
                'updated_at' => $user->updated_at->format('d M Y'),
            ],
            'linkedContact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'types' => $contact->types,
                'students_count' => $contact->students_count,
                'exam_entries_count' => $contact->exam_entries_count,
                'students' => $contact->students->map(fn ($s) => [
                    'id' => $s->id,
                    // GDPR: first name + initial only. Same rule we apply on
                    // public pages — also used here so admin gets a consistent
                    // view of what minors look like in the system.
                    'name' => trim($s->first_name . ' ' . substr($s->last_name ?? '', 0, 1) . '.'),
                ]),
                'exam_entries' => $contact->examEntries->map(fn ($e) => [
                    'id' => $e->id,
                    'candidate_name' => $e->candidate_name,
                    'grade' => $e->grade,
                    'subject_area' => $e->subject_area,
                    'delivery_method' => $e->delivery_method,
                    'result' => $e->result,
                    'score' => $e->score,
                    'exam_date' => $e->exam_date?->format('d M Y'),
                ]),
            ] : null,
        ]);
    }
}
