<script setup lang="ts">
import { Head, Link, Form, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { LayoutDashboard, ClipboardList, Users, GraduationCap, CheckSquare, Award, AlertCircle, Home, LogOut, Mail, MessageCircle, Info, ChevronDown, ChevronRight, Gift, Ticket } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { dashboard, logout } from '@/routes'

interface ExamEntryRow {
    id: number
    student_id: number | null
    instrument: string | null
    candidate_number: string | null
    candidate_name: string | null
    date_of_birth: string | null
    grade: string | null
    subject_area: string | null
    delivery_method: string | null
    result: string | null
    score: number | null
    exam_date: string | null
    pending_correction: { submitted_at: string | null; note: string } | null
}

interface PrizeDrawQuarter {
    quarter: number
    year: number
    label: string
    drawn_at: string | null
    has_winner: boolean
    winner_display_name: string | null
    winner_entries: number
    total_tickets: number
}

interface TeacherPrizeDrawPayload {
    quarters: PrizeDrawQuarter[]
    my_current_quarter_tickets: number
    current_quarter_label: string
}

const props = defineProps<{
    examEntries?: ExamEntryRow[]
    hasLinkedContact?: boolean
    teacherPrizeDraw?: TeacherPrizeDrawPayload
}>()

const handleLogout = () => {
    router.flushAll()
}

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => user.value?.role === 'admin')
const flashSuccess = computed(() => (page.props.flash as any)?.success)

const showLinkForm = ref(false)
const entries = computed<ExamEntryRow[]>(() => props.examEntries ?? [])
const hasEntries = computed(() => entries.value.length > 0)

// ─── Teacher prize draw card ──────────────────────────────────────────────
// Defaults to the most recent quarter (quarters[] is sorted newest-first by
// the controller, including a "current quarter, not yet drawn" placeholder
// when there's no draw row yet — that placeholder always sits at index 0).
const prizeDraw = computed<TeacherPrizeDrawPayload | null>(() => props.teacherPrizeDraw ?? null)
const hasPrizeDraw = computed(() => (prizeDraw.value?.quarters?.length ?? 0) > 0)
const selectedQuarterKey = ref<string>(
    prizeDraw.value?.quarters?.[0]
        ? `${prizeDraw.value.quarters[0].year}-${prizeDraw.value.quarters[0].quarter}`
        : '',
)
const selectedQuarter = computed<PrizeDrawQuarter | null>(() => {
    const list = prizeDraw.value?.quarters ?? []
    return list.find(q => `${q.year}-${q.quarter}` === selectedQuarterKey.value) ?? list[0] ?? null
})
// The "you have N tickets" line only shows for an undrawn quarter (the
// controller flags those with has_winner=false, drawn_at=null). Once the
// draw is run, we show the snapshotted winner_entries instead.
const showLiveTicketCount = computed(() =>
    selectedQuarter.value !== null && selectedQuarter.value.has_winner === false
)

const correctionFormFor = ref<number | null>(null)
// Modal state: which entry's already-submitted correction we're viewing.
const viewingCorrectionFor = ref<number | null>(null)

function toggleCorrectionForm(entryId: number) {
    correctionFormFor.value = correctionFormFor.value === entryId ? null : entryId
}

function openCorrectionView(entryId: number) {
    viewingCorrectionFor.value = entryId
}

function closeCorrectionView() {
    viewingCorrectionFor.value = null
}

const viewedCorrectionEntry = computed<ExamEntryRow | null>(() =>
    entries.value.find((e) => e.id === viewingCorrectionFor.value) ?? null,
)

// ─────────────────────────────────────────────────────────────────────────
// Group exam entries by candidate so the parent row is ONE per candidate
// (e.g. Alexander Johnson) with a count + expand drill-down to their exams.
// Key on candidate_name + candidate_number to be safe with same-name kids
// from different families.
// ─────────────────────────────────────────────────────────────────────────
interface CandidateGroup {
    key: string
    candidate_name: string | null
    candidate_number: string | null
    date_of_birth: string | null
    entries: ExamEntryRow[]
}

