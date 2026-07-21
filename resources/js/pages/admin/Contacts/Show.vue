<!-- resources/js/pages/admin/Contacts/Show.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { ArrowLeft, Mail, Phone, User, Music, ShoppingCart, Tag, Pencil, ChevronDown, ChevronUp, AlertTriangle, GitMerge, Search, Eye } from 'lucide-vue-next'
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
    relationship?: 'teacher' | 'submitted'
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

interface DuplicateContact {
    id: number
    name: string
    email: string | null
    types: string[]
    score: number
}

interface MergeCandidate {
    id: number
    name: string
    email: string | null
}

const props = defineProps<{
    contact: Contact
    possibleDuplicates: DuplicateContact[]
    mergeCandidates: MergeCandidate[]
}>()

// Merge / dismiss. Merging keeps THIS record and folds the other in, so to
// keep a particular email as primary you open the contact that has it.
const confirmingMergeId = ref<number | null>(null)
const showManualMerge = ref(false)
const manualSearch = ref('')

const filteredCandidates = computed(() => {
    const q = manualSearch.value.trim().toLowerCase()
    const list = q
        ? props.mergeCandidates.filter(c =>
            c.name.toLowerCase().includes(q) || (c.email ?? '').toLowerCase().includes(q))
        : props.mergeCandidates
    return list.slice(0, 25)
})

function mergeIn(dropId: number) {
    router.post(`/admin/contacts/${props.contact.id}/merge`, { drop_id: dropId }, {
        preserveScroll: true,
        onFinish: () => { confirmingMergeId.value = null },
    })
}

