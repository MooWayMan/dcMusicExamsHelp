<!-- resources/js/pages/admin/Schools/Show.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Pencil, Trash2, MapPin, Phone, Mail, User } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
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

const teacherColumns = [
    { key: 'name', title: 'Name' },
    { key: 'email', title: 'Email' },
    { key: 'phone', title: 'Phone' },
    { key: 'students_count', title: 'Students', align: 'center' as const },
    { key: 'orders_count', title: 'Orders', align: 'center' as const },
]

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
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/schools" class="rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-text-soft">School</p>
                    <h1 class="text-2xl font-bold text-brand-text sm:text-3xl">{{ school.name }}</h1>
                </div>
            </div>
            <div class="flex gap-2">
                <Link :href="`/admin/schools/${school.id}/edit`">
                    <MyButtonConstructor variant="outline" size="small" :icon="Pencil">Edit</MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="danger" size="small" :icon="Trash2" @click="deleteSchool">Archive</MyButtonConstructor>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Details Card -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>School Details</template>
                </MyTextConstructor>
                <div class="mt-4 space-y-3">
                    <div v-if="school.address" class="flex items-start gap-3">
                        <MapPin class="mt-0.5 h-4 w-4 text-brand-text-soft" />
                        <div class="text-sm text-brand-text">
                            <p>{{ school.address }}</p>
                            <p v-if="school.city || school.postcode">{{ school.city }}<span v-if="school.postcode">, {{ school.postcode }}</span></p>
                        </div>
                    </div>
                    <div v-if="school.phone" class="flex items-center gap-3">
                        <Phone class="h-4 w-4 text-brand-text-soft" />
                        <a :href="`tel:${school.phone}`" class="text-sm text-brand-text">{{ school.phone }}</a>
                    </div>
                    <div v-if="school.email" class="flex items-center gap-3">
                        <Mail class="h-4 w-4 text-brand-text-soft" />
                        <a :href="`mailto:${school.email}`" class="text-sm text-brand-accent hover:underline">{{ school.email }}</a>
                    </div>
                    <div v-if="school.contact_name" class="flex items-center gap-3">
                        <User class="h-4 w-4 text-brand-text-soft" />
                        <span class="text-sm text-brand-text">{{ school.contact_name }}</span>
                    </div>
                </div>
                <div v-if="school.notes" class="mt-5 border-t border-brand-border pt-4">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Notes</p>
                    <p class="text-sm text-brand-text">{{ school.notes }}</p>
                </div>
                <p class="mt-4 text-xs text-brand-text-soft">Added {{ school.created_at }}</p>
            </div>

            <!-- Teachers at this school -->
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5 lg:col-span-2">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Teachers ({{ school.teachers.length }})</template>
                </MyTextConstructor>
                <div class="mt-3">
                    <MyTableConstructor
                        v-if="school.teachers.length"
                        :data="school.teachers"
                        :columns="teacherColumns"
                        row-key="id"
                        size="small"
                        :striped="true"
                        :bordered="false"
                        :clickable-rows="true"
                        @row-click="(row: any) => router.visit(`/admin/teachers/${row.id}`)"
                    />
                    <p v-else class="py-4 text-center text-sm text-brand-text-soft">No teachers linked to this school</p>
                </div>
            </div>
        </div>

        <!-- Orders at this school -->
        <div class="mt-6 rounded-xl border border-brand-border bg-brand-surface">
            <div class="border-b border-brand-border p-4">
                <MyTextConstructor variant="button-lg">
                    <template #myTitle>Orders ({{ school.orders.length }})</template>
                </MyTextConstructor>
            </div>
            <div class="p-4">
                <MyTableConstructor
                    v-if="school.orders.length"
                    :data="school.orders"
                    :columns="orderColumns"
                    row-key="id"
                    size="small"
                    :striped="true"
                    :bordered="false"
                />
                <p v-else class="py-4 text-center text-sm text-brand-text-soft">No orders for this school</p>
            </div>
        </div>
    </div>
</template>
