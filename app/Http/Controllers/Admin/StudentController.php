<?php

// app/Http/Controllers/Admin/StudentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Student::with([
            'teacher:id,name',
            'instrument:id,name,family',
        ])->withCount('examEntries');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('instrument', fn ($iq) => $iq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by instrument family
        if ($family = $request->input('family')) {
            $query->whereHas('instrument', fn ($q) => $q->where('family', $family));
        }

        $sortBy = $request->input('sort', 'last_name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['first_name', 'last_name', 'exam_entries_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $students = $query->paginate(20)->withQueryString();

        $students->through(fn ($student) => [
            'id' => $student->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'full_name' => $student->full_name,
            'email' => $student->email,
            'teacher_name' => $student->teacher->name ?? '—',
            'teacher_id' => $student->user_id,
            'instrument' => $student->instrument->name ?? '—',
            'instrument_family' => $student->instrument->family ?? '—',
            'exam_entries_count' => $student->exam_entries_count,
        ]);

        return Inertia::render('admin/Students/Index', [
            'students' => $students,
            'filters' => [
                'search' => $search,
                'family' => $family,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }
}
