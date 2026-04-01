<!-- resources/js/pages/admin/Teachers/Show.vue -->
<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import {
    ArrowLeft,
    Pencil,
    Trash2,
    Mail,
    Phone,
    UserCheck,
    School,
    Music,
    GraduationCap,
    ClipboardList,
    MessageSquare,
    Plus,
    X,
} from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Teacher {
    id: number
    name: string
    email: string
    phone: string | null
    notes: string | null
    how_they_found_us: string | null
    met_face_to_face: boolean
    spoken_on_phone: boolean
    contacted_by_email: boolean
    created_at: string
    schools: Array<{ id: number; name: string; city: string }>
    instruments: Array<{ id: number; name: string; family: string }>
    subject_areas: Array<{ id: number; name: string }>
    students: Array<{ id: number; full_name: string; instrument: string }>
    orders: Array<{
        id: number
        trinity_order_number: string
        delivery_method: string
        candidates: number
        commission_amount: string
        order_status: string
        school_name: string
        requested_start_date: string
    }>
    contact_logs: Array<{
        id: number
        contact_type: string
        direction: string
        subject: string
        summary: string
        contacted_at: string
    }>
}

const props = defineProps<{ teacher: Teacher }>()

// Contact log form
const showContactForm = ref(false)
const contactForm = useForm({
    contact_type: 'email',
    direction: 'outbound',
    subject: '',
    summary: '',
    contacted_at: new Date().toISOString().split('T')[0],
    notes: '',
})

function submitContactLog() {
    contactForm.post(`/admin/teachers/${props.teacher.id}/contact-logs`, {
        preserveScroll: true,
        onSuccess: () => {
            showContactForm.value = false
            contactForm.reset()
            contactForm.contacted_at = new Date().toISOString().split('T')[0]
        },
    })
}

function deleteContactLog(logId: number) {
    if (confirm('Remove this contact log entry?')) {
        router.delete(`/admin/teachers/${props.teacher.id}/contact-logs/${logId}`, {
            preserveScroll: true,
        })
    }
}

async function deleteTeacher() {
    try {
        const response = await fetch(`/admin/teachers/${props.teacher.id}/deletion-impact`)
        const impact = await response.json()

        const parts = []
        if (impact.students_count > 0) parts.push(`${impact.students_count} student${impact.students_count !== 1 ? 's' : ''}`)
        if (impact.orders_count > 0) parts.push(`${impact.orders_count} order${impact.orders_count !== 1 ? 's' : ''}`)
        if (impact.contact_logs_count > 0) parts.push(`${impact.contact_logs_count} contact log${impact.contact_logs_count !== 1 ? 's' : ''}`)

        let message = `Are you sure you want to archive ${props.teacher.name}?`
        if (parts.length) {
            message += `\n\nThis teacher has ${parts.join(', ')} linked to their account.`
        }
        message += `\n\nThey can be restored later from the archived list.`

        if (confirm(message)) {
            router.delete(`/admin/teachers/${props.teacher.id}`)
        }
    } catch {
        if (confirm(`Are you sure you want to archive ${props.teacher.name}? They can be restored later.`)) {
            router.delete(`/admin/teachers/${props.teacher.id}`)
        }
    }
}

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()

function goBack() { window.history.back() }

const orderColumns = [
    { key: 'trinity_order_number', title: 'Order #' },
    { key: 'school_name', title: 'School' },
    { key: 'delivery_method', title: 'Type' },
    { key: 'candidates', title: 'Candidates' },
    { key: 'commission_amount', title: 'Commission' },
    { key: 'order_status', title: 'Status' },
    { key: 'requested_start_date', title: 'Date' },
]

const contactColumns = [
    { key: 'contacted_at', title: 'Date' },
    { key: 'contact_type', title: 'Type' },
    { key: 'direction', title: 'Direction' },
    { key: 'subject', title: 'Subject' },
    { key: 'summary', title: 'Summary' },
]

