<!-- resources/js/pages/admin/Tasks/partials/TaskForm.vue -->
<script setup lang="ts">
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

const props = defineProps<{
    form: any
    priorities: string[]
    statuses?: string[]
    categories: string[]
    submitLabel: string
    isEdit?: boolean
}>()

const emit = defineEmits<{
    submit: []
}>()

const assigneeOptions = ['Paul', 'Spider-Man', 'Paul + SM']

function handleSubmit() {
    emit('submit')
}
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6 rounded-xl border border-brand-border bg-brand-surface p-6">
        <!-- Title -->
        <div>
            <label class="mb-2 block text-lg font-semibold text-brand-text">Task Title</label>
            <input v-model="form.title" type="text" required
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                placeholder="What needs to be done?" />
            <p v-if="form.errors.title" class="mt-1 text-sm text-brand-danger">{{ form.errors.title }}</p>
        </div>

        <!-- Detail -->
        <div>
            <label class="mb-2 block text-lg font-semibold text-brand-text">Detail</label>
            <textarea v-model="form.detail" rows="3"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                placeholder="Any extra details or notes..." />
            <p v-if="form.errors.detail" class="mt-1 text-sm text-brand-danger">{{ form.errors.detail }}</p>
        </div>

        <!-- Priority + Category + Assigned To row -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Priority -->
            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text">Priority</label>
                <select v-model="form.priority"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                    <option v-for="p in priorities" :key="p" :value="p">
                        {{ p.charAt(0).toUpperCase() + p.slice(1) }}
                    </option>
                </select>
            </div>

            <!-- Category -->
            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text">Category</label>
                <select v-model="form.category"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                    <option value="">None</option>
                    <option v-for="c in categories" :key="c" :value="c">
                        {{ c.charAt(0).toUpperCase() + c.slice(1) }}
                    </option>
                </select>
            </div>

            <!-- Assigned To -->
            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text">Assigned To</label>
                <select v-model="form.assigned_to"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                    <option v-for="a in assigneeOptions" :key="a" :value="a">{{ a }}</option>
                </select>
            </div>
        </div>

        <!-- Status (edit only) -->
        <div v-if="isEdit && statuses">
            <label class="mb-2 block text-lg font-semibold text-brand-text">Status</label>
            <div class="flex flex-wrap gap-3">
                <label v-for="s in statuses" :key="s"
                    class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-3 text-lg transition-colors"
                    :class="form.status === s ? 'border-brand-accent bg-brand-accent/10 text-brand-accent' : 'border-brand-border text-brand-text-soft hover:border-brand-accent'">
                    <input type="radio" v-model="form.status" :value="s" class="sr-only" />
                    <span class="font-medium">
                        {{ s === 'pending' ? 'To Do' : s === 'in_progress' ? 'In Progress' : 'Done' }}
                    </span>
                </label>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-2">
            <MyButtonConstructor type="submit" variant="primary" size="medium" :disabled="form.processing">
                {{ submitLabel }}
            </MyButtonConstructor>
            <p v-if="form.recentlySuccessful" class="text-base text-brand-success">Saved!</p>
        </div>
    </form>
</template>
