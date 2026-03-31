<!-- resources/js/pages/admin/Schools/Edit.vue -->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Save, X } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface SchoolData {
    id: number
    name: string
    address: string | null
    city: string | null
    postcode: string | null
    phone: string | null
    email: string | null
    contact_name: string | null
    notes: string | null
}

const props = defineProps<{ school: SchoolData }>()

const form = useForm({
    name: props.school.name,
    address: props.school.address ?? '',
    city: props.school.city ?? '',
    postcode: props.school.postcode ?? '',
    phone: props.school.phone ?? '',
    email: props.school.email ?? '',
    contact_name: props.school.contact_name ?? '',
    notes: props.school.notes ?? '',
})

function submit() {
    form.put(`/admin/schools/${props.school.id}`)
}

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
</script>

<template>
    <div class="mx-auto w-full max-w-screen-lg px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <Link :href="`/admin/schools/${school.id}`" class="rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Edit {{ school.name }}</h1>
            </div>
        </div>

        <form @submit.prevent="submit" :class="['space-y-6', animClass('fade-up', 1)]">
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">School Details</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-lg font-medium text-brand-text">School Name *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-brand-danger">{{ form.errors.name }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-lg font-medium text-brand-text">Address</label>
                        <input v-model="form.address" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">City</label>
                        <input v-model="form.city" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Postcode</label>
                        <input v-model="form.postcode" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Phone</label>
                        <input v-model="form.phone" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Email</label>
                        <input v-model="form.email" type="email"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Contact Name</label>
                        <input v-model="form.contact_name" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    </div>
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-lg font-medium text-brand-text">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="`/admin/schools/${school.id}`">
                    <MyButtonConstructor variant="ghost" size="large" :icon="X">Cancel</MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="primary" size="large" :icon="Save" type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </MyButtonConstructor>
            </div>
        </form>
    </div>
</template>
