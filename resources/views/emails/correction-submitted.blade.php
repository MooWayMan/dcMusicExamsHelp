<x-mail::message>
# Correction request

**Reported by:** {{ $user->name }} ({{ $user->email }} — role {{ $user->role }})

**Entry:** #{{ $entry->id }}
**Candidate:** {{ $entry->candidate_name ?? '—' }} @if($entry->candidate_number) ({{ $entry->candidate_number }}) @endif
**DOB on file:** {{ $entry->date_of_birth?->format('d M Y') ?? '—' }}
**Grade / subject:** {{ $entry->grade ?? '—' }} / {{ $entry->subject_area ?? '—' }}
**Delivery:** {{ $entry->delivery_method ?? '—' }}
**Exam date:** {{ $entry->exam_date?->format('d M Y') ?? '—' }}

## User's note

{{ $task->detail }}

<x-mail::button :url="url('/admin/tasks')">
View on /admin/tasks
</x-mail::button>

Thanks,<br>
musicExams.help
</x-mail::message>
