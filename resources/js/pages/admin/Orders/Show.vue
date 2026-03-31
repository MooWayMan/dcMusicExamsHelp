<!-- resources/js/pages/admin/Orders/Show.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, Monitor, MapPin, School, User, Music } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'

interface ExamEntry {
    id: number
    student_name: string
    instrument: string
    grade: string
    result: string
    exam_date: string
}

interface Order {
    id: number
    trinity_order_number: string
    delivery_method: string
    delivery_method_raw: string
    subject_area: string
    candidates: number
    venue: string
    order_status: string
    commission_rate: number
    commission_amount: string
    requested_start_date: string
    notes: string | null
    created_at: string
    teacher: { id: number; name: string; email: string; phone: string | null } | null
    school: { id: number; name: string; city: string } | null
    exam_entries: ExamEntry[]
}

const props = defineProps<{ order: Order }>()

const examColumns = [
    { key: 'student_name', title: 'Student' },
    { key: 'instrument', title: 'Instrument' },
    { key: 'grade', title: 'Grade' },
    { key: 'result', title: 'Result' },
    { key: 'exam_date', title: 'Exam Date' },
]
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <Link href="/admin/orders" class="rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Order</p>
                <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ order.trinity_order_number }}</h1>
            </div>
            <span class="ml-2 inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium"
                :class="order.delivery_method === 'DG' ? 'bg-brand-accent/10 text-brand-accent' : 'bg-brand-primary/10 text-brand-primary'">
                <Monitor v-if="order.delivery_method === 'DG'" class="h-4 w-4" />
                <MapPin v-else class="h-4 w-4" />
                {{ order.delivery_method }}
            </span>
            <span class="rounded-full px-3 py-1 text-sm font-medium"
                :class="{
                    'bg-brand-success-soft text-brand-success': order.order_status === 'Completed',
                    'bg-brand-accent/10 text-brand-accent': order.order_status === 'Confirmed',
                    'bg-brand-surface-soft text-brand-text-soft': order.order_status === 'Submitted',
                }">
                {{ order.order_status }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Order Details -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Order Details</template>
                </MyTextConstructor>
                <div class="mt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-brand-text-soft">Subject Area</span>
                        <span class="text-sm font-medium text-brand-text">{{ order.subject_area }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-brand-text-soft">Candidates</span>
                        <span class="text-sm font-medium text-brand-text">{{ order.candidates }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-brand-text-soft">Venue</span>
                        <span class="text-sm font-medium text-brand-text">{{ order.venue }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-brand-text-soft">Requested Start</span>
                        <span class="text-sm font-medium text-brand-text">{{ order.requested_start_date ?? '—' }}</span>
                    </div>
                    <div class="border-t border-brand-border pt-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-brand-text-soft">Commission Rate</span>
                            <span class="text-sm font-medium text-brand-text">{{ order.commission_rate }}%</span>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-brand-text-soft">Commission Amount</span>
                        <span class="text-lg font-bold text-brand-success">&pound;{{ order.commission_amount }}</span>
                    </div>
                </div>
                <div v-if="order.notes" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                    <p class="text-sm text-brand-text">{{ order.notes }}</p>
                </div>
                <p class="mt-4 text-xs text-brand-text-soft">Created {{ order.created_at }}</p>
            </div>

            <!-- Teacher -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <User class="h-4 w-4 text-brand-text-soft" />
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>Teacher</template>
                    </MyTextConstructor>
                </div>
                <div v-if="order.teacher" class="mt-4">
                    <Link :href="`/admin/teachers/${order.teacher.id}`" class="text-lg font-semibold text-brand-accent hover:underline">
                        {{ order.teacher.name }}
                    </Link>
                    <p class="mt-1 text-sm text-brand-text-soft">{{ order.teacher.email }}</p>
                    <p v-if="order.teacher.phone" class="text-sm text-brand-text-soft">{{ order.teacher.phone }}</p>
                </div>
                <p v-else class="mt-4 text-sm text-brand-text-soft">Teacher removed or unlinked</p>
            </div>

            <!-- School -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <School class="h-4 w-4 text-brand-text-soft" />
                    <MyTextConstructor variant="button-lg">
                        <template #myTitle>School</template>
                    </MyTextConstructor>
                </div>
                <div v-if="order.school" class="mt-4">
                    <Link :href="`/admin/schools/${order.school.id}`" class="text-lg font-semibold text-brand-accent hover:underline">
                        {{ order.school.name }}
                    </Link>
                    <p class="mt-1 text-sm text-brand-text-soft">{{ order.school.city }}</p>
                </div>
                <p v-else class="mt-4 text-sm text-brand-text-soft">No school linked</p>
            </div>
        </div>

        <!-- Exam Entries -->
        <div class="mt-6 rounded-xl border border-brand-border bg-brand-surface">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <Music class="h-4 w-4 text-brand-text-soft" />
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Exam Entries ({{ order.exam_entries.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="order.exam_entries.length"
                    :data="order.exam_entries"
                    :columns="examColumns"
                    row-key="id"
                    size="small"
                    :striped="true"
                    :bordered="false"
                />
                <p v-else class="py-4 text-center text-sm text-brand-text-soft">No exam entries recorded</p>
            </div>
        </div>
    </div>
</template>
