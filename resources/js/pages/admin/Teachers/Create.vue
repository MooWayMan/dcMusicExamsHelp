<!-- resources/js/pages/admin/Teachers/Create.vue -->
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

const props = defineProps<{
    instruments: Instrument[]
    schools: SchoolOption[]
    subjectAreas: SubjectAreaOption[]
}>()

const form = useForm({
    name: '',
    email: '',
    phone: '',
    notes: '',
    how_they_found_us: '',
    met_face_to_face: false,
    spoken_on_phone: false,
    contacted_by_email: false,
    instruments: [] as number[],
    schools: [] as number[],
    subject_areas: [] as number[],
})

function submit() {
    form.post('/admin/teachers')
}
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <Link href="/admin/teachers" class="rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Add New Teacher</h1>
            </div>
        </div>

        <TeacherForm
            :form="form"
            :instruments="instruments"
            :schools="schools"
            :subject-areas="subjectAreas"
            submit-label="Add Teacher"
            @submit="submit"
        />
    </div>
</template>
