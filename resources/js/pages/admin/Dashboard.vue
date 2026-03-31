<!-- resources/js/pages/admin/Dashboard.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
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
    UserCheck,
    AlertCircle,
} from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
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

const orderColumns = [
    { key: 'trinity_order_number', title: 'Order #', sortable: false },
    { key: 'teacher_name', title: 'Teacher', sortable: false },
    { key: 'delivery_method', title: 'Type', sortable: false },
    { key: 'candidates', title: 'Candidates', sortable: false },
    { key: 'commission_amount', title: 'Commission', sortable: false },
    { key: 'order_status', title: 'Status', sortable: false },
]

const contactColumns = [
    { key: 'teacher_name', title: 'Teacher', sortable: false },
    { key: 'contact_type', title: 'Type', sortable: false },
    { key: 'direction', title: 'Direction', sortable: false },
    { key: 'subject', title: 'Subject', sortable: false },
    { key: 'contacted_at', title: 'Date', sortable: false },
]
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <PageHeader
            title="Admin Dashboard"
            subtitle="Overview of your MusicExams.help centre"
            eyebrow="Centre 120"
            size="compact"
        />

        <!-- Stat Cards Row 1: Counts -->
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Teachers -->
            <Link href="/admin/teachers" class="group">
                <div class="rounded-xl border border-brand-border bg-brand-surface p-5 transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-brand-text-soft">Teachers</p>
                            <p class="mt-1 text-3xl font-bold text-brand-text">{{ stats.totalTeachers }}</p>
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
                        <p class="text-sm font-medium text-brand-text-soft">Students</p>
                        <p class="mt-1 text-3xl font-bold text-brand-text">{{ stats.totalStudents }}</p>
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
                        <p class="text-sm font-medium text-brand-text-soft">Total Orders</p>
                        <p class="mt-1 text-3xl font-bold text-brand-text">{{ stats.totalOrders }}</p>
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
                        <p class="text-sm font-medium text-brand-text-soft">Schools</p>
                        <p class="mt-1 text-3xl font-bold text-brand-text">{{ stats.totalSchools }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-primary/10 p-3">
                        <School class="h-6 w-6 text-brand-primary" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Cards Row 2: Commission -->
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Total Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-brand-text-soft">Total Commission</p>
                        <p class="mt-1 text-3xl font-bold text-brand-success">&pound;{{ stats.totalCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-success/10 p-3">
                        <TrendingUp class="h-6 w-6 text-brand-success" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-brand-text-soft">{{ stats.totalCandidates }} total candidates</p>
            </div>

            <!-- DG Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-brand-text-soft">Digital (DG) — 20%</p>
                        <p class="mt-1 text-2xl font-bold text-brand-text">&pound;{{ stats.dgCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-accent/10 p-3">
                        <Monitor class="h-6 w-6 text-brand-accent" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-brand-text-soft">{{ stats.dgOrders }} orders</p>
            </div>

            <!-- F2F Commission -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-brand-text-soft">Face to Face (F2F) — 28%</p>
                        <p class="mt-1 text-2xl font-bold text-brand-text">&pound;{{ stats.f2fCommission }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-primary/10 p-3">
                        <MapPin class="h-6 w-6 text-brand-primary" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-brand-text-soft">{{ stats.f2fOrders }} orders</p>
            </div>
        </div>

        <!-- Two-column layout: Recent Orders + Contacts -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Orders -->
            <div class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="border-b border-brand-border p-4">
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Recent Orders</template>
                    </MyTextConstructor>
                </div>
                <div class="p-4">
                    <MyTableConstructor
                        v-if="recentOrders.length"
                        :data="recentOrders"
                        :columns="orderColumns"
                        row-key="id"
                        size="small"
                        :sortable="false"
                        :striped="true"
                        :bordered="false"
                    />
                    <p v-else class="py-4 text-center text-brand-text-soft">No orders yet</p>
                </div>
            </div>

            <!-- Recent Contacts -->
            <div class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="border-b border-brand-border p-4">
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Recent Contact Activity</template>
                    </MyTextConstructor>
                </div>
                <div class="p-4">
                    <MyTableConstructor
                        v-if="recentContacts.length"
                        :data="recentContacts"
                        :columns="contactColumns"
                        row-key="id"
                        size="small"
                        :sortable="false"
                        :striped="true"
                        :bordered="false"
                    />
                    <p v-else class="py-4 text-center text-brand-text-soft">No contacts logged</p>
                </div>
            </div>
        </div>

        <!-- Stale Teachers Alert -->
        <div v-if="staleTeachers.length" class="mt-8">
            <div class="rounded-xl border border-brand-danger/30 bg-brand-danger-soft p-5">
                <div class="mb-3 flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-brand-danger" />
                    <MyTextConstructor variant="button-lg" textColor="text-brand-danger">
                        <template #myTitle>Teachers Needing Follow-up</template>
                    </MyTextConstructor>
                </div>
                <p class="mb-4 text-sm text-brand-text-soft">
                    These teachers haven't been contacted in the last 30 days.
                </p>
                <div class="space-y-2">
                    <Link
                        v-for="teacher in staleTeachers"
                        :key="teacher.id"
                        :href="`/admin/teachers/${teacher.id}`"
                        class="flex items-center justify-between rounded-lg border border-brand-border bg-brand-surface p-3 transition-colors hover:bg-brand-surface-soft"
                    >
                        <div>
                            <p class="font-semibold text-brand-text">{{ teacher.name }}</p>
                            <p class="text-sm text-brand-text-soft">{{ teacher.email }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-brand-text-soft">
                            <Phone v-if="teacher.phone" class="h-4 w-4" />
                            <Mail class="h-4 w-4" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
