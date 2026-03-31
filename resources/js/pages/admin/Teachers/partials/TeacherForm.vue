<!-- resources/js/pages/admin/Teachers/partials/TeacherForm.vue -->
<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { type InertiaForm } from '@inertiajs/vue3'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Save, X, ChevronRight, ChevronLeft, Check } from 'lucide-vue-next'
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

// ──────────────────────────────────────────
// Wizard Steps
// ──────────────────────────────────────────

const steps = [
    { id: 1, title: 'Details', description: 'Name, email & phone' },
    { id: 2, title: 'Contact', description: 'Contact status & notes' },
    { id: 3, title: 'Schools', description: 'Teaching locations' },
    { id: 4, title: 'Music', description: 'Instruments & subjects' },
    { id: 5, title: 'Review', description: 'Confirm & save' },
]

const currentStep = ref(1)
const animating = ref(false)
const slideDirection = ref<'left' | 'right'>('left')

function canProceed(): boolean {
    if (currentStep.value === 1) {
        return Boolean(props.form.name.trim() && props.form.email.trim())
    }
    return true
}

async function nextStep() {
    if (currentStep.value >= steps.length || !canProceed()) return
    slideDirection.value = 'left'
    animating.value = true
    await nextTick()
    requestAnimationFrame(() => {
        currentStep.value++
        animating.value = false
    })
}

async function prevStep() {
    if (currentStep.value <= 1) return
    slideDirection.value = 'right'
    animating.value = true
    await nextTick()
    requestAnimationFrame(() => {
        currentStep.value--
        animating.value = false
    })
}

function goToStep(step: number) {
    if (step < currentStep.value) {
        slideDirection.value = 'right'
        currentStep.value = step
    } else if (step <= furthestStep.value) {
        slideDirection.value = 'left'
        currentStep.value = step
    }
}

const furthestStep = computed(() => {
    if (!props.form.name.trim() || !props.form.email.trim()) return 1
    return 5
})

// ──────────────────────────────────────────
// Toggle helpers
// ──────────────────────────────────────────

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

// ──────────────────────────────────────────
// Select All / Clear All helpers
// ──────────────────────────────────────────

function selectAllSubjectAreas() {
    props.form.subject_areas.splice(0, props.form.subject_areas.length, ...props.subjectAreas.map(a => a.id))
}

function clearAllSubjectAreas() {
    props.form.subject_areas.splice(0, props.form.subject_areas.length)
}

const allSubjectAreasSelected = computed(() =>
    props.subjectAreas.length > 0 && props.form.subject_areas.length === props.subjectAreas.length
)

function selectAllInstrumentsInFamily(family: string) {
    const familyIds = instrumentsByFamily[family].map(i => i.id)
    for (const id of familyIds) {
        if (!props.form.instruments.includes(id)) {
            props.form.instruments.push(id)
        }
    }
}

function clearAllInstrumentsInFamily(family: string) {
    const familyIds = new Set(instrumentsByFamily[family].map(i => i.id))
    const remaining = props.form.instruments.filter(id => !familyIds.has(id))
    props.form.instruments.splice(0, props.form.instruments.length, ...remaining)
}

function allInFamilySelected(family: string): boolean {
    return instrumentsByFamily[family].every(i => props.form.instruments.includes(i.id))
}

function selectAllInstruments() {
    props.form.instruments.splice(0, props.form.instruments.length, ...props.instruments.map(i => i.id))
}

function clearAllInstruments() {
    props.form.instruments.splice(0, props.form.instruments.length)
}

const allInstrumentsSelected = computed(() =>
    props.instruments.length > 0 && props.form.instruments.length === props.instruments.length
)

// ──────────────────────────────────────────
// Review helpers
// ──────────────────────────────────────────

const selectedSchoolNames = computed(() =>
    props.schools.filter(s => props.form.schools.includes(s.id)).map(s => s.name)
)

const selectedInstrumentNames = computed(() =>
    props.instruments.filter(i => props.form.instruments.includes(i.id)).map(i => i.name)
)

