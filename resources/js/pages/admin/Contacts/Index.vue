<!-- resources/js/pages/admin/Contacts/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, Users, GraduationCap, UserCheck, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

interface Contact {
    id: number
    name: string
    email: string | null
    phone: string | null
    types: string[]
    role: string  // primary type — kept for legacy template fragments
    exam_entries_count: number
    students_count: number
    orders_count: number
}

interface PaginatedData {
    data: Contact[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    contacts: PaginatedData
    summary: {
        total: number
        teachers: number
        parents: number
        candidates: number
        school_admins: number
        trinity_admins: number
        subscribers: number
    }
    filters: { search: string | null; type: string | null; role: string | null; sort: string; direction: string }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

// `type` is the canonical filter; `role` only kept as URL alias.
const activeType = ref(props.filters.type ?? props.filters.role ?? null)

function currentFilters(overrides: Record<string, string | undefined> = {}) {
    return {
        search: search.value || undefined,
        type: activeType.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        ...overrides,
    }
}

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/contacts', currentFilters({ search: value || undefined }), { preserveState: true, replace: true })
    }, 300)
})

function filterType(type: string | null) {
    activeType.value = type
    router.get('/admin/contacts', currentFilters({ type: type || undefined }), { preserveState: true, replace: true })
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/contacts', currentFilters({ sort: column, direction }), { preserveState: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

function typeBadgeClass(type: string): string {
    switch (type) {
        case 'teacher': return 'bg-brand-accent/10 text-brand-accent'
        case 'parent': return 'bg-brand-teal-soft text-brand-teal'
        case 'candidate': return 'bg-brand-surface-soft text-brand-text-soft'
        case 'school_admin': return 'bg-brand-success-soft text-brand-success'
        case 'trinity_admin': return 'bg-brand-burgundy-soft text-brand-burgundy'
        case 'subscriber': return 'bg-brand-surface-soft text-brand-text-soft'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

function typeLabel(type: string): string {
    switch (type) {
        case 'school_admin': return 'School Admin'
        case 'trinity_admin': return 'Trinity Admin'
        default: return type.charAt(0).toUpperCase() + type.slice(1)
    }
}

const filterPills: { value: string | null; label: string }[] = [
    { value: null, label: 'All' },
    { value: 'teacher', label: 'Teachers' },
    { value: 'parent', label: 'Parents' },
    { value: 'candidate', label: 'Candidates' },
    { value: 'school_admin', label: 'School Admins' },
    { value: 'trinity_admin', label: 'Trinity Admins' },
    { value: 'subscriber', label: 'Subscribers' },
]
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Contacts" subtitle="All people in the system" eyebrow="Admin" size="compact" />

        <!-- Summary pills -->
        <div :class="['mt-6 flex flex-wrap gap-3', animClass('fade-up', 1)]">
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Users class="h-4 w-4 text-brand-text-soft" />
                <span class="text-sm font-medium text-brand-text-soft">Total</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <GraduationCap class="h-4 w-4 text-brand-accent" />
                <span class="text-sm font-medium text-brand-text-soft">Teachers</span>
                <span class="text-xl font-bold text-brand-accent">{{ summary.teachers }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <UserCheck class="h-4 w-4 text-brand-teal" />
                <span class="text-sm font-medium text-brand-text-soft">Parents</span>
                <span class="text-xl font-bold text-brand-teal">{{ summary.parents }}</span>
            </div>
            <div v-if="summary.candidates > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Candidates</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.candidates }}</span>
            </div>
            <div v-if="summary.school_admins > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">School Admins</span>
                <span class="text-xl font-bold text-brand-success">{{ summary.school_admins }}</span>
            </div>
            <div v-if="summary.trinity_admins > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Trinity</span>
                <span class="text-xl font-bold text-brand-burgundy">{{ summary.trinity_admins }}</span>
            </div>
            <div v-if="summary.subscribers > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Subscribers</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.subscribers }}</span>
            </div>
        </div>

        <!-- Search -->
        <div :class="['mt-4', animClass('fade-up', 2)]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by name, email, or phone..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
        </div>

        <!-- Type filter pills -->
        <div :class="['mt-3 flex flex-wrap gap-1', animClass('fade-up', 2)]">
            <button v-for="pill in filterPills" :key="pill.label"
                @click="filterType(pill.value)"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="(activeType ?? null) === pill.value ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                {{ pill.label }}
            </button>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="contacts.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ contacts.current_page }} of {{ contacts.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="contacts.current_page > 1" :href="contacts.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="contacts.current_page < contacts.last_page" :href="contacts.links[contacts.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in contacts.links" :key="'top-' + link.label">
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
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('name')">
                                Name{{ sortIcon('name') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('email')">
                                Email{{ sortIcon('email') }}
                            </th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Phone</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Types</th>
                            <th class="px-4 py-3 text-center font-semibold text-brand-text">Entries</th>
                            <th class="px-4 py-3 text-center font-semibold text-brand-text">Orders</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="contact in contacts.data" :key="contact.id"
                            class="cursor-pointer transition-colors hover:bg-brand-surface-soft"
                            @click="router.visit(`/admin/contacts/${contact.id}`)">
                            <td class="px-4 py-3">
                                <span class="font-medium text-brand-accent hover:underline">{{ contact.name }}</span>
                            </td>
                            <td class="px-4 py-3 text-brand-text-soft">{{ contact.email ?? '—' }}</td>
                            <td class="px-4 py-3 text-brand-text-soft">{{ contact.phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="t in contact.types" :key="t"
                                        class="rounded-full px-2 py-0.5 text-sm font-medium"
                                        :class="typeBadgeClass(t)">
                                        {{ typeLabel(t) }}
                                    </span>
                                    <span v-if="!contact.types || contact.types.length === 0"
                                        class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm text-brand-text-soft">
                                        unknown
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ contact.exam_entries_count }}</td>
                            <td class="px-4 py-3 text-center text-brand-text">{{ contact.orders_count }}</td>
                        </tr>
                        <tr v-if="!contacts.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-base text-brand-text-soft">No contacts found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination -->
            <div v-if="contacts.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ contacts.current_page }} of {{ contacts.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="contacts.current_page > 1" :href="contacts.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="contacts.current_page < contacts.last_page" :href="contacts.links[contacts.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in contacts.links" :key="link.label">
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
