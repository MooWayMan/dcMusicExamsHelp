<x-mail::message>
# Thanks {{ $firstName }}

We've got your account link request and will match **{{ $alternativeEmail }}** to your candidates as soon as we can.

You'll get another email from us once your dashboard is ready, with all your candidates and results in one place.

In the meantime, you don't need to do anything — and please don't contact Trinity directly. We'll handle it from here.

<x-mail::button :url="url('/dashboard')">
Back to your dashboard
</x-mail::button>

Speak soon,<br>
Paul Sheridan<br>
musicExams.help · Centre 120
</x-mail::message>
