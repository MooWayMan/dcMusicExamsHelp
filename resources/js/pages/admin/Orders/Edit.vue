<!-- resources/js/pages/admin/Orders/Edit.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Save, X, Plus, Trash2 } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface Teacher { id: number; name: string; email?: string }
interface School { id: number; name: string }
interface Instrument { id: number; name: string }
interface DeliveryMethod { value: string; label: string; default_rate: number }

interface Entry {
    id: number | null
    candidate_name: string
    candidate_number: string | null
    instrument_id: number | null
    grade: string | null
    exam_date: string | null
    score: number | null
    result: string | null
    fee: number | null
    notes: string | null
}

interface OrderPayload {
    id: number
    trinity_order_number: string
    delivery_method: string
    subject_area: string | null
    order_status: string
    requested_start_date: string | null
    created_by_contact_id: number | null
    school_id: number | null
    venue: string | null
    commission_rate: number | string
    commission_amount: number | string | null
    applicant_name: string | null
    applicant_email: string | null
    notes: string | null
    entries: Entry[]
}

const props = defineProps<{
    order: OrderPayload
    teachers: Teacher[]
    schools: School[]
    instruments: Instrument[]
    options: {
        delivery_methods: DeliveryMethod[]
        subject_areas: string[]
        order_statuses: string[]
        grades: string[]
        results: string[]
    }
}>()

const { animClass } = usePageAnimation()

function emptyEntry() {
    return {
        id: null,
        candidate_name: '',
        candidate_number: '',
        instrument_id: null,
        grade: '',
        exam_date: '',
        score: null,
        result: '',
        fee: null,
        notes: '',
    }
}

const form = useForm({
    trinity_order_number: props.order.trinity_order_number,
    delivery_method: props.order.delivery_method,
    subject_area: props.order.subject_area ?? 'Music',
    order_status: props.order.order_status,
    requested_start_date: props.order.requested_start_date ?? '',
    created_by_contact_id: props.order.created_by_contact_id,
    school_id: props.order.school_id,
    venue: props.order.venue ?? '',
    commission_rate: Number(props.order.commission_rate),
    commission_amount: props.order.commission_amount !== null ? Number(props.order.commission_amount) : null,
    applicant_name: props.order.applicant_name ?? '',
    applicant_email: props.order.applicant_email ?? '',
    notes: props.order.notes ?? '',
    // Map existing candidates as-is. An order awaiting Trinity's candidate
    // import has none — start empty rather than injecting a blank required
    // row (which would block header/applicant-only edits from saving).
    entries: props.order.entries.map(e => ({
        ...e,
        candidate_number: e.candidate_number ?? '',
        grade: e.grade ?? '',
        exam_date: e.exam_date ?? '',
        result: e.result ?? '',
        notes: e.notes ?? '',
    })),
})

const isFaceToFace = computed(() => form.delivery_method === 'Default')

function onDeliveryMethodChange() {
    const match = props.options.delivery_methods.find(m => m.value === form.delivery_method)
    if (match) form.commission_rate = match.default_rate
}

function addEntry() {
    form.entries.push(emptyEntry())
}

function removeEntry(index: number) {
    if (form.entries.length === 1) return
    form.entries.splice(index, 1)
}

function submit() {
    form.put(`/admin/orders/${props.order.id}`)
}

function goBack() { window.history.back() }

