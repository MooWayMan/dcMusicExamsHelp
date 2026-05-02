<!-- resources/js/pages/admin/Users/Show.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import {
    ArrowLeft,
    Mail,
    Phone,
    User as UserIcon,
    Shield,
    BadgeCheck,
    BadgeX,
    Link2,
    Music,
    GraduationCap,
} from 'lucide-vue-next'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

interface ExamEntry {
    id: number
    candidate_name: string | null
    grade: string | null
    subject_area: string | null
    delivery_method: string | null
    result: string | null
    score: number | null
    exam_date: string | null
}

interface StudentLink {
    id: number
    name: string
}

interface LinkedContact {
    id: number
    name: string
    types: string[]
    students_count: number
    exam_entries_count: number
    students: StudentLink[]
    exam_entries: ExamEntry[]
}

interface UserDetail {
    id: number
    name: string
    email: string
    phone: string | null
    role: string
    notes: string | null
    how_they_found_us: string | null
    met_face_to_face: boolean
    spoken_on_phone: boolean
    contacted_by_email: boolean
    hubspot_contact_id: string | null
    email_verified_at: string | null
    two_factor_enabled: boolean
    created_at: string
    updated_at: string
}

defineProps<{
    user: UserDetail
    linkedContact: LinkedContact | null
}>()

