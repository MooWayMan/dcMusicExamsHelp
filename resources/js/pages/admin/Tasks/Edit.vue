<!-- resources/js/pages/admin/Tasks/Edit.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import TaskForm from './partials/TaskForm.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface TaskData {
    id: number
    title: string
    detail: string | null
    notes: string | null
    priority: string
    status: string
    assigned_to: string
    category: string | null
}

const props = defineProps<{
    task: TaskData
    priorities: string[]
    statuses: string[]
    categories: string[]
}>()

const { animClass } = usePageAnimation()

const form = useForm({
    title: props.task.title,
    detail: props.task.detail ?? '',
    notes: props.task.notes ?? '',
    priority: props.task.priority,
    status: props.task.status,
    assigned_to: props.task.assigned_to,
    category: props.task.category ?? '',
})

function submit() {
    form.put(`/admin/tasks/${props.task.id}`)
}

function goBack() { window.history.back() }
</script>

<template>
    <div class="mx-auto w-full max-w-screen-lg px-4 py-6 sm:px-6 lg:px-8">
        <div :class="['mb-6 flex items-center gap-4', animClass('fade-up', 0)]">
            <button @click="goBack" class="cursor-pointer rounded-lg p-2 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                <ArrowLeft class="h-5 w-5" />
            </button>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Edit Task</h1>
            </div>
        </div>

        <div :class="animClass('fade-up', 1)">
            <TaskForm
                :form="form"
                :priorities="priorities"
                :statuses="statuses"
                :categories="categories"
                submit-label="Update Task"
                :is-edit="true"
                @submit="submit"
            />
        </div>
    </div>
</template>
