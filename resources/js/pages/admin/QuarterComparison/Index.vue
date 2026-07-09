<!-- resources/js/pages/admin/QuarterComparison/Index.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { BarChart3, Users, Coins, Receipt } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface InstrumentPill {
    name: string
    count: number
}

interface Quarter {
    label: string
    short_label: string
    year: number
    quarter: number
    dg_candidates: number
    f2f_candidates: number
    total_candidates: number
    total_fees: number
    total_commission: number
    teacher_count: number
    exam_types: {
        cj_dg: number
        cj_f2f: number
        rp_dg: number
        rp_f2f: number
    }
    instruments: InstrumentPill[]
}

const props = defineProps<{
    quarters: Quarter[]
    year: number | string
    availableYears: number[]
    method: string
}>()

// ── Y-axis / scaling helpers ──────────────────────────────
// Round a max up to a "nice" round number so axis ticks read cleanly.
function niceMax(raw: number): number {
    if (raw <= 0) return 1
    const pow = Math.pow(10, Math.floor(Math.log10(raw)))
    const steps = [1, 2, 2.5, 5, 10]
    for (const s of steps) {
        if (raw <= s * pow) return s * pow
    }
    return 10 * pow
}

// Four tick labels from max → 0 for the left axis.
function ticks(max: number): number[] {
    return [max, max * 0.75, max * 0.5, max * 0.25, 0]
}

