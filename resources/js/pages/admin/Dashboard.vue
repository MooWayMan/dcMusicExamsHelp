<!-- resources/js/pages/admin/Dashboard.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import {
    Users,
    GraduationCap,
    ClipboardList,
    School,
    TrendingUp,
    Monitor,
    MapPin,
    Phone,
    Mail,
    AlertCircle,
} from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'

interface Stats {
    totalTeachers: number
    totalStudents: number
    totalOrders: number
    totalSchools: number
    totalCommission: string
    dgCommission: string
    f2fCommission: string
    dgOrders: number
    f2fOrders: number
    totalCandidates: number
}

interface RecentOrder {
    id: number
    trinity_order_number: string
    teacher_id: number
    teacher_name: string
    school_name: string
    delivery_method: string
    candidates: number
    commission_amount: string
    order_status: string
    requested_start_date: string
}

interface RecentContact {
    id: number
    teacher_id: number
    teacher_name: string
    contact_type: string
    direction: string
    subject: string
    contacted_at: string
}

interface StaleTeacher {
    id: number
    name: string
    email: string
    phone: string | null
}

const props = defineProps<{
    stats: Stats
    recentOrders: RecentOrder[]
    recentContacts: RecentContact[]
    staleTeachers: StaleTeacher[]
}>()

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <PageHeader
            title="Admin Dashboard"
            subtitle="Overview of your MusicExams.help centre"
            eyebrow="Centre 120"
            size="compact"
        />

        <!-- Stat Cards Row 1: Counts -->
        <div :class="['mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4', animClass('fade-up', 1)]">
            <!-- Teachers -->
            <Link href="/admin/teachers" class="group">
                <div class="rounded-xl border border-brand-border bg-brand-surface p-5 transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-base font-medium text-brand-text-soft">Teachers</p>
                            <p class="mt-1 text-4xl font-bold text-brand-text">{{ stats.totalTeachers }}</p>
                        </div>
                        <div class="rounded-lg bg-brand-accent/10 p-3">
                            <Users class="h-6 w-6 text-brand-accent" />
                        </div>
                    </div>
                </div>
            </Link>

            <!-- Students -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Students</p>
                        <p class="mt-1 text-4xl font-bold text-brand-text">{{ stats.totalStudents }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-success/10 p-3">
                        <GraduationCap class="h-6 w-6 text-brand-success" />
                    </div>
                </div>
            </div>

            <!-- Orders -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Total Orders</p>
                        <p class="mt-1 text-4xl font-bold text-brand-text">{{ stats.totalOrders }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-cta/10 p-3">
                        <ClipboardList class="h-6 w-6 text-brand-cta" />
                    </div>
                </div>
            </div>

            <!-- Schools -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Schools</p>
                        <p class="mt-1 text-4xl font-bold text-brand-text">{{ stats.totalSchools }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-primary/10 p-3">
                        <School class="h-6 w-6 text-brand-primary" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Cards Row 2: Commission -->
        <div :class="['mt-4 grid grid-cols-1 gap-4 md:grid-cols-3', animClass('fade-up', 2)]">
            <!-- Total Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Total Commission</p>
                        <p class="mt-1 text-4xl font-bold text-brand-success">&pound;{{ stats.totalCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-success/10 p-3">
                        <TrendingUp class="h-6 w-6 text-brand-success" />
                    </div>
                </div>
                <p class="mt-2 text-base text-brand-text-soft">{{ stats.totalCandidates }} total candidates</p>
            </div>

            <!-- DG Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Digital (DG) — 20%</p>
                        <p class="mt-1 text-3xl font-bold text-brand-text">&pound;{{ stats.dgCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-accent/10 p-3">
                        <Monitor class="h-6 w-6 text-brand-accent" />
                    </div>
                </div>
                <p class="mt-2 text-base text-brand-text-soft">{{ stats.dgOrders }} orders</p>
            </div>

            <!-- F2F Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-base font-medium text-brand-text-soft">Face to Face (F2F) — 28%</p>
                        <p class="mt-1 text-3xl font-bold text-brand-text">&pound;{{ stats.f2fCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-primary/10 p-3">
                        <MapPin class="h-6 w-6 text-brand-primary" />
                    </div>
                </div>
                <p class="mt-2 text-base text-brand-text-soft">{{ stats.f2fOrders }} orders</p>
            </div>
        </div>

        <!-- Recent Orders + Contacts (stacked full width) -->
        <div :class="['mt-8 space-y-6', animClass('fade-up', 3)]">
            <!-- Recent Orders -->
            <div class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="border-b border-brand-border p-4">
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Recent Orders</template>
                    </MyTextConstructor>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="recentOrders.length" class="w-full text-left">
                        <thead>
                            <tr class="border-b border-brand-border bg-brand-primary text-brand-text-inverse">
                                <th class="px-4 py-3 text-base font-semibold">Order #</th>
                                <th class="px-4 py-3 text-base font-semibold">Teacher</th>
                                <th class="px-4 py-3 text-base font-semibold">Type</th>
                                <th class="px-4 py-3 text-base font-semibold">Candidates</th>
                                <th class="px-4 py-3 text-base font-semibold">Commission</th>
                                <th class="px-4 py-3 text-base font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(order, idx) in recentOrders" :key="order.id"
                                class="cursor-pointer border-b border-brand-border transition-colors hover:bg-brand-surface-soft"
                                :class="idx % 2 === 1 ? 'bg-brand-surface-soft/50' : ''"
                                @click="router.visit(`/admin/orders/${order.id}`)">
                                <td class="px-4 py-3 text-base font-medium text-brand-accent">{{ order.trinity_order_number }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ order.teacher_name }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ order.delivery_method }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ order.candidates }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">&pound;{{ order.commission_amount }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ order.order_status }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="px-4 py-6 text-center text-lg text-brand-text-soft">No orders yet</p>
                </div>
            </div>

            <!-- Recent Contacts -->
            <div class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="border-b border-brand-border p-4">
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Recent Contact Activity</template>
                    </MyTextConstructor>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="recentContacts.length" class="w-full text-left">
                        <thead>
                            <tr class="border-b border-brand-border bg-brand-primary text-brand-text-inverse">
                                <th class="px-4 py-3 text-base font-semibold">Teacher</th>
                                <th class="px-4 py-3 text-base font-semibold">Type</th>
                                <th class="px-4 py-3 text-base font-semibold">Direction</th>
                                <th class="px-4 py-3 text-base font-semibold">Subject</th>
                                <th class="px-4 py-3 text-base font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(contact, idx) in recentContacts" :key="contact.id"
                                class="cursor-pointer border-b border-brand-border transition-colors hover:bg-brand-surface-soft"
                                :class="idx % 2 === 1 ? 'bg-brand-surface-soft/50' : ''"
                                @click="router.visit(`/admin/teachers/${contact.teacher_id}`)">
                                <td class="px-4 py-3 text-base font-medium text-brand-accent">{{ contact.teacher_name }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ contact.contact_type }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ contact.direction }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ contact.subject }}</td>
                                <td class="px-4 py-3 text-base text-brand-text">{{ contact.contacted_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="px-4 py-6 text-center text-lg text-brand-text-soft">No contacts logged</p>
                </div>
            </div>
        </div>

        <!-- Stale Teachers Alert -->
        <div v-if="staleTeachers.length" :class="['mt-8', animClass('fade-up', 4)]">
            <div class="rounded-xl border border-brand-accent/30 bg-brand-surface-soft p-5">
                <div class="mb-3 flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-brand-accent" />
                    <MyTextConstructor variant="button-lg" textColor="text-brand-accent">
                        <template #myTitle>Teachers Needing Follow-up</template>
                    </MyTextConstructor>
                </div>
                <p class="mb-4 text-lg text-brand-text-soft">
                    These teachers haven't been contacted in the last 30 days.
                </p>
                <div class="space-y-3">
                    <Link
                        v-for="teacher in staleTeachers"
                        :key="teacher.id"
                        :href="`/admin/teachers/${teacher.id}`"
                        class="flex items-center justify-between rounded-lg border border-brand-border bg-brand-surface p-4 transition-colors hover:bg-brand-surface-soft"
                    >
                        <div>
                            <p class="text-lg font-semibold text-brand-text">{{ teacher.name }}</p>
                            <p class="text-base text-brand-text-soft">{{ teacher.email }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-brand-text-soft">
                            <Phone v-if="teacher.phone" class="h-5 w-5" />
                            <Mail class="h-5 w-5" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
