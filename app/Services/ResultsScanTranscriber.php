<?php

// app/Services/ResultsScanTranscriber.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Turns a handwritten Trinity "Examination Report" PDF into the candidates JSON
 * that /admin/results-scan already understands — the step Paul previously did by
 * hand in Cowork. It posts the PDF to the Anthropic (Claude) Messages API, which
 * reads each report and returns one record per candidate.
 *
 * This is deliberately the ONLY new trust boundary: the model transcribes, but
 * ResultsScanParser still runs the arithmetic checks and Paul still reviews and
 * commits from the editable grid. Nothing here writes to the database.
 *
 * The key lives in config/services.php ('anthropic.key'). Blank key => disabled
 * (staging/local/test run blank), so this never fires by accident off-prod.
 */
class ResultsScanTranscriber
{
    /** Max PDF size accepted for a single transcription run. */
    public const MAX_BYTES = 25 * 1024 * 1024;

    public function enabled(): bool
    {
        return (string) config('services.anthropic.key') !== '';
    }

    /**
     * Transcribe every report in the PDF to candidate records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function transcribe(UploadedFile $pdf): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Scan transcription is not configured — add an Anthropic API key first.');
        }

        $base64 = base64_encode((string) file_get_contents($pdf->getRealPath()));

        $response = Http::withHeaders([
            'x-api-key' => (string) config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(120)
            ->acceptJson()
            ->post(rtrim((string) config('services.anthropic.base_url'), '/').'/v1/messages', [
                'model' => (string) config('services.anthropic.model'),
                'max_tokens' => 8000,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'document',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $base64,
                            ],
                        ],
                        ['type' => 'text', 'text' => self::PROMPT],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            $detail = (string) ($response->json('error.message') ?? $response->status());
            throw new RuntimeException("The transcription service returned an error ({$detail}).");
        }

        $text = trim((string) $response->json('content.0.text'));

        return $this->decode($text);
    }

    /**
     * Pull the JSON array out of the model's reply, tolerating a stray code
     * fence or lead-in line, and shape-check it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function decode(string $text): array
    {
        // Strip ```json fences if present, then grab the outermost [...] block.
        $text = preg_replace('/^```(?:json)?|```$/m', '', $text) ?? $text;
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $text = $m[0];
        }

        try {
            $rows = json_decode(trim($text), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException("Couldn't read the transcription result — please try again or fall back to the JSON upload.");
        }

        if (! is_array($rows) || $rows === [] || ! array_is_list($rows)) {
            throw new RuntimeException('The transcription came back empty — check the PDF is a Trinity exam report.');
        }

        return $rows;
    }

    /**
     * The transcription instruction. Mirrors the identity block + section layout
     * of a Trinity Examination Report and the exact keys ResultsScanParser reads.
     */
    private const PROMPT = <<<'TXT'
        You are transcribing handwritten Trinity College London "Examination Report" forms into JSON.
        The PDF may contain several reports (one per candidate). Return ONE JSON object per report.

        On each form the identity block is machine-printed (subject + grade, candidate name, Candidate ID,
        Centre, and an Order number at the foot). Every MARK is handwritten: each piece and each test/
        section has an awarded mark and a printed maximum (e.g. 22 for pieces, 14/10 for tests), and there
        is a handwritten overall Total out of 100.

        Return ONLY a JSON array (no prose, no code fences), each element shaped EXACTLY like this:
        {
          "subject": "<instrument only, e.g. 'Acoustic Guitar', 'Piano', 'Rock & Pop Vocals'>",
          "grade": "<e.g. 'Grade 7', 'Initial'>",
          "candidate_name": "<printed candidate name>",
          "candidate_id": "<printed Candidate ID digits>",
          "order_number": "<printed Order number, e.g. '1-16044465878'>",
          "exam_date": "<ISO yyyy-mm-dd if legible, else ''>",
          "examiner_number": "<printed examiner number>",
          "examiner_total": <the handwritten overall total out of 100, as an integer, or null if unreadable>,
          "general_comments": "<any 'General comments' text, else ''>",
          "sections": [
            { "label": "<piece or test name, e.g. 'Graham: Anji', 'Technical Work', 'Sight Reading', 'Aural'>",
              "mark": <awarded mark as integer, or null if unreadable>,
              "max": <printed section maximum as integer> },
            ...one object per scored row on the form (all pieces AND all technical/supporting tests)...
          ]
        }

        Rules:
        - Include EVERY scored row so the section marks sum to the overall total. Do not include unscored
          rows (own composition prompts, marking-scheme text).
        - Add a "comment" field to each section with the examiner's handwritten remarks for that row if
          you can read them; if unsure, use "". Transcribe comments as best you can but NEVER invent marks.
        - Numbers must be integers or null. Do not guess a mark you cannot read — use null and it will be
          flagged for a human.
        - Output the JSON array only.
        TXT;
}
