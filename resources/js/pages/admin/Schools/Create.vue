<!-- resources/js/pages/admin/Schools/Create.vue -->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Save, X } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

const form = useForm({
    name: '',
    address: '',
    city: '',
    postcode: '',
    phone: '',
    email: '',
    contact_name: '',
    notes: '',
})

function submit() {
    form.post('/admin/schools')
}

import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()

function goBack() { window.history.back() }
</script>

<template>
    <div class="mx-auto w-full max-w-screen-lg px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Add New School</h1>
            </div>
        </div>

        <form @submit.prevent="submit" :class="['space-y-6', animClass('fade-up', 1)]">
            <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                <h3 class="mb-4 text-xl font-semibold text-brand-text">School Details</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-lg font-medium text-brand-text">School Name *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="School name" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-brand-danger">{{ form.errors.name }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-lg font-medium text-brand-text">Address</label>
                        <input v-model="form.address" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="Street address" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">City</label>
                        <input v-model="form.city" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="City" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Postcode</label>
                        <input v-model="form.postcode" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="e.g. CH43 2JD" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Phone</label>
                        <input v-model="form.phone" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="Phone number" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Email</label>
                        <input v-model="form.email" type="email"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="School email" />
                    </div>
                    <div>
                        <label class="mb-1 block text-lg font-medium text-brand-text">Contact Name</label>
                        <input v-model="form.contact_name" type="text"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="e.g. Mrs Thompson" />
                    </div>
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-lg font-medium text-brand-text">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        placeholder="Any notes about this school..."></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/schools">
                    <MyButtonConstructor variant="ghost" size="large" :icon="X">Cancel</MyButtonConstructor>
                </Link>
                <MyButtonConstructor variant="primary" size="large" :icon="Save" type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Add School' }}
                </MyButtonConstructor>
            </div>
        </form>
    </div>
</template>
