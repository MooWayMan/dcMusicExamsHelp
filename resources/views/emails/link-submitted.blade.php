<x-mail::message>
# Account link request

**User:** {{ $user->name }} (id {{ $user->id }}, role {{ $user->role }})
**Registered email:** {{ $user->email }}
**Trinity email they say they used:** {{ $alternativeEmail }}

@if($note)
## User's note

{{ $note }}
@endif

Next step: link the user to the matching exam_contacts row (or create one) so their dashboard surfaces their exam entries.

<x-mail::button :url="url('/admin/tasks')">
View on /admin/tasks
</x-mail::button>

Thanks,<br>
musicExams.help
</x-mail::message>
