<!-- resources/js/pages/admin/Schools/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Plus, Search, Eye, Pencil, Trash2, MapPin, Phone } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface School {
    id: number
    name: string
    address: string | null
    city: string | null
    postcode: string | null
    phone: string | null
    email: string | null
    contact_name: string | null
    teachers_count: number
    orders_count: number
}

interface PaginatedData {
    data: School[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    schools: PaginatedData
    filters: { search: string | null; sort: string; direction: string }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/schools', { search: value || undefined }, {
            preserveState: true,
            replace: true,
        })
    }, 300)
})

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/schools', { search: search.value || undefined, sort: column, direction }, {
        preserveState: true, replace: true,
    })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

function deleteSchool(school: School) {
    if (confirm(`Are you sure you want to archive ${school.name}? It can be restored later.`)) {
        router.delete(`/admin/schools/${school.id}`)
    }
}
</script>

<template>
    <div class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Schools" subtitle="Manage exam venues and teaching locations" eyebrow="Admin" size="compact">
            <template #actions>
                <Link href="/admin/schools/create">
                    <MyButtonConstructor variant="primary" size="medium" :icon="Plus">Add School</MyButtonConstructor>
                </Link>
            </template>
        </PageHeader>

        <div class="mt-6 flex items-center gap-4">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by name, city, postcode, or contact..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
            <p class="text-base text-brand-text-soft">{{ schools.total }} school{{ schools.total !== 1 ? 's' : '' }}</p>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-brand-border bg-brand-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('name')">
                                School{{ sortIcon('name') }}
                            </th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text md:table-cell">Location</th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text lg:table-cell">Contact</th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('teachers_count')">
                                Teachers{{ sortIcon('teachers_count') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('orders_count')">
                                Orders{{ sortIcon('orders_count') }}
                            </th>
                            <th class="px-4 py-3 text-right font-semibold text-brand-text">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="school in schools.data" :key="school.id" class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/schools/${school.id}`" class="font-medium text-brand-accent hover:underline">
                                    {{ school.name }}
                                </Link>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <div class="flex items-center gap-1.5 text-base text-brand-text-soft">
                                    <MapPin class="h-5 w-5" />
                                    {{ school.city || '—' }}<span v-if="school.postcode">, {{ school.postcode }}</span>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <p class="text-base text-brand-text">{{ school.contact_name || '—' }}</p>
                                <p v-if="school.phone" class="text-sm text-brand-text-soft">{{ school.phone }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-base text-brand-text">{{ school.teachers_count }}</td>
                            <td class="px-4 py-3 text-center text-base text-brand-text">{{ school.orders_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/admin/schools/${school.id}`" class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link :href="`/admin/schools/${school.id}/edit`" class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button @click="deleteSchool(school)" class="rounded p-1.5 text-brand-text-soft hover:bg-brand-danger-soft hover:text-brand-danger">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!schools.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-base text-brand-text-soft">
                                No schools found{{ search ? ' matching your search' : '' }}.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="schools.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ schools.current_page }} of {{ schools.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in schools.links" :key="link.label">
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
