<!-- resources/js/components/dashboard/DashboardCharts.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'

interface ChartEntry {
    grade: string | null
    result: string | null
    score: number | null
    exam_date: string | null
}

const props = defineProps<{
    entries: ChartEntry[]
    from: string
    to: string
}>()

// Trinity's own band boundaries, mirrored from ExamEntry::getResultBandAttribute().
const PASS = 60
const MERIT = 75
const DISTINCTION = 87

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

// The controller sends dates pre-formatted as "d M Y", so parse that shape
// rather than trusting Date() with a non-ISO string.
function parseDmy(value: string | null): Date | null {
    if (!value) return null
    const [d, m, y] = value.split(' ')
    const month = MONTHS.indexOf(m)
    if (month < 0) return null
    return new Date(Number(y), month, Number(d))
}

// ─── Shared tooltip ───────────────────────────────────────────────────────
// Hover enhances; it never gates. Every value here is also printed on the
// chart itself (legend counts, bar-tip values), so nothing is hover-only.
const tip = ref<{ x: number; y: number; label: string; value: string } | null>(null)
function showTip(event: MouseEvent, label: string, value: string) {
    const host = (event.currentTarget as SVGElement).ownerSVGElement?.parentElement
    if (!host) return
    const box = host.getBoundingClientRect()
    tip.value = { x: event.clientX - box.left, y: event.clientY - box.top, label, value }
}
function hideTip() {
    tip.value = null
}

// ─── 1. Results mix (donut) ───────────────────────────────────────────────
interface Band {
    key: string
    label: string
    count: number
    color: string
}

const bands = computed<Band[]>(() => {
    const counts = { Distinction: 0, Merit: 0, Pass: 0, Below: 0, Awaiting: 0 }
    for (const e of props.entries) {
        if (e.score === null && e.result === null) counts.Awaiting++
        else if (e.result === 'Distinction' || (e.score ?? -1) >= DISTINCTION) counts.Distinction++
        else if (e.result === 'Merit' || (e.score ?? -1) >= MERIT) counts.Merit++
        else if (e.result === 'Pass' || (e.score ?? -1) >= PASS) counts.Pass++
        else counts.Below++
    }
    return [
        { key: 'distinction', label: 'Distinction', count: counts.Distinction, color: 'var(--chart-distinction)' },
        { key: 'merit', label: 'Merit', count: counts.Merit, color: 'var(--chart-merit)' },
        { key: 'pass', label: 'Pass', count: counts.Pass, color: 'var(--chart-pass)' },
        { key: 'below', label: 'Below Pass', count: counts.Below, color: 'var(--chart-below)' },
        { key: 'awaiting', label: 'Awaiting result', count: counts.Awaiting, color: 'var(--chart-awaiting)' },
    ].filter((b) => b.count > 0)
})

const totalExams = computed(() => props.entries.length)

const RING_R = 46
const RING_W = 16
// A 2px gap in the surface colour is what separates touching segments —
// never a stroke drawn around them.
const GAP_DEG = (2 / (2 * Math.PI * RING_R)) * 360

function polar(cx: number, cy: number, r: number, deg: number) {
    const rad = ((deg - 90) * Math.PI) / 180
    return { x: cx + r * Math.cos(rad), y: cy + r * Math.sin(rad) }
}

function arc(startDeg: number, endDeg: number): string {
    const a = polar(60, 60, RING_R, startDeg)
    const b = polar(60, 60, RING_R, endDeg)
    const large = endDeg - startDeg > 180 ? 1 : 0
    return `M ${a.x} ${a.y} A ${RING_R} ${RING_R} 0 ${large} 1 ${b.x} ${b.y}`
}

const donutSegments = computed(() => {
    const total = totalExams.value
    if (!total) return []
    const list = bands.value
    let cursor = 0
    return list.map((b) => {
        const sweep = (b.count / total) * 360
        const start = cursor
        cursor += sweep
        // A lone segment is a full ring — inserting a gap would carve a
        // meaningless notch out of "100%".
        const gap = list.length === 1 ? 0 : GAP_DEG / 2
        return {
            ...b,
            d: arc(start + gap, Math.max(start + gap, start + sweep - gap)),
            pct: Math.round((b.count / total) * 100),
        }
    })
})

const singleFullRing = computed(() => bands.value.length === 1)

// ─── 2. Exams over time (columns) ─────────────────────────────────────────
interface Bucket {
    label: string
    full: string
    count: number
}

