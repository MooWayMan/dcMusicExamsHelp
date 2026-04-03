<!-- resources/js/pages/admin/SessionLogs/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Clock, Plus, Trash2, Edit3, CheckSquare } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyTextareaConstructor from '@/components/reusables/MyTextareaConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface LogEntry {
    id: number
    date: string
    dateFormatted: string
    hours: number
    tasks: number
    notes: string | null
}

interface ChartPoint {
    date: string
    fullDate: string
    hours: number
    tasks: number
    notes: string | null
}

const props = defineProps<{
    logs: LogEntry[]
    chartData: ChartPoint[]
    totalHours: number
    totalDays: number
    totalTasksCompleted: number
}>()

const { animClass } = usePageAnimation()

// Form state
const showForm = ref(false)
const editingId = ref<number | null>(null)
const form = ref({
    date: new Date().toISOString().split('T')[0],
    hours: '',
    notes: '',
})

const maxHours = computed(() => {
    if (props.chartData.length === 0) return 10
    return Math.ceil(Math.max(...props.chartData.map(d => d.hours)) / 2) * 2
})

const avgHours = computed(() => {
    if (props.totalDays === 0) return 0
    return (props.totalHours / props.totalDays).toFixed(1)
})

function barHeight(hours: number): string {
    const percentage = (hours / maxHours.value) * 100
    return `${Math.max(percentage, 4)}%`
}

function openAddForm() {
    editingId.value = null
    form.value = {
        date: new Date().toISOString().split('T')[0],
        hours: '',
        notes: '',
    }
    showForm.value = true
}

function openEditForm(log: LogEntry) {
    editingId.value = log.id
    form.value = {
        date: log.date,
        hours: String(log.hours),
        notes: log.notes ?? '',
    }
    showForm.value = true
}

function submitForm() {
    const data = {
        date: form.value.date,
        hours: parseFloat(form.value.hours),
        notes: form.value.notes || null,
    }

    if (editingId.value) {
        router.put(`/admin/session-logs/${editingId.value}`, data, {
            onSuccess: () => { showForm.value = false },
        })
    } else {
        router.post('/admin/session-logs', data, {
            onSuccess: () => { showForm.value = false },
        })
    }
}

function deleteLog(id: number) {
    if (confirm('Delete this session log?')) {
        router.delete(`/admin/session-logs/${id}`)
    }
}

const tableColumns = [
    { key: 'dateFormatted', title: 'Date', sortable: true },
    { key: 'hours', title: 'Hours', sortable: true, align: 'center' as const },
    { key: 'tasks', title: 'Tasks', sortable: true, align: 'center' as const },
    { key: 'notes', title: 'Notes', sortable: false },
    { key: 'actions', title: '', sortable: false, align: 'right' as const, width: '100px' },
]
</script>

