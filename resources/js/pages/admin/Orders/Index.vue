<!-- resources/js/pages/admin/Orders/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, Eye, Monitor, MapPin, TrendingUp, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Order {
    id: number
    trinity_order_number: string
    teacher_name: string
    teacher_id: number | null
    school_name: string
    school_id: number | null
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
    filters: { search: string | null; method: string | null; status: string | null; period: string | null; sort: string; direction: string }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

function currentFilters(overrides: Record<string, string | undefined> = {}) {
    return {
        search: search.value || undefined,
        method: props.filters.method || undefined,
        status: props.filters.status || undefined,
        period: props.filters.period || undefined,
        ...overrides,
    }
}

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/orders', currentFilters({ search: value || undefined }), { preserveState: true, replace: true })
    }, 300)
})

function filterByMethod(method: string | null) {
    router.get('/admin/orders', currentFilters({ method: method || undefined }), { preserveState: true, replace: true })
}

function filterByStatus(status: string | null) {
    router.get('/admin/orders', currentFilters({ status: status || undefined }), { preserveState: true, replace: true })
}

function filterByPeriod(period: string | null) {
    router.get('/admin/orders', currentFilters({ period: period || undefined }), { preserveState: true, replace: true })
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/admin/orders', currentFilters({ sort: column, direction }), { preserveState: true, replace: true })
}

function sortIcon(column: string): string {
    if (props.filters.sort !== column) return ''
    return props.filters.direction === 'asc' ? ' ↑' : ' ↓'
}

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Orders" subtitle="Trinity exam orders and commission tracking" eyebrow="Admin" size="compact" />

        <!-- Summary pills -->
        <div :class="['mt-6 flex flex-wrap gap-3', animClass('fade-up', 1)]">
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Orders</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total_orders }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Commission</span>
                <span class="text-xl font-bold text-brand-success">&pound;{{ summary.total_commission }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <span class="text-sm font-medium text-brand-text-soft">Candidates</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total_candidates }}</span>
            </div>
        </div>

        <!-- Search -->
        <div :class="['mt-4', animClass('fade-up', 2)]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by order #, teacher, or school..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
        </div>

        <!-- Filters -->
        <div :class="['mt-3 flex flex-wrap items-center gap-4', animClass('fade-up', 2)]">
            <!-- Method filter -->
            <div class="flex gap-1">
                <button @click="filterByMethod(null)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="!filters.method ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    All
                </button>
                <button @click="filterByMethod('Digital')"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.method === 'Digital' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    DG
                </button>
                <button @click="filterByMethod('Default')"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.method === 'Default' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    F2F
                </button>
            </div>

            <!-- Status filter -->
            <div class="flex gap-1">
                <button @click="filterByStatus(null)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="!filters.status ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    All Status
                </button>
                <button v-for="s in ['Submitted', 'Confirmed', 'Completed']" :key="s"
                    @click="filterByStatus(s)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filters.status === s ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    {{ s }}
                </button>
            </div>

            <!-- Time period filter -->
            <div class="flex flex-wrap gap-1">
                <button v-for="p in [
                    { label: 'All Time', value: null },
                    { label: 'This Quarter', value: 'this_quarter' },
                    { label: 'Last Quarter', value: 'last_quarter' },
                    { label: 'This Year', value: 'this_year' },
                    { label: 'Last 12 Months', value: 'last_12' },
                ]" :key="p.label"
                    @click="filterByPeriod(p.value)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="(filters.period ?? null) === p.value ? 'bg-brand-success text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="orders.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ orders.current_page }} of {{ orders.last_page }}</p>
                <!-- Mobile: just prev/next arrows -->
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="orders.current_page > 1" :href="orders.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="orders.current_page < orders.last_page" :href="orders.links[orders.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <!-- Desktop: full pagination -->
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in orders.links" :key="'top-' + link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[800px] w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('trinity_order_number')">
                                Order #{{ sortIcon('trinity_order_number') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('teacher')">
                                Teacher{{ sortIcon('teacher') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('school')">
                                School{{ sortIcon('school') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('delivery_method')">
                                Type{{ sortIcon('delivery_method') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('subject_area')">
                                Subject{{ sortIcon('subject_area') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('candidates')">
                                Cands{{ sortIcon('candidates') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-right font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('commission_amount')">
                                Commission{{ sortIcon('commission_amount') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-center font-semibold text-brand-text hover:text-brand-accent" @click="sortBy('order_status')">
                                Status{{ sortIcon('order_status') }}
                            </th>
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
                            <td class="px-4 py-3 text-base">
                                <Link v-if="order.school_id" :href="`/admin/schools/${order.school_id}`" class="text-brand-text-soft hover:text-brand-accent hover:underline">
                                    {{ order.school_name }}
                                </Link>
                                <span v-else class="text-brand-text-soft">{{ order.school_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="order.delivery_method === 'DG' ? 'bg-brand-accent/10 text-brand-accent' : 'bg-brand-primary/10 text-brand-primary'">
                                    <Monitor v-if="order.delivery_method === 'DG'" class="h-4 w-4" />
                                    <MapPin v-else class="h-4 w-4" />
                                    {{ order.delivery_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-base text-brand-text-soft">{{ order.subject_area }}</td>
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
                <!-- Mobile: just prev/next arrows -->
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="orders.current_page > 1" :href="orders.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="orders.current_page < orders.last_page" :href="orders.links[orders.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <!-- Desktop: full pagination -->
                <div class="hidden gap-1 sm:flex">
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
