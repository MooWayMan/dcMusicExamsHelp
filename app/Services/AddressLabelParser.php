<?php

// app/Services/AddressLabelParser.php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Turns Trinity's messy address-label data into a clean, de-duplicated set of
 * labels ready to reflow onto Paul's Avery L7173 sheets.
 *
 * Trinity hands Paul "address label" PDFs laid out 8-up (2 columns × 4 rows)
 * that are full of junk: a trailing "United Kingdom" on every address, town
 * lines duplicated, a street line repeated, and a "combined then split"
 * pattern where "Willow Cottage, 4 The Ridgeway" is followed by "4 The
 * Ridgeway" again. The same teacher also enters candidates on different days,
 * so appears in several day PDFs and Paul only wants ONE label per teacher.
 *
 * Strategy — coordinate based, not text based. smalot's getText() interleaves
 * the two columns, so instead we read each text run's X/Y position
 * (getDataTm), split the page into columns by the largest horizontal gap,
 * group runs into lines by Y, then group lines into address blocks by the
 * vertical gap between them. Each block is then cleaned and the whole set is
 * de-duplicated. This survives the fact that Trinity's mistakes aren't
 * consistent — the editable grid in the UI is the final safety net.
 *
 * All the shaping methods (clean/dedupe/postcode/normaliseName) are pure so
 * they can be unit-tested without a PDF; extractBlocks() is covered by an
 * integration test against captured Trinity sample PDFs.
 */
class AddressLabelParser
{
    /** A UK postcode, tolerant of a missing space (e.g. "L255HP"). */
    private const POSTCODE_RE = '/[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}/i';

    /** Minimum horizontal gap (pt) between the two label columns. */
    private const COLUMN_GAP = 100.0;

    /** Runs whose baseline Y is within this many pt are on the same line. */
    private const LINE_TOLERANCE = 4.0;

    /** A vertical gap (pt) larger than this starts a new address block. */
    private const BLOCK_GAP = 20.0;

    public function __construct(private PdfParser $parser = new PdfParser()) {}

    /**
     * Parse one or more label PDFs into a de-duplicated list of labels.
     *
     * @param  array<int, string>  $paths  absolute paths to the PDF files
     * @return array<int, array{name: string, lines: array<int, string>, postcode: string, source: string, flag: string}>
     */
    public function parseFiles(array $paths): array
    {
        $labels = [];

        foreach ($paths as $source => $path) {
            $name = is_string($source) ? $source : basename($path);
            foreach ($this->extractBlocks($path) as $block) {
                $lines = $this->clean($block);
                if ($lines === []) {
                    continue;
                }
                $labels[] = [
                    'name' => $lines[0],
                    'lines' => $lines,
                    'postcode' => $this->postcode($lines),
                    'source' => $name,
                    'flag' => '',
                    'dupeKey' => '',
                ];
            }
        }

        return $this->dedupe($labels);
    }

