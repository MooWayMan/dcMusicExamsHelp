<!-- resources/js/pages/admin/Students/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, Music } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Student {
    id: number
    first_name: string
    last_name: string
    full_name: string
    email: string | null
    teacher_name: string
    teacher_id: number
    instrument: string
    instrument_family: string
    exam_entries_count: number
}

interface PaginatedData {
    data: Student[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    students: PaginatedData
    filters: { search: string | null; family: string | null; sort: string; direction: string }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/students', {
            search: value || undefined,
            family: props.filters.family || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

function filterByFamily(family: string | null) {
    router.get('/admin/students', {
        search: search.value || undefined,
        family: family || undefined,
    }, { preserveState: true, replace: true })
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/students', {
        search: search.value || undefined,
        family: props.filters.family || undefined,
        sort: column,
        direction,
    }, { preserveState: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

const families = ['Keyboard', 'Strings', 'Brass', 'Woodwind', 'Voice', 'Percussion']

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Students" subtitle="All students across your teaching network" eyebrow="Admin" size="compact" />

        <!-- Filters -->
        <div :class="['mt-6 flex flex-wrap items-center gap-4', animClass('fade-up', 1)]">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by name, teacher, or instrument..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
            <p class="text-base text-brand-text-soft">{{ students.total }} student{{ students.total !== 1 ? 's' : '' }}</p>
        </div>

        <!-- Instrument family filter -->
        <div :class="['mt-3 flex flex-wrap gap-1', animClass('fade-up', 2)]">
            <button @click="filterByFamily(null)"
                class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="!filters.family ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                All Families
            </button>
            <button v-for="family in families" :key="family"
                @click="filterByFamily(family)"
                class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="filters.family === family ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                {{ family }}
            </button>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="students.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ students.current_page }} of {{ students.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in students.links" :key="'top-' + link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[600px] w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('last_name')">
                                Name{{ sortIcon('last_name') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('teacher')">
                                Teacher{{ sortIcon('teacher') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('instrument')">
                                Instrument{{ sortIcon('instrument') }}
                            </th>
                            <th class="hidden cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent md:table-cell" @click="sortBy('instrument_family')">
                                Family{{ sortIcon('instrument_family') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('exam_entries_count')">
                                Exams{{ sortIcon('exam_entries_count') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="student in students.data" :key="student.id" class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3">
                                <p class="font-medium text-brand-text">{{ student.full_name }}</p>
                                <p v-if="student.email" class="text-sm text-brand-text-soft">{{ student.email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="`/admin/teachers/${student.teacher_id}`" class="text-base text-brand-accent hover:underline">
                                    {{ student.teacher_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <Music class="h-5 w-5 text-brand-text-soft" />
                                    <span class="text-base text-brand-text">{{ student.instrument }}</span>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span class="rounded-full bg-brand-surface-soft px-2.5 py-1 text-sm font-medium text-brand-text-soft">
                                    {{ student.instrument_family }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-base text-brand-text">{{ student.exam_entries_count }}</td>
                        </tr>
                        <tr v-if="!students.data.length">
                            <td colspan="5" class="px-4 py-8 text-center text-base text-brand-text-soft">
                                No students found{{ search ? ' matching your search' : '' }}.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="students.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ students.current_page }} of {{ students.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in students.links" :key="link.label">
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
