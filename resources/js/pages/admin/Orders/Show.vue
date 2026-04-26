<!-- resources/js/pages/admin/Orders/Show.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, Monitor, MapPin, School, User, Music, Pencil } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'

interface ExamEntry {
    id: number
    student_id: number | null
    student_name: string
    instrument: string
    grade: string
    result: string
    score: number | null
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

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()

function goBack() { window.history.back() }

const examColumns = [
    { key: 'student_name', title: 'Student' },
    { key: 'instrument', title: 'Instrument' },
    { key: 'grade', title: 'Grade' },
    { key: 'result', title: 'Result' },
    { key: 'exam_date', title: 'Exam Date' },
]
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Order</p>
                <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ order.trinity_order_number }}</h1>
            </div>
            <span class="ml-2 inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium"
                :class="order.delivery_method === 'DG' ? 'bg-brand-accent/10 text-brand-accent' : 'bg-brand-primary/10 text-brand-primary'">
                <Monitor v-if="order.delivery_method === 'DG'" class="h-5 w-5" />
                <MapPin v-else class="h-5 w-5" />
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
            <div class="ml-auto">
                <Link :href="`/admin/orders/${order.id}/edit`">
                    <MyButtonConstructor variant="primary" size="small" :icon="Pencil">Edit</MyButtonConstructor>
                </Link>
            </div>
        </div>

        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-3', animClass('fade-up', 1)]">
            <!-- Order Details -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h2 class="text-xl font-semibold text-brand-text">Order Details</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Subject Area</span>
                        <span class="text-base font-medium text-brand-text">{{ order.subject_area }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Candidates</span>
                        <span class="text-base font-medium text-brand-text">{{ order.candidates }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Venue</span>
                        <span class="text-base font-medium text-brand-text">{{ order.venue }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base text-brand-text-soft">Requested Start</span>
                        <span class="text-base font-medium text-brand-text">{{ order.requested_start_date ?? '—' }}</span>
                    </div>
                    <div class="border-t border-brand-border pt-3">
                        <div class="flex justify-between">
                            <span class="text-base text-brand-text-soft">Commission Rate</span>
                            <span class="text-base font-medium text-brand-text">{{ order.commission_rate }}%</span>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base font-medium text-brand-text-soft">Commission Amount</span>
                        <span class="text-xl font-bold text-brand-success">&pound;{{ order.commission_amount }}</span>
                    </div>
                </div>
                <div v-if="order.notes" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-1 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                    <p class="text-base text-brand-text">{{ order.notes }}</p>
                </div>
                <p class="mt-4 text-sm text-brand-text-soft">Created {{ order.created_at }}</p>
            </div>

            <!-- Teacher -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <User class="h-5 w-5 text-brand-text-soft" />
                    <h2 class="text-xl font-semibold text-brand-text">Teacher</h2>
                </div>
                <div v-if="order.teacher" class="mt-4">
                    <Link v-if="order.teacher.id" :href="`/admin/contacts/${order.teacher.id}`" class="text-xl font-semibold text-brand-accent hover:underline">
                        {{ order.teacher.name }}
                    </Link>
                    <span v-else class="text-xl font-semibold text-brand-text">{{ order.teacher.name }}</span>
                    <p class="mt-1 truncate text-base text-brand-text-soft">{{ order.teacher.email }}</p>
                    <p v-if="order.teacher.phone" class="text-base text-brand-text-soft">{{ order.teacher.phone }}</p>
                </div>
                <p v-else class="mt-4 text-base text-brand-text-soft">Teacher removed or unlinked</p>
            </div>

            <!-- School -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <div class="flex items-center gap-2">
                    <School class="h-5 w-5 text-brand-text-soft" />
                    <h2 class="text-xl font-semibold text-brand-text">School</h2>
                </div>
                <div v-if="order.school" class="mt-4">
                    <Link :href="`/admin/schools/${order.school.id}`" class="text-xl font-semibold text-brand-accent hover:underline">
                        {{ order.school.name }}
                    </Link>
                    <p class="mt-1 text-base text-brand-text-soft">{{ order.school.city }}</p>
                </div>
                <p v-else class="mt-4 text-base text-brand-text-soft">No school linked</p>
            </div>
        </div>

        <!-- Exam Entries -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <div class="flex items-center gap-2 border-b border-brand-border p-4">
                <Music class="h-5 w-5 text-brand-text-soft" />
                <h2 class="text-xl font-semibold text-brand-text">Exam Entries ({{ order.exam_entries.length }})</h2>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="order.exam_entries.length"
                    :data="order.exam_entries"
                    :columns="examColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                >
                    <template #cell-student_name="{ row }">
                        <Link v-if="row.student_id"
                            :href="`/admin/exam-entries?student_id=${row.student_id}&from=order`"
                            class="font-medium text-brand-accent hover:underline"
                            @click.stop>
                            {{ row.student_name }}
                        </Link>
                        <span v-else class="text-brand-text">{{ row.student_name }}</span>
                    </template>
                    <template #cell-instrument="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                    <template #cell-grade="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                    <template #cell-result="{ value }">
                        <span v-if="value && value !== 'Pending'" class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                                'bg-brand-success-soft text-brand-success': value === 'Distinction',
                                'bg-brand-accent/10 text-brand-accent': value === 'Merit',
                                'bg-brand-surface-soft text-brand-text-soft': value === 'Pass',
                                'bg-brand-danger-soft text-brand-danger': value === 'Below Pass',
                            }">
                            {{ value }}
                        </span>
                        <span v-else class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                    <template #cell-exam_date="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value }}</span>
                    </template>
                </MyTableConstructor>
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No exam entries recorded</p>
            </div>
        </div>
    </div>
</template>
