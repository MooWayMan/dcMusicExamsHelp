<!-- resources/js/pages/admin/Teachers/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import {
    Plus,
    Search,
    Eye,
    Pencil,
    Trash2,
    Phone,
    Mail,
    UserCheck,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Teacher {
    id: number
    name: string
    email: string
    phone: string | null
    schools: string
    instruments: string
    subject_areas: string
    students_count: number
    orders_count: number
    met_face_to_face: boolean
    spoken_on_phone: boolean
    contacted_by_email: boolean
    how_they_found_us: string | null
    created_at: string
}

interface PaginatedData {
    data: Teacher[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    teachers: PaginatedData
    filters: {
        search: string | null
        sort: string
        direction: string
    }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/teachers', { search: value || undefined }, {
            preserveState: true,
            replace: true,
        })
    }, 300)
})

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/teachers', {
        search: search.value || undefined,
        sort: column,
        direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

async function deleteTeacher(teacher: Teacher) {
    // Fetch impact data so the user knows what they're archiving
    try {
        const response = await fetch(`/admin/teachers/${teacher.id}/deletion-impact`)
        const impact = await response.json()

        const parts = []
        if (impact.students_count > 0) parts.push(`${impact.students_count} student${impact.students_count !== 1 ? 's' : ''}`)
        if (impact.orders_count > 0) parts.push(`${impact.orders_count} order${impact.orders_count !== 1 ? 's' : ''}`)
        if (impact.contact_logs_count > 0) parts.push(`${impact.contact_logs_count} contact log${impact.contact_logs_count !== 1 ? 's' : ''}`)

        let message = `Are you sure you want to archive ${teacher.name}?`
        if (parts.length) {
            message += `\n\nThis teacher has ${parts.join(', ')} linked to their account.`
        }
        message += `\n\nThey can be restored later from the archived list.`

        if (confirm(message)) {
            router.delete(`/admin/teachers/${teacher.id}`)
        }
    } catch {
        if (confirm(`Are you sure you want to archive ${teacher.name}? They can be restored later.`)) {
            router.delete(`/admin/teachers/${teacher.id}`)
        }
    }
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <PageHeader
            title="Teachers"
            subtitle="Manage your teaching network"
            eyebrow="Admin"
            size="compact"
        >
            <template #actions>
                <Link href="/admin/teachers/create">
                    <MyButtonConstructor variant="primary" size="medium" :icon="Plus">
                        Add Teacher
                    </MyButtonConstructor>
                </Link>
            </template>
        </PageHeader>

        <!-- Search bar -->
        <div class="mt-6 flex items-center gap-4">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, email, or phone..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-sm text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                />
            </div>
            <p class="text-sm text-brand-text-soft">{{ teachers.total }} teacher{{ teachers.total !== 1 ? 's' : '' }}</p>
        </div>

        <!-- Table -->
        <div class="mt-4 overflow-hidden rounded-xl border border-brand-border bg-brand-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th
                                class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent"
                                @click="sortBy('name')"
                            >
                                Name{{ sortIcon('name') }}
                            </th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Contact</th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text lg:table-cell">Schools</th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text md:table-cell">Instruments</th>
                            <th
                                class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent"
                                @click="sortBy('students_count')"
                            >
                                Students{{ sortIcon('students_count') }}
                            </th>
                            <th
                                class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent"
                                @click="sortBy('orders_count')"
                            >
                                Orders{{ sortIcon('orders_count') }}
                            </th>
                            <th class="px-4 py-3 text-center font-semibold text-brand-text">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-brand-text">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr
                            v-for="teacher in teachers.data"
                            :key="teacher.id"
                            class="transition-colors hover:bg-brand-surface-soft"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/admin/teachers/${teacher.id}`"
                                    class="font-medium text-brand-accent hover:underline"
                                >
                                    {{ teacher.name }}
                                </Link>
                                <p class="text-xs text-brand-text-soft">{{ teacher.how_they_found_us ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-brand-text">{{ teacher.email }}</p>
                                <p v-if="teacher.phone" class="text-xs text-brand-text-soft">{{ teacher.phone }}</p>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <p class="max-w-[200px] truncate text-sm text-brand-text-soft">
                                    {{ teacher.schools || '—' }}
                                </p>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <p class="max-w-[200px] truncate text-sm text-brand-text-soft">
                                    {{ teacher.instruments || '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-brand-text">
                                {{ teacher.students_count }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-brand-text">
                                {{ teacher.orders_count }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <Mail
                                        class="h-4 w-4"
                                        :class="teacher.contacted_by_email ? 'text-brand-success' : 'text-brand-border'"
                                    />
                                    <Phone
                                        class="h-4 w-4"
                                        :class="teacher.spoken_on_phone ? 'text-brand-success' : 'text-brand-border'"
                                    />
                                    <UserCheck
                                        class="h-4 w-4"
                                        :class="teacher.met_face_to_face ? 'text-brand-success' : 'text-brand-border'"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="`/admin/teachers/${teacher.id}`"
                                        class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        :href="`/admin/teachers/${teacher.id}/edit`"
                                        class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        @click="deleteTeacher(teacher)"
                                        class="rounded p-1.5 text-brand-text-soft hover:bg-brand-danger-soft hover:text-brand-danger"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!teachers.data.length">
                            <td colspan="8" class="px-4 py-8 text-center text-brand-text-soft">
                                No teachers found{{ search ? ' matching your search' : '' }}.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="teachers.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-sm text-brand-text-soft">
                    Page {{ teachers.current_page }} of {{ teachers.last_page }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in teachers.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded px-3 py-1 text-sm transition-colors"
                            :class="link.active
                                ? 'bg-brand-accent text-brand-text-inverse font-semibold'
                                : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label"
                            preserve-state
                        />
                        <span
                            v-else
                            class="rounded px-3 py-1 text-sm text-brand-border"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
