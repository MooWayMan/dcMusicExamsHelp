<?php
// app/Http/Controllers/Admin/StudentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\Instrument;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $family = $request->input('family');

        $query = Student::with([
            'instrument:id,name,family',
            'examEntries.order.orderContacts.examContact',
        ])->withCount('examEntries');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhereHas('instrument', fn ($iq) => $iq->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('examEntries', function ($eq) use ($search) {
                        $eq->where('candidate_name', 'ilike', "%{$search}%")
                            ->orWhere('teacher_name', 'ilike', "%{$search}%")
                            ->orWhere('school_name', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($family) {
            $query->whereHas('instrument', fn ($q) => $q->where('family', $family));
        }

        $sortBy = $request->input('sort', 'last_name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['first_name', 'last_name', 'exam_entries_count', 'created_at'];

        if ($sortBy === 'instrument') {
            $query->orderBy(
                Instrument::select('name')
                    ->whereColumn('instruments.id', 'students.instrument_id')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'instrument_family') {
            $query->orderBy(
                Instrument::select('family')
                    ->whereColumn('instruments.id', 'students.instrument_id')
                    ->limit(1),
                $sortDir
            );
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $students = $query->paginate(20)->withQueryString();

        $students->through(function ($student) {
            $teacherContact = $this->resolveTeacherContact($student);

            return [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'teacher_name' => $teacherContact?->name ?? '—',
                'teacher_id' => $teacherContact?->id,
                'instrument' => $student->instrument->name ?? '—',
                'instrument_family' => $student->instrument->family ?? '—',
                'exam_entries_count' => $student->exam_entries_count,
            ];
        });

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

    private function resolveTeacherContact(Student $student): ?ExamContact
    {
        $teacherCounts = [];

        foreach ($student->examEntries as $entry) {
            $order = $entry->order;

            if (! $order || ! $order->relationLoaded('orderContacts')) {
                continue;
            }

            foreach ($order->orderContacts as $orderContact) {
                if ($orderContact->role_in_order !== 'teacher') {
                    continue;
                }

                $contact = $orderContact->examContact;

                if (! $contact) {
                    continue;
                }

                $teacherCounts[$contact->id] = [
                    'contact' => $contact,
                    'count' => ($teacherCounts[$contact->id]['count'] ?? 0) + 1,
                ];
            }
        }

        if ($teacherCounts === []) {
            return null;
        }

        uasort($teacherCounts, fn ($a, $b) => $b['count'] <=> $a['count']);

        $top = reset($teacherCounts);

        return $top['contact'] ?? null;
    }
}