function money(n: number): string {
    return `£${n.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function moneyShort(n: number): string {
    if (n >= 1000) return `£${(n / 1000).toFixed(1)}k`
    return `£${Math.round(n)}`
}

// ── Chart maxima ──────────────────────────────────────────
const candidatesMax = computed(() => niceMax(Math.max(0, ...props.quarters.map((q) => q.total_candidates))))
const commissionMax = computed(() => niceMax(Math.max(0, ...props.quarters.map((q) => q.total_commission))))
const examTypeMax = computed(() =>
    niceMax(
        Math.max(
            0,
            ...props.quarters.flatMap((q) => [q.exam_types.cj_dg, q.exam_types.cj_f2f, q.exam_types.rp_dg, q.exam_types.rp_f2f]),
        ),
    ),
)

function pct(value: number, max: number): string {
    return `${Math.min((value / max) * 100, 100)}%`
}

const examSeries = [
    { key: 'cj_dg', label: 'C&J DG', color: 'bg-brand-accent' },
    { key: 'cj_f2f', label: 'C&J F2F', color: 'bg-brand-purple' },
    { key: 'rp_dg', label: 'R&P DG', color: 'bg-brand-success' },
    { key: 'rp_f2f', label: 'R&P F2F', color: 'bg-brand-primary' },
] as const

const totals = computed(() => ({
    candidates: props.quarters.reduce((sum, q) => sum + q.total_candidates, 0),
    fees: props.quarters.reduce((sum, q) => sum + q.total_fees, 0),
    commission: props.quarters.reduce((sum, q) => sum + q.total_commission, 0),
}))

const hasData = computed(() => totals.value.candidates > 0)

function selectYear(year: number | string): void {
    router.get('/admin/quarter-comparison', { year, method: props.method || undefined }, { preserveScroll: true, preserveState: true })
}

function selectMethod(method: string): void {
    router.get('/admin/quarter-comparison', { year: props.year, method: method || undefined }, { preserveScroll: true, preserveState: true })
}

const methodOptions = [
    { value: '', label: 'All' },
    { value: 'digital', label: 'Digital' },
    { value: 'f2f', label: 'F2F' },
] as const
</script>

<template>
    <div>
        <PageHeader
            title="Quarter Comparison"
            subtitle="Side-by-side quarterly performance — candidates, fees, commission and mix"
            eyebrow="Reporting"
            size="compact"
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex overflow-hidden rounded-lg border border-brand-border">
                        <button
                            v-for="opt in methodOptions"
                            :key="opt.value"
                            type="button"
                            class="border-r border-brand-border px-3 py-1.5 text-sm font-medium transition-colors last:border-r-0"
                            :class="(method || '') === opt.value
                                ? 'bg-brand-accent text-white'
                                : 'bg-brand-surface text-brand-text-soft hover:bg-brand-surface-soft'"
                            @click="selectMethod(opt.value)"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                <div class="inline-flex flex-wrap overflow-hidden rounded-lg border border-brand-border">
                    <button
                        v-for="yr in availableYears"
                        :key="yr"
                        type="button"
                        class="border-r border-brand-border px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="yr === year
                            ? 'bg-brand-accent text-white'
                            : 'bg-brand-surface text-brand-text-soft hover:bg-brand-surface-soft'"
                        @click="selectYear(yr)"
                    >
                        {{ yr }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="year === 'all'
                            ? 'bg-brand-accent text-white'
                            : 'bg-brand-surface text-brand-text-soft hover:bg-brand-surface-soft'"
                        @click="selectYear('all')"
                    >
                        All years
                    </button>
                </div>
                </div>
            </template>
        </PageHeader>

        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Totals across the selection -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <div class="flex items-center gap-3">
                        <Users class="h-8 w-8 text-brand-accent" />
                        <div>
                            <p class="text-sm text-brand-text-soft">Candidates</p>
                            <p class="text-2xl font-bold text-brand-text">{{ totals.candidates }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <div class="flex items-center gap-3">
                        <Receipt class="h-8 w-8 text-brand-accent" />
                        <div>
                            <p class="text-sm text-brand-text-soft">Total fees</p>
                            <p class="text-2xl font-bold text-brand-text">{{ money(totals.fees) }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <div class="flex items-center gap-3">
                        <Coins class="h-8 w-8 text-brand-success" />
                        <div>
                            <p class="text-sm text-brand-text-soft">Total commission</p>
                            <p class="text-2xl font-bold text-brand-success">{{ money(totals.commission) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!hasData" class="rounded-2xl border border-brand-border bg-brand-surface p-10 text-center text-brand-text-soft">
                No candidate data for this selection yet.
            </div>

            <template v-else>
                <!-- Candidates chart (stacked DG + F2F) -->
                <div class="mb-8 rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xl font-semibold text-brand-text">
                            <BarChart3 class="h-5 w-5 text-brand-accent" />
                            Candidates per quarter
                        </h2>
                        <div class="flex items-center gap-4 text-sm text-brand-text-soft">
                            <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-sm bg-brand-accent"></span> Digital</span>
                            <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-sm bg-brand-purple"></span> Face-to-Face</span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <!-- Y-axis -->
                        <div class="flex w-10 flex-col justify-between py-1 text-right text-[10px] text-brand-text-soft" style="height: 260px;">
                            <span v-for="t in ticks(candidatesMax)" :key="t">{{ Math.round(t) }}</span>
                        </div>
                        <!-- Bars -->
                        <div class="flex flex-1 items-end gap-2 border-l border-brand-border pl-3 sm:gap-4" style="height: 260px;">
                            <div v-for="q in quarters" :key="q.short_label" class="group relative flex flex-1 flex-col items-center justify-end" style="height: 100%;">
                                <div class="pointer-events-none absolute -top-10 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lg bg-brand-text px-3 py-1.5 text-xs font-semibold text-brand-surface opacity-0 shadow-lg transition-opacity group-hover:opacity-100">
                                    {{ q.total_candidates }} candidates · {{ q.dg_candidates }} DG / {{ q.f2f_candidates }} F2F
                                </div>
                                <div class="flex w-full max-w-[64px] flex-col justify-end" style="height: 100%;">
                                    <div class="w-full rounded-t-md bg-brand-purple transition-all duration-500" :style="{ height: pct(q.f2f_candidates, candidatesMax) }"></div>
                                    <div class="w-full bg-brand-accent transition-all duration-500" :class="{ 'rounded-t-md': q.f2f_candidates === 0 }" :style="{ height: pct(q.dg_candidates, candidatesMax) }"></div>
                                </div>
                                <p class="mt-2 text-sm font-bold text-brand-text">{{ q.total_candidates }}</p>
                                <p class="text-[10px] text-brand-text-soft sm:text-xs">{{ q.short_label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission chart -->
                <div class="mb-8 rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
                    <h2 class="mb-6 flex items-center gap-2 text-xl font-semibold text-brand-text">
                        <Coins class="h-5 w-5 text-brand-success" />
                        Commission per quarter
                    </h2>
                    <div class="flex gap-3">
                        <div class="flex w-12 flex-col justify-between py-1 text-right text-[10px] text-brand-text-soft" style="height: 260px;">
                            <span v-for="t in ticks(commissionMax)" :key="t">{{ moneyShort(t) }}</span>
                        </div>
                        <div class="flex flex-1 items-end gap-2 border-l border-brand-border pl-3 sm:gap-4" style="height: 260px;">
                            <div v-for="q in quarters" :key="q.short_label" class="group relative flex flex-1 flex-col items-center justify-end" style="height: 100%;">
                                <div class="pointer-events-none absolute -top-10 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lg bg-brand-text px-3 py-1.5 text-xs font-semibold text-brand-surface opacity-0 shadow-lg transition-opacity group-hover:opacity-100">
                                    {{ money(q.total_commission) }}
                                </div>
                                <div class="w-full max-w-[64px] rounded-t-md bg-brand-success transition-all duration-500" :style="{ height: pct(q.total_commission, commissionMax) }"></div>
                                <p class="mt-2 text-xs font-bold text-brand-success sm:text-sm">{{ moneyShort(q.total_commission) }}</p>
                                <p class="text-[10px] text-brand-text-soft sm:text-xs">{{ q.short_label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam-types chart (grouped bars) -->
                <div class="mb-8 rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-xl font-semibold text-brand-text">
                            <BarChart3 class="h-5 w-5 text-brand-accent" />
                            Exam types per quarter
                        </h2>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-brand-text-soft">
                            <span v-for="s in examSeries" :key="s.key" class="flex items-center gap-1.5">
                                <span class="inline-block h-3 w-3 rounded-sm" :class="s.color"></span> {{ s.label }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex w-10 flex-col justify-between py-1 text-right text-[10px] text-brand-text-soft" style="height: 260px;">
                            <span v-for="t in ticks(examTypeMax)" :key="t">{{ Math.round(t) }}</span>
                        </div>
                        <div class="flex flex-1 items-end gap-3 border-l border-brand-border pl-3 sm:gap-6" style="height: 260px;">
                            <div v-for="q in quarters" :key="q.short_label" class="flex flex-1 flex-col items-center justify-end" style="height: 100%;">
                                <div class="flex w-full flex-1 items-end justify-center gap-1" style="height: 100%;">
                                    <div v-for="s in examSeries" :key="s.key" class="group relative flex flex-1 flex-col justify-end" style="height: 100%;">
                                        <div class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-brand-text px-2 py-1 text-[10px] font-semibold text-brand-surface opacity-0 transition-opacity group-hover:opacity-100">
                                            {{ s.label }}: {{ q.exam_types[s.key] }}
                                        </div>
                                        <div class="w-full rounded-t-sm transition-all duration-500" :class="s.color" :style="{ height: pct(q.exam_types[s.key], examTypeMax) }"></div>
                                    </div>
                                </div>
                                <p class="mt-2 text-[10px] text-brand-text-soft sm:text-xs">{{ q.short_label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Per-quarter detail table -->
            <div class="overflow-x-auto rounded-2xl border border-brand-border bg-brand-surface shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-brand-border text-left text-brand-text">
                            <th class="px-4 py-3 font-semibold">Quarter</th>
                            <th class="px-4 py-3 text-center font-semibold">DG</th>
                            <th class="px-4 py-3 text-center font-semibold">F2F</th>
                            <th class="px-4 py-3 text-right font-semibold">Fees</th>
                            <th class="px-4 py-3 text-right font-semibold">Commission</th>
                            <th class="px-4 py-3 text-center font-semibold">Teachers</th>
                            <th class="px-4 py-3 font-semibold">Exam types</th>
                            <th class="px-4 py-3 font-semibold">Instruments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="q in quarters" :key="q.short_label" class="border-b border-brand-border align-top last:border-0">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-brand-text">{{ q.label }}</td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ q.dg_candidates }}</td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ q.f2f_candidates }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-brand-text">{{ money(q.total_fees) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-brand-success">{{ money(q.total_commission) }}</td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ q.teacher_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-medium text-brand-accent">C&amp;J DG {{ q.exam_types.cj_dg }}</span>
                                    <span class="rounded-full bg-brand-purple/10 px-2 py-0.5 text-xs font-medium text-brand-purple">C&amp;J F2F {{ q.exam_types.cj_f2f }}</span>
                                    <span class="rounded-full bg-brand-success/10 px-2 py-0.5 text-xs font-medium text-brand-success">R&amp;P DG {{ q.exam_types.rp_dg }}</span>
                                    <span class="rounded-full bg-brand-primary/10 px-2 py-0.5 text-xs font-medium text-brand-primary">R&amp;P F2F {{ q.exam_types.rp_f2f }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="q.instruments.length" class="flex flex-wrap gap-1.5">
                                    <span v-for="inst in q.instruments" :key="inst.name" class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs font-medium text-brand-text">
                                        {{ inst.name }} {{ inst.count }}
                                    </span>
                                </div>
                                <span v-else class="text-xs text-brand-text-soft">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
