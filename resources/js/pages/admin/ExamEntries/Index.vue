<!-- resources/js/pages/admin/ExamEntries/Index.vue -->
<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Search, ArrowLeft, Award, Star, Trophy, ChevronLeft, ChevronRight, Clock, CheckCircle2, Pencil, X } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

interface ExamEntryRow {
    id: number
    order_id: number
    order_number: string
    candidate_number: string | null
    candidate_name: string | null
    grade: string | null
    subject_area: string | null
    delivery_method: string | null
    result: string | null
    score: number | null
    exam_date: string | null
    teacher_name: string | null
    teacher_contact_id: number | null
    school_name: string | null
    fee: string | null
    raw_teacher_name: string | null
    notes: string | null
    show_full_name: boolean
    booking_role: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedEntries {
    data: ExamEntryRow[]
    links: PaginationLink[]
    from: number | null
    to: number | null
    total: number
    current_page: number
    last_page: number
}

const props = defineProps<{
    entries: PaginatedEntries
    summary: { total: number; with_results: number; distinctions: number; merits: number; awaiting: number }
    filters: {
        sort: string
        direction: 'asc' | 'desc'
        search: string
        quarter: string
        student_id?: string | number | null
        from?: string | null
    }
}>()

const search = ref(props.filters.search ?? '')

// Derived stats — make the nesting explicit so the cards are self-documenting
const passCount = computed(() => Math.max(0, props.summary.with_results - props.summary.distinctions - props.summary.merits))
// Awaiting now comes directly from the controller — excludes CANCELLED + NO_SHOW
// (formerly: total − with_results, which incorrectly included them).
const awaitingCount = computed(() => props.summary.awaiting ?? 0)
function pct(part: number, whole: number): string {
    if (!whole) return '0%'
    return `${Math.round((part / whole) * 100)}%`
}
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function currentFilters(overrides: Record<string, string | number | undefined | null> = {}) {
    return {
        search: search.value || undefined,
        quarter: props.filters.quarter || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        student_id: props.filters.student_id ?? undefined,
        from: props.filters.from ?? undefined,
        ...overrides,
    }
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/exam-entries', currentFilters({ sort: column, direction }), { preserveState: true, preserveScroll: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

function filterQuarter(quarter: string | null) {
    router.get('/admin/exam-entries', currentFilters({ quarter: quarter || undefined }), { preserveState: true, preserveScroll: true, replace: true })
}

function filterByValue(value: string | null | undefined) {
    if (!value) return
    search.value = value
    router.get('/admin/exam-entries', currentFilters({ search: value }), { preserveState: true, preserveScroll: true, replace: true })
}

function clearFilters() {
    search.value = ''
    router.get('/admin/exam-entries', {
        sort: 'exam_date',
        direction: 'desc',
        student_id: props.filters.student_id ?? undefined,
        from: props.filters.from ?? undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/exam-entries', currentFilters({ search: search.value || undefined }), { preserveState: true, preserveScroll: true, replace: true })
    }, 300)
})

function resultBadgeClass(result: string): string {
    switch (result) {
        case 'Distinction': return 'bg-brand-success-soft text-brand-success'
        case 'Merit': return 'bg-brand-accent/10 text-brand-accent'
        case 'Pass': return 'bg-brand-surface-soft text-brand-text-soft'
        case 'Below Pass': return 'bg-brand-danger-soft text-brand-danger'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

// ── Inline edit modal ──────────────────────────────────────────────
// Correct a single imported entry (wrong candidate name, parent-in-teacher
// field, or a result/score Trinity reported wrong) without going to TablePlus.
const editing = ref<ExamEntryRow | null>(null)
const form = useForm({
    candidate_name: '',
    teacher_name: '',
    result: '',
    score: null as number | null,
    notes: '',
    show_full_name: false,
    booking_role: '' as string,
})

// Same four roles the import page offers. Kept in this order so the two
// draw-eligible roles sit together.
const bookingRoleOptions = [
    { value: 'teacher', label: 'Teacher — in the prize draw' },
    { value: 'school_admin', label: 'School admin — in the prize draw' },
    { value: 'parent', label: 'Parent — not in the draw' },
    { value: 'self', label: 'Self — candidate entered themselves (not in draw)' },
]

function openEdit(entry: ExamEntryRow) {
    editing.value = entry
    form.clearErrors()
    form.candidate_name = entry.candidate_name ?? ''
    form.teacher_name = entry.raw_teacher_name ?? ''
    form.result = entry.result ?? ''
    form.score = entry.score
    form.notes = entry.notes ?? ''
    form.show_full_name = entry.show_full_name
    form.booking_role = entry.booking_role ?? ''
}

function closeEdit() {
    editing.value = null
}

function submitEdit() {
    if (!editing.value) return
    form.put(`/admin/exam-entries/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    })
}
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Back link when coming from students -->
        <div v-if="filters.from === 'students'" :class="['mb-4', animClass('fade-up', 0)]">
            <Link href="/admin/students"
                class="inline-flex items-center gap-2 text-brand-text-soft transition-colors hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
                <span class="text-base font-medium">Students</span>
            </Link>
        </div>

        <PageHeader title="Exam Entries" subtitle="Imported candidate results and raw exam data" eyebrow="Admin" size="compact" />

        <!-- Summary pills — top row: top-level totals; bottom row: breakdown of "Results In" -->
        <div :class="['mt-6 space-y-2', animClass('fade-up', 1)]">
            <!-- Top row: Total | Results In | Awaiting -->
            <div class="flex flex-wrap gap-3">
                <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                    <Award class="h-4 w-4 text-brand-text-soft" />
                    <span class="text-sm font-medium text-brand-text-soft">Total</span>
                    <span class="text-xl font-bold text-brand-text">{{ summary.total }}</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                    <CheckCircle2 class="h-4 w-4 text-brand-text-soft" />
                    <span class="text-sm font-medium text-brand-text-soft">Results In</span>
                    <span class="text-xl font-bold text-brand-text">{{ summary.with_results }}</span>
                    <span class="text-sm text-brand-text-soft">of {{ summary.total }}</span>
                </div>
                <div v-if="awaitingCount > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                    <Clock class="h-4 w-4 text-brand-text-soft" />
                    <span class="text-sm font-medium text-brand-text-soft">Awaiting</span>
                    <span class="text-xl font-bold text-brand-text">{{ awaitingCount }}</span>
                </div>
            </div>

            <!-- Bottom row: breakdown of the "Results In" total -->
            <div v-if="summary.with_results > 0" class="flex flex-wrap items-center gap-3 pl-2">
                <span class="text-xs font-medium uppercase tracking-wide text-brand-text-soft">Result breakdown:</span>
                <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface-soft px-3 py-1.5">
                    <Trophy class="h-4 w-4 text-brand-success" />
                    <span class="text-sm text-brand-text-soft">Distinction</span>
                    <span class="text-base font-bold text-brand-success">{{ summary.distinctions }}</span>
                    <span class="text-xs text-brand-text-soft">({{ pct(summary.distinctions, summary.with_results) }})</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface-soft px-3 py-1.5">
                    <Star class="h-4 w-4 text-brand-accent" />
                    <span class="text-sm text-brand-text-soft">Merit</span>
                    <span class="text-base font-bold text-brand-accent">{{ summary.merits }}</span>
                    <span class="text-xs text-brand-text-soft">({{ pct(summary.merits, summary.with_results) }})</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface-soft px-3 py-1.5">
                    <span class="text-sm text-brand-text-soft">Pass</span>
                    <span class="text-base font-bold text-brand-text">{{ passCount }}</span>
                    <span class="text-xs text-brand-text-soft">({{ pct(passCount, summary.with_results) }})</span>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div :class="['mt-4', animClass('fade-up', 2)]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search candidate, teacher, school, order..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
        </div>

        <!-- Quarter filter pills + clear -->
        <div :class="['mt-3 flex flex-wrap items-center gap-4', animClass('fade-up', 2)]">
            <div class="flex gap-1">
                <button @click="filterQuarter(null)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="!filters.quarter ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    All
                </button>
                <button v-for="q in ['Q1', 'Q2', 'Q3', 'Q4']" :key="q"
                    @click="filterQuarter(q)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.quarter === q ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    {{ q }}
                </button>
            </div>
            <button v-if="search || filters.quarter"
                @click="clearFilters"
                class="cursor-pointer rounded-full border border-brand-border px-3 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:text-brand-text">
                Clear Filters
            </button>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="entries.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ entries.current_page }} of {{ entries.last_page }} &middot; {{ entries.total }} entries</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="entries.current_page > 1" :href="entries.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="entries.current_page < entries.last_page" :href="entries.links[entries.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in entries.links" :key="'top-' + link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1000px] w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('exam_date')">
                                Date{{ sortIcon('exam_date') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('order_number')">
                                Order{{ sortIcon('order_number') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('candidate_name')">
                                Candidate{{ sortIcon('candidate_name') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('candidate_number')">
                                Candidate #{{ sortIcon('candidate_number') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('subject_area')">
                                Subject{{ sortIcon('subject_area') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('grade')">
                                Grade{{ sortIcon('grade') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('delivery_method')">
                                Type{{ sortIcon('delivery_method') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('result')">
                                Result{{ sortIcon('result') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('score')">
                                Score{{ sortIcon('score') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('teacher_name')">
                                Teacher{{ sortIcon('teacher_name') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('school_name')">
                                School{{ sortIcon('school_name') }}
                            </th>
                            <th class="px-4 py-3 text-right font-semibold text-brand-text">Edit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="entry in entries.data" :key="entry.id" class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ entry.exam_date ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <Link :href="`/admin/orders/${entry.order_id}`" class="font-medium text-brand-accent hover:underline">
                                    {{ entry.order_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.candidate_name" type="button"
                                    class="text-left font-medium text-brand-accent hover:underline"
                                    @click="filterByValue(entry.candidate_name)">
                                    {{ entry.candidate_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="entry.candidate_number" class="select-all text-sm text-brand-text-soft">{{ entry.candidate_number }}</span>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.subject_area" type="button"
                                    class="text-left text-sm text-brand-text-soft hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.subject_area)">
                                    {{ entry.subject_area }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ entry.grade ?? '—' }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ entry.delivery_method ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <span v-if="entry.result" class="rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="resultBadgeClass(entry.result)">
                                    {{ entry.result }}
                                </span>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="text-sm font-medium text-brand-text">{{ entry.score ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <button v-if="entry.teacher_name" type="button"
                                    class="text-left text-brand-accent hover:underline"
                                    @click="filterByValue(entry.teacher_name)">
                                    {{ entry.teacher_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.school_name" type="button"
                                    class="text-left text-sm text-brand-text-soft hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.school_name)">
                                    {{ entry.school_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" title="Edit entry"
                                    class="inline-flex items-center rounded-lg p-1.5 text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-accent"
                                    @click="openEdit(entry)">
                                    <Pencil class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!entries.data.length">
                            <td colspan="12" class="px-4 py-8 text-center text-base text-brand-text-soft">No exam entries found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination -->
            <div v-if="entries.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ entries.current_page }} of {{ entries.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="entries.current_page > 1" :href="entries.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="entries.current_page < entries.last_page" :href="entries.links[entries.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in entries.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Inline edit modal -->
        <div v-if="editing" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:items-center"
            @click.self="closeEdit">
            <div class="w-full max-w-lg rounded-2xl border border-brand-border bg-brand-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <h2 class="text-lg font-semibold text-brand-text">Edit exam entry</h2>
                    <button type="button" class="rounded-lg p-1 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text" @click="closeEdit">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Candidate name</label>
                        <input v-model="form.candidate_name" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                        <p v-if="form.errors.candidate_name" class="mt-1 text-sm text-brand-danger">{{ form.errors.candidate_name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Teacher name</label>
                        <input v-model="form.teacher_name" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                        <p v-if="editing.teacher_contact_id" class="mt-1 text-xs text-brand-text-soft">
                            This entry is linked to a confirmed teacher contact, so the list keeps showing that contact's name. Editing here only changes the raw imported string.
                        </p>
                        <p v-if="form.errors.teacher_name" class="mt-1 text-sm text-brand-danger">{{ form.errors.teacher_name }}</p>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-brand-text">Result</label>
                            <input v-model="form.result" type="text" placeholder="Distinction / Merit / Pass…"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                            <p v-if="form.errors.result" class="mt-1 text-sm text-brand-danger">{{ form.errors.result }}</p>
                        </div>
                        <div class="w-28">
                            <label class="mb-1 block text-sm font-medium text-brand-text">Score</label>
                            <input v-model.number="form.score" type="number" min="0" max="100"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                            <p v-if="form.errors.score" class="mt-1 text-sm text-brand-danger">{{ form.errors.score }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Booking role</label>
                        <select v-model="form.booking_role"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                            <option value="">Not set — infer from the contact's type</option>
                            <option v-for="role in bookingRoleOptions" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-brand-text-soft">
                            Set on this entry, this beats the contact's type on Quarter End — use it when the importer read a parent entering their own child as a teacher.
                        </p>
                        <p v-if="form.errors.booking_role" class="mt-1 text-sm text-brand-danger">{{ form.errors.booking_role }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Notes</label>
                        <textarea v-model="form.notes" rows="2"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"></textarea>
                        <p class="mt-1 text-xs text-brand-text-soft">CANCELLED or NO_SHOW here drive result-based filters — use them exactly.</p>
                        <p v-if="form.errors.notes" class="mt-1 text-sm text-brand-danger">{{ form.errors.notes }}</p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input v-model="form.show_full_name" type="checkbox"
                            class="h-4 w-4 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                        <span class="text-sm text-brand-text">Show full name publicly (consent given)</span>
                    </label>

                    <div class="flex justify-end gap-3 border-t border-brand-border pt-4">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-brand-text-soft hover:text-brand-text" @click="closeEdit">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