const groupedCandidates = computed<CandidateGroup[]>(() => {
    const map = new Map<string, CandidateGroup>()
    for (const e of entries.value) {
        // Trinity issues a new candidate_number for every exam, so we can't
        // dedup on that. The reliable identifier is name + DOB. If DOB is
        // missing (seeder data, legacy imports), fall back to name alone —
        // accept the small risk of merging two genuinely-different same-name
        // students until the data is enriched.
        // student_id is preferred when present (canonical link).
        const key = e.student_id != null
            ? `s:${e.student_id}`
            : `n:${(e.candidate_name ?? '?').toLowerCase().trim()}|d:${e.date_of_birth ?? ''}`

        if (!map.has(key)) {
            map.set(key, {
                key,
                candidate_name: e.candidate_name,
                candidate_number: e.candidate_number,
                date_of_birth: e.date_of_birth,
                entries: [],
            })
        }
        map.get(key)!.entries.push(e)
    }
    // Sort groups by candidate_name asc
    return Array.from(map.values()).sort((a, b) =>
        (a.candidate_name ?? '').localeCompare(b.candidate_name ?? ''),
    )
})

// ─────────────────────────────────────────────────────────────────────────
// Result summary + filter pills
//
// Teachers want answers at a glance: "how many distinctions did my class
// score?" — and to be able to slice the list by result band rather than
// drilling into each candidate. Counts are at ENTRY level (not candidate)
// because one candidate can have a Distinction AND a Merit in the same
// quarter; the totals should add up to total exams, not total candidates.
// ─────────────────────────────────────────────────────────────────────────
const resultSummary = computed(() => {
    const summary = { distinctions: 0, merits: 0, passes: 0, fails: 0, pending: 0 }
    for (const e of entries.value) {
        switch (e.result) {
            case 'Distinction': summary.distinctions++; break
            case 'Merit':       summary.merits++; break
            case 'Pass':        summary.passes++; break
            case 'Fail':        summary.fails++; break
            default:            summary.pending++
        }
    }
    return summary
})

type ResultFilter = 'all' | 'Distinction' | 'Merit' | 'Pass' | 'Pending'
const activeFilter = ref<ResultFilter>('all')

// A candidate is included in a filter if ANY of their exam entries match.
// "Pending" matches entries where result is null (waiting on Trinity scores).
const filteredCandidates = computed<CandidateGroup[]>(() => {
    if (activeFilter.value === 'all') return groupedCandidates.value
    if (activeFilter.value === 'Pending') {
        return groupedCandidates.value.filter((g) => g.entries.some((e) => e.result === null))
    }
    return groupedCandidates.value.filter((g) =>
        g.entries.some((e) => e.result === activeFilter.value),
    )
})

// For the parent row inline summary: when a candidate has 1 exam, show that
// exam's result + score directly (no need to drill in). For 2+ exams, show
// a compact mix like "1 Dist · 1 Merit · 1 Pending" so the teacher can see
// the spread without expanding.
function candidateInlineResult(group: CandidateGroup): { single: ExamEntryRow | null; mix: { label: string; cls: string }[] } {
    if (group.entries.length === 1) {
        return { single: group.entries[0], mix: [] }
    }
    const counts = { Distinction: 0, Merit: 0, Pass: 0, Fail: 0, Pending: 0 }
    for (const e of group.entries) {
        const k = (e.result ?? 'Pending') as keyof typeof counts
        counts[k]++
    }
    const mix = [
        { label: counts.Distinction ? `${counts.Distinction} Dist` : '', cls: 'bg-brand-success-soft text-brand-success' },
        { label: counts.Merit ? `${counts.Merit} Merit` : '', cls: 'bg-brand-accent/10 text-brand-accent' },
        { label: counts.Pass ? `${counts.Pass} Pass` : '', cls: 'bg-brand-surface-soft text-brand-text-soft' },
        { label: counts.Fail ? `${counts.Fail} Fail` : '', cls: 'bg-brand-danger-soft text-brand-danger' },
        { label: counts.Pending ? `${counts.Pending} Pending` : '', cls: 'bg-brand-surface-soft text-brand-text-soft' },
    ].filter((m) => m.label !== '')
    return { single: null, mix }
}

const expandedCandidates = ref<Set<string>>(new Set())
function toggleCandidate(key: string) {
    if (expandedCandidates.value.has(key)) {
        expandedCandidates.value.delete(key)
    } else {
        expandedCandidates.value.add(key)
    }
}
function isExpanded(key: string): boolean {
    return expandedCandidates.value.has(key)
}

