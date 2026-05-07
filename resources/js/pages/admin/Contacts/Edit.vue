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
    types: string[]
    source: string | null
    notes: string | null
    show_full_name: boolean
    excluded_from_prize_draw: boolean
}

const props = defineProps<{
    contact: ContactData
    allTypes: string[]
}>()

const form = useForm({
    name: props.contact.name,
    email: props.contact.email ?? '',
    phone: props.contact.phone ?? '',
    types: [...(props.contact.types ?? [])],
    notes: props.contact.notes ?? '',
    show_full_name: !!props.contact.show_full_name,
    excluded_from_prize_draw: !!props.contact.excluded_from_prize_draw,
})

function submit() {
    form.put(`/admin/contacts/${props.contact.id}`)
}

function toggleType(type: string) {
    const i = form.types.indexOf(type)
    if (i === -1) {
        form.types.push(type)
    } else {
        form.types.splice(i, 1)
    }
}

function typeLabel(type: string): string {
    const map: Record<string, string> = {
        teacher: 'Teacher',
        parent: 'Parent',
        candidate: 'Candidate (adult or child applicant)',
        school_admin: 'School Admin',
        trinity_admin: 'Trinity Admin',
        subscriber: 'Newsletter Subscriber',
    }
    return map[type] ?? type
}

const { animClass } = usePageAnimation()

function goBack() { window.history.back() }
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

            <!-- Types (multi-select) -->
            <div>
                <span class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Types</span>
                <p class="mt-1 text-sm text-brand-text-soft">
                    A contact can be more than one thing — e.g. a teacher who also enters their own children, or a teacher who runs a school.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="t in allTypes" :key="t" type="button"
                        @click="toggleType(t)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium border transition-colors"
                        :class="form.types.includes(t)
                            ? 'bg-brand-accent text-brand-text-inverse border-brand-accent'
                            : 'bg-brand-surface text-brand-text-soft border-brand-border hover:text-brand-text'">
                        {{ typeLabel(t) }}
                    </button>
                </div>
                <p v-if="form.errors.types" class="mt-1 text-sm text-brand-danger">{{ form.errors.types }}</p>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Notes</label>
                <textarea id="notes" v-model="form.notes" rows="4"
                    class="mt-2 w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                <p v-if="form.errors.notes" class="mt-1 text-sm text-brand-danger">{{ form.errors.notes }}</p>
            </div>

            <!-- Privacy / draw flags -->
            <div class="space-y-3 rounded-lg border border-brand-border bg-brand-surface-soft p-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Display &amp; draw flags</p>

                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        v-model="form.show_full_name"
                        type="checkbox"
                        class="mt-1 h-4 w-4 cursor-pointer rounded border-brand-border text-brand-accent focus:ring-brand-accent"
                    />
                    <span class="text-sm text-brand-text">
                        <span class="block font-medium">Show full name on dashboard prize-draw widget</span>
                        <span class="block text-brand-text-soft">
                            Default off — contact appears as "First L". Tick once they explicitly consent (typically by replying to your gift-token email).
                        </span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        v-model="form.excluded_from_prize_draw"
                        type="checkbox"
                        class="mt-1 h-4 w-4 cursor-pointer rounded border-brand-border text-brand-accent focus:ring-brand-accent"
                    />
                    <span class="text-sm text-brand-text">
                        <span class="block font-medium">Exclude from prize draws</span>
                        <span class="block text-brand-text-soft">
                            Tick if this contact runs the centre / shouldn't be eligible to win their own draw.
                        </span>
                    </span>
                </label>
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
