<!-- resources/js/pages/admin/Teachers/partials/TeacherForm.vue -->
<script setup lang="ts">
import { type InertiaForm } from '@inertiajs/vue3'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyTextareaConstructor from '@/components/reusables/MyTextareaConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Save, X } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'

interface Instrument {
    id: number
    name: string
    family: string
}

interface SchoolOption {
    id: number
    name: string
}

interface SubjectAreaOption {
    id: number
    name: string
}

const props = defineProps<{
    form: InertiaForm<{
        name: string
        email: string
        phone: string
        notes: string
        how_they_found_us: string
        met_face_to_face: boolean
        spoken_on_phone: boolean
        contacted_by_email: boolean
        instruments: number[]
        schools: number[]
        subject_areas: number[]
    }>
    instruments: Instrument[]
    schools: SchoolOption[]
    subjectAreas: SubjectAreaOption[]
    submitLabel: string
}>()

const emit = defineEmits<{
    submit: []
}>()

// Group instruments by family for the selector
const instrumentsByFamily = props.instruments.reduce((groups, instrument) => {
    if (!groups[instrument.family]) {
        groups[instrument.family] = []
    }
    groups[instrument.family].push(instrument)
    return groups
}, {} as Record<string, Instrument[]>)

function toggleInstrument(id: number) {
    const index = props.form.instruments.indexOf(id)
    if (index > -1) {
        props.form.instruments.splice(index, 1)
    } else {
        props.form.instruments.push(id)
    }
}

function toggleSchool(id: number) {
    const index = props.form.schools.indexOf(id)
    if (index > -1) {
        props.form.schools.splice(index, 1)
    } else {
        props.form.schools.push(id)
    }
}

function toggleSubjectArea(id: number) {
    const index = props.form.subject_areas.indexOf(id)
    if (index > -1) {
        props.form.subject_areas.splice(index, 1)
    } else {
        props.form.subject_areas.push(id)
    }
}
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <!-- Basic Details -->
        <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <h3 class="mb-4 text-xl font-semibold text-brand-text">Basic Details</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-lg font-medium text-brand-text">Name *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        placeholder="Full name"
                        required
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-brand-danger">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-lg font-medium text-brand-text">Email *</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        placeholder="Email address"
                        required
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-brand-danger">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-lg font-medium text-brand-text">Phone</label>
                    <input
                        v-model="form.phone"
                        type="text"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        placeholder="Phone number"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-lg font-medium text-brand-text">How they found us</label>
                    <input
                        v-model="form.how_they_found_us"
                        type="text"
                        class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        placeholder="e.g. Trinity website, Word of mouth"
                    />
                </div>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-lg font-medium text-brand-text">Notes</label>
                <textarea
                    v-model="form.notes"
                    rows="3"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                    placeholder="Any notes about this teacher..."
                ></textarea>
            </div>
        </div>

        <!-- Contact Status -->
        <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <h3 class="mb-4 text-xl font-semibold text-brand-text">Contact Status</h3>
            <div class="flex flex-wrap gap-4">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" v-model="form.contacted_by_email" class="h-4 w-4 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                    <span class="text-base text-brand-text">Contacted by email</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" v-model="form.spoken_on_phone" class="h-4 w-4 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                    <span class="text-base text-brand-text">Spoken on phone</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" v-model="form.met_face_to_face" class="h-4 w-4 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                    <span class="text-base text-brand-text">Met face to face</span>
                </label>
            </div>
        </div>

        <!-- Schools -->
        <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <h3 class="mb-4 text-xl font-semibold text-brand-text">Schools</h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="school in schools"
                    :key="school.id"
                    type="button"
                    @click="toggleSchool(school.id)"
                    class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
                    :class="form.schools.includes(school.id)
                        ? 'border-brand-accent bg-brand-accent/10 text-brand-accent'
                        : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent'"
                >
                    {{ school.name }}
                </button>
            </div>
        </div>

        <!-- Instruments -->
        <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <h3 class="mb-4 text-xl font-semibold text-brand-text">Instruments</h3>
            <div class="space-y-4">
                <div v-for="(instruments, family) in instrumentsByFamily" :key="family">
                    <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">{{ family }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="instrument in instruments"
                            :key="instrument.id"
                            type="button"
                            @click="toggleInstrument(instrument.id)"
                            class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
                            :class="form.instruments.includes(instrument.id)
                                ? 'border-brand-accent bg-brand-accent/10 text-brand-accent'
                                : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent'"
                        >
                            {{ instrument.name }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Areas -->
        <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <h3 class="mb-4 text-xl font-semibold text-brand-text">Subject Areas</h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="area in subjectAreas"
                    :key="area.id"
                    type="button"
                    @click="toggleSubjectArea(area.id)"
                    class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
                    :class="form.subject_areas.includes(area.id)
                        ? 'border-brand-accent bg-brand-accent/10 text-brand-accent'
                        : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent'"
                >
                    {{ area.name }}
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <Link href="/admin/teachers">
                <MyButtonConstructor variant="ghost" size="large" :icon="X">
                    Cancel
                </MyButtonConstructor>
            </Link>
            <MyButtonConstructor
                variant="primary"
                size="large"
                :icon="Save"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving...' : submitLabel }}
            </MyButtonConstructor>
        </div>
    </form>
</template>
