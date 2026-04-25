<!-- resources/js/pages/admin/Contacts/Show.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { ArrowLeft, Mail, Phone, User, Music, ShoppingCart, Tag, Pencil, ChevronDown, ChevronUp } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

const INITIAL_ROWS = 10

interface ContactEmail {
    id: number
    email: string
    label: string | null
    is_primary: boolean
}

interface ExamEntry {
    id: number
    order_id: number
    order_number: string
    candidate_name: string | null
    candidate_number: string | null
    grade: string | null
    subject_area: string | null
    delivery_method: string | null
    result: string | null
    score: number | null
    exam_date: string | null
    fee: string | null
}

interface StudentLink {
    id: number
    name: string
}

interface OrderLink {
    id: number
    trinity_order_number: string
    delivery_method: string
    subject_area: string
    candidates: number
    order_status: string
    requested_start_date: string | null
    roles_in_order: string[]
}

interface Contact {
    id: number
    name: string
    email: string | null
    phone: string | null
    types: string[]
    source: string | null
    notes: string | null
    primary_email: string | null
    created_at: string
    emails: ContactEmail[]
    exam_entries_count: number
    students_count: number
    orders_count: number
    exam_entries: ExamEntry[]
    students: StudentLink[]
    orders: OrderLink[]
}

const props = defineProps<{ contact: Contact }>()

function goBack() { window.history.back() }

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

const entryColumns = [
    { key: 'exam_date', title: 'Date', sortable: true },
    { key: 'order_number', title: 'Order', sortable: true },
    { key: 'candidate_name', title: 'Candidate', sortable: true },
    { key: 'grade', title: 'Grade', sortable: true },
    { key: 'subject_area', title: 'Subject', sortable: true },
    { key: 'delivery_method', title: 'Type', sortable: true },
    { key: 'result', title: 'Result', sortable: true },
    { key: 'score', title: 'Score', sortable: true, align: 'center' as const },
]

const orderColumns = [
    { key: 'trinity_order_number', title: 'Order #', sortable: true },
    { key: 'roles_in_order', title: 'Role', sortable: false },
    { key: 'delivery_method', title: 'Type', sortable: true },
    { key: 'subject_area', title: 'Subject', sortable: true },
    { key: 'candidates', title: 'Cands', sortable: true, align: 'center' as const },
    { key: 'order_status', title: 'Status', sortable: true },
]

// Show-more toggles for long lists
const showAllEntries = ref(false)
const showAllOrders = ref(false)

const visibleEntries = computed(() =>
    showAllEntries.value
        ? props.contact.exam_entries
        : props.contact.exam_entries.slice(0, INITIAL_ROWS)
)

