<?php

// app/Services/AddressLabelPdf.php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Renders cleaned address labels onto Paul's Avery L7173 sheets as a
 * print-ready PDF (10 labels per A4 sheet, 2 columns × 5 rows).
 *
 * The geometry below is the LOCKED calibration confirmed against Paul's
 * physical sheets (see Projects/MusicExams.help/label-tool/). Do not tweak
 * without re-calibrating: the column gutter in particular (2.5mm) is what
 * fixed the small horizontal drift the old no-gutter output had. Always print
 * the resulting PDF at Actual Size / 100% — never "fit to page".
 */
class AddressLabelPdf
{
    // A4 page, all values in millimetres.
    private const PAGE_W = 210.0;

    private const PAGE_H = 297.0;

    private const COLS = 2;

    private const ROWS = 5;

    private const LABEL_W = 99.1;

    private const LABEL_H = 57.0;

    /** Page left edge → first label's left edge. */
    private const LEFT_MARGIN = 4.65;

    /** Gutter between the two columns (column pitch = LABEL_W + COL_GAP). */
    private const COL_GAP = 2.5;

    /** Page top edge → first label's top edge. */
    private const TOP_MARGIN = 6.0;

    /** Text inset inside each label. */
    private const TEXT_INSET_X = 5.0;

    private const TEXT_INSET_Y = 6.0;

    private const PER_SHEET = self::COLS * self::ROWS;

    /**
     * Build the label-sheet PDF and return the raw bytes.
     *
     * @param  array<int, array<int, string>>  $labels  each label is a list of address lines
     */
    public function render(array $labels): string
    {
        // Drop any empty labels, then chunk into sheets of 10.
        $labels = array_values(array_filter(
            array_map(fn (array $lines): array => $this->tidyLines($lines), $labels),
            static fn (array $lines): bool => $lines !== [],
        ));

        $sheets = array_chunk($labels, self::PER_SHEET);
        if ($sheets === []) {
            $sheets = [[]];
        }

        $html = $this->documentHtml($sheets);

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    /** @param array<int, array<int, array<int, string>>> $sheets */
    private function documentHtml(array $sheets): string
    {
        $sheetHtml = '';
        $lastSheet = count($sheets) - 1;

        foreach ($sheets as $i => $sheetLabels) {
            $break = $i < $lastSheet ? 'page-break-after: always;' : '';
            $labelsHtml = '';

            foreach ($sheetLabels as $slot => $lines) {
                $col = $slot % self::COLS;
                $row = intdiv($slot, self::COLS);
                $x = self::LEFT_MARGIN + $col * (self::LABEL_W + self::COL_GAP) + self::TEXT_INSET_X;
                $y = self::TOP_MARGIN + $row * self::LABEL_H + self::TEXT_INSET_Y;
                $w = self::LABEL_W - self::TEXT_INSET_X * 2;

                $body = implode('<br>', array_map(static fn (string $l): string => e($l), $lines));
                $fontSize = $this->fontSizeFor($lines);
                // Hard height cap so a label can never bleed into the one below.
                $h = self::LABEL_H - self::TEXT_INSET_Y - 2.0;

                $labelsHtml .= sprintf(
                    '<div class="label" style="left:%.2fmm; top:%.2fmm; width:%.2fmm; height:%.2fmm; font-size:%.1fpt;">%s</div>',
                    $x,
                    $y,
                    $w,
                    $h,
                    $fontSize,
                    $body,
                );
            }

            $sheetHtml .= sprintf('<div class="sheet" style="%s">%s</div>', $break, $labelsHtml);
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    .sheet {
        position: relative;
        width: {$this->mm(self::PAGE_W)};
        height: {$this->mm(self::PAGE_H)};
        overflow: hidden;
    }
    .label {
        position: absolute;
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        line-height: 1.25;
        color: #000;
        overflow: hidden;
    }
</style>
</head>
<body>
{$sheetHtml}
</body>
</html>
HTML;
    }

    private function mm(float $value): string
    {
        return sprintf('%.2fmm', $value);
    }

    /**
     * Shrink-to-fit font size (pt) for a label: 11pt normally, dropping toward
     * 8pt when there are many lines or a very long line, so the text fits the
     * sticker. The label's fixed height + overflow:hidden are the hard backstop.
     *
     * @param  array<int, string>  $lines
     */
    private function fontSizeFor(array $lines): float
    {
        $count = max(1, count($lines));
        $longest = 0;
        foreach ($lines as $l) {
            $longest = max($longest, mb_strlen($l));
        }

        $mmPerPt = 0.352778;
        $lineFactor = 1.25;
        $availH = self::LABEL_H - self::TEXT_INSET_Y - 2.0;   // ~49mm of usable height
        $availW = self::LABEL_W - self::TEXT_INSET_X * 2;     // ~89mm of usable width

        $byHeight = $availH / ($count * $lineFactor * $mmPerPt);
        // ~0.52 = average glyph width as a fraction of font size for DejaVu Sans.
        $byWidth = $longest > 0 ? $availW / ($longest * 0.52 * $mmPerPt) : 11.0;

        return max(8.0, min(11.0, $byHeight, $byWidth));
    }

    /**
     * Normalise a label's lines: trim, drop blanks, cap at 8 lines so an
     * over-long address can't spill into the label below.
     *
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function tidyLines(array $lines): array
    {
        $lines = array_values(array_filter(
            array_map('trim', $lines),
            static fn (string $l): bool => $l !== '',
        ));

        return array_slice($lines, 0, 8);
    }
}
