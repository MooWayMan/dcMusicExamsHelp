<!-- resources/js/pages/admin/ExamEntries/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import { ArrowLeft } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

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
    filters: {
        sort: string
        direction: 'asc' | 'desc'
        search: string
        quarter: string
        student_id?: string | number | null
        from?: string | null
    }
}>()

const form = reactive({
    search: props.filters.search ?? '',
    quarter: props.filters.quarter ?? '',
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function runQuery(overrides: Record<string, string | number | null> = {}) {
    router.get(
        '/admin/exam-entries',
        {
            sort: props.filters.sort,
            direction: props.filters.direction,
            search: form.search,
            quarter: form.quarter,
            student_id: props.filters.student_id ?? undefined,
            from: props.filters.from ?? undefined,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

function clearFilters() {
    form.search = ''
    form.quarter = ''

    router.get(
        '/admin/exam-entries',
        {
            sort: 'exam_date',
            direction: 'desc',
            student_id: props.filters.student_id ?? undefined,
            from: props.filters.from ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

function sortBy(column: string) {
    const sameColumn = props.filters.sort === column
    const direction =
        sameColumn && props.filters.direction === 'asc' ? 'desc' : 'asc'

    router.get(
        '/admin/exam-entries',
        {
            sort: column,
            direction,
            search: form.search,
            quarter: form.quarter,
            student_id: props.filters.student_id ?? undefined,
            from: props.filters.from ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

function sortIndicator(column: string) {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

function filterByValue(value: string | null | undefined) {
    if (!value) return

    form.search = value

    router.get(
        '/admin/exam-entries',
        {
            sort: props.filters.sort,
            direction: props.filters.direction,
            search: value,
            quarter: form.quarter,
            student_id: props.filters.student_id ?? undefined,
            from: props.filters.from ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

watch(
    () => form.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout)

        searchTimeout = setTimeout(() => {
            runQuery()
        }, 250)
    },
)

watch(
    () => form.quarter,
    () => {
        runQuery()
    },
)
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div v-if="filters.from === 'students'" class="mb-4">
            <Link
                href="/admin/students"
                class="inline-flex items-center gap-2 text-brand-text-soft transition-colors hover:text-brand-accent"
            >
                <ArrowLeft class="h-5 w-5" />
                <span class="text-base font-medium">Students</span>
            </Link>
        </div>

        <PageHeader
            title="Exam Entries"
            subtitle="Imported candidate results and raw exam data"
            eyebrow="Admin"
            size="compact"
        />

        <div class="mt-4">
            <MyTextConstructor variant="body">
                <template #myText>
                    Showing {{ entries.from ?? 0 }}–{{ entries.to ?? 0 }} of {{ entries.total }} exam entries
                </template>
            </MyTextConstructor>
        </div>

        <div class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
                <div>
                    <MyTextConstructor variant="body">
                        <template #myText>Search</template>
                    </MyTextConstructor>
                    <input
                        v-model="form.search"
                        type="text"
                        placeholder="Candidate, teacher, school, order..."
                        class="mt-2 w-full rounded-lg border border-brand-border bg-white px-4 py-3 text-base text-brand-text"
                    >
                </div>

                <div>
                    <MyTextConstructor variant="body">
                        <template #myText>Quarter</template>
                    </MyTextConstructor>
                    <select
                        v-model="form.quarter"
                        class="mt-2 w-full rounded-lg border border-brand-border bg-white px-4 py-3 text-base text-brand-text"
                    >
                        <option value="">All</option>
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                    </select>
                </div>

                <div>
                    <button
                        type="button"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base font-semibold text-brand-text"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-xl border border-brand-border bg-brand-surface">
            <div class="border-b border-brand-border p-4">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>
                        Exam Entries
                    </template>
                </MyTextConstructor>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-brand-border bg-brand-primary text-brand-text-inverse">
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('exam_date')">
                                    Date{{ sortIndicator('exam_date') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('order_number')">
                                    Order{{ sortIndicator('order_number') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('candidate_name')">
                                    Candidate{{ sortIndicator('candidate_name') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('candidate_number')">
                                    Cand #{{ sortIndicator('candidate_number') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('subject_area')">
                                    Subject{{ sortIndicator('subject_area') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('grade')">
                                    Grade{{ sortIndicator('grade') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('delivery_method')">
                                    Type{{ sortIndicator('delivery_method') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('result')">
                                    Result{{ sortIndicator('result') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('score')">
                                    Score{{ sortIndicator('score') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('teacher_name')">
                                    Teacher{{ sortIndicator('teacher_name') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-base font-semibold">
                                <button type="button" class="text-left hover:underline" @click="sortBy('school_name')">
                                    School{{ sortIndicator('school_name') }}
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(entry, idx) in entries.data"
                            :key="entry.id"
                            class="border-b border-brand-border"
                            :class="idx % 2 === 1 ? 'bg-brand-surface-soft/50' : ''"
                        >
                            <td class="px-4 py-3 text-base text-brand-text">
                                {{ entry.exam_date ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-base">
                                <Link
                                    :href="`/admin/orders/${entry.order_id}`"
                                    class="text-brand-accent hover:underline"
                                >
                                    {{ entry.order_number }}
                                </Link>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.candidate_name"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.candidate_name)"
                                >
                                    {{ entry.candidate_name }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.candidate_number"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.candidate_number)"
                                >
                                    {{ entry.candidate_number }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.subject_area"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.subject_area)"
                                >
                                    {{ entry.subject_area }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.grade"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.grade)"
                                >
                                    {{ entry.grade }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.delivery_method"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.delivery_method)"
                                >
                                    {{ entry.delivery_method }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.result"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.result)"
                                >
                                    {{ entry.result }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base text-brand-text">
                                {{ entry.score ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.teacher_name"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.teacher_name)"
                                >
                                    {{ entry.teacher_name }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>

                            <td class="px-4 py-3 text-base">
                                <button
                                    v-if="entry.school_name"
                                    type="button"
                                    class="text-left text-brand-text hover:text-brand-accent hover:underline"
                                    @click="filterByValue(entry.school_name)"
                                >
                                    {{ entry.school_name }}
                                </button>
                                <span v-else class="text-brand-text">—</span>
                            </td>
                        </tr>

                        <tr v-if="entries.data.length === 0">
                            <td colspan="11" class="px-4 py-6 text-center">
                                <MyTextConstructor variant="body">
                                    <template #myText>
                                        No exam entries found
                                    </template>
                                </MyTextConstructor>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-border p-4">
                <MyTextConstructor variant="body">
                    <template #myText>
                        Page {{ entries.current_page }} of {{ entries.last_page }}
                    </template>
                </MyTextConstructor>

                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in entries.links"
                        :key="link.label"
                        :href="link.url || ''"
                        class="rounded-md border px-3 py-2 text-sm"
                        :class="[
                            link.active
                                ? 'border-brand-primary bg-brand-primary text-brand-text-inverse'
                                : 'border-brand-border bg-brand-surface text-brand-text',
                            !link.url ? 'pointer-events-none opacity-50' : 'hover:bg-brand-surface-soft',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>