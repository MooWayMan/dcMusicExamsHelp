<?php

// app/Services/QuarterCertificateBatch.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Support\EntryCredit;
use App\Support\TopScorers;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Support\PublicName;
use App\Support\QuarterLabel;

/**
 * A whole quarter's certificates, grouped by teacher into ZIPs.
 *
 * The work is split into small steps so no single web request is long:
 *
 *   start()   clears the quarter's folder and lists the steps
 *   step()    draws one slice of one teacher's certificates
 *   finish()  zips each teacher's folder and builds the master ZIP
 *
 * The page calls them in turn and shows progress. Doing it all in one request
 * timed out at around fifty candidates, because every certificate is a
 * full-size PNG drawn and wrapped in a PDF.
 *
 * Output on the local disk, unchanged from the one-request version (Quarter
 * End reads the ZIP names back to show download links):
 *
 *   certificates/{year}-Q{q}/{Teacher}/...pdf
 *   certificates/{year}-Q{q}/top-scorers/...pdf
 *   certificates/{year}-Q{q}/zips/{Teacher}_Q{q}_{year}.zip
 *   certificates/{year}-Q{q}/ALL_Q{q}_{year}_Certificates.zip
 */
class QuarterCertificateBatch
{
    /** Certificates drawn per step. Each one takes a second or two. */
    public const PER_STEP = 8;

    public function __construct(private CertificateRenderer $renderer, private EntryCertificates $certificates) {}

    public static function dir(int $quarter, int $year): string
    {
        return "certificates/{$year}-Q{$quarter}";
    }


    /**
     * Every scored, non-cancelled entry sat in the quarter. A Below Pass
     * still gets a Bravo — that is the point of the scheme.
     *
     * @return Collection<int,ExamEntry>
     */
    public function entries(int $quarter, int $year): Collection
    {
        return $this->inQuarter(ExamEntry::whereNotNull('score'), $quarter, $year);
    }

    /**
     * Clear the quarter's folder and list the steps, or null when there is
     * nothing to draw.
     *
     * @return array{quarter_label: string, steps: list<array{teacher: string, part: int, parts: int}>}|null
     */
    public function start(int $quarter, int $year): ?array
    {
        $groups = $this->byTeacher($this->entries($quarter, $year));
        if ($groups->isEmpty()) {
            return null;
        }

        Storage::disk('local')->deleteDirectory(self::dir($quarter, $year));
        Storage::disk('local')->makeDirectory(self::dir($quarter, $year));

        $steps = [];
        foreach ($groups as $teacher => $teacherEntries) {
            $parts = max(1, (int) ceil($teacherEntries->count() / self::PER_STEP));
            for ($part = 1; $part <= $parts; $part++) {
                $steps[] = ['teacher' => (string) $teacher, 'part' => $part, 'parts' => $parts];
            }
        }

        return ['quarter_label' => QuarterLabel::for($quarter, $year), 'steps' => $steps];
    }

    /**
     * Draw one slice of one teacher's student certificates. The first slice
     * also draws the teacher's appreciation certificate and badge, and any
     * top-scorer award one of their students won.
     *
     * @return int certificates written
     */
    public function step(int $quarter, int $year, string $teacher, int $part): int
    {
        $entries = $this->entries($quarter, $year);
        $credit = $this->creditName($entries);
        $teacherEntries = $entries->groupBy($credit)->get($teacher, collect())->sortBy('id')->values();
        $label = QuarterLabel::for($quarter, $year);
        $folder = self::dir($quarter, $year).'/'.EntryCertificates::safe($teacher);
        Storage::disk('local')->makeDirectory($folder);

        $written = 0;
        foreach ($teacherEntries->slice(($part - 1) * self::PER_STEP, self::PER_STEP) as $entry) {
            $pdf = $this->certificates->pdf($entry, $label);
            if ($pdf === null) {
                continue;
            }
            Storage::disk('local')->put("{$folder}/".EntryCertificates::fileName($entry->candidate_name, $entry->certificate_name), $pdf);
            $written++;
        }

        if ($part === 1) {
            $written += $this->teacherAward($quarter, $year, $teacher, $teacherEntries->count(), $folder);
            foreach ($this->topScorerAwards($entries) as $award) {
                if ($credit($award['entry']) === $teacher && $this->drawTopScorer($award, $quarter, $year, $folder)) {
                    $written++;
                }
            }
        }

        return $written;
    }