const visibleOrders = computed(() =>
    showAllOrders.value
        ? props.contact.orders
        : props.contact.orders.slice(0, INITIAL_ROWS)
)
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header with back button -->
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Contact</p>
                <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ contact.name }}</h1>
            </div>
            <div class="ml-2 flex flex-wrap gap-1">
                <span v-for="t in contact.types" :key="t"
                    class="rounded-full px-3 py-1 text-sm font-medium"
                    :class="typeBadgeClass(t)">
                    {{ typeLabel(t) }}
                </span>
                <span v-if="!contact.types || contact.types.length === 0"
                    class="rounded-full bg-brand-surface-soft px-3 py-1 text-sm text-brand-text-soft">
                    unknown
                </span>
            </div>
            <Link :href="`/admin/contacts/${contact.id}/edit`"
                class="ml-auto inline-flex items-center gap-2 rounded-lg bg-brand-accent px-3 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90">
                <Pencil class="h-4 w-4" />
                Edit
            </Link>
        </div>

        <!-- Info cards -->
        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-2', animClass('fade-up', 1)]">
            <!-- Contact Details -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <User class="h-5 w-5 text-brand-text-soft" />
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Details</template>
                    </MyTextConstructor>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-2">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-brand-text-soft" />
                        <div>
                            <p class="text-base font-medium text-brand-text">{{ contact.primary_email ?? '—' }}</p>
                            <template v-if="contact.emails.length > 1">
                                <p v-for="emailRecord in contact.emails.filter(e => e.email !== contact.primary_email)" :key="emailRecord.id"
                                    class="text-sm text-brand-text-soft">
                                    {{ emailRecord.email }}
                                    <span v-if="emailRecord.label" class="text-xs text-brand-text-soft">({{ emailRecord.label }})</span>
                                </p>
                            </template>
                        </div>
                    </div>
                    <div v-if="contact.phone" class="flex items-center gap-2">
                        <Phone class="h-4 w-4 shrink-0 text-brand-text-soft" />
                        <span class="text-base text-brand-text">{{ contact.phone }}</span>
                    </div>
                    <div v-if="contact.notes" class="border-t border-brand-border pt-3">
                        <p class="mb-1 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                        <p class="text-base text-brand-text">{{ contact.notes }}</p>
                    </div>
                    <p class="text-sm text-brand-text-soft">Added {{ contact.created_at }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Activity</template>
                </MyTextConstructor>
                <div class="mt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Exam Entries</span>
                        <span class="text-base font-medium text-brand-text">{{ contact.exam_entries.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Students</span>
                        <span class="text-base font-medium text-brand-text">{{ contact.students.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Orders</span>
                        <span class="text-base font-medium text-brand-text">{{ contact.orders.length }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Exam Entries table -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <Music class="h-5 w-5 text-brand-text-soft" />
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Exam Entries ({{ contact.exam_entries.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="contact.exam_entries.length"
                    :data="visibleEntries"
                    :columns="entryColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                    :clickable-rows="true"
                    @row-click="(row: ExamEntry) => router.visit(`/admin/orders/${row.order_id}`)"
                >
                    <template #cell-order_number="{ row }">
                        <Link :href="`/admin/orders/${row.order_id}`" class="text-brand-accent hover:underline" @click.stop>
                            {{ row.order_number }}
                        </Link>
                    </template>
                    <template #cell-result="{ value }">
                        <span v-if="value" class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                                'bg-brand-success-soft text-brand-success': value === 'Distinction',
                                'bg-brand-accent/10 text-brand-accent': value === 'Merit',
                                'bg-brand-surface-soft text-brand-text-soft': value === 'Pass',
                            }">
                            {{ value }}
                        </span>
                        <span v-else>—</span>
                    </template>
                </MyTableConstructor>
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No exam entries recorded</p>

                <div v-if="contact.exam_entries.length > INITIAL_ROWS" class="mt-4 flex justify-center">
                    <button
                        @click="showAllEntries = !showAllEntries"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand-border bg-brand-surface-soft px-4 py-2 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-accent/10 hover:text-brand-accent"
                    >
                        <template v-if="showAllEntries">
                            Show less
                            <ChevronUp class="h-4 w-4" />
                        </template>
                        <template v-else>
                            Show all {{ contact.exam_entries.length }}
                            <ChevronDown class="h-4 w-4" />
                        </template>
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders table -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <ShoppingCart class="h-5 w-5 text-brand-text-soft" />
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Orders ({{ contact.orders.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="contact.orders.length"
                    :data="visibleOrders"
                    :columns="orderColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                    :clickable-rows="true"
                    @row-click="(row: OrderLink) => router.visit(`/admin/orders/${row.id}`)"
                >
                    <template #cell-trinity_order_number="{ row }">
                        <Link :href="`/admin/orders/${row.id}`" class="font-medium text-brand-accent hover:underline" @click.stop>
                            {{ row.trinity_order_number }}
                        </Link>
                    </template>
                    <template #cell-roles_in_order="{ row }">
                        <span v-if="!row.roles_in_order?.length" class="text-brand-text-soft">—</span>
                        <span v-else class="flex flex-wrap gap-1">
                            <span v-for="role in row.roles_in_order" :key="role"
                                class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm font-medium text-brand-text-soft">
                                {{ role }}
                            </span>
                        </span>
                    </template>
                    <template #cell-order_status="{ value }">
                        <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                                'bg-brand-success-soft text-brand-success': value === 'Completed',
                                'bg-brand-accent/10 text-brand-accent': value === 'Confirmed',
                                'bg-brand-surface-soft text-brand-text-soft': value === 'Submitted',
                            }">
                            {{ value }}
                        </span>
                    </template>
                </MyTableConstructor>
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No orders linked</p>

                <div v-if="contact.orders.length > INITIAL_ROWS" class="mt-4 flex justify-center">
                    <button
                        @click="showAllOrders = !showAllOrders"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand-border bg-brand-surface-soft px-4 py-2 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-accent/10 hover:text-brand-accent"
                    >
                        <template v-if="showAllOrders">
                            Show less
                            <ChevronUp class="h-4 w-4" />
                        </template>
                        <template v-else>
                            Show all {{ contact.orders.length }}
                            <ChevronDown class="h-4 w-4" />
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