const selectedSubjectAreaNames = computed(() =>
    props.subjectAreas.filter(a => props.form.subject_areas.includes(a.id)).map(a => a.name)
)
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <!-- Step Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <button
                    v-for="(step, idx) in steps"
                    :key="step.id"
                    type="button"
                    @click="goToStep(step.id)"
                    class="group flex flex-1 flex-col items-center gap-1.5"
                    :class="step.id <= furthestStep ? 'cursor-pointer' : 'cursor-not-allowed'"
                >
                    <!-- Circle + connecting line -->
                    <div class="flex w-full items-center">
                        <div
                            v-if="idx > 0"
                            class="h-0.5 flex-1 transition-colors duration-300"
                            :class="currentStep > idx ? 'bg-brand-accent' : 'bg-brand-border'"
                        />
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-300"
                            :class="
                                currentStep === step.id
                                    ? 'border-brand-accent bg-brand-accent text-white scale-110 shadow-lg'
                                    : currentStep > step.id
                                        ? 'border-brand-accent bg-brand-accent/10 text-brand-accent'
                                        : 'border-brand-border bg-brand-surface text-brand-text-soft'
                            "
                        >
                            <Check v-if="currentStep > step.id" class="h-5 w-5" />
                            <span v-else>{{ step.id }}</span>
                        </div>
                        <div
                            v-if="idx < steps.length - 1"
                            class="h-0.5 flex-1 transition-colors duration-300"
                            :class="currentStep > step.id ? 'bg-brand-accent' : 'bg-brand-border'"
                        />
                    </div>
                    <!-- Label -->
                    <span
                        class="hidden text-xs font-medium transition-colors sm:block"
                        :class="currentStep === step.id ? 'text-brand-accent' : 'text-brand-text-soft'"
                    >
                        {{ step.title }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Step Content -->
        <div class="min-h-[300px]">
            <!-- Step 1: Basic Details -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-x-8 opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="-translate-x-8 opacity-0"
                mode="out-in"
            >
                <div v-if="currentStep === 1" key="step1" class="rounded-xl border border-brand-border bg-brand-surface p-5">
                    <h3 class="mb-1 text-xl font-semibold text-brand-text">Basic Details</h3>
                    <p class="mb-5 text-base text-brand-text-soft">Enter the teacher's contact information</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Name *</label>
                            <input v-model="form.name" type="text" required
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                placeholder="Full name" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-brand-danger">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Email *</label>
                            <input v-model="form.email" type="email" required
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                placeholder="Email address" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-brand-danger">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">Phone</label>
                            <input v-model="form.phone" type="text"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                placeholder="Phone number" />
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-medium text-brand-text">How they found us</label>
                            <input v-model="form.how_they_found_us" type="text"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                placeholder="e.g. Trinity website, Word of mouth" />
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Status & Notes -->
                <div v-else-if="currentStep === 2" key="step2" class="rounded-xl border border-brand-border bg-brand-surface p-5">
                    <h3 class="mb-1 text-xl font-semibold text-brand-text">Contact Status</h3>
                    <p class="mb-5 text-base text-brand-text-soft">How have you been in touch with this teacher?</p>
                    <div class="space-y-4">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-brand-border p-4 transition-colors hover:bg-brand-surface-soft"
                            :class="form.contacted_by_email ? 'border-brand-accent bg-brand-accent/5' : ''">
                            <input type="checkbox" v-model="form.contacted_by_email" class="h-5 w-5 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                            <span class="text-lg text-brand-text">Contacted by email</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-brand-border p-4 transition-colors hover:bg-brand-surface-soft"
                            :class="form.spoken_on_phone ? 'border-brand-accent bg-brand-accent/5' : ''">
                            <input type="checkbox" v-model="form.spoken_on_phone" class="h-5 w-5 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                            <span class="text-lg text-brand-text">Spoken on phone</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-brand-border p-4 transition-colors hover:bg-brand-surface-soft"
                            :class="form.met_face_to_face ? 'border-brand-accent bg-brand-accent/5' : ''">
                            <input type="checkbox" v-model="form.met_face_to_face" class="h-5 w-5 rounded border-brand-border text-brand-accent focus:ring-brand-accent" />
                            <span class="text-lg text-brand-text">Met face to face</span>
                        </label>
                    </div>
                    <div class="mt-6">
                        <label class="mb-1 block text-lg font-medium text-brand-text">Notes</label>
                        <textarea v-model="form.notes" rows="3"
                            class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            placeholder="Any notes about this teacher..."></textarea>
                    </div>
                </div>

                <!-- Step 3: Schools -->
                <div v-else-if="currentStep === 3" key="step3" class="rounded-xl border border-brand-border bg-brand-surface p-5">
                    <h3 class="mb-1 text-xl font-semibold text-brand-text">Schools</h3>
                    <p class="mb-5 text-base text-brand-text-soft">Select the schools this teacher is linked to</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="school in schools"
                            :key="school.id"
                            type="button"
                            @click="toggleSchool(school.id)"
                            class="cursor-pointer rounded-full border px-4 py-2.5 text-base font-medium transition-all"
                            :class="form.schools.includes(school.id)
                                ? 'border-brand-accent bg-brand-accent text-white shadow-md'
                                : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent hover:text-brand-accent'"
                        >
                            {{ school.name }}
                        </button>
                    </div>
                    <p v-if="form.schools.length" class="mt-4 text-base text-brand-accent">
                        {{ form.schools.length }} school{{ form.schools.length !== 1 ? 's' : '' }} selected
                    </p>
                </div>

                <!-- Step 4: Instruments & Subject Areas -->
                <div v-else-if="currentStep === 4" key="step4" class="space-y-6">
                    <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <h3 class="mb-1 text-xl font-semibold text-brand-text">Instruments</h3>
                                <p class="text-base text-brand-text-soft">What instruments does this teacher cover?</p>
                            </div>
                            <button
                                type="button"
                                @click="allInstrumentsSelected ? clearAllInstruments() : selectAllInstruments()"
                                class="cursor-pointer rounded-lg border border-brand-accent px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="allInstrumentsSelected
                                    ? 'bg-brand-accent text-white hover:bg-brand-accent-dark'
                                    : 'text-brand-accent hover:bg-brand-accent/10'"
                            >
                                {{ allInstrumentsSelected ? 'Clear All' : 'Select All' }}
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(instruments, family) in instrumentsByFamily" :key="family">
                                <div class="mb-2 flex items-center gap-2">
                                    <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">{{ family }}</p>
                                    <button
                                        type="button"
                                        @click="allInFamilySelected(family as string) ? clearAllInstrumentsInFamily(family as string) : selectAllInstrumentsInFamily(family as string)"
                                        class="cursor-pointer rounded px-2 py-0.5 text-xs font-medium text-brand-accent transition-colors hover:bg-brand-accent/10"
                                    >
                                        {{ allInFamilySelected(family as string) ? 'clear' : 'all' }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="instrument in instruments"
                                        :key="instrument.id"
                                        type="button"
                                        @click="toggleInstrument(instrument.id)"
                                        class="cursor-pointer rounded-full border px-4 py-2 text-sm font-medium transition-all"
                                        :class="form.instruments.includes(instrument.id)
                                            ? 'border-brand-accent bg-brand-accent text-white shadow-md'
                                            : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent hover:text-brand-accent'"
                                    >
                                        {{ instrument.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-brand-border bg-brand-surface p-5">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <h3 class="mb-1 text-xl font-semibold text-brand-text">Subject Areas</h3>
                                <p class="text-base text-brand-text-soft">Which Trinity subject areas?</p>
                            </div>
                            <button
                                type="button"
                                @click="allSubjectAreasSelected ? clearAllSubjectAreas() : selectAllSubjectAreas()"
                                class="cursor-pointer rounded-lg border border-brand-accent px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="allSubjectAreasSelected
                                    ? 'bg-brand-accent text-white hover:bg-brand-accent-dark'
                                    : 'text-brand-accent hover:bg-brand-accent/10'"
                            >
                                {{ allSubjectAreasSelected ? 'Clear All' : 'Select All' }}
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="area in subjectAreas"
                                :key="area.id"
                                type="button"
                                @click="toggleSubjectArea(area.id)"
                                class="cursor-pointer rounded-full border px-4 py-2.5 text-base font-medium transition-all"
                                :class="form.subject_areas.includes(area.id)
                                    ? 'border-brand-accent bg-brand-accent text-white shadow-md'
                                    : 'border-brand-border bg-brand-surface text-brand-text-soft hover:border-brand-accent hover:text-brand-accent'"
                            >
                                {{ area.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div v-else-if="currentStep === 5" key="step5" class="rounded-xl border border-brand-border bg-brand-surface p-5">
                    <h3 class="mb-1 text-xl font-semibold text-brand-text">Review & Confirm</h3>
                    <p class="mb-5 text-base text-brand-text-soft">Check everything looks right before saving</p>

                    <div class="space-y-4">
                        <!-- Details summary -->
                        <div class="flex items-start justify-between rounded-lg bg-brand-surface-soft p-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Teacher Details</p>
                                <p class="mt-1 text-lg font-medium text-brand-text">{{ form.name }}</p>
                                <p class="text-base text-brand-text-soft">{{ form.email }}</p>
                                <p v-if="form.phone" class="text-base text-brand-text-soft">{{ form.phone }}</p>
                                <p v-if="form.how_they_found_us" class="text-base text-brand-text-soft">Found via: {{ form.how_they_found_us }}</p>
                            </div>
                            <button type="button" @click="goToStep(1)" class="cursor-pointer text-sm font-medium text-brand-accent hover:underline">Edit</button>
                        </div>

                        <!-- Contact status -->
                        <div class="flex items-start justify-between rounded-lg bg-brand-surface-soft p-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Contact Status</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span v-if="form.contacted_by_email" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">Email</span>
                                    <span v-if="form.spoken_on_phone" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">Phone</span>
                                    <span v-if="form.met_face_to_face" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">Face to face</span>
                                    <span v-if="!form.contacted_by_email && !form.spoken_on_phone && !form.met_face_to_face" class="text-base text-brand-text-soft">No contact yet</span>
                                </div>
                                <p v-if="form.notes" class="mt-2 text-base text-brand-text-soft">Notes: {{ form.notes }}</p>
                            </div>
                            <button type="button" @click="goToStep(2)" class="cursor-pointer text-sm font-medium text-brand-accent hover:underline">Edit</button>
                        </div>

                        <!-- Schools -->
                        <div class="flex items-start justify-between rounded-lg bg-brand-surface-soft p-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Schools</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span v-for="name in selectedSchoolNames" :key="name" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">{{ name }}</span>
                                    <span v-if="!selectedSchoolNames.length" class="text-base text-brand-text-soft">None selected</span>
                                </div>
                            </div>
                            <button type="button" @click="goToStep(3)" class="cursor-pointer text-sm font-medium text-brand-accent hover:underline">Edit</button>
                        </div>

                        <!-- Instruments & Subject Areas -->
                        <div class="flex items-start justify-between rounded-lg bg-brand-surface-soft p-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Instruments</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span v-for="name in selectedInstrumentNames" :key="name" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">{{ name }}</span>
                                    <span v-if="!selectedInstrumentNames.length" class="text-base text-brand-text-soft">None selected</span>
                                </div>
                                <p class="mt-3 text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Subject Areas</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span v-for="name in selectedSubjectAreaNames" :key="name" class="rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-medium text-brand-accent">{{ name }}</span>
                                    <span v-if="!selectedSubjectAreaNames.length" class="text-base text-brand-text-soft">None selected</span>
                                </div>
                            </div>
                            <button type="button" @click="goToStep(4)" class="cursor-pointer text-sm font-medium text-brand-accent hover:underline">Edit</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-6 flex items-center justify-between">
            <div>
                <MyButtonConstructor
                    v-if="currentStep > 1"
                    variant="ghost"
                    size="large"
                    :icon="ChevronLeft"
                    @click="prevStep"
                >
                    Back
                </MyButtonConstructor>
                <Link v-else href="/admin/teachers">
                    <MyButtonConstructor variant="ghost" size="large" :icon="X">
                        Cancel
                    </MyButtonConstructor>
                </Link>
            </div>

            <div class="flex items-center gap-3">
                <!-- Step indicator on mobile -->
                <span class="text-base text-brand-text-soft sm:hidden">
                    {{ currentStep }} / {{ steps.length }}
                </span>

                <MyButtonConstructor
                    v-if="currentStep < steps.length"
                    variant="primary"
                    size="large"
                    :icon="ChevronRight"
                    icon-position="right"
                    :disabled="!canProceed()"
                    @click="nextStep"
                >
                    Next
                </MyButtonConstructor>

                <MyButtonConstructor
                    v-else
                    variant="success"
                    size="large"
                    :icon="Save"
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : submitLabel }}
                </MyButtonConstructor>
            </div>
        </div>
    </form>
</template>
