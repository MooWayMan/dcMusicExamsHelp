<?php
// app/Http/Controllers/Admin/StudentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\Instrument;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $family = $request->input('family');

        // Base filtered query — used for both the listing and the summary
        // counts so the totals at the top respond to the active filters.
        // (Bug fix: previously `summary.total` was an unfiltered global count.)
        $base = Student::query();

        if ($search) {
            $base->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhereHas('examEntries.instrument', fn ($iq) => $iq->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('examEntries', function ($eq) use ($search) {
                        $eq->where('candidate_name', 'ilike', "%{$search}%")
                            ->orWhere('teacher_name', 'ilike', "%{$search}%")
                            ->orWhere('school_name', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($family) {
            // A student "belongs to" a family if any of their exam entries'
            // instruments are in that family (chips model — many-per-student).
            $base->whereHas('examEntries.instrument', fn ($q) => $q->where('family', $family));
        }

        // Listing query: clone base, attach eager loads + sort + count.
        // Per-entry teacher (exam_entries.teacher_contact_id) is the
        // authoritative source — order_contacts.teacher is the order-level
        // coordinator (Paul on F2F batches), which is wrong for individual
        // candidates. Backfilled 24 Apr in `exam-entries:link-teachers`.
        $query = (clone $base)
            ->with([
                'examEntries.instrument:id,name,family',
                'examEntries.teacherContact:id,name',
            ])
            ->withCount('examEntries');

        $sortBy = $request->input('sort', 'last_name');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['first_name', 'last_name', 'exam_entries_count', 'created_at'];

        if ($sortBy === 'instrument') {
            // Order by the alphabetically-first instrument name across the
            // student's exam entries. Works as a stable tiebreaker for chips.
            $query->orderBy(
                Instrument::select('instruments.name')
                    ->join('exam_entries', 'exam_entries.instrument_id', '=', 'instruments.id')
                    ->whereColumn('exam_entries.student_id', 'students.id')
                    ->orderBy('instruments.name')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'instrument_family') {
            $query->orderBy(
                Instrument::select('instruments.family')
                    ->join('exam_entries', 'exam_entries.instrument_id', '=', 'instruments.id')
                    ->whereColumn('exam_entries.student_id', 'students.id')
                    ->orderBy('instruments.family')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'teacher') {
            // Same shape as instrument sort but joining through teacherContact.
            $query->orderBy(
                ExamContact::select('exam_contacts.name')
                    ->join('exam_entries', 'exam_entries.teacher_contact_id', '=', 'exam_contacts.id')
                    ->whereColumn('exam_entries.student_id', 'students.id')
                    ->orderBy('exam_contacts.name')
                    ->limit(1),
                $sortDir
            );
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $students = $query->paginate(20)->withQueryString();

        $students->through(function ($student) {
            $teacherContact = $this->resolveTeacherContact($student);

            // Distinct instruments across all exam entries — chips model.
            $instruments = $student->examEntries
                ->pluck('instrument')
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($i) => [
                    'id'     => $i->id,
                    'name'   => $i->name,
                    'family' => $i->family,
                ]);

            return [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'teacher_name' => $teacherContact?->name ?? '—',
                'teacher_id' => $teacherContact?->id,
                'instruments' => $instruments,
                'exam_entries_count' => $student->exam_entries_count,
            ];
        });

        // Filter-aware summary counts. Each summary number reflects the
        // currently active search/family filter.
        $matchedIds = (clone $base)->pluck('students.id');

        $familyCount = Instrument::query()
            ->join('exam_entries', 'exam_entries.instrument_id', '=', 'instruments.id')
            ->whereIn('exam_entries.student_id', $matchedIds)
            ->distinct()
            ->count(DB::raw('instruments.family'));

        $summary = [
            'total'      => $matchedIds->count(),
            'with_exams' => (clone $base)->has('examEntries')->count(),
            'families'   => $familyCount,
        ];

        return Inertia::render('admin/Students/Index', [
            'students' => $students,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'family' => $family,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Resolve a student's "main" teacher from the per-entry FK
     * (exam_entries.teacher_contact_id), which was the source of truth from
     * 24 Apr's exam-entries:link-teachers backfill onwards.
     *
     * Picks the most-frequent teacher across the student's entries (handles
     * the rare case where the same student has been entered by two different
     * teachers over time). Returns null when no entry has a teacher FK —
     * those students are surfaced as "—" in the table, not falsely attributed
     * to whoever happened to coordinate the F2F batch order.
     */
    private function resolveTeacherContact(Student $student): ?ExamContact
    {
        $teacherCounts = [];

        foreach ($student->examEntries as $entry) {
            $contact = $entry->teacherContact;

            if (! $contact) {
                continue;
            }

            // Defensive: only surface contacts who carry 'teacher' OR
            // 'school_admin' (Daniel Rogers / Pulse Music represents the
            // school's teachers). F2F imports historically stamped the
            // Trinity submitter (often a parent or self-applicant) into
            // exam_entries.teacher_contact_id; this filter keeps those out
            // of the Teacher column. See submitter_vs_teacher pending memory
            // for the import-side fix.
            if (! $contact->hasType('teacher') && ! $contact->hasType('school_admin')) {
                continue;
            }

            $teacherCounts[$contact->id] = [
                'contact' => $contact,
                'count'   => ($teacherCounts[$contact->id]['count'] ?? 0) + 1,
            ];
        }

        if ($teacherCounts === []) {
            return null;
        }

        uasort($teacherCounts, fn ($a, $b) => $b['count'] <=> $a['count']);

        return reset($teacherCounts)['contact'] ?? null;
    }
}