    /**
     * Zip every teacher's folder, then zip the zips.
     *
     * @return array{total: int, quarter_label: string, teachers: array<string,int>, download_links: array<string,string>, master_zip: ?string, top_scorer_certs: list<array>, top_scorer_count: int}
     */
    public function finish(int $quarter, int $year): array
    {
        $disk = Storage::disk('local');
        $dir = self::dir($quarter, $year);
        $entries = $this->entries($quarter, $year);
        $disk->makeDirectory("{$dir}/zips");

        $teachers = [];
        $links = [];
        foreach ($this->byTeacher($entries)->keys() as $teacher) {
            $folder = "{$dir}/".EntryCertificates::safe($teacher);
            $files = $disk->exists($folder) ? $disk->files($folder) : [];
            $teachers[$teacher] = collect($files)->filter(fn ($f) => str_ends_with($f, '.pdf'))->count();
            if ($files === []) {
                continue;
            }
            $zipName = "{$dir}/zips/".EntryCertificates::safe($teacher)."_Q{$quarter}_{$year}.zip";
            if ($this->zip($zipName, $files)) {
                $links[$teacher] = $zipName;
            }
        }

        $master = "{$dir}/ALL_Q{$quarter}_{$year}_Certificates.zip";
        $masterMade = $links !== [] && $this->zip($master, array_values($links));

        $topScorers = collect($this->topScorerAwards($entries))
            ->map(fn ($award) => $this->topScorerRow($award, $quarter, $year))
            ->filter(fn ($row) => $disk->exists($row['standalone_path']))
            ->values()
            ->all();

        return [
            'total' => array_sum($teachers),
            'quarter_label' => QuarterLabel::for($quarter, $year),
            'teachers' => $teachers,
            'download_links' => $links,
            'master_zip' => $masterMade ? $master : null,
            'top_scorer_certs' => $topScorers,
            'top_scorer_count' => count($topScorers),
        ];
    }

    /**
     * Just the top-scorer PDFs, into top-scorers/ only, for attaching to the
     * winner emails without re-running the whole quarter.
     *
     * @return list<array>
     */
    public function topScorersOnly(int $quarter, int $year): array
    {
        $rows = [];
        foreach ($this->topScorerAwards($this->entries($quarter, $year)) as $award) {
            if ($this->drawTopScorer($award, $quarter, $year, null)) {
                $rows[] = $this->topScorerRow($award, $quarter, $year);
            }
        }

        return $rows;
    }

    /**
     * The highest Distinction and highest Merit in each grade group; every
     * candidate tied on the top score gets the award.
     *
     * @param  Collection<int,ExamEntry>  $entries
     * @return list<array{entry: ExamEntry, group: string, band: string, certificate: string}>
     */
    public function topScorerAwards(Collection $entries): array
    {
        $awards = [];
        foreach (['initial_5', '6_8'] as $group) {
            foreach (['distinction', 'merit'] as $band) {
                $bucket = $entries->filter(fn ($e) => $e->score !== null
                    && TopScorers::groupOf((string) $e->grade) === $group
                    && TopScorers::bandOf((int) $e->score) === $band);
                if ($bucket->isEmpty()) {
                    continue;
                }
                $top = $bucket->max('score');
                foreach ($bucket->where('score', $top) as $entry) {
                    $awards[] = [
                        'entry' => $entry,
                        'group' => $group,
                        'band' => $band,
                        'certificate' => $band === 'distinction' ? 'Showstopper' : 'Centre Stage',
                    ];
                }
            }
        }

        return $awards;
    }

    /**
     * The badge counts every non-cancelled booking in the quarter, NO_SHOW,
     * Fails and still-pending included, because the booking earns the tally.
     * Same rule as Quarter End, so the certificate and the email agree.
     */
    public function teacherCandidateCount(int $quarter, int $year, string $teacher): int
    {
        return $this->inQuarter(ExamEntry::where('teacher_name', $teacher), $quarter, $year)->count();
    }

    /**
     * The appreciation certificate a teacher earns for a quarter, or null
     * below 10. $scoredCount is how many of the quarter's scored entries are
     * credited to them, the fallback when no entry names them directly. The
     * batch and the dashboard's Rewards card both ask here, so the badge a
     * teacher sees is the certificate they are given.
     */
    public function teacherTierFor(int $quarter, int $year, string $teacher, int $scoredCount): ?string
    {
        return CertificateRenderer::teacherTier($this->teacherCandidateCount($quarter, $year, $teacher) ?: $scoredCount);
    }

    /**
     * A teacher's appreciation certificate for a quarter, or null if drawing
     * fails. The one place it is drawn: the batch and the teacher's own
     * dashboard download both ask here. A teacher linked to a school gets the
     * school's name on it (existing rule).
     */
    public function teacherCertificatePdf(string $teacher, string $tier, int $quarter, int $year): ?string
    {
        try {
            $school = ExamContact::withType('teacher')
                ->with('schools')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($teacher)])
                ->first()?->schools->first()?->name;

