<!-- resources/js/pages/admin/Contacts/Edit.vue -->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Save } from 'lucide-vue-next'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface ContactData {
    id: number
    name: string
    email: string | null
    phone: string | null
    role: string | null
    source: string | null
    notes: string | null
}

const props = defineProps<{
    contact: ContactData
    roles: string[]
}>()

const form = useForm({
    name: props.contact.name,
    email: props.contact.email ?? '',
    phone: props.contact.phone ?? '',
    role: props.contact.role ?? 'unknown',
    notes: props.contact.notes ?? '',
})

function submit() {
    form.put(`/admin/contacts/${props.contact.id}`)
}

const { animClass } = usePageAnimation()

function goBack() { window.history.back() }

function roleLabel(role: string): string {
    const map: Record<string, string> = {
        teacher: 'Teacher',
        parent: 'Parent',
        self: 'Self (adult candidate)',
        applicant: 'Applicant',
        admin: 'Admin',
        unknown: 'Unknown',
    }
    return map[role] ?? role
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-md px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack"
                class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Edit {{ contact.name }}</h1>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit"
            :class="['space-y-6 rounded-xl border border-brand-border bg-brand-surface p-6', animClass('fade-up', 1)]">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Name</label>
                <input id="name" v-model="form.name" type="text" required
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-brand-danger">{{ form.errors.name }}</p>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Email</label>
                <input id="email" v-model="form.email" type="email"
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-brand-danger">{{ form.errors.email }}</p>
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Phone</label>
                <input id="phone" v-model="form.phone" type="text"
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                <p v-if="form.errors.phone" class="mt-1 text-sm text-brand-danger">{{ form.errors.phone }}</p>
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Role</label>
                <p class="mt-1 text-sm text-brand-text-soft">
                    Role controls who the contact is treated as. Parents are excluded from the teacher prize draw.
                </p>
                <select id="role" v-model="form.role" required
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                    <option v-for="r in roles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                </select>
                <p v-if="form.errors.role" class="mt-1 text-sm text-brand-danger">{{ form.errors.role }}</p>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</label>
                <textarea id="notes" v-model="form.notes" rows="4"
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                <p v-if="form.errors.notes" class="mt-1 text-sm text-brand-danger">{{ form.errors.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-3 border-t border-brand-border pt-4">
                <button type="submit" :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-accent px-4 py-2 text-base font-semibold text-brand-text-inverse transition-colors hover:opacity-90 disabled:opacity-60">
                    <Save class="h-4 w-4" />
                    Save Changes
                </button>
                <Link :href="`/admin/contacts/${contact.id}`"
                    class="rounded-lg px-4 py-2 text-base font-medium text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text">
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>