const studentColumns = [
    { key: 'full_name', title: 'Name' },
    { key: 'instrument', title: 'Instrument' },
]
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div :class="['mb-6 flex items-center justify-between', animClass('fade-up', 0)]">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                    <ArrowLeft class="h-5 w-5" />
                </button>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Teacher Profile</p>
                    <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ teacher.name }}</h1>
                </div>
            </div>
            <div class="flex gap-2">
                <Link :href="`/admin/teachers/${teacher.id}/edit`">
                    <MyButtonConstructor variant="outline" size="medium" :icon="Pencil">
                        Edit
                    </MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="outline" size="medium" :icon="Trash2" @click="deleteTeacher">
                    Archive
                </MyButtonConstructor>
            </div>
        </div>

        <!-- Info Cards Row: Contact + Schools side by side -->
        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-2', animClass('fade-up', 1)]">
            <!-- Contact & Status Card -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Contact Details</template>
                </MyTextConstructor>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <Mail class="h-6 w-6 text-brand-text-soft" />
                        <a :href="`mailto:${teacher.email}`" class="text-lg text-brand-accent hover:underline">{{ teacher.email }}</a>
                    </div>
                    <div v-if="teacher.phone" class="flex items-center gap-3">
                        <Phone class="h-6 w-6 text-brand-text-soft" />
                        <a :href="`tel:${teacher.phone}`" class="text-lg text-brand-text">{{ teacher.phone }}</a>
                    </div>
                    <div class="flex items-center gap-3">
                        <UserCheck class="h-6 w-6 text-brand-text-soft" />
                        <span class="text-lg text-brand-text-soft">Found us via: {{ teacher.how_they_found_us ?? '—' }}</span>
                    </div>
                </div>

                <div class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-2 text-base font-semibold uppercase tracking-wider text-brand-text-soft">Contact Status</p>
                    <div class="flex gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                            :class="teacher.contacted_by_email ? 'bg-brand-teal-soft text-brand-teal' : 'bg-brand-surface-soft text-brand-text-soft'">
                            <Mail class="h-4 w-4" /> Email
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                            :class="teacher.spoken_on_phone ? 'bg-brand-teal-soft text-brand-teal' : 'bg-brand-surface-soft text-brand-text-soft'">
                            <Phone class="h-4 w-4" /> Phone
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                            :class="teacher.met_face_to_face ? 'bg-brand-teal-soft text-brand-teal' : 'bg-brand-surface-soft text-brand-text-soft'">
                            <UserCheck class="h-4 w-4" /> F2F
                        </span>
                    </div>
                </div>

                <div v-if="teacher.notes" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-1 text-base font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                    <p class="text-lg text-brand-text">{{ teacher.notes }}</p>
                </div>

                <p class="mt-4 text-base text-brand-text-soft">Added {{ teacher.created_at }}</p>
            </div>

            <!-- Schools & Instruments Card -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="mb-4">
                    <div class="flex items-center gap-2">
                        <School class="h-6 w-6 text-brand-text-soft" />
                        <MyTextConstructor variant="button-lg">
                            <template #myTitle>Schools</template>
                        </MyTextConstructor>
                    </div>
                    <div class="mt-2 space-y-1">
                        <p v-for="school in teacher.schools" :key="school.id" class="text-lg text-brand-text">
                            {{ school.name }} <span class="text-brand-text-soft">— {{ school.city }}</span>
                        </p>
                        <p v-if="!teacher.schools.length" class="text-lg text-brand-text-soft">No schools assigned</p>
                    </div>
                </div>

                <div class="mb-4 border-t border-brand-border pt-4">
                    <div class="flex items-center gap-2">
                        <Music class="h-6 w-6 text-brand-text-soft" />
                        <MyTextConstructor variant="button-lg">
                            <template #myTitle>Instruments</template>
                        </MyTextConstructor>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span
                            v-for="instrument in teacher.instruments"
                            :key="instrument.id"
                            class="rounded-full bg-brand-surface-soft px-3 py-1.5 text-sm font-medium text-brand-text"
                        >
                            {{ instrument.name }}
                        </span>
                        <p v-if="!teacher.instruments.length" class="text-lg text-brand-text-soft">No instruments assigned</p>
                    </div>
                </div>

                <div class="border-t border-brand-border pt-4">
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Subject Areas</template>
                    </MyTextConstructor>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span
                            v-for="area in teacher.subject_areas"
                            :key="area.id"
                            class="rounded-full bg-brand-accent/10 px-3 py-1.5 text-sm font-medium text-brand-accent"
                        >
                            {{ area.name }}
                        </span>
                        <p v-if="!teacher.subject_areas.length" class="text-lg text-brand-text-soft">None assigned</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Card (full width, stacked below) -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <GraduationCap class="h-6 w-6 text-brand-text-soft" />
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Students ({{ teacher.students.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="teacher.students.length"
                    :data="teacher.students"
                    :columns="studentColumns"
                    row-key="id"
                    size="medium"
                    :sortable="false"
                    :bordered="false"
                    :striped="true"
                    :full-width="true"
                    :bare="true"
                />
                <p v-else class="py-4 text-center text-lg text-brand-text-soft">No students registered</p>
            </div>
        </div>

        <!-- Orders Table -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <ClipboardList class="h-5 w-5 text-brand-text-soft" />
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Orders ({{ teacher.orders.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="teacher.orders.length"
                    :data="teacher.orders"
                    :columns="orderColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                />
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No orders yet</p>
            </div>
        </div>

        <!-- Contact Logs Table -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 4)]">
            <div class="flex items-center justify-between border-b border-brand-border p-4">
                <div class="flex items-center gap-2">
                    <MessageSquare class="h-5 w-5 text-brand-text-soft" />
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Contact History ({{ teacher.contact_logs.length }})</template>
                    </MyTextConstructor>
                </div>
                <MyButtonConstructor
                    :variant="showContactForm ? 'ghost' : 'primary'"
                    size="small"
                    :icon="showContactForm ? X : Plus"
                    @click="showContactForm = !showContactForm"
                >
                    {{ showContactForm ? 'Cancel' : 'Add' }}
                </MyButtonConstructor>
            </div>

            <!-- Add Contact Log Form -->
            <div v-if="showContactForm" class="border-b border-brand-border bg-brand-surface-soft p-4">
                <form @submit.prevent="submitContactLog" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-brand-text">Type</label>
                            <select v-model="contactForm.contact_type" class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                                <option value="email">Email</option>
                                <option value="phone">Phone</option>
                                <option value="face_to_face">Face to Face</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-brand-text">Direction</label>
                            <select v-model="contactForm.direction" class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                                <option value="outbound">Outbound (I contacted them)</option>
                                <option value="inbound">Inbound (They contacted me)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-brand-text">Date</label>
                            <input v-model="contactForm.contacted_at" type="date" class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-brand-text">Subject</label>
                        <input v-model="contactForm.subject" type="text" placeholder="e.g. Follow-up on exam entries" class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-brand-text">Summary</label>
                        <textarea v-model="contactForm.summary" rows="3" placeholder="Brief notes on what was discussed..." class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <MyButtonConstructor variant="ghost" size="small" @click="showContactForm = false">
                            Cancel
                        </MyButtonConstructor>
                        <MyButtonConstructor variant="primary" size="small" type="submit" :disabled="contactForm.processing">
                            Save Contact Log
                        </MyButtonConstructor>
                    </div>
                    <p v-if="contactForm.errors.contact_type" class="text-sm text-brand-danger">{{ contactForm.errors.contact_type }}</p>
                    <p v-if="contactForm.errors.contacted_at" class="text-sm text-brand-danger">{{ contactForm.errors.contacted_at }}</p>
                </form>
            </div>

            <div class="p-4">
                <MyTableConstructor
                    v-if="teacher.contact_logs.length"
                    :data="teacher.contact_logs"
                    :columns="contactColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                />
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No contact history yet — add your first entry above</p>
            </div>
        </div>
    </div>
</template>
