<!-- resources/js/pages/admin/PendingResults/Index.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, AlertCircle, CheckCircle, Clock } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'

interface PendingEntry {
    id: number
    candidate_number: string
    candidate_name: string
    instrument: string
    grade: string
    delivery_method: string
    subject_area: string
    teacher_name: string
    school_name: string
    fee: string
    order_number: string
    order_date: string
}

const props = defineProps<{
    entries: PendingEntry[]
    summary: { pending: number; with_results: number; total: number }
    filters: { search: string | null; method: string | null }
}>()

const search = ref(props.filters.search ?? '')
const method = ref(props.filters.method ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/pending-results', {
            search: value || undefined,
            method: method.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

function filterByMethod(val: string) {
    method.value = val
    router.get('/admin/pending-results', {
        search: search.value || undefined,
        method: val || undefined,
    }, { preserveState: true, replace: true })
}

const columns = [
    { key: 'candidate_name', title: 'Candidate', sortable: true },
    { key: 'candidate_number', title: 'Candidate #', sortable: true },
    { key: 'instrument', title: 'Instrument', sortable: true },
    { key: 'grade', title: 'Grade', sortable: true },
    { key: 'delivery_method', title: 'Method', sortable: true },
    { key: 'teacher_name', title: 'Teacher', sortable: true },
    { key: 'order_date', title: 'Order Date', sortable: true },
]
</script>

<template>
    <div>
        <PageHeader
            title="Pending Results"
            subtitle="Candidates awaiting exam scores — check these in MOB each week"
            eyebrow="Weekly Checklist"
        />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Summary cards -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-3">
                        <Clock class="h-8 w-8 text-amber-600" />
                        <div>
                            <p class="text-2xl font-bold text-amber-700">{{ summary.pending }}</p>
                            <p class="text-sm text-amber-600">Awaiting results</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center gap-3">
                        <CheckCircle class="h-8 w-8 text-green-600" />
                        <div>
                            <p class="text-2xl font-bold text-green-700">{{ summary.with_results }}</p>
                            <p class="text-sm text-green-600">Results received</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <div class="flex items-center gap-3">
                        <AlertCircle class="h-8 w-8 text-brand-accent" />
                        <div>
                            <p class="text-2xl font-bold text-brand-text">{{ summary.total }}</p>
                            <p class="text-sm text-brand-text-soft">Total entries</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and filters -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name, candidate number, teacher..."
                        class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-sm text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                    />
                </div>
                <div class="flex gap-2">
                    <button
                        @click="filterByMethod('')"
                        :class="method === '' ? 'bg-brand-accent text-white' : 'bg-brand-surface text-brand-text-soft border border-brand-border'"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition"
                    >
                        All
                    </button>
                    <button
                        @click="filterByMethod('Digital')"
                        :class="method === 'Digital' ? 'bg-brand-accent text-white' : 'bg-brand-surface text-brand-text-soft border border-brand-border'"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition"
                    >
                        Digital
                    </button>
                    <button
                        @click="filterByMethod('Default')"
                        :class="method === 'Default' ? 'bg-brand-accent text-white' : 'bg-brand-surface text-brand-text-soft border border-brand-border'"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition"
                    >
                        F2F
                    </button>
                </div>
            </div>

            <!-- Table -->
            <MyTableConstructor
                v-if="entries.length"
                :data="entries"
                :columns="columns"
                rowKey="id"
                :sortable="true"
                :striped="true"
                :bordered="true"
                size="medium"
                title="Pending Results"
                subtitle="Copy candidate numbers to search in MOB Candidates & Contacts"
            />

            <!-- Empty state -->
            <div v-else class="rounded-xl border border-green-200 bg-green-50 p-12 text-center">
                <CheckCircle class="mx-auto h-12 w-12 text-green-500" />
                <p class="mt-4 text-lg font-semibold text-green-700">All results collected!</p>
                <p class="mt-1 text-sm text-green-600">No pending candidates — everything is up to date.</p>
            </div>
        </div>
    </div>
</template>