const overTime = computed<Bucket[]>(() => {
    const start = new Date(props.from)
    const end = new Date(props.to)
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return []

    const months: Bucket[] = []
    const cursor = new Date(start.getFullYear(), start.getMonth(), 1)
    const last = new Date(end.getFullYear(), end.getMonth(), 1)
    // Hard stop so a silly range can't render hundreds of columns.
    while (cursor <= last && months.length < 36) {
        months.push({
            label: MONTHS[cursor.getMonth()],
            full: `${MONTHS[cursor.getMonth()]} ${cursor.getFullYear()}`,
            count: 0,
        })
        cursor.setMonth(cursor.getMonth() + 1)
    }
    if (!months.length) return []

    const index = new Map<string, Bucket>()
    months.forEach((m) => index.set(m.full, m))

    for (const e of props.entries) {
        const d = parseDmy(e.exam_date)
        if (!d) continue
        const bucket = index.get(`${MONTHS[d.getMonth()]} ${d.getFullYear()}`)
        if (bucket) bucket.count++
    }
    return months
})

// A one-column column chart is a stat, not a chart.
const showOverTime = computed(() => overTime.value.length >= 2)

const TIME_H = 96
const TIME_SLOT = 34
const timeMax = computed(() => Math.max(1, ...overTime.value.map((b) => b.count)))
const timeWidth = computed(() => Math.max(1, overTime.value.length) * TIME_SLOT)

// Every column label would collide once the range gets long; thin them out
// rather than letting them overlap.
const timeLabelStep = computed(() => (overTime.value.length > 12 ? 3 : overTime.value.length > 7 ? 2 : 1))

const timeColumns = computed(() =>
    overTime.value.map((b, i) => {
        const w = Math.min(24, TIME_SLOT - 10)
        const x = i * TIME_SLOT + (TIME_SLOT - w) / 2
        const h = b.count === 0 ? 0 : Math.max(2, (b.count / timeMax.value) * TIME_H)
        return { ...b, i, x, y: TIME_H - h, w, h, isMax: b.count === timeMax.value && b.count > 0 }
    }),
)

// Rounded at the data end, square at the baseline.
function topRounded(x: number, y: number, w: number, h: number, r = 4) {
    const rr = Math.min(r, h, w / 2)
    return `M ${x} ${y + h} L ${x} ${y + rr} Q ${x} ${y} ${x + rr} ${y} L ${x + w - rr} ${y} Q ${x + w} ${y} ${x + w} ${y + rr} L ${x + w} ${y + h} Z`
}

// ─── 3. Grade spread (horizontal bars) ────────────────────────────────────
const GRADE_ORDER = ['Initial', '1', '2', '3', '4', '5', '6', '7', '8']

const gradeSpread = computed(() => {
    const counts = new Map<string, number>()
    for (const e of props.entries) {
        const g = (e.grade ?? '').trim()
        if (!g) continue
        counts.set(g, (counts.get(g) ?? 0) + 1)
    }
    const rows = Array.from(counts.entries()).map(([grade, count]) => ({ grade, count }))
    rows.sort((a, b) => {
        const ai = GRADE_ORDER.indexOf(a.grade)
        const bi = GRADE_ORDER.indexOf(b.grade)
        if (ai === -1 && bi === -1) return a.grade.localeCompare(b.grade)
        if (ai === -1) return 1
        if (bi === -1) return -1
        return ai - bi
    })
    return rows
})

const showGrades = computed(() => gradeSpread.value.length >= 2)
const gradeMax = computed(() => Math.max(1, ...gradeSpread.value.map((r) => r.count)))
const GRADE_ROW = 24
const GRADE_TRACK = 190

function gradeBar(count: number) {
    return Math.max(3, (count / gradeMax.value) * GRADE_TRACK)
}

function rightRounded(x: number, y: number, w: number, h: number, r = 4) {
    const rr = Math.min(r, w, h / 2)
    return `M ${x} ${y} L ${x + w - rr} ${y} Q ${x + w} ${y} ${x + w} ${y + rr} L ${x + w} ${y + h - rr} Q ${x + w} ${y + h} ${x + w - rr} ${y + h} L ${x} ${y + h} Z`
}

// ─── 4. Score distribution (histogram) ────────────────────────────────────
// Single hue on purpose. The band a score falls in is carried by the marked
// thresholds below the axis, not by colouring the bars — 5-wide bins straddle
// 87, so colouring by band would misstate which bin is a Distinction.
const BIN = 5

const scores = computed(() => props.entries.map((e) => e.score).filter((s): s is number => s !== null))

const histogram = computed(() => {
    if (scores.value.length < 3) return []
    const lo = Math.min(50, Math.floor(Math.min(...scores.value) / BIN) * BIN)
    const bins: { from: number; to: number; count: number }[] = []
    for (let start = lo; start < 100; start += BIN) {
        bins.push({ from: start, to: start + BIN - 1, count: 0 })
    }
    // A perfect 100 would otherwise fall past the last bin and land in it
    // under a "95–99" label. The top bin is 95–100.
    bins[bins.length - 1].to = 100
    for (const s of scores.value) {
        const i = Math.min(bins.length - 1, Math.floor((s - lo) / BIN))
        if (i >= 0) bins[i].count++
    }
    return bins
})

