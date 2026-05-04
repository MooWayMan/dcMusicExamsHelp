<x-mail::message>
# Thanks {{ $firstName }}

We've logged your correction for **{{ $candidateName }}** and will action it shortly.

We'll handle the fix on musicExams.help and with Trinity for you — you don't need to contact Trinity directly.

If you spot anything else, just head back to your dashboard and use **Report correction** next to the candidate.

<x-mail::button :url="url('/dashboard')">
Back to your dashboard
</x-mail::button>

Speak soon,<br>
Paul Sheridan<br>
musicExams.help · Centre 120
</x-mail::message>
