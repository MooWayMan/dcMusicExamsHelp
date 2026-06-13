<!-- resources/js/pages/admin/Schools/Show.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Pencil, Trash2, MapPin, Phone, Mail, User } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'

interface School {
    id: number
    name: string
    address: string | null
    city: string | null
    postcode: string | null
    phone: string | null
    email: string | null
    contact_name: string | null
    notes: string | null
    created_at: string
    instruments: Array<{ id: number; name: string; family: string }>
    teachers: Array<{
        id: number
        name: string
        email: string
        phone: string | null
        students_count: number
        orders_count: number
    }>
    orders: Array<{
        id: number
        trinity_order_number: string
        teacher_name: string
        teacher_contact_id: number | null
        delivery_method: string
        candidates: number
        commission_amount: string
        order_status: string
        requested_start_date: string
    }>
}

const props = defineProps<{ school: School }>()

function deleteSchool() {
    if (confirm(`Are you sure you want to archive ${props.school.name}? It can be restored later.`)) {
        router.delete(`/admin/schools/${props.school.id}`)
    }
}

// Teachers list at the top is intentionally minimal — just clickable names
// in a chip layout. Detailed info lives on each teacher's contact page.

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()

function goBack() { window.history.back() }

const orderColumns = [
    { key: 'trinity_order_number', title: 'Order #' },
    { key: 'teacher_name', title: 'Teacher' },
    { key: 'delivery_method', title: 'Type' },
    { key: 'candidates', title: 'Candidates' },
    { key: 'commission_amount', title: 'Commission' },
    { key: 'order_status', title: 'Status' },
    { key: 'requested_start_date', title: 'Date' },
]
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center justify-between', animClass('fade-up', 0)]">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                    <ArrowLeft class="h-5 w-5" />
                </button>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">School</p>
                    <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ school.name }}</h1>
                </div>
            </div>
            <div class="flex gap-2">
                <Link :href="`/admin/schools/${school.id}/edit`">
                    <MyButtonConstructor variant="outline" size="medium" :icon="Pencil">Edit</MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="outline" size="medium" :icon="Trash2" @click="deleteSchool">Archive</MyButtonConstructor>
            </div>
        </div>

        <div :class="['grid grid-cols-1 gap-6 lg:grid-cols-3', animClass('fade-up', 1)]">
            <!-- Details Card -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h2 class="text-xl font-semibold text-brand-text">School Details</h2>
                <div class="mt-4 space-y-3">
                    <div v-if="school.address" class="flex items-start gap-3">
                        <MapPin class="mt-0.5 h-5 w-5 text-brand-text-soft" />
                        <div class="text-base text-brand-text">
                            <p>{{ school.address }}</p>
                            <p v-if="school.city || school.postcode">{{ school.city }}<span v-if="school.postcode">, {{ school.postcode }}</span></p>
                        </div>
                    </div>
                    <div v-if="school.phone" class="flex items-center gap-3">
                        <Phone class="h-5 w-5 text-brand-text-soft" />
                        <a :href="`tel:${school.phone}`" class="text-base text-brand-text">{{ school.phone }}</a>
                    </div>
                    <div v-if="school.email" class="flex items-center gap-3">
                        <Mail class="h-5 w-5 text-brand-text-soft" />
                        <a :href="`mailto:${school.email}`" class="text-base text-brand-accent hover:underline">{{ school.email }}</a>
                    </div>
                    <div v-if="school.contact_name" class="flex items-center gap-3">
                        <User class="h-5 w-5 text-brand-text-soft" />
                        <span class="text-base text-brand-text">{{ school.contact_name }}</span>
                    </div>
                </div>
                <div v-if="school.instruments.length" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Instruments</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="i in school.instruments"
                            :key="i.id"
                            class="inline-flex items-center rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent"
                        >
                            {{ i.name }}
                        </span>
                    </div>
                </div>
                <div v-if="school.notes" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-1 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                    <p class="text-base text-brand-text">{{ school.notes }}</p>
                </div>
                <p class="mt-4 text-sm text-brand-text-soft">Added {{ school.created_at }}</p>
            </div>

            <!-- Teachers at this school -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5 lg:col-span-2">
                <h2 class="text-xl font-semibold text-brand-text">Teachers ({{ school.teachers.length }})</h2>
                <div class="mt-3">
                    <div v-if="school.teachers.length" class="flex flex-wrap gap-2">
                        <Link v-for="t in school.teachers" :key="t.id"
                            :href="`/admin/contacts/${t.id}`"
                            class="rounded-full border border-brand-border bg-brand-surface-soft px-3 py-1.5 text-base font-medium text-brand-accent transition-colors hover:bg-brand-accent hover:text-brand-text-inverse">
                            {{ t.name }}
                        </Link>
                    </div>
                    <p v-else class="py-4 text-center text-base text-brand-text-soft">No teachers linked to this school</p>
                </div>
            </div>
        </div>

        <!-- Orders at this school -->
        <div :class="['mt-6 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <div class="border-b border-brand-border p-4">
                <h2 class="text-xl font-semibold text-brand-text">Orders ({{ school.orders.length }})</h2>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="school.orders.length"
                    :data="school.orders"
                    :columns="orderColumns"
                    row-key="id"
                    size="medium"
                    :striped="true"
                    :bordered="false"
                    :full-width="true"
                    :bare="true"
                >
                    <template #cell-trinity_order_number="{ row }">
                        <Link :href="`/admin/orders/${row.id}`"
                            class="font-medium text-brand-accent hover:underline"
                            @click.stop>
                            {{ row.trinity_order_number }}
                        </Link>
                    </template>
                    <template #cell-teacher_name="{ row }">
                        <Link v-if="row.teacher_contact_id"
                            :href="`/admin/contacts/${row.teacher_contact_id}`"
                            class="text-brand-accent hover:underline"
                            @click.stop>
                            {{ row.teacher_name }}
                        </Link>
                        <span v-else class="text-brand-text">{{ row.teacher_name }}</span>
                    </template>
                    <template #cell-delivery_method="{ row }">
                        <span class="text-sm text-brand-text-soft">{{ row.delivery_method }}</span>
                    </template>
                    <template #cell-candidates="{ row }">
                        <span class="text-sm text-brand-text-soft">{{ row.candidates }}</span>
                    </template>
                    <template #cell-commission_amount="{ row }">
                        £{{ row.commission_amount }}
                    </template>
                    <template #cell-order_status="{ row }">
                        <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                                'bg-brand-success-soft text-brand-success': row.order_status === 'Completed',
                                'bg-brand-accent/10 text-brand-accent': row.order_status === 'Confirmed',
                                'bg-brand-surface-soft text-brand-text-soft': row.order_status === 'Submitted',
                                'bg-brand-danger-soft text-brand-danger': row.order_status === 'Cancelled',
                            }">
                            {{ row.order_status }}
                        </span>
                    </template>
                    <template #cell-requested_start_date="{ row }">
                        <span class="text-sm text-brand-text-soft">{{ row.requested_start_date }}</span>
                    </template>
                </MyTableConstructor>
                <p v-else class="py-4 text-center text-base text-brand-text-soft">No orders for this school</p>
            </div>
        </div>
    </div>
</template>