function goBack() { window.history.back() }

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
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header with back button -->
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Registered User</p>
                <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ user.name }}</h1>
            </div>
            <div class="ml-2 flex flex-wrap gap-1">
                <span class="rounded-full px-3 py-1 text-sm font-medium" :class="roleBadgeClass(user.role)">
                    {{ roleLabel(user.role) }}
                </span>
            </div>
        </div>

        <!-- Info cards -->
        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-2', animClass('fade-up', 1)]">
            <!-- Account Details -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <UserIcon class="h-5 w-5 text-brand-text-soft" />
                    <h2 class="text-xl font-semibold text-brand-text">Account</h2>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-2">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-brand-text-soft" />
                        <p class="text-base font-medium text-brand-text">{{ user.email }}</p>
                    </div>
                    <div v-if="user.phone" class="flex items-center gap-2">
                        <Phone class="h-4 w-4 shrink-0 text-brand-text-soft" />
                        <span class="text-base text-brand-text">{{ user.phone }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <BadgeCheck v-if="user.email_verified_at" class="h-4 w-4 shrink-0 text-brand-success" />
                        <BadgeX v-else class="h-4 w-4 shrink-0 text-brand-text-soft" />
                        <span v-if="user.email_verified_at" class="text-base text-brand-text">
                            Email verified <span class="text-sm text-brand-text-soft">({{ user.email_verified_at }})</span>
                        </span>
                        <span v-else class="text-base text-brand-text-soft">Email not verified</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Shield class="h-4 w-4 shrink-0" :class="user.two_factor_enabled ? 'text-brand-success' : 'text-brand-text-soft'" />
                        <span class="text-base" :class="user.two_factor_enabled ? 'text-brand-text' : 'text-brand-text-soft'">
                            {{ user.two_factor_enabled ? 'Two-factor enabled' : 'Two-factor not set up' }}
                        </span>
                    </div>
                    <div v-if="user.notes" class="border-t border-brand-border pt-3">
                        <p class="mb-1 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                        <p class="text-base text-brand-text">{{ user.notes }}</p>
                    </div>
                    <p class="text-sm text-brand-text-soft">Joined {{ user.created_at }} · Updated {{ user.updated_at }}</p>
                </div>
            </div>

            <!-- CRM-ish profile fields -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h2 class="text-xl font-semibold text-brand-text">Profile</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">How they found us</span>
                        <span class="text-base font-medium text-brand-text">{{ user.how_they_found_us ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Met face-to-face</span>
                        <span class="text-base font-medium" :class="user.met_face_to_face ? 'text-brand-success' : 'text-brand-text-soft'">
                            {{ user.met_face_to_face ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Spoken on phone</span>
                        <span class="text-base font-medium" :class="user.spoken_on_phone ? 'text-brand-success' : 'text-brand-text-soft'">
                            {{ user.spoken_on_phone ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Contacted by email</span>
                        <span class="text-base font-medium" :class="user.contacted_by_email ? 'text-brand-success' : 'text-brand-text-soft'">
                            {{ user.contacted_by_email ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div v-if="user.hubspot_contact_id" class="flex justify-between border-t border-brand-border pt-3">
                        <span class="text-base text-brand-text-soft">HubSpot ID</span>
                        <span class="text-base font-mono text-brand-text">{{ user.hubspot_contact_id }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked exam_contacts row -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <Link2 class="h-5 w-5 text-brand-text-soft" />
                <h2 class="text-xl font-semibold text-brand-text">Linked Contact</h2>
            </div>
            <div class="p-5">
                <p v-if="!linkedContact" class="text-base text-brand-text-soft">
                    No <span class="font-medium text-brand-text">exam_contacts</span> row matches this email. On first login this user
                    would land on the &ldquo;we couldn&rsquo;t find any exams under this email&rdquo; fallback.
                </p>
                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <Link :href="`/admin/contacts/${linkedContact.id}`" class="text-lg font-semibold text-brand-accent hover:underline">
                                {{ linkedContact.name }}
                            </Link>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span v-for="t in linkedContact.types" :key="t"
                                    class="rounded-full px-2 py-0.5 text-sm font-medium"
                                    :class="typeBadgeClass(t)">
                                    {{ typeLabel(t) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-4 text-right">
                            <div>
                                <p class="text-sm text-brand-text-soft">Entries</p>
                                <p class="text-xl font-bold text-brand-text">{{ linkedContact.exam_entries_count }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-brand-text-soft">Students</p>
                                <p class="text-xl font-bold text-brand-text">{{ linkedContact.students_count }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Students (GDPR: first name + initial only — already enforced server-side) -->
                    <div v-if="linkedContact.students.length">
                        <div class="mb-2 flex items-center gap-2">
                            <GraduationCap class="h-4 w-4 text-brand-text-soft" />
                            <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Students</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="s in linkedContact.students" :key="s.id"
                                class="rounded-full bg-brand-surface-soft px-3 py-1 text-sm text-brand-text">
                                {{ s.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Recent exam entries preview -->
                    <div v-if="linkedContact.exam_entries.length">
                        <div class="mb-2 flex items-center gap-2">
                            <Music class="h-4 w-4 text-brand-text-soft" />
                            <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Recent Exam Entries</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-[600px] w-full text-left text-sm">
                                <thead class="border-b border-brand-border">
                                    <tr class="text-brand-text-soft">
                                        <th class="py-2 pr-4 font-semibold">Date</th>
                                        <th class="py-2 pr-4 font-semibold">Candidate</th>
                                        <th class="py-2 pr-4 font-semibold">Grade</th>
                                        <th class="py-2 pr-4 font-semibold">Subject</th>
                                        <th class="py-2 pr-4 font-semibold">Type</th>
                                        <th class="py-2 pr-4 font-semibold">Result</th>
                                        <th class="py-2 pr-4 text-center font-semibold">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-border">
                                    <tr v-for="e in linkedContact.exam_entries" :key="e.id">
                                        <td class="py-2 pr-4 text-brand-text-soft">{{ e.exam_date ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-brand-text">{{ e.candidate_name ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-brand-text-soft">{{ e.grade ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-brand-text-soft">{{ e.subject_area ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-brand-text-soft">{{ e.delivery_method ?? '—' }}</td>
                                        <td class="py-2 pr-4">
                                            <span v-if="e.result" class="rounded-full px-2 py-0.5 text-sm font-medium"
                                                :class="{
                                                    'bg-brand-success-soft text-brand-success': e.result === 'Distinction',
                                                    'bg-brand-accent/10 text-brand-accent': e.result === 'Merit',
                                                    'bg-brand-surface-soft text-brand-text-soft': e.result === 'Pass',
                                                }">
                                                {{ e.result }}
                                            </span>
                                            <span v-else class="text-brand-text-soft">—</span>
                                        </td>
                                        <td class="py-2 pr-4 text-center">
                                            <span v-if="e.score !== null && e.score !== undefined" class="font-medium text-brand-text">{{ e.score }}</span>
                                            <span v-else class="text-brand-text-soft">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