const showScores = computed(() => histogram.value.length >= 2)
const histMax = computed(() => Math.max(1, ...histogram.value.map((b) => b.count)))
const HIST_H = 96
const HIST_SLOT = 26
const histWidth = computed(() => Math.max(1, histogram.value.length) * HIST_SLOT)

const histColumns = computed(() =>
    histogram.value.map((b, i) => {
        const w = Math.min(24, HIST_SLOT - 6)
        const x = i * HIST_SLOT + (HIST_SLOT - w) / 2
        const h = b.count === 0 ? 0 : Math.max(2, (b.count / histMax.value) * HIST_H)
        return { ...b, x, y: HIST_H - h, w, h }
    }),
)

const thresholds = computed(() => {
    const bins = histogram.value
    if (!bins.length) return []
    const lo = bins[0].from
    return [
        { at: PASS, label: 'Pass' },
        { at: MERIT, label: 'Merit' },
        { at: DISTINCTION, label: 'Dist' },
    ]
        .map((t) => ({ ...t, x: ((t.at - lo) / BIN) * HIST_SLOT }))
        .filter((t) => t.x > 0 && t.x < histWidth.value)
})

const hasAnyChart = computed(
    () => bands.value.length > 0 && (showOverTime.value || showGrades.value || showScores.value || totalExams.value > 0),
)

</script>

