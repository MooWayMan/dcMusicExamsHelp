<!-- resources/js/pages/admin/Orders/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, Eye, Monitor, MapPin, TrendingUp } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Order {
    id: number
    trinity_order_number: string
    teacher_name: string
    teacher_id: number | null
    school_name: string
    delivery_method: string
    subject_area: string
    candidates: number
    venue: string
    order_status: string
    commission_rate: string
    commission_amount: string
    requested_start_date: string
    exam_entries_count: number
}

interface PaginatedData {
    data: Order[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    orders: PaginatedData
    summary: { total_orders: number; total_commission: string; total_candidates: number }
    filters: { search: string | null; method: string | null; status: string | null; sort: string; direction: string }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/orders', {
            search: value || undefined,
            method: props.filters.method || undefined,
            status: props.filters.status || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

function filterByMethod(method: string | null) {
    router.get('/admin/orders', {
        search: search.value || undefined,
        method: method || undefined,
        status: props.filters.status || undefined,
    }, { preserveState: true, replace: true })
}

function filterByStatus(status: string | null) {
    router.get('/admin/orders', {
        search: search.value || undefined,
        method: props.filters.method || undefined,
        status: status || undefined,
    }, { preserveState: true, replace: true })
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/orders', {
        search: search.value || undefined,
        method: props.filters.method || undefined,
        status: props.filters.status || undefined,
        sort: column,
        direction,
    }, { preserveState: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}
</script>

<template>
    <div class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Orders" subtitle="Trinity exam orders and commission tracking" eyebrow="Admin" size="compact" />

        <!-- Summary cards -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                <p class="text-base font-medium text-brand-text-soft">Orders</p>
                <p class="mt-1 text-3xl font-bold text-brand-text">{{ summary.total_orders }}</p>
            </div>
            <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                <p class="text-base font-medium text-brand-text-soft">Total Commission</p>
                <p class="mt-1 text-3xl font-bold text-brand-success">&pound;{{ summary.total_commission }}</p>
            </div>
            <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                <p class="text-base font-medium text-brand-text-soft">Total Candidates</p>
                <p class="mt-1 text-3xl font-bold text-brand-text">{{ summary.total_candidates }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-6 flex flex-wrap items-center gap-4">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by order #, teacher, or school..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>

            <!-- Method filter -->
            <div class="flex gap-1">
                <button @click="filterByMethod(null)"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="!filters.method ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    All
                </button>
                <button @click="filterByMethod('Digital')"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.method === 'Digital' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    DG
                </button>
                <button @click="filterByMethod('Default')"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.method === 'Default' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    F2F
                </button>
            </div>

            <!-- Status filter -->
            <div class="flex gap-1">
                <button @click="filterByStatus(null)"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="!filters.status ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    All Status
                </button>
                <button v-for="s in ['Submitted', 'Confirmed', 'Completed']" :key="s"
                    @click="filterByStatus(s)"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.status === s ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    {{ s }}
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="mt-4 overflow-hidden rounded-xl border border-brand-border bg-brand-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('trinity_order_number')">
                                Order #{{ sortIcon('trinity_order_number') }}
                            </th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Teacher</th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text lg:table-cell">School</th>
                            <th class="px-4 py-3 text-center font-semibold text-brand-text">Type</th>
                            <th class="hidden px-4 py-3 font-semibold text-brand-text md:table-cell">Subject</th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('candidates')">
                                Cands{{ sortIcon('candidates') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-right font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('commission_amount')">
                                Commission{{ sortIcon('commission_amount') }}
                            </th>
                            <th class="px-4 py-3 text-center font-semibold text-brand-text">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-brand-text">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/orders/${order.id}`" class="font-medium text-brand-accent hover:underline">
                                    {{ order.trinity_order_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <Link v-if="order.teacher_id" :href="`/admin/teachers/${order.teacher_id}`" class="text-base text-brand-text hover:text-brand-accent hover:underline">
                                    {{ order.teacher_name }}
                                </Link>
                                <span v-else class="text-base text-brand-text-soft">{{ order.teacher_name }}</span>
                            </td>
                            <td class="hidden px-4 py-3 text-base text-brand-text-soft lg:table-cell">{{ order.school_name }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="order.delivery_method === 'DG' ? 'bg-brand-accent/10 text-brand-accent' : 'bg-brand-primary/10 text-brand-primary'">
                                    <Monitor v-if="order.delivery_method === 'DG'" class="h-4 w-4" />
                                    <MapPin v-else class="h-4 w-4" />
                                    {{ order.delivery_method }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-base text-brand-text-soft md:table-cell">{{ order.subject_area }}</td>
                            <td class="px-4 py-3 text-center text-base text-brand-text">{{ order.candidates }}</td>
                            <td class="px-4 py-3 text-right text-base font-medium text-brand-success">&pound;{{ order.commission_amount }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="{
                                        'bg-brand-success-soft text-brand-success': order.order_status === 'Completed',
                                        'bg-brand-accent/10 text-brand-accent': order.order_status === 'Confirmed',
                                        'bg-brand-surface-soft text-brand-text-soft': order.order_status === 'Submitted',
                                    }">
                                    {{ order.order_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/admin/orders/${order.id}`" class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                                    <Eye class="inline h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="9" class="px-4 py-8 text-center text-base text-brand-text-soft">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="orders.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ orders.current_page }} of {{ orders.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in orders.links" :key="link.label">
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
