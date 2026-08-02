{{-- resources/views/exports/teacher-results.blade.php --}}
{{--
    Printable results summary for the teacher dashboard's "Download PDF".
    Replaces the per-teacher report that used to be built into the Quarter End
    ZIP — same information, but generated on demand for whatever date range the
    teacher picks, rather than one fixed quarter.
--}}
@php
    $band = function ($entry) {
        if ($entry->score === null) {
            return in_array($entry->notes, \App\Models\ExamEntry::NOTES_NO_RESULT, true)
                ? str_replace('_', ' ', ucfirst(strtolower($entry->notes)))
                : 'Awaiting';
        }
        return $entry->result_band;
    };

    $scored = $entries->filter(fn ($e) => $e->score !== null);
    $distinctions = $scored->filter(fn ($e) => $e->score >= 87)->count();
    $merits = $scored->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();
    $passes = $scored->filter(fn ($e) => $e->score >= 60 && $e->score < 75)->count();
    $belowPass = $scored->filter(fn ($e) => $e->score < 60)->count();
    $awaiting = $entries->filter(fn ($e) => \App\Support\EntryCredit::isAwaitingResult($e))->count();
@endphp
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 40px; }
        body { font-family: Georgia, serif; color: #1e3a5f; font-size: 12px; }
        .header { background: #0f1b2d; padding: 20px 30px; text-align: center; margin: -30px -40px 20px -40px; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; }
        .header p { color: rgba(255,255,255,0.8); font-size: 13px; margin: 5px 0 0; }
        .summary-box { display: inline-block; padding: 8px 16px; margin-right: 10px; border-radius: 6px; font-weight: bold; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0f1b2d; color: #ffffff; padding: 10px 12px; text-align: left; font-size: 12px; }
        td { padding: 8px 12px; border-bottom: 1px solid #dddddd; }
        tr:nth-child(even) { background: #f5f7fa; }
        .center { text-align: center; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 2px solid #1a4a7a; font-size: 10px; color: #666666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>musicExams.help</h1>
        <p>Results for {{ $contactName }} — {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</p>
    </div>

    <div style="margin-bottom:15px;">
        <span class="summary-box" style="background:#f0e6ea;color:#7a1f3d;">{{ $distinctions }} Distinction{{ $distinctions !== 1 ? 's' : '' }}</span>
        <span class="summary-box" style="background:#e6f0f2;color:#2a6e7a;">{{ $merits }} Merit{{ $merits !== 1 ? 's' : '' }}</span>
        <span class="summary-box" style="background:#e8edf2;color:#1e3a5f;">{{ $passes }} Pass{{ $passes !== 1 ? 'es' : '' }}</span>
        @if ($belowPass > 0)
            <span class="summary-box" style="background:#f0f0f0;color:#666666;">{{ $belowPass }} Below Pass</span>
        @endif
        <span class="summary-box" style="background:#f5f5f5;color:#333333;">{{ $entries->count() }} Total</span>
        @if ($awaiting > 0)
            <span class="summary-box" style="background:#eef3fb;color:#1a4a7a;">{{ $awaiting }} Awaiting Results</span>
        @endif
    </div>

    @if ($entries->isEmpty())
        <p>No candidates in this date range.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Instrument</th>
                    <th class="center">Grade</th>
                    <th class="center">Exam date</th>
                    <th class="center">Score</th>
                    <th class="center">Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries->sortByDesc('score') as $entry)
                    <tr>
                        <td>{{ $entry->candidate_name }}</td>
                        <td>{{ $entry->instrument?->name }}</td>
                        <td class="center">{{ $entry->grade }}</td>
                        <td class="center">{{ $entry->exam_date?->format('j M Y') ?? '—' }}</td>
                        <td class="center">{{ $entry->score ?? '—' }}</td>
                        <td class="center"><strong>{{ $band($entry) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        musicExams.help — Trinity College London Exam Centre 120<br>
        Generated {{ now()->format('j M Y') }}. If you have any queries, please get in touch.
    </div>
</body>
</html>