<template>
    <div v-if="hasAnyChart" class="relative mb-4 rounded-xl border border-brand-border bg-brand-surface p-5">
        <h2 class="text-xl font-semibold text-brand-text">At a glance</h2>
        <p class="mb-5 text-sm text-brand-text-soft">
            Your candidates between {{ from }} and {{ to }}. Change the dates above to redraw these.
        </p>

        <div class="grid gap-8 lg:grid-cols-2">
            <!-- 1. Results mix -->
            <section>
                <h3 class="mb-3 text-sm font-semibold text-brand-text">Results mix</h3>
                <div class="flex flex-wrap items-center gap-6">
                    <svg :viewBox="`0 0 120 120`" class="h-32 w-32 shrink-0" role="img" aria-label="Results mix">
                        <circle
                            v-if="singleFullRing"
                            cx="60"
                            cy="60"
                            :r="RING_R"
                            fill="none"
                            :stroke="donutSegments[0]?.color"
                            :stroke-width="RING_W"
                        />
                        <path
                            v-for="seg in donutSegments"
                            v-show="!singleFullRing"
                            :key="seg.key"
                            :d="seg.d"
                            fill="none"
                            :stroke="seg.color"
                            :stroke-width="RING_W"
                            stroke-linecap="butt"
                            class="cursor-default"
                            @mousemove="showTip($event, seg.label, `${seg.count} of ${totalExams} (${seg.pct}%)`)"
                            @mouseleave="hideTip"
                        />
                        <text
                            x="60"
                            y="58"
                            text-anchor="middle"
                            class="fill-brand-text text-[20px] font-semibold"
                        >{{ totalExams }}</text>
                        <text
                            x="60"
                            y="74"
                            text-anchor="middle"
                            class="fill-brand-text-soft text-[10px]"
                        >{{ totalExams === 1 ? 'exam' : 'exams' }}</text>
                    </svg>

                    <!-- Legend carries the counts, so identity and value are
                         never colour-only or hover-only. -->
                    <ul class="min-w-0 flex-1 space-y-1.5">
                        <li v-for="b in bands" :key="b.key" class="flex items-center gap-2 text-sm">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: b.color }" />
                            <span class="text-brand-text-soft">{{ b.label }}</span>
                            <span class="ml-auto font-semibold text-brand-text tabular-nums">{{ b.count }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- 2. Exams over time -->
            <section v-if="showOverTime">
                <h3 class="mb-3 text-sm font-semibold text-brand-text">Exams over time</h3>
                <div class="overflow-x-auto">
                    <svg
                        :viewBox="`0 0 ${timeWidth} 124`"
                        :width="timeWidth"
                        height="124"
                        class="max-w-full"
                        role="img"
                        aria-label="Exams per month"
                    >
                        <line
                            x1="0"
                            :y1="TIME_H"
                            :x2="timeWidth"
                            :y2="TIME_H"
                            class="stroke-brand-border"
                            stroke-width="1"
                        />
                        <template v-for="c in timeColumns" :key="c.full">
                            <path
                                v-if="c.count > 0"
                                :d="topRounded(c.x, c.y, c.w, c.h)"
                                fill="var(--chart-merit)"
                                @mousemove="showTip($event, c.full, `${c.count} ${c.count === 1 ? 'exam' : 'exams'}`)"
                                @mouseleave="hideTip"
                            />
                            <text
                                v-if="c.isMax"
                                :x="c.x + c.w / 2"
                                :y="c.y - 5"
                                text-anchor="middle"
                                class="fill-brand-text text-[10px] font-semibold"
                            >{{ c.count }}</text>
                            <text
                                v-if="c.i % timeLabelStep === 0"
                                :x="c.i * TIME_SLOT + TIME_SLOT / 2"
                                :y="TIME_H + 16"
                                text-anchor="middle"
                                class="fill-brand-text-soft text-[10px]"
                            >{{ c.label }}</text>
                        </template>
                    </svg>
                </div>
            </section>

            <!-- 3. Grade spread -->
            <section v-if="showGrades">
                <h3 class="mb-3 text-sm font-semibold text-brand-text">Grades entered</h3>
                <svg
                    :viewBox="`0 0 260 ${gradeSpread.length * GRADE_ROW}`"
                    class="w-full"
                    :style="{ height: `${gradeSpread.length * GRADE_ROW}px` }"
                    role="img"
                    aria-label="Exams per grade"
                >
                    <template v-for="(row, i) in gradeSpread" :key="row.grade">
                        <text
                            x="0"
                            :y="i * GRADE_ROW + 15"
                            class="fill-brand-text-soft text-[11px]"
                        >{{ row.grade === 'Initial' ? 'Init' : `Gr ${row.grade}` }}</text>
                        <path
                            :d="rightRounded(38, i * GRADE_ROW + 4, gradeBar(row.count), 14)"
                            fill="var(--chart-merit)"
                            @mousemove="showTip($event, row.grade === 'Initial' ? 'Initial' : `Grade ${row.grade}`, `${row.count} ${row.count === 1 ? 'exam' : 'exams'}`)"
                            @mouseleave="hideTip"
                        />
                        <text
                            :x="38 + gradeBar(row.count) + 6"
                            :y="i * GRADE_ROW + 15"
                            class="fill-brand-text text-[11px] font-semibold tabular-nums"
                        >{{ row.count }}</text>
                    </template>
                </svg>
            </section>

            <!-- 4. Score distribution -->
            <section v-if="showScores">
                <h3 class="mb-3 text-sm font-semibold text-brand-text">Score spread</h3>
                <div class="overflow-x-auto">
                    <svg
                        :viewBox="`0 0 ${histWidth} 130`"
                        :width="histWidth"
                        height="130"
                        class="max-w-full"
                        role="img"
                        aria-label="Score distribution"
                    >
                        <line
                            x1="0"
                            :y1="HIST_H"
                            :x2="histWidth"
                            :y2="HIST_H"
                            class="stroke-brand-border"
                            stroke-width="1"
                        />
                        <template v-for="c in histColumns" :key="c.from">
                            <path
                                v-if="c.count > 0"
                                :d="topRounded(c.x, c.y, c.w, c.h)"
                                fill="var(--chart-merit)"
                                @mousemove="showTip($event, `${c.from}–${c.to} marks`, `${c.count} ${c.count === 1 ? 'candidate' : 'candidates'}`)"
                                @mouseleave="hideTip"
                            />
                        </template>
                        <!-- Trinity's band boundaries, so a teacher can read
                             which side of Pass/Merit/Distinction a bar sits. -->
                        <template v-for="t in thresholds" :key="t.at">
                            <line
                                :x1="t.x"
                                y1="0"
                                :x2="t.x"
                                :y2="HIST_H"
                                class="stroke-brand-border"
                                stroke-width="1"
                            />
                            <text
                                :x="t.x + 3"
                                y="10"
                                class="fill-brand-text-soft text-[9px]"
                            >{{ t.label }} {{ t.at }}</text>
                        </template>
                        <text
                            x="0"
                            :y="HIST_H + 16"
                            class="fill-brand-text-soft text-[10px]"
                        >{{ histogram[0]?.from }}</text>
                        <text
                            :x="histWidth"
                            :y="HIST_H + 16"
                            text-anchor="end"
                            class="fill-brand-text-soft text-[10px]"
                        >100</text>
                    </svg>
                </div>
            </section>
        </div>

        <div
            v-if="tip"
            class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full rounded-lg border border-brand-border bg-brand-surface px-2.5 py-1.5 text-xs shadow-lg"
            :style="{ left: `${tip.x}px`, top: `${tip.y - 8}px` }"
        >
            <span class="font-semibold text-brand-text">{{ tip.label }}</span>
            <span class="ml-2 text-brand-text-soft">{{ tip.value }}</span>
        </div>
    </div>
</template>