<template>
    <div>
        <PageHeader
            title="Session Hours"
            subtitle="Daily working hours tracked across all sessions"
            :showIcon="true"
        >
            <template #actions>
                <MyButtonConstructor
                    variant="primary"
                    size="small"
                    :icon="Plus"
                    @click="openAddForm"
                >
                    Log Hours
                </MyButtonConstructor>
            </template>
        </PageHeader>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <!-- Stats row -->
            <div :class="animClass('fade-up', 1)" class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand-accent/10 p-3">
                            <Clock class="h-6 w-6 text-brand-accent" />
                        </div>
                        <div>
                            <p class="text-sm text-brand-text-soft">Total Hours</p>
                            <p class="text-2xl font-bold text-brand-primary">{{ totalHours }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand-success-soft p-3">
                            <Clock class="h-6 w-6 text-brand-success" />
                        </div>
                        <div>
                            <p class="text-sm text-brand-text-soft">Days Worked</p>
                            <p class="text-2xl font-bold text-brand-primary">{{ totalDays }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand-teal/10 p-3">
                            <Clock class="h-6 w-6 text-brand-teal" />
                        </div>
                        <div>
                            <p class="text-sm text-brand-text-soft">Avg Hours/Day</p>
                            <p class="text-2xl font-bold text-brand-primary">{{ avgHours }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand-primary/10 p-3">
                            <CheckSquare class="h-6 w-6 text-brand-primary" />
                        </div>
                        <div>
                            <p class="text-sm text-brand-text-soft">Tasks Completed</p>
                            <p class="text-2xl font-bold text-brand-primary">{{ totalTasksCompleted }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div :class="animClass('fade-up', 2)" class="mb-8 rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-xl">
                <MyTextConstructor variant="button-lg" spacing="tight" class="mb-6">
                    <template #myTitle>Hours Per Day</template>
                </MyTextConstructor>

                <div class="flex items-end gap-2 sm:gap-3" style="height: 280px;">
                    <div
                        v-for="point in chartData"
                        :key="point.fullDate"
                        class="group relative flex flex-1 flex-col items-center justify-end"
                        style="height: 100%;"
                    >
                        <!-- Tooltip -->
                        <div class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lg bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">
                            {{ point.hours }}h · {{ point.tasks }} task{{ point.tasks !== 1 ? 's' : '' }} — {{ point.date }}
                        </div>

                        <!-- Bar -->
                        <div
                            class="w-full rounded-t-lg bg-gradient-to-t from-brand-primary to-brand-accent transition-all duration-500 group-hover:from-brand-accent group-hover:to-brand-accent"
                            :style="{ height: barHeight(point.hours) }"
                        ></div>

                        <!-- Hours label -->
                        <p class="mt-1 text-xs font-bold text-brand-primary sm:text-sm">
                            {{ point.hours }}
                        </p>

                        <!-- Date label -->
                        <p class="text-[10px] text-brand-text-soft sm:text-xs">
                            {{ point.date }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <Teleport to="body">
                <div
                    v-if="showForm"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    @click.self="showForm = false"
                >
                    <div class="w-full max-w-md rounded-2xl bg-brand-surface p-6 shadow-2xl">
                        <MyTextConstructor variant="button-lg" spacing="tight" class="mb-4">
                            <template #myTitle>
                                {{ editingId ? 'Edit Session Log' : 'Log Hours' }}
                            </template>
                        </MyTextConstructor>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <MyInputConstructor
                                v-model="form.date"
                                type="date"
                                label="Date"
                                size="small"
                                required
                            />
                            <MyInputConstructor
                                v-model="form.hours"
                                type="number"
                                label="Hours"
                                size="small"
                                placeholder="e.g. 4.5"
                                required
                            />
                            <MyTextareaConstructor
                                v-model="form.notes"
                                label="Notes (optional)"
                                size="small"
                                :rows="3"
                                placeholder="What was worked on..."
                            />
                            <div class="flex gap-3">
                                <MyButtonConstructor
                                    type="submit"
                                    variant="success"
                                    size="small"
                                >
                                    {{ editingId ? 'Update' : 'Save' }}
                                </MyButtonConstructor>
                                <MyButtonConstructor
                                    variant="ghost"
                                    size="small"
                                    @click="showForm = false"
                                >
                                    Cancel
                                </MyButtonConstructor>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Table -->
            <div :class="animClass('fade-up', 3)">
                <MyTableConstructor
                    :data="logs"
                    :columns="tableColumns"
                    rowKey="id"
                    title="Session Log"
                    subtitle="All recorded working days"
                    headerColor="bg-brand-primary"
                    headerTextColor="text-brand-text-inverse"
                    size="small"
                    striped
                    hoverable
                >
                    <template #cell-hours="{ value }">
                        <span class="font-bold text-brand-accent">{{ value }}h</span>
                    </template>
                    <template #cell-tasks="{ value }">
                        <span class="font-semibold text-brand-teal">{{ value }}</span>
                    </template>
                    <template #cell-notes="{ value }">
                        <span class="text-sm text-brand-text-soft">{{ value || '—' }}</span>
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                @click="openEditForm(row)"
                                class="rounded-lg p-1.5 text-brand-text-soft transition-colors hover:bg-brand-bg hover:text-brand-accent"
                            >
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button
                                @click="deleteLog(row.id)"
                                class="rounded-lg p-1.5 text-brand-text-soft transition-colors hover:bg-brand-danger-soft hover:text-brand-danger"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </template>
                </MyTableConstructor>
            </div>
        </div>
    </div>
</template>
