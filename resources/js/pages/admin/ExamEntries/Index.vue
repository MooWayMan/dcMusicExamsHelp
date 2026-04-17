<!-- resources/js/pages/admin/ExamEntries/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, ArrowLeft, Award, Star, Trophy, ChevronLeft, ChevronRight } from 'lucide-vue-next'
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
    school_name: string | null
    fee: string | null
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
    summary: { total: number; with_results: number; distinctions: number; merits: number }
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
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
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

        <!-- Summary pills -->
        <div :class="['mt-6 flex flex-wrap gap-3', animClass('fade-up', 1)]">
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Award class="h-4 w-4 text-brand-text-soft" />
                <span class="text-sm font-medium text-brand-text-soft">Total</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Results In</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.with_results }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Trophy class="h-4 w-4 text-brand-success" />
                <span class="text-sm font-medium text-brand-text-soft">Distinctions</span>
                <span class="text-xl font-bold text-brand-success">{{ summary.distinctions }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Star class="h-4 w-4 text-brand-accent" />
                <span class="text-sm font-medium text-brand-text-soft">Merits</span>
                <span class="text-xl font-bold text-brand-accent">{{ summary.merits }}</span>
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
                <table class="min-w-[900px] w-full text-left text-base">
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="entry in entries.data" :key="entry.id" class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3 text-brand-text">{{ entry.exam_date ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="`/admin/orders/${entry.order_id}`" class="font-medium text-brand-accent hover:underline">
                                    {{ entry.order_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.candidate_name" type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.candidate_name)">
                                    {{ entry.candidate_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.subject_area" type="button"
                                    class="text-left text-brand-text-soft hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.subject_area)">
                                    {{ entry.subject_area }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3 text-brand-text">{{ entry.grade ?? '—' }}</td>
                            <td class="px-4 py-3 text-brand-text-soft">{{ entry.delivery_method ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="entry.result" class="rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="resultBadgeClass(entry.result)">
                                    {{ entry.result }}
                                </span>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ entry.score ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <button v-if="entry.teacher_name" type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.teacher_name)">
                                    {{ entry.teacher_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="entry.school_name" type="button"
                                    class="text-left text-brand-text-soft hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.school_name)">
                                    {{ entry.school_name }}
                                </button>
                                <span v-else class="text-brand-text-soft">—</span>
                            </td>
                        </tr>
                        <tr v-if="!entries.data.length">
                            <td colspan="10" class="px-4 py-8 text-center text-base text-brand-text-soft">No exam entries found.</td>
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
    </div>
</template>
