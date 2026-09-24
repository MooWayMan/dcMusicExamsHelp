<?php

// app/Services/CertificateRenderer.php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * Draws every certificate the site hands out: the student certificates
 * (Bravo, Take a Bow, Standing Ovation, and the Showstopper / Centre Stage
 * top-scorer awards) and the teacher appreciation certificates.
 *
 * This is the ONLY place a certificate is drawn. The single-certificate
 * preview, the per-teacher weekly ZIP, the quarter batch and the artisan
 * command all come through here, so a certificate looks the same whichever
 * button made it. Before this existed the drawing code had been copied six
 * times and the copies had already drifted.
 *
 * One instance per request or batch step: blank templates are fetched from
 * S3 once and reused for every certificate drawn by that instance.
 */
class CertificateRenderer
{
    public const S3_BASE = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/';

    public const STUDENT_TEMPLATES = [
        'Bravo Certificate'            => 'certStu_1.png',
        'Take a Bow Certificate'       => 'certStu_2.png',
        'Standing Ovation Certificate' => 'certStu_3.png',
        'Centre Stage Certificate'     => 'certStu_4.png',
        'Showstopper Certificate'      => 'certStu_5.png',
    ];

    /**
     * Group-specific top-scorer templates, [tier][group] => file. Each
     * quarter awards one per (group x tier), so the certificate says
     * "highest in YOUR group" rather than "highest this quarter".
     */
    public const TOP_SCORER_TEMPLATES = [
        'Showstopper' => [
            'initial_5' => 'certStu_5_initial5.png',
            '6_8'       => 'certStu_5_g68.png',
        ],
        'Centre Stage' => [
            'initial_5' => 'certStu_4_initial5.png',
            '6_8'       => 'certStu_4_g68.png',
        ],
    ];

    public const TEACHER_TEMPLATES = [
        'Bronze Appreciation Certificate'    => 'certTeach_1.png',
        'Silver Appreciation Certificate'    => 'certTeach_2.png',
        'Gold Appreciation Certificate'      => 'certTeach_3.png',
        'Top Award Appreciation Certificate' => 'certTeach_4.png',
    ];

    /** The badge image a teacher can put on their own website or socials. */
    public const TEACHER_BADGE_PNGS = [
        'Bronze Appreciation Certificate'    => 'awardTA10.png',
        'Silver Appreciation Certificate'    => 'awardTA20.png',
        'Gold Appreciation Certificate'      => 'awardTA30.png',
        'Top Award Appreciation Certificate' => 'awardTA40.png',
    ];

    private const INK = '#1e3a5f';

    /** @var array<string,string> template file => raw PNG bytes */
    private array $templates = [];

    public static function topScorerTemplate(string $tier, string $group): ?string
    {
        return self::TOP_SCORER_TEMPLATES[$tier][$group] ?? null;
    }

    /**
     * The teacher badge earned by a quarter's candidate count: Bronze at 10,
     * Silver at 20, Gold at 30, Top Award at 40, none below 10. Counts reset
     * every quarter. Quarter End, the certificate page and the batch all ask
     * here, so the badge in the email and the one on the certificate agree.
     */
    public static function teacherBadge(int $candidates): ?string
    {
        return match (true) {
            $candidates >= 40 => 'Top Award',
            $candidates >= 30 => 'Gold',
            $candidates >= 20 => 'Silver',
            $candidates >= 10 => 'Bronze',
            default => null,
        };
    }

    /** The appreciation certificate for that badge, e.g. "Silver Appreciation Certificate". */
    public static function teacherTier(int $candidates): ?string
    {
        $badge = self::teacherBadge($candidates);

        return $badge ? "{$badge} Appreciation Certificate" : null;
    }

    /**
     * Name at 47% and "Instrument Grade N" at 52%, both right-aligned clear of
     * the badge on the left; the quarter in bold at the foot.
     */
    public function student(string $templateFile, string $name, string $instrument, string $grade, string $quarterLabel): ImageInterface
    {
        $image = $this->blank($templateFile);
        $w = $image->width();
        $h = $image->height();
        [$regular, $bold] = $this->fonts();

        $this->write($image, $name, (int) ($w * 0.92), (int) ($h * 0.47), $regular, (int) ($w * 0.038), 'right');
        $this->write($image, trim("{$instrument} Grade {$grade}"), (int) ($w * 0.92), (int) ($h * 0.52), $regular, (int) ($w * 0.028), 'right');
        $this->write($image, $quarterLabel, (int) ($w * 0.50), (int) ($h * 0.96), $bold, (int) ($w * 0.042), 'center');

        return $image;
    }

    /**
     * The school (or teacher) name at 47%, the quarter in bold at 94%.
     */
    public function teacher(string $templateFile, string $name, string $quarterLabel): ImageInterface
    {
        $image = $this->blank($templateFile);
        $w = $image->width();
        $h = $image->height();
        [$regular, $bold] = $this->fonts();

        $this->write($image, $name, (int) ($w * 0.92), (int) ($h * 0.47), $regular, (int) ($w * 0.038), 'right');
        $this->write($image, $quarterLabel, (int) ($w * 0.50), (int) ($h * 0.94), $bold, (int) ($w * 0.04), 'center');

        return $image;
    }

    public function png(ImageInterface $image): string
    {
        return (string) $image->encode(new PngEncoder());
    }

    /** One A4 page with the certificate filling it. */
    public function pdf(ImageInterface $image): string
    {
        $html = '<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>'
            .'<img src="data:image/png;base64,'.base64_encode($this->png($image)).'" style="width:210mm;height:297mm;display:block;">'
            .'</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    /** The raw badge PNG for a tier, or null if the tier has none or S3 fails. */
    public function teacherBadgePng(string $tier): ?string
    {
        $file = self::TEACHER_BADGE_PNGS[$tier] ?? null;

        return $file ? $this->download($file) : null;
    }

    private function blank(string $templateFile): ImageInterface
    {
        $bytes = $this->templates[$templateFile] ??= $this->download($templateFile)
            ?? throw new \RuntimeException("Failed to download certificate template {$templateFile}");

        return (new ImageManager(new Driver()))->decode($bytes);
    }

    private function download(string $file): ?string
    {
        $response = Http::get(self::S3_BASE.$file);

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Georgia when the app ships it, DejaVu otherwise, then any TTF on the
     * box. GD's built-in fonts only go up to size 5, so a path is needed.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function fonts(): array
    {
        $first = fn (array $paths) => collect($paths)->first(fn ($p) => $p && file_exists($p));

        $regular = $first([
            resource_path('fonts/Georgia.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            glob('/usr/share/fonts/truetype/*/*.ttf')[0] ?? null,
        ]);
        $bold = $first([
            resource_path('fonts/Georgia-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ]) ?? $regular;

        return [$regular, $bold];
    }

    private function write(ImageInterface $image, string $text, int $x, int $y, ?string $fontPath, int $size, string $align): void
    {
        $image->text($text, $x, $y, function (FontFactory $font) use ($fontPath, $size, $align) {
            if ($fontPath) {
                $font->filename($fontPath);
            }
            $font->size($size);
            $font->color(self::INK);
            $font->align($align);
        });
    }
}
