<!-- resources/js/pages/admin/Users/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import {
    Search,
    Users,
    GraduationCap,
    UserCheck,
    Shield,
    School,
    User,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

interface UserRow {
    id: number
    name: string
    email: string
    phone: string | null
    role: string
    email_verified_at: string | null
    created_at: string
}

interface PaginatedData {
    data: UserRow[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    users: PaginatedData
    summary: {
        total: number
        admins: number
        school_admins: number
        teachers: number
        parents: number
        selves: number
    }
    filters: { search: string | null; role: string | null; sort: string; direction: string }
    roles: string[]
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

const activeRole = ref(props.filters.role ?? null)

function currentFilters(overrides: Record<string, string | undefined> = {}) {
    return {
        search: search.value || undefined,
        role: activeRole.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        ...overrides,
    }
}

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/users', currentFilters({ search: value || undefined }), { preserveState: true, replace: true })
    }, 300)
})

function filterRole(role: string | null) {
    activeRole.value = role
    router.get('/admin/users', currentFilters({ role: role || undefined }), { preserveState: true, replace: true })
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/users', currentFilters({ sort: column, direction }), { preserveState: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

function roleBadgeClass(role: string): string {
    switch (role) {
        case 'admin': return 'bg-brand-burgundy-soft text-brand-burgundy'
        case 'school_admin': return 'bg-brand-success-soft text-brand-success'
        case 'teacher': return 'bg-brand-accent/10 text-brand-accent'
        case 'parent': return 'bg-brand-teal-soft text-brand-teal'
        case 'self': return 'bg-brand-surface-soft text-brand-text-soft'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

function roleLabel(role: string): string {
    switch (role) {
        case 'admin': return 'Admin'
        case 'school_admin': return 'School Admin'
        case 'teacher': return 'Teacher'
        case 'parent': return 'Parent'
        case 'self': return 'Self'
        default: return role.charAt(0).toUpperCase() + role.slice(1)
    }
}

const filterPills: { value: string | null; label: string }[] = [
    { value: null, label: 'All' },
    { value: 'admin', label: 'Admins' },
    { value: 'school_admin', label: 'School Admins' },
    { value: 'teacher', label: 'Teachers' },
    { value: 'parent', label: 'Parents' },
    { value: 'self', label: 'Self' },
]
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Users" subtitle="Registered accounts (auth side)" eyebrow="Admin" size="compact" />

        <!-- Summary pills -->
        <div :class="['mt-6 flex flex-wrap gap-3', animClass('fade-up', 1)]">
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Users class="h-4 w-4 text-brand-text-soft" />
                <span class="text-sm font-medium text-brand-text-soft">Total</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total }}</span>
            </div>
            <div v-if="summary.admins > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Shield class="h-4 w-4 text-brand-burgundy" />
                <span class="text-sm font-medium text-brand-text-soft">Admins</span>
                <span class="text-xl font-bold text-brand-burgundy">{{ summary.admins }}</span>
            </div>
            <div v-if="summary.school_admins > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <School class="h-4 w-4 text-brand-success" />
                <span class="text-sm font-medium text-brand-text-soft">School Admins</span>
                <span class="text-xl font-bold text-brand-success">{{ summary.school_admins }}</span>
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
            <div v-if="summary.selves > 0" class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <User class="h-4 w-4 text-brand-text-soft" />
                <span class="text-sm font-medium text-brand-text-soft">Self</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.selves }}</span>
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

        <!-- Role filter pills -->
        <div :class="['mt-3 flex flex-wrap gap-1', animClass('fade-up', 2)]">
            <button v-for="pill in filterPills" :key="pill.label"
                @click="filterRole(pill.value)"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="(activeRole ?? null) === pill.value ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                {{ pill.label }}
            </button>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="users.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ users.current_page }} of {{ users.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="users.current_page > 1" :href="users.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="users.current_page < users.last_page" :href="users.links[users.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in users.links" :key="'top-' + link.label">
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
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('role')">
                                Role{{ sortIcon('role') }}
                            </th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Verified</th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('created_at')">
                                Joined{{ sortIcon('created_at') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="user in users.data" :key="user.id"
                            class="cursor-pointer transition-colors hover:bg-brand-surface-soft"
                            @click="router.visit(`/admin/users/${user.id}`)">
                            <td class="px-4 py-3">
                                <span class="font-medium text-brand-accent hover:underline">{{ user.name }}</span>
                            </td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ user.email }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ user.phone ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-sm font-medium" :class="roleBadgeClass(user.role)">
                                    {{ roleLabel(user.role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="user.email_verified_at" class="rounded-full bg-brand-success-soft px-2 py-0.5 text-sm font-medium text-brand-success">
                                    {{ user.email_verified_at }}
                                </span>
                                <span v-else class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm text-brand-text-soft">
                                    Unverified
                                </span>
                            </td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ user.created_at }}</span></td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-base text-brand-text-soft">No users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination -->
            <div v-if="users.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ users.current_page }} of {{ users.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="users.current_page > 1" :href="users.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="users.current_page < users.last_page" :href="users.links[users.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in users.links" :key="link.label">
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
