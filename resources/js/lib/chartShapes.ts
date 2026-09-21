// resources/js/lib/chartShapes.ts
//
// SVG paths for bar marks: rounded at the data end, square at the baseline.
// Shared by DashboardCharts and MyBarChartConstructor; a guard test fails if
// a second copy appears (tests/Feature/SiteStatsGuardTest.php).

/** A column: rounded at the top. */
export function topRounded(x: number, y: number, w: number, h: number, r = 4) {
    const rr = Math.min(r, h, w / 2)
    return `M ${x} ${y + h} L ${x} ${y + rr} Q ${x} ${y} ${x + rr} ${y} L ${x + w - rr} ${y} Q ${x + w} ${y} ${x + w} ${y + rr} L ${x + w} ${y + h} Z`
}

/** A horizontal bar: rounded at the right-hand end. */
export function rightRounded(x: number, y: number, w: number, h: number, r = 4) {
    const rr = Math.min(r, w, h / 2)
    return `M ${x} ${y} L ${x + w - rr} ${y} Q ${x + w} ${y} ${x + w} ${y + rr} L ${x + w} ${y + h - rr} Q ${x + w} ${y + h} ${x + w - rr} ${y + h} L ${x} ${y + h} Z`
}