function inputClass() {
    return 'w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent'
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-lg px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Edit Order {{ order.trinity_order_number }}</h1>
                <p class="mt-1 text-sm text-brand-text-soft">Update order details or fill in results as they come back from Trinity.</p>
            </div>
        </div>

        <form @submit.prevent="submit" :class="['space-y-6', animClass('fade-up', 1)]">
            <!-- Section 1: Order header -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">Order Details</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Trinity Order Number *</label>
                        <input v-model="form.trinity_order_number" type="text" required :class="inputClass()" />
                        <p v-if="form.errors.trinity_order_number" class="mt-1 text-sm text-brand-danger">{{ form.errors.trinity_order_number }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Booking Date *</label>
                        <input v-model="form.requested_start_date" type="date" required :class="inputClass()" />
                        <p v-if="form.errors.requested_start_date" class="mt-1 text-sm text-brand-danger">{{ form.errors.requested_start_date }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Delivery Method *</label>
                        <select v-model="form.delivery_method" @change="onDeliveryMethodChange" required :class="inputClass()">
                            <option v-for="m in options.delivery_methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Subject Area</label>
                        <select v-model="form.subject_area" :class="inputClass()">
                            <option v-for="s in options.subject_areas" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Order Status *</label>
                        <select v-model="form.order_status" required :class="inputClass()">
                            <option v-for="s in options.order_statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div v-if="isFaceToFace">
                        <label class="mb-1 block text-lg font-medium text-brand-text">Venue</label>
                        <input v-model="form.venue" type="text" :class="inputClass()" />
                    </div>
                </div>
            </div>

            <!-- Section 2: Applicant -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">Applicant / Submitter</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Full Name</label>
                        <input v-model="form.applicant_name" type="text" :class="inputClass()" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Email</label>
                        <input v-model="form.applicant_email" type="email" :class="inputClass()" />
                    </div>
                </div>
            </div>

            <!-- Section 3: Teacher & commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">Teacher &amp; Commission</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Teacher</label>
                        <select v-model="form.created_by_contact_id" :class="inputClass()">
                            <option :value="null">— No teacher (applicant-only) —</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.created_by_contact_id" class="mt-1 text-sm text-brand-danger">{{ form.errors.created_by_contact_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">School</label>
                        <select v-model="form.school_id" :class="inputClass()">
                            <option :value="null">— None —</option>
                            <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Commission Rate (%) *</label>
                        <input v-model.number="form.commission_rate" type="number" step="0.1" min="0" max="100" required :class="inputClass()" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Commission Amount (£)</label>
                        <input v-model.number="form.commission_amount" type="number" step="0.01" min="0" :class="inputClass()" />
                    </div>
                </div>
            </div>

            <!-- Section 4: Candidates -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-brand-text">Candidates ({{ form.entries.length }})</h3>
                    <MyButtonConstructor type="button" variant="ghost" size="small" :icon="Plus" @click="addEntry">Add candidate</MyButtonConstructor>
                </div>

                <p v-if="form.entries.length === 0" class="mb-4 rounded-lg border border-dashed border-brand-border bg-brand-surface-soft p-4 text-sm text-brand-text-soft">
                    No candidates on this order yet — awaiting Trinity's candidate data. You can save the order details as they are, or use "Add candidate" to enter them manually.
                </p>

                <div v-for="(entry, i) in form.entries" :key="entry.id ?? `new-${i}`" class="mb-4 rounded-lg border border-brand-border bg-brand-surface-soft p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">
                            Candidate {{ i + 1 }}
                            <span v-if="entry.id" class="ml-2 rounded bg-brand-accent/10 px-2 py-0.5 text-xs text-brand-accent">Existing</span>
                            <span v-else class="ml-2 rounded bg-brand-success/10 px-2 py-0.5 text-xs text-brand-success">New</span>
                        </p>
                        <button v-if="form.entries.length > 1 && !entry.id" type="button" @click="removeEntry(i)" class="cursor-pointer rounded p-1 text-brand-text-soft hover:bg-brand-danger/10 hover:text-brand-danger" title="Remove new entry">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Candidate Name *</label>
                            <input v-model="entry.candidate_name" type="text" required :class="inputClass()" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Candidate Number</label>
                            <input v-model="entry.candidate_number" type="text" :class="inputClass()" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Instrument</label>
                            <select v-model="entry.instrument_id" :class="inputClass()">
                                <option :value="null">— Select —</option>
                                <option v-for="inst in instruments" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Grade</label>
                            <select v-model="entry.grade" :class="inputClass()">
                                <option value="">— Select —</option>
                                <option v-for="g in options.grades" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Exam Date</label>
                            <input v-model="entry.exam_date" type="date" :class="inputClass()" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Fee (£)</label>
                            <input v-model.number="entry.fee" type="number" step="0.01" min="0" :class="inputClass()" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Score</label>
                            <input v-model.number="entry.score" type="number" min="0" max="100" :class="inputClass()" placeholder="Leave blank if pending" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Result</label>
                            <select v-model="entry.result" :class="inputClass()">
                                <option value="">— Pending —</option>
                                <option v-for="r in options.results" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">Notes</h3>
                <textarea v-model="form.notes" rows="3" :class="inputClass()"></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link :href="`/admin/orders/${order.id}`">
                    <MyButtonConstructor variant="ghost" size="large" :icon="X">Cancel</MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="primary" size="large" :icon="Save" type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </MyButtonConstructor>
            </div>
        </form>
    </div>
</template>