const quickLinks = [
    { title: 'Admin Dashboard', subtitle: 'Stats, orders & contacts', href: '/admin', icon: LayoutDashboard },
    { title: 'Orders', subtitle: 'View all exam orders', href: '/admin/orders', icon: ClipboardList },
    { title: 'Pending Results', subtitle: 'Weekly results checklist', href: '/admin/pending-results', icon: AlertCircle },
    { title: 'Teachers', subtitle: 'Manage teacher records', href: '/admin/teachers', icon: Users },
    { title: 'Tasks', subtitle: 'Your to-do list', href: '/admin/tasks', icon: CheckSquare },
    { title: 'Certificates', subtitle: 'Generate certificates', href: '/admin/certificates', icon: Award },
]

function resultBadgeClass(result: string | null): string {
    switch (result) {
        case 'Distinction': return 'bg-brand-success-soft text-brand-success'
        case 'Merit': return 'bg-brand-accent/10 text-brand-accent'
        case 'Pass': return 'bg-brand-surface-soft text-brand-text-soft'
        case 'Fail': return 'bg-brand-danger-soft text-brand-danger'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col items-center p-6 sm:p-8">
        <!-- Top bar with logout (visible to all logged-in users) -->
        <div class="mb-4 flex w-full max-w-5xl items-center justify-end">
            <Link
                :href="logout()"
                @click="handleLogout"
                as="button"
                class="inline-flex items-center gap-2 rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-text"
            >
                <LogOut class="h-4 w-4" />
                Log out
            </Link>
        </div>

        <!-- Welcome — layout already shows the musicExams.help logo at the
             top, so we don't repeat it here (was visually doubling up). -->
        <div class="mt-4 flex flex-col items-center gap-5 sm:mt-8">
            <MyTextConstructor variant="heading" alignment="center" spacing="none">
                <template #myTitle>Welcome back, {{ user?.name?.split(' ')[0] }}</template>
            </MyTextConstructor>
            <p class="text-base text-brand-text-soft sm:text-lg">
                Centre 120 — Trinity College London
            </p>
        </div>

        <!-- Quick links grid (admin only) -->
        <div v-if="isAdmin" class="mt-10 w-full max-w-3xl">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="link in quickLinks"
                    :key="link.href"
                    :href="link.href"
                    class="group flex items-center gap-4 rounded-xl border border-brand-border bg-brand-surface p-5 transition-all hover:border-brand-accent hover:shadow-md"
                >
                    <div class="rounded-lg bg-brand-accent/10 p-3 transition-colors group-hover:bg-brand-accent/20">
                        <component :is="link.icon" class="h-6 w-6 text-brand-accent" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-brand-text">{{ link.title }}</p>
                        <p class="text-sm text-brand-text-soft">{{ link.subtitle }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Non-admin: candidate list OR linkage form -->
        <div v-else class="mt-8 w-full max-w-5xl">
            <!-- Flash from a successful link request -->
            <div
                v-if="flashSuccess"
                class="mb-6 rounded-lg border border-brand-success bg-brand-success-soft px-4 py-3 text-base text-brand-success"
            >
                {{ flashSuccess }}
            </div>

            <!-- Banner — sets expectations before the table -->
            <div v-if="hasEntries" class="mb-4 flex items-start gap-3 rounded-xl border border-brand-border bg-brand-surface-soft px-4 py-3 text-sm text-brand-text">
                <Info class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <p>
                    Spotted a typo in a name or a wrong date of birth?
                    Use <span class="font-semibold">Report correction</span> next to the candidate and we&rsquo;ll handle the fix on
                    musicExams.help and with Trinity for you &mdash; please don&rsquo;t contact Trinity directly.
                </p>
            </div>

            <!-- "More coming soon" — sets expectations for Phase B (Music Register lite) -->
            <div v-if="hasEntries" class="mb-6 flex items-start gap-3 rounded-xl border border-brand-accent/30 bg-brand-accent/5 px-4 py-3 text-sm text-brand-text">
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-accent/20 text-xs font-bold text-brand-accent">★</span>
                <div>
                    <p class="font-semibold text-brand-text">More coming soon</p>
                    <p class="mt-0.5 text-brand-text-soft">
                        We&rsquo;re building a <span class="font-medium text-brand-text">piece tracker</span> so you can plan and follow your students&rsquo; next exam pieces &mdash; with Trinity syllabus dropdowns where available. Look out for it in the next couple of weeks.
                    </p>
                </div>
            </div>

            <!-- Quarterly Teacher Prize Draw — visible only to authenticated
                 teachers, never on the public marketing site. School admins
                 (Daniel Rogers / Pulse Music etc.) display by their school
                 name. Individual teachers default to "First L" until they
                 opt in to full-name display via show_full_name on their
                 exam_contacts row (set when Paul confirms by email). -->
            <div v-if="hasPrizeDraw" class="mb-6 rounded-xl border border-brand-border bg-brand-surface">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-brand-border px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <Gift class="h-5 w-5 text-brand-accent" />
                        <div>
                            <h2 class="text-xl font-semibold text-brand-text">Quarterly Teacher Prize Draw</h2>
                            <p class="text-sm text-brand-text-soft">
                                £50 gift token to invest back into your teaching. Every non-cancelled student entry through centre 120 = one ticket.
                            </p>
                        </div>
                    </div>
                    <!-- Quarter dropdown — current at top, then descending. -->
                    <label class="flex items-center gap-2 text-sm">
                        <span class="font-medium text-brand-text-soft">Quarter</span>
                        <select
                            v-model="selectedQuarterKey"
                            class="rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-sm font-medium text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        >
                            <option
                                v-for="q in prizeDraw?.quarters ?? []"
                                :key="`${q.year}-${q.quarter}`"
                                :value="`${q.year}-${q.quarter}`"
                            >
                                {{ q.label }}{{ q.has_winner ? '' : ' — not yet drawn' }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="px-5 py-4">
                    <!-- Drawn quarter — show the winner card. -->
                    <div v-if="selectedQuarter && selectedQuarter.has_winner" class="rounded-lg border border-brand-accent/30 bg-brand-accent/5 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-accent">
                            Winner — {{ selectedQuarter.label }}
                        </p>
                        <p class="mt-1.5 text-2xl font-bold text-brand-text">
                            {{ selectedQuarter.winner_display_name }}
                        </p>
                        <p class="mt-1 text-sm text-brand-text-soft">
                            Drew {{ selectedQuarter.winner_entries }} {{ selectedQuarter.winner_entries === 1 ? 'ticket' : 'tickets' }}
                            of {{ selectedQuarter.total_tickets }} in the pool · drawn {{ selectedQuarter.drawn_at }}
                        </p>
                    </div>

                    <!-- Undrawn quarter — banner + live ticket count for the user. -->
                    <div v-else-if="selectedQuarter" class="rounded-lg border border-brand-border bg-brand-surface-soft px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-text-soft">
                            {{ selectedQuarter.label }}
                        </p>
                        <p class="mt-1.5 text-lg font-semibold text-brand-text">
                            Not yet drawn
                        </p>
                        <p class="mt-1 text-sm text-brand-text-soft">
                            The teacher prize draw runs after the quarter ends and all results are in.
                        </p>
                    </div>

                    <!-- "You have N tickets" — only on the current undrawn quarter. -->
                    <div
                        v-if="showLiveTicketCount && prizeDraw"
                        class="mt-3 flex items-center gap-2.5 rounded-lg bg-brand-accent/5 px-4 py-3 text-sm text-brand-text"
                    >
                        <Ticket class="h-4 w-4 shrink-0 text-brand-accent" />
                        <span>
                            <span class="font-semibold">You have {{ prizeDraw.my_current_quarter_tickets }} {{ prizeDraw.my_current_quarter_tickets === 1 ? 'ticket' : 'tickets' }}</span>
                            in the {{ prizeDraw.current_quarter_label }} draw so far. Each non-cancelled entry adds one more.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Candidates table — when the user is linked and has entries -->
            <div v-if="hasEntries" class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="border-b border-brand-border px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-brand-text">Your candidates</h2>
                            <p class="text-sm text-brand-text-soft">
                                One row per candidate. Click a row to drill into individual exam details.
                            </p>
                        </div>
                        <!-- Result summary chips: total counts at a glance.
                             Counts are entry-level so they sum to total exams. -->
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-brand-surface-soft px-3 py-1 font-medium text-brand-text-soft">
                                {{ groupedCandidates.length }} {{ groupedCandidates.length === 1 ? 'candidate' : 'candidates' }}
                                &middot;
                                {{ entries.length }} {{ entries.length === 1 ? 'exam' : 'exams' }}
                            </span>
                            <span v-if="resultSummary.distinctions" class="rounded-full bg-brand-success-soft px-3 py-1 font-semibold text-brand-success">
                                {{ resultSummary.distinctions }} Distinction{{ resultSummary.distinctions === 1 ? '' : 's' }}
                            </span>
                            <span v-if="resultSummary.merits" class="rounded-full bg-brand-accent/10 px-3 py-1 font-semibold text-brand-accent">
                                {{ resultSummary.merits }} Merit{{ resultSummary.merits === 1 ? '' : 's' }}
                            </span>
                            <span v-if="resultSummary.passes" class="rounded-full bg-brand-surface-soft px-3 py-1 font-semibold text-brand-text-soft">
                                {{ resultSummary.passes }} Pass{{ resultSummary.passes === 1 ? '' : 'es' }}
                            </span>
                            <span v-if="resultSummary.fails" class="rounded-full bg-brand-danger-soft px-3 py-1 font-semibold text-brand-danger">
                                {{ resultSummary.fails }} Fail{{ resultSummary.fails === 1 ? '' : 's' }}
                            </span>
                            <span v-if="resultSummary.pending" class="rounded-full bg-brand-surface-soft px-3 py-1 font-semibold text-brand-text-soft">
                                {{ resultSummary.pending }} Pending
                            </span>
                        </div>
                    </div>
                    <!-- Filter pills — match the /admin/users pattern. Only
                         shown when there are at least 2 candidates AND the
                         results are mixed enough to warrant filtering. -->
                    <div v-if="groupedCandidates.length > 1" class="mt-4 flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
                            :class="activeFilter === 'all' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'"
                            @click="activeFilter = 'all'"
                        >
                            All
                        </button>
                        <button
                            v-if="resultSummary.distinctions"
                            type="button"
                            class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
                            :class="activeFilter === 'Distinction' ? 'bg-brand-success text-white' : 'bg-brand-success-soft text-brand-success hover:opacity-80'"
                            @click="activeFilter = 'Distinction'"
                        >
                            Distinctions
                        </button>
                        <button
                            v-if="resultSummary.merits"
                            type="button"
                            class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
                            :class="activeFilter === 'Merit' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-accent/10 text-brand-accent hover:opacity-80'"
                            @click="activeFilter = 'Merit'"
                        >
                            Merits
                        </button>
                        <button
                            v-if="resultSummary.passes"
                            type="button"
                            class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
                            :class="activeFilter === 'Pass' ? 'bg-brand-text text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'"
                            @click="activeFilter = 'Pass'"
                        >
                            Passes
                        </button>
                        <button
                            v-if="resultSummary.pending"
                            type="button"
                            class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
                            :class="activeFilter === 'Pending' ? 'bg-brand-text text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'"
                            @click="activeFilter = 'Pending'"
                        >
                            Pending
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[800px] w-full text-left text-sm">
                        <thead class="border-b border-brand-border bg-brand-surface-soft text-brand-text-soft">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Candidate</th>
                                <th class="px-4 py-3 font-semibold">DOB</th>
                                <th class="px-4 py-3 font-semibold">Instrument</th>
                                <th class="px-4 py-3 font-semibold">Grade</th>
                                <th class="px-4 py-3 font-semibold">Subject</th>
                                <th class="px-4 py-3 font-semibold">Delivery</th>
                                <th class="px-4 py-3 font-semibold">Exam date</th>
                                <th class="px-4 py-3 font-semibold">Result</th>
                                <th class="px-4 py-3 text-center font-semibold">Score</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            <template v-for="group in filteredCandidates" :key="group.key">
                                <!-- Single-exam candidate: render as a normal
                                     data row with every column filled, so it
                                     lines up with the headers. No chevron, no
                                     drill-down — the one exam IS the row.
                                     This is the common Q1 case where every
                                     candidate sat exactly one exam. -->
                                <tr
                                    v-if="group.entries.length === 1"
                                    class="transition-colors hover:bg-brand-surface-soft"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="text-base font-medium text-brand-text">{{ group.candidate_name ?? '—' }}</div>
                                            <div v-if="group.candidate_number" class="text-xs text-brand-text-soft">
                                                {{ group.candidate_number }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.date_of_birth ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.entries[0].instrument ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.entries[0].grade ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.entries[0].subject_area ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.entries[0].delivery_method ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.entries[0].exam_date ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="group.entries[0].result" class="rounded-full px-2 py-0.5 text-sm font-medium" :class="resultBadgeClass(group.entries[0].result)">
                                            {{ group.entries[0].result }}
                                        </span>
                                        <span v-else class="text-brand-text-soft">Pending</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="group.entries[0].score !== null && group.entries[0].score !== undefined" class="font-medium text-brand-text">{{ group.entries[0].score }}</span>
                                        <span v-else class="text-brand-text-soft">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-col items-end gap-1.5">
                                            <button
                                                v-if="group.entries[0].pending_correction"
                                                type="button"
                                                class="cursor-pointer inline-flex items-center gap-1.5 rounded-full bg-brand-accent/10 px-2.5 py-0.5 text-xs font-medium text-brand-accent transition-colors hover:bg-brand-accent/20"
                                                @click.stop="openCorrectionView(group.entries[0].id)"
                                            >
                                                ✓ Correction sent
                                            </button>
                                            <button
                                                type="button"
                                                class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-xs font-medium text-brand-text-soft transition-colors hover:bg-brand-accent/10 hover:text-brand-accent"
                                                @click.stop="toggleCorrectionForm(group.entries[0].id)"
                                            >
                                                <MessageCircle class="h-3.5 w-3.5" />
                                                <template v-if="correctionFormFor === group.entries[0].id">Cancel</template>
                                                <template v-else-if="group.entries[0].pending_correction">Send another</template>
                                                <template v-else>Report correction</template>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Inline correction form for single-exam candidates -->
                                <tr v-if="group.entries.length === 1 && correctionFormFor === group.entries[0].id" class="bg-brand-surface-soft/60">
                                    <td colspan="10" class="px-5 py-4">
                                        <Form
                                            :action="`/dashboard/entries/${group.entries[0].id}/correction-request`"
                                            method="post"
                                            :reset-on-success="['note']"
                                            v-slot="{ errors, processing }"
                                            class="grid gap-3"
                                            @success="correctionFormFor = null"
                                        >
                                            <p class="text-sm text-brand-text-soft">
                                                What needs correcting for {{ group.entries[0].candidate_name }} ({{ group.entries[0].grade }}, {{ group.entries[0].subject_area }})?
                                            </p>
                                            <textarea
                                                name="note"
                                                rows="3"
                                                required
                                                placeholder="e.g. Name spelled wrong on certificate, wrong DOB, wrong grade..."
                                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                            />
                                            <p v-if="errors.note" class="text-xs text-brand-danger">{{ errors.note }}</p>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="cursor-pointer rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-xs font-medium text-brand-text-soft hover:bg-brand-surface-soft" @click="correctionFormFor = null">Cancel</button>
                                                <button type="submit" :disabled="processing" class="cursor-pointer rounded-lg bg-brand-accent px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-50">
                                                    <span v-if="processing">Sending…</span>
                                                    <span v-else>Send to musicExams.help</span>
                                                </button>
                                            </div>
                                        </Form>
                                    </td>
                                </tr>

                                <!-- Multi-exam candidate (Q2+): keep the
                                     parent + expandable children pattern, with
                                     a mix-of-results summary on the parent.
                                     NOTE: explicit v-if (not v-else) because
                                     the inline correction form above acts as
                                     a v-if sibling and would steal the v-else
                                     binding, double-rendering every single-
                                     exam candidate. -->
                                <tr
                                    v-if="group.entries.length > 1"
                                    class="cursor-pointer transition-colors hover:bg-brand-surface-soft"
                                    @click="toggleCandidate(group.key)"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <ChevronDown v-if="isExpanded(group.key)" class="h-4 w-4 shrink-0 text-brand-text-soft" />
                                            <ChevronRight v-else class="h-4 w-4 shrink-0 text-brand-text-soft" />
                                            <div>
                                                <div class="text-base font-medium text-brand-text">{{ group.candidate_name ?? '—' }}</div>
                                                <div v-if="group.candidate_number" class="text-xs text-brand-text-soft">
                                                    {{ group.candidate_number }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ group.date_of_birth ?? '—' }}</td>
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-brand-surface-soft px-3 py-1 text-sm font-medium text-brand-text-soft">
                                                {{ group.entries.length }} exams
                                            </span>
                                            <span
                                                v-for="m in candidateInlineResult(group).mix"
                                                :key="m.label"
                                                class="rounded-full px-3 py-1 text-sm font-semibold"
                                                :class="m.cls"
                                            >
                                                {{ m.label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-xs text-brand-text-soft">
                                            {{ isExpanded(group.key) ? 'Hide' : 'Details' }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Child rows: only for multi-exam candidates
                                     (single-exam groups already render their
                                     one entry as the parent row above). -->
                                <template v-if="group.entries.length > 1 && isExpanded(group.key)">
                                    <template v-for="row in group.entries" :key="row.id">
                                        <tr class="bg-brand-surface-soft/30">
                                            <td colspan="2" class="pl-12 pr-4 py-3">
                                                <span class="text-xs text-brand-text-soft">↳ exam entry</span>
                                            </td>
                                            <td class="px-4 py-3 text-brand-text-soft">{{ row.instrument ?? '—' }}</td>
                                            <td class="px-4 py-3 text-brand-text-soft">{{ row.grade ?? '—' }}</td>
                                            <td class="px-4 py-3 text-brand-text-soft">{{ row.subject_area ?? '—' }}</td>
                                            <td class="px-4 py-3 text-brand-text-soft">{{ row.delivery_method ?? '—' }}</td>
                                            <td class="px-4 py-3 text-brand-text-soft">{{ row.exam_date ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <span v-if="row.result" class="rounded-full px-2 py-0.5 text-sm font-medium" :class="resultBadgeClass(row.result)">
                                                    {{ row.result }}
                                                </span>
                                                <span v-else class="text-brand-text-soft">—</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="row.score !== null && row.score !== undefined" class="font-medium text-brand-text">{{ row.score }}</span>
                                                <span v-else class="text-brand-text-soft">—</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex flex-col items-end gap-1.5">
                                                    <!-- Correction-sent indicator: clickable to re-read submitted note -->
                                                    <button
                                                        v-if="row.pending_correction"
                                                        type="button"
                                                        class="inline-flex items-center gap-1.5 rounded-full bg-brand-accent/10 px-2.5 py-0.5 text-xs font-medium text-brand-accent transition-colors hover:bg-brand-accent/20"
                                                        @click.stop="openCorrectionView(row.id)"
                                                    >
                                                        ✓ Correction sent
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-xs font-medium text-brand-text-soft transition-colors hover:bg-brand-accent/10 hover:text-brand-accent"
                                                        @click.stop="toggleCorrectionForm(row.id)"
                                                    >
                                                        <MessageCircle class="h-3.5 w-3.5" />
                                                        <template v-if="correctionFormFor === row.id">Cancel</template>
                                                        <template v-else-if="row.pending_correction">Send another</template>
                                                        <template v-else>Report correction</template>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="correctionFormFor === row.id" class="bg-brand-surface-soft/60">
                                            <td colspan="10" class="px-5 py-4">
                                                <Form
                                                    :action="`/dashboard/entries/${row.id}/correction-request`"
                                                    method="post"
                                                    :reset-on-success="['note']"
                                                    v-slot="{ errors, processing }"
                                                    class="grid gap-3"
                                                    @success="correctionFormFor = null"
                                                >
                                                    <label class="text-sm font-semibold text-brand-text">
                                                        What needs correcting for {{ row.candidate_name }} ({{ row.grade }}, {{ row.subject_area }})?
                                                    </label>
                                                    <textarea
                                                        name="note"
                                                        rows="3"
                                                        required
                                                        placeholder="e.g. Spelling should be Freddie not Fred. Date of birth should be 14/05/2014."
                                                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                                    ></textarea>
                                                    <p v-if="errors.note" class="text-sm text-brand-danger">{{ errors.note }}</p>
                                                    <div class="flex gap-2">
                                                        <MyButtonConstructor
                                                            type="submit"
                                                            variant="primary"
                                                            size="small"
                                                            :disabled="processing"
                                                        >
                                                            <Spinner v-if="processing" class="mr-2" />
                                                            Send correction
                                                        </MyButtonConstructor>
                                                        <button
                                                            type="button"
                                                            class="rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-surface-soft"
                                                            @click="correctionFormFor = null"
                                                        >
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </Form>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Linkage path — when no entries match the user's email -->
            <div v-else class="rounded-xl border border-brand-border bg-brand-surface p-6 sm:p-8">
                <div class="flex flex-col items-start gap-4">
                    <h2 class="text-2xl font-semibold text-brand-text">No candidates linked yet</h2>
                    <p class="text-base text-brand-text-soft">
                        We couldn&rsquo;t find any Trinity exam entries under your registered email
                        (<span class="font-medium text-brand-text">{{ user?.email }}</span>).
                    </p>
                    <p class="text-base text-brand-text-soft">
                        If you used a different email when applying through Trinity, the easiest fix is to log out and
                        sign up again with that email. Or, tell us which email you used on Trinity and we&rsquo;ll
                        link your account.
                    </p>

                    <div class="mt-2 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90"
                            @click="showLinkForm = !showLinkForm"
                        >
                            <Mail class="h-4 w-4" />
                            {{ showLinkForm ? 'Hide form' : 'Tell us your Trinity email' }}
                        </button>
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-lg border border-brand-border bg-brand-surface px-4 py-2 text-sm font-semibold text-brand-text transition-colors hover:bg-brand-surface-soft"
                        >
                            <Home class="h-4 w-4" />
                            Back to home
                        </Link>
                        <Link
                            :href="logout()"
                            @click="handleLogout"
                            as="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-brand-border bg-brand-surface px-4 py-2 text-sm font-semibold text-brand-text transition-colors hover:bg-brand-surface-soft"
                        >
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </div>

                    <Form
                        v-if="showLinkForm"
                        action="/dashboard/link-request"
                        method="post"
                        :reset-on-success="['alternative_email', 'note']"
                        v-slot="{ errors, processing }"
                        class="mt-4 grid w-full max-w-xl gap-4"
                    >
                        <MyInputConstructor
                            type="email"
                            name="alternative_email"
                            label="Email used on your Trinity application"
                            placeholder="trinity-email@example.com"
                            size="small"
                            required
                            :error="errors.alternative_email"
                        />
                        <div>
                            <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">Anything else? (optional)</label>
                            <textarea
                                name="note"
                                rows="3"
                                placeholder="e.g. school name, candidate names — anything that helps us match you"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            ></textarea>
                            <p v-if="errors.note" class="mt-1 text-sm text-brand-danger">{{ errors.note }}</p>
                        </div>
                        <MyButtonConstructor
                            type="submit"
                            variant="primary"
                            size="medium"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" class="mr-2" />
                            Send to musicExams.help
                        </MyButtonConstructor>
                    </Form>
                </div>
            </div>
        </div>

        <!-- Correction view modal — shows the user the note they previously submitted -->
        <div
            v-if="viewingCorrectionFor !== null && viewedCorrectionEntry?.pending_correction"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            @click.self="closeCorrectionView"
        >
            <div class="w-full max-w-2xl rounded-xl bg-brand-surface p-6 shadow-xl">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-brand-text">Correction you sent</h3>
                        <p class="text-sm text-brand-text-soft">
                            For {{ viewedCorrectionEntry?.candidate_name }}
                            ({{ viewedCorrectionEntry?.grade }}, {{ viewedCorrectionEntry?.subject_area }})
                            &middot;
                            sent {{ viewedCorrectionEntry?.pending_correction?.submitted_at }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-text"
                        aria-label="Close"
                        @click="closeCorrectionView"
                    >
                        ✕
                    </button>
                </div>

                <div class="rounded-lg border border-brand-border bg-brand-surface-soft px-4 py-3">
                    <p class="whitespace-pre-wrap text-base text-brand-text">{{ viewedCorrectionEntry?.pending_correction?.note }}</p>
                </div>

                <p class="mt-4 text-sm text-brand-text-soft">
                    Status: <span class="font-medium text-brand-accent">Pending</span> &mdash; we&rsquo;ll action this and email you when it&rsquo;s done.
                </p>

                <div class="mt-5 flex justify-end">
                    <MyButtonConstructor
                        type="button"
                        variant="primary"
                        size="small"
                        @click="closeCorrectionView"
                    >
                        Close
                    </MyButtonConstructor>
                </div>
            </div>
        </div>
    </div>
</template>