    /**
     * Extract the raw address blocks (each an array of text lines) from a
     * single PDF, reading every page.
     *
     * @return array<int, array<int, string>>
     */
    public function extractBlocks(string $path): array
    {
        $document = $this->parser->parseFile($path);
        $blocks = [];

        foreach ($document->getPages() as $page) {
            $runs = [];
            foreach ($page->getDataTm() as $item) {
                [$tm, $text] = $item;
                $text = trim((string) $text);
                if ($text === '') {
                    continue;
                }
                $runs[] = [
                    'x' => (float) $tm[4],
                    'y' => (float) $tm[5],
                    'text' => $text,
                ];
            }
            foreach ($this->blocksFromRuns($runs) as $block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    /**
     * Turn a page's positioned text runs into ordered address blocks.
     *
     * @param  array<int, array{x: float, y: float, text: string}>  $runs
     * @return array<int, array<int, string>>
     */
    public function blocksFromRuns(array $runs): array
    {
        if ($runs === []) {
            return [];
        }

        $mid = $this->columnSplit($runs);

        // Bucket runs into columns (0 = left, 1 = right).
        $columns = [0 => [], 1 => []];
        foreach ($runs as $run) {
            $col = ($mid === null || $run['x'] < $mid) ? 0 : 1;
            $columns[$col][] = $run;
        }

        $blocks = [];
        foreach ($columns as $col => $colRuns) {
            foreach ($this->blocksFromColumn($colRuns) as [$topY, $lines]) {
                // Round Y into ~15pt bands so a left/right pair on the same
                // visual row sorts together; then read left column first.
                $blocks[] = [
                    'band' => (int) round($topY / 15),
                    'col' => $col,
                    'lines' => $lines,
                ];
            }
        }

        // Trinity's PDF origin is bottom-left, so a LARGER Y is higher up the
        // page. Read top-to-bottom (band desc), then left column before right.
        usort($blocks, function (array $a, array $b): int {
            return [$b['band'], $a['col']] <=> [$a['band'], $b['col']];
        });

        return array_map(static fn (array $b): array => $b['lines'], $blocks);
    }

    /**
     * Find the X midpoint separating the two label columns, or null if the
     * page only uses one column. Uses the largest horizontal gap between the
     * distinct run start positions.
     *
     * @param  array<int, array{x: float, y: float, text: string}>  $runs
     */
    private function columnSplit(array $runs): ?float
    {
        $xs = [];
        foreach ($runs as $run) {
            $xs[(string) round($run['x'])] = round($run['x']);
        }
        $xs = array_values($xs);
        sort($xs);

        if (count($xs) < 2) {
            return null;
        }

        $bestGap = 0.0;
        $mid = null;
        for ($i = 0; $i < count($xs) - 1; $i++) {
            $gap = $xs[$i + 1] - $xs[$i];
            if ($gap > $bestGap) {
                $bestGap = $gap;
                $mid = ($xs[$i] + $xs[$i + 1]) / 2;
            }
        }

        return $bestGap > self::COLUMN_GAP ? $mid : null;
    }

    /**
     * Group one column's runs into address blocks.
     *
     * @param  array<int, array{x: float, y: float, text: string}>  $runs
     * @return array<int, array{0: float, 1: array<int, string>}>  [topY, lines][]
     */
    private function blocksFromColumn(array $runs): array
    {
        if ($runs === []) {
            return [];
        }

        // Sort top-to-bottom (Y desc), then left-to-right within a line.
        usort($runs, static fn (array $a, array $b): int => [$b['y'], $a['x']] <=> [$a['y'], $b['x']]);

        // Assemble lines: runs whose Y is within tolerance join one line.
        $lines = [];
        foreach ($runs as $run) {
            $last = $lines === [] ? null : $lines[count($lines) - 1];
            if ($last !== null && abs($run['y'] - $last['y']) < self::LINE_TOLERANCE) {
                $lines[count($lines) - 1]['parts'][] = $run;
            } else {
                $lines[] = ['y' => $run['y'], 'parts' => [$run]];
            }
        }

        $assembled = [];
        foreach ($lines as $line) {
            $parts = $line['parts'];
            usort($parts, static fn (array $a, array $b): int => $a['x'] <=> $b['x']);
            $text = trim(implode(' ', array_map(static fn (array $p): string => $p['text'], $parts)));
            $assembled[] = ['y' => $line['y'], 'text' => $text];
        }

        // Split into blocks on a big vertical gap.
        $blocks = [];
        $current = [];
        $prevY = null;
        foreach ($assembled as $line) {
            if ($prevY !== null && ($prevY - $line['y']) > self::BLOCK_GAP) {
                $blocks[] = $current;
                $current = [];
            }
            $current[] = $line;
            $prevY = $line['y'];
        }
        if ($current !== []) {
            $blocks[] = $current;
        }

        return array_map(
            static fn (array $block): array => [
                $block[0]['y'],
                array_map(static fn (array $l): string => $l['text'], $block),
            ],
            $blocks
        );
    }

    /**
     * Clean a single address block: strip the trailing country line, collapse
     * Trinity's duplicated / combined-then-split lines, and drop any repeated
     * line. Order is preserved.
     *
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    public function clean(array $lines): array
    {
        // Split any combined "A, B, C" line into one part per line, and drop
        // the trailing "United Kingdom". This keeps single lines short enough
        // to fit a label, and collapses Trinity's "combined then split"
        // duplicates cleanly in the next step.
        $expanded = [];
        foreach ($lines as $line) {
            foreach (explode(',', (string) $line) as $part) {
                $part = trim($part);
                if ($part !== '' && strcasecmp($part, 'United Kingdom') !== 0) {
                    $expanded[] = $part;
                }
            }
        }

        // Collapse consecutive duplicate lines (e.g. "Liverpool" / "Liverpool",
        // or a part that Trinity also printed on its own after the combined line).
        $step = [];
        foreach ($expanded as $l) {
            if ($step !== [] && strcasecmp(end($step), $l) === 0) {
                continue;
            }
            $step[] = $l;
        }

        // Remove any remaining exact duplicate line anywhere (keep first).
        $seen = [];
        $out = [];
        foreach ($step as $l) {
            $key = strtolower($l);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $l;
        }

        return $out;
    }

    /**
     * De-duplicate labels across all uploaded files.
     *
     * - Exact duplicate (same name AND same full body) → dropped silently.
     * - Near duplicate (same postcode, or names within edit-distance 2, or the
     *   same first+last name token — catches typos and inserted middle names)
     *   → KEPT but flagged for Paul to review in the grid.
     *
     * @param  array<int, array{name: string, lines: array<int, string>, postcode: string, source: string, flag: string}>  $labels
     * @return array<int, array{name: string, lines: array<int, string>, postcode: string, source: string, flag: string}>
     */
    public function dedupe(array $labels): array
    {
        $kept = [];
        $group = 0;

        foreach ($labels as $label) {
            $label['dupeKey'] = $label['dupeKey'] ?? '';
            $name = $this->normaliseName($label['name']);
            $body = $this->body($label['lines']);
            $postcode = $label['postcode'];

            $isExact = false;
            foreach ($kept as $k) {
                if ($this->normaliseName($k['name']) === $name && $this->body($k['lines']) === $body) {
                    $isExact = true;
                    break;
                }
            }
            if ($isExact) {
                continue;
            }

            foreach ($kept as $i => $k) {
                $kName = $this->normaliseName($k['name']);
                $samePostcode = $postcode !== '' && $k['postcode'] === $postcode;
                $closeName = levenshtein($kName, $name) <= 2;
                $sameEnds = $this->nameEnds($k['name']) === $this->nameEnds($label['name']);
                if ($samePostcode || $closeName || $sameEnds) {
                    // Give this near-dup and the label it matched a shared key
                    // so the grid can highlight them together on hover.
                    if (($kept[$i]['dupeKey'] ?? '') === '') {
                        $kept[$i]['dupeKey'] = 'g'.(++$group);
                    }
                    $label['dupeKey'] = $kept[$i]['dupeKey'];
                    $label['flag'] = 'Possible duplicate of '.$k['name'].' ('.$k['source'].')';
                    break;
                }
            }

            $kept[] = $label;
        }

        return $kept;
    }

    /**
     * Parse CSV text where each non-empty row is one address; the first
     * populated cell that looks like a name leads. Cells become label lines.
     *
     * @return array<int, array{name: string, lines: array<int, string>, postcode: string, source: string, flag: string}>
     */
    public function parseCsv(string $csv, string $source = 'spreadsheet'): array
    {
        $labels = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
        $rows = array_filter(array_map(
            static fn (string $line): array => str_getcsv($line, ',', '"', ''),
            $lines,
        ));

        foreach ($rows as $row) {
            $cells = array_map(static fn ($c): string => trim((string) $c), $row);
            $lines = array_values(array_filter($cells, static fn (string $c): bool => $c !== ''));
            $lines = $this->clean($lines);
            if ($lines === []) {
                continue;
            }
            $labels[] = [
                'name' => $lines[0],
                'lines' => $lines,
                'postcode' => $this->postcode($lines),
                'source' => $source,
                'flag' => '',
                'dupeKey' => '',
            ];
        }

        return $this->dedupe($labels);
    }

    /** Pull the first UK postcode out of an address, normalised (no spaces, upper). */
    public function postcode(array $lines): string
    {
        foreach ($lines as $line) {
            if (preg_match(self::POSTCODE_RE, str_replace(' ', '', $line), $m)) {
                return strtoupper($m[0]);
            }
        }

        return '';
    }

    /** Lower-case, letters-and-spaces-only form of a name for comparison. */
    private function normaliseName(string $name): string
    {
        return trim(preg_replace('/[^a-z ]/', '', strtolower($name)) ?? '');
    }

    /** First + last token of a normalised name (catches inserted middle names). */
    private function nameEnds(string $name): string
    {
        $tokens = array_values(array_filter(explode(' ', $this->normaliseName($name))));
        if ($tokens === []) {
            return '';
        }

        return $tokens[0].' '.$tokens[count($tokens) - 1];
    }

    /** Whole address as one normalised string, for exact-duplicate matching. */
    private function body(array $lines): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $lines)) ?? ''));
    }
}
