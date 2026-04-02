<!-- resources/js/pages/admin/Tasks/Create.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import TaskForm from './partials/TaskForm.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const props = defineProps<{
    priorities: string[]
    categories: string[]
}>()

const { animClass } = usePageAnimation()

const form = useForm({
    title: '',
    detail: '',
    notes: '',
    priority: 'medium',
    assigned_to: 'Paul',
    category: '',
})

function submit() {
    form.post('/admin/tasks')
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
                <h1 class="text-2xl font-bold text-brand-text">Add New Task</h1>
            </div>
        </div>

        <div :class="animClass('fade-up', 1)">
            <TaskForm
                :form="form"
                :priorities="priorities"
                :categories="categories"
                submit-label="Add Task"
                @submit="submit"
            />
        </div>
    </div>
</template>