function dismiss(otherId: number) {
    router.post(`/admin/contacts/${props.contact.id}/dismiss-duplicate`, { other_id: otherId }, {
        preserveScroll: true,
    })
}

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
    { key: 'candidate_number', title: 'Candidate #', sortable: true },
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
            <div class="ml-auto flex items-center gap-2">
                <a v-if="contact.types?.includes('teacher') || contact.exam_entries_count > 0" :href="`/admin/contacts/${contact.id}/preview-dashboard`" target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-brand-border px-3 py-2 text-sm font-semibold text-brand-text transition-colors hover:bg-brand-surface-soft">
                    <Eye class="h-4 w-4" />
                    Preview dashboard
                </a>
                <Link :href="`/admin/contacts/${contact.id}/edit`"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-accent px-3 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90">
                    <Pencil class="h-4 w-4" />
                    Edit
                </Link>
            </div>
        </div>

        <!-- Possible duplicates — same person under a different email. Merging
             folds the other record into THIS one and keeps this email primary. -->
        <div v-if="possibleDuplicates.length" class="mb-6 rounded-xl border border-brand-border bg-brand-purple-soft p-4">
            <div class="flex items-center gap-2">
                <AlertTriangle class="h-5 w-5 text-brand-purple" />
                <h2 class="text-base font-semibold text-brand-purple">
                    Possible duplicate{{ possibleDuplicates.length > 1 ? 's' : '' }}
                </h2>
            </div>
            <p class="mt-1 text-sm text-brand-text-soft">
                These look like the same person as {{ contact.name }}. Merging combines every entry, order, email and role onto this record.
            </p>
            <ul class="mt-3 space-y-2">
                <li v-for="dup in possibleDuplicates" :key="dup.id" class="rounded-lg border border-brand-border bg-brand-surface p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <Link :href="`/admin/contacts/${dup.id}`" class="font-medium text-brand-accent hover:underline">{{ dup.name }}</Link>
                            <span class="ml-2 text-sm text-brand-text-soft">{{ dup.email ?? 'no email' }}</span>
                            <span class="ml-2 text-xs text-brand-text-soft">{{ dup.score }}% match</span>
                        </div>
                        <div v-if="confirmingMergeId === dup.id" class="flex items-center gap-2">
                            <span class="text-sm text-brand-text">Merge into {{ contact.name }}?</span>
                            <button @click="mergeIn(dup.id)"
                                class="cursor-pointer rounded-lg bg-brand-danger px-3 py-1.5 text-sm font-semibold text-brand-text-inverse hover:opacity-90">
                                Confirm merge
                            </button>
                            <button @click="confirmingMergeId = null"
                                class="cursor-pointer rounded-lg border border-brand-border px-3 py-1.5 text-sm text-brand-text-soft hover:text-brand-text">
                                Cancel
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <button @click="confirmingMergeId = dup.id"
                                class="cursor-pointer rounded-lg bg-brand-accent px-3 py-1.5 text-sm font-semibold text-brand-text-inverse hover:opacity-90">
                                Merge in
                            </button>
                            <button @click="dismiss(dup.id)"
                                class="cursor-pointer rounded-lg border border-brand-border px-3 py-1.5 text-sm text-brand-text-soft hover:text-brand-text">
                                Not the same
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Manual merge — for aliases the fuzzy match won't catch (e.g. a
             different surname). Always available. -->
        <div :class="['mb-6', animClass('fade-up', 0)]">
            <button @click="showManualMerge = !showManualMerge"
                class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-brand-text-soft hover:text-brand-accent">
                <GitMerge class="h-4 w-4" />
                Merge another contact into this one
                <ChevronDown v-if="!showManualMerge" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
            <div v-if="showManualMerge" class="mt-3 rounded-xl border border-brand-border bg-brand-surface p-4">
                <p class="mb-3 text-sm text-brand-text-soft">
                    Pick the record to fold into {{ contact.name }}. That record is retired and everything moves here.
                </p>
                <div class="relative mb-3">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                    <input v-model="manualSearch" type="text" placeholder="Search contacts by name or email…"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-sm text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                </div>
                <ul class="max-h-72 space-y-1 overflow-y-auto">
                    <li v-for="cand in filteredCandidates" :key="cand.id"
                        class="flex items-center justify-between gap-2 rounded-lg border border-brand-border px-3 py-2">
                        <div class="min-w-0">
                            <span class="font-medium text-brand-text">{{ cand.name }}</span>
                            <span class="ml-2 text-sm text-brand-text-soft">{{ cand.email ?? 'no email' }}</span>
                        </div>
                        <div v-if="confirmingMergeId === cand.id" class="flex items-center gap-2">
                            <button @click="mergeIn(cand.id)"
                                class="cursor-pointer rounded-lg bg-brand-danger px-3 py-1.5 text-sm font-semibold text-brand-text-inverse hover:opacity-90">
                                Confirm
                            </button>
                            <button @click="confirmingMergeId = null"
                                class="cursor-pointer rounded-lg border border-brand-border px-3 py-1.5 text-sm text-brand-text-soft hover:text-brand-text">
                                Cancel
                            </button>
                        </div>
                        <button v-else @click="confirmingMergeId = cand.id"
                            class="cursor-pointer rounded-lg border border-brand-border px-3 py-1.5 text-sm font-medium text-brand-accent hover:bg-brand-surface-soft">
                            Merge in
                        </button>
                    </li>
                    <li v-if="!filteredCandidates.length" class="px-3 py-2 text-sm text-brand-text-soft">No contacts match.</li>
                </ul>
            </div>
        </div>

        <!-- Info cards -->
        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-2', animClass('fade-up', 1)]">
            <!-- Contact Details -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <User class="h-5 w-5 text-brand-text-soft" />
                    <h2 class="text-xl font-semibold text-brand-text">Details</h2>
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
                <h2 class="text-xl font-semibold text-brand-text">Activity</h2>
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
                <h2 class="text-xl font-semibold text-brand-text">Exam Entries ({{ contact.exam_entries.length }})</h2>
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
                    <template #cell-exam_date="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value ?? '—' }}</span>
                    </template>
                    <template #cell-order_number="{ row }">
                        <Link :href="`/admin/orders/${row.order_id}`" class="text-brand-accent hover:underline" @click.stop>
                            {{ row.order_number }}
                        </Link>
                    </template>
                    <template #cell-candidate_name="{ value, row }">
                        <span class="text-base text-brand-text">{{ value ?? '—' }}</span>
                        <span v-if="row.relationship === 'submitted'"
                            class="ml-2 rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs text-brand-text-soft">
                            submitted
                        </span>
                    </template>
                    <template #cell-candidate_number="{ value }">
                        <span class="select-all text-sm text-brand-text-soft" @click.stop>{{ value ?? '—' }}</span>
                    </template>
                    <template #cell-grade="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value ?? '—' }}</span>
                    </template>
                    <template #cell-subject_area="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value ?? '—' }}</span>
                    </template>
                    <template #cell-delivery_method="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value ?? '—' }}</span>
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
                        <span v-else class="text-brand-text-soft">—</span>
                    </template>
                    <template #cell-score="{ value }">
                        <span v-if="value !== null && value !== undefined" class="text-sm font-medium text-brand-text">{{ value }}</span>
                        <span v-else class="text-sm text-brand-text-soft">—</span>
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
                <h2 class="text-xl font-semibold text-brand-text">Orders ({{ contact.orders.length }})</h2>
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
                    <template #cell-delivery_method="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                    <template #cell-subject_area="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value ?? '—' }}</span>
                    </template>
                    <template #cell-candidates="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                    <template #cell-order_status="{ value }">
                        <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                                'bg-brand-success-soft text-brand-success': value === 'Completed',
                                'bg-brand-accent/10 text-brand-accent': value === 'Confirmed',
                                'bg-brand-surface-soft text-brand-text-soft': value === 'Submitted',
                                'bg-brand-danger-soft text-brand-danger': value === 'Cancelled',
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
