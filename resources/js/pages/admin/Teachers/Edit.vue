<!-- resources/js/pages/admin/Teachers/Edit.vue -->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import TeacherForm from './partials/TeacherForm.vue'

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

interface TeacherData {
    id: number
    name: string
    email: string
    phone: string | null
    notes: string | null
    how_they_found_us: string | null
    met_face_to_face: boolean
    spoken_on_phone: boolean
    contacted_by_email: boolean
    instruments: number[]
    schools: number[]
    subject_areas: number[]
}

const props = defineProps<{
    teacher: TeacherData
    instruments: Instrument[]
    schools: SchoolOption[]
    subjectAreas: SubjectAreaOption[]
}>()

const form = useForm({
    name: props.teacher.name,
    email: props.teacher.email,
    phone: props.teacher.phone ?? '',
    notes: props.teacher.notes ?? '',
    how_they_found_us: props.teacher.how_they_found_us ?? '',
    met_face_to_face: props.teacher.met_face_to_face,
    spoken_on_phone: props.teacher.spoken_on_phone,
    contacted_by_email: props.teacher.contacted_by_email,
    instruments: [...props.teacher.instruments],
    schools: [...props.teacher.schools],
    subject_areas: [...props.teacher.subject_areas],
})

function submit() {
    form.put(`/admin/teachers/${props.teacher.id}`)
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
                <h1 class="text-2xl font-bold text-brand-text">Edit {{ teacher.name }}</h1>
            </div>
        </div>

        <div :class="animClass('fade-up', 1)">
            <TeacherForm
                :form="form"
                :instruments="instruments"
                :schools="schools"
                :subject-areas="subjectAreas"
                submit-label="Save Changes"
                @submit="submit"
            />
        </div>
    </div>
</template>