            return $this->renderer->pdf($this->renderer->teacher(
                CertificateRenderer::TEACHER_TEMPLATES[$tier], $school ?? $teacher, QuarterLabel::for($quarter, $year),
            ));
        } catch (\Throwable $e) {
            Log::error("Badge cert failed for {$teacher}: {$e->getMessage()}");

            return null;
        }
    }

    private function teacherAward(int $quarter, int $year, string $teacher, int $scoredCount, string $folder): int
    {
        if ($teacher === 'Unassigned') {
            return 0;
        }

        $tier = $this->teacherTierFor($quarter, $year, $teacher, $scoredCount);
        if (! $tier) {
            return 0;
        }

        $safe = EntryCertificates::safe($teacher);
        $written = 0;

        $pdf = $this->teacherCertificatePdf($teacher, $tier, $quarter, $year);
        if ($pdf !== null) {
            $short = str_replace([' Certificate', ' '], ['', '_'], $tier);
            Storage::disk('local')->put("{$folder}/{$safe}_{$short}.pdf", $pdf);
            $written++;
        }

        $badge = $this->renderer->teacherBadgePng($tier);
        if ($badge !== null) {
            $short = str_replace([' Appreciation Certificate', ' '], ['', '_'], $tier);
            Storage::disk('local')->put("{$folder}/{$safe}_{$short}_Badge.png", $badge);
        }

        return $written;
    }

    private function drawTopScorer(array $award, int $quarter, int $year, ?string $teacherFolder): bool
    {
        $entry = $award['entry'];
        $certName = $award['certificate'].' Certificate';
        $file = CertificateRenderer::topScorerTemplate($award['certificate'], $award['group'])
            ?? CertificateRenderer::STUDENT_TEMPLATES[$certName];

        try {
            $pdf = $this->renderer->pdf($this->renderer->student(
                $file, $entry->candidate_name, $entry->instrument?->name ?? '', (string) ($entry->grade ?? ''), QuarterLabel::for($quarter, $year),
            ));
        } catch (\Throwable $e) {
            Log::error("Top-scorer cert failed for {$entry->candidate_name}: {$e->getMessage()}");

            return false;
        }

        $name = EntryCertificates::fileName($entry->candidate_name, $certName);
        Storage::disk('local')->makeDirectory(self::dir($quarter, $year).'/top-scorers');
        Storage::disk('local')->put(self::dir($quarter, $year)."/top-scorers/{$name}", $pdf);
        if ($teacherFolder) {
            Storage::disk('local')->put("{$teacherFolder}/{$name}", $pdf);
        }

        return true;
    }

    private function topScorerRow(array $award, int $quarter, int $year): array
    {
        $entry = $award['entry'];
        $certName = $award['certificate'].' Certificate';
        $path = self::dir($quarter, $year).'/top-scorers/'.EntryCertificates::fileName($entry->candidate_name, $certName);

        return [
            'name' => $entry->candidate_name,
            'short_name' => PublicName::short($entry->candidate_name),
            'certificate' => $certName,
            'group' => $award['group'],
            'band' => $award['band'],
            'score' => $entry->score,
            'instrument' => $entry->instrument?->name,
            'grade' => $entry->grade,
            'standalone_path' => $path,
            'download_url' => '/admin/certificates/download/'.$path,
        ];
    }

    /** GDPR display name: "Anna M". */


    /**
     * @param  Collection<int,ExamEntry>  $entries
     * @return Collection<string,Collection<int,ExamEntry>>
     */
    private function byTeacher(Collection $entries): Collection
    {
        return $entries->groupBy($this->creditName($entries));
    }

    /**
     * Enrolment-list entries have no teacher_name until results arrive, so
     * they are credited to whoever submitted the booking (see EntryCredit).
     */
    private function creditName(Collection $entries): \Closure
    {
        $submitters = EntryCredit::submitterNames($entries);

        return fn (ExamEntry $e) => EntryCredit::nameFor($e, $submitters);
    }

    /**
     * Non-cancelled entries whose exam date (or, failing that, the order's
     * requested start date) falls in the quarter.
     */
    private function inQuarter($query, int $quarter, int $year): Collection
    {
        $start = CarbonImmutable::create($year, (($quarter - 1) * 3) + 1, 1)->startOfDay();
        $end = $start->addMonths(3)->subDay()->endOfDay();

        return $query
            ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED'))
            ->with(['instrument:id,name', 'order:id,requested_start_date'])
            ->get()
            ->filter(function (ExamEntry $entry) use ($start, $end) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;

                return $date && $date->between($start, $end);
            })
            ->values();
    }

    /** @param  list<string>  $files  paths on the local disk */
    private function zip(string $zipName, array $files): bool
    {
        $zip = new ZipArchive();
        if ($zip->open(Storage::disk('local')->path($zipName), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        foreach ($files as $file) {
            $zip->addFile(Storage::disk('local')->path($file), basename($file));
        }

        return $zip->close();
    }
}
