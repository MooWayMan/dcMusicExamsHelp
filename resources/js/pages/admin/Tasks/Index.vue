<!-- resources/js/pages/admin/Tasks/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, reactive, watch, computed } from 'vue'
import { Search, Plus, Pencil, Trash2, CheckCircle2, Circle, ChevronLeft, ChevronRight, Clock, X, SlidersHorizontal } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface Task {
    id: number
    title: string
    detail: string | null
    priority: string
    status: string
    assigned_to: string
    category: string | null
    completed_at: string | null
    completed_today: boolean
    created_at: string
}

interface PaginatedData {
    data: Task[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    tasks: PaginatedData
    summary: { total: number; pending: number; in_progress: number; completed: number }
    filters: { search: string | null; priority: string | null; status: string | null; category: string | null; sort: string; direction: string }
    priorities: string[]
    statuses: string[]
    categories: string[]
}>()

const { animClass } = usePageAnimation()

const search = ref(props.filters.search ?? '')
const showFilters = ref(false)

// Count active filters (excluding defaults)
const activeFilterCount = computed(() => {
    let count = 0
    if (props.filters.priority) count++
    if (props.filters.status && props.filters.status !== 'all') count++
    if (props.filters.category) count++
    return count
})
let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters({ search: value || undefined })
    }, 300)
})

function applyFilters(overrides: Record<string, string | undefined> = {}) {
    router.get('/admin/tasks', {
        search: search.value || undefined,
        priority: props.filters.priority || undefined,
        status: props.filters.status || undefined,
        category: props.filters.category || undefined,
        ...overrides,
    }, { preserveState: true, replace: true })
}

function filterByPriority(priority: string | null) {
    applyFilters({ priority: priority || undefined })
    showFilters.value = false
}

function filterByStatus(status: string | null) {
    applyFilters({ status: status || undefined })
    showFilters.value = false
}

function filterByCategory(category: string | null) {
    applyFilters({ category: category || undefined })
    showFilters.value = false
}

// Track which tasks are in the "just completed" transition state
const justCompleted = reactive<Record<number, boolean>>({})
const justReopened = reactive<Record<number, boolean>>({})

async function toggleTask(task: Task) {
    try {
        const response = await fetch(`/admin/tasks/${task.id}/toggle`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
        })
        if (response.ok) {
            const wasCompleted = task.status === 'completed'

            if (wasCompleted) {
                // Reopening — flash green, restore styling, then after delay move back up
                justReopened[task.id] = true
                router.reload({ only: ['summary'] })
                setTimeout(() => {
                    delete justReopened[task.id]
                    router.reload({ only: ['tasks', 'summary'] })
                }, 1500)
            } else {
                // Completing — grey out immediately, then reload after delay so it slides to bottom
                justCompleted[task.id] = true
                // Update summary count immediately for responsiveness
                router.reload({ only: ['summary'] })
                setTimeout(() => {
                    delete justCompleted[task.id]
                    router.reload({ only: ['tasks', 'summary'] })
                }, 1500)
            }
        }
    } catch (e) {
        console.error('Toggle failed', e)
    }
}

function deleteTask(task: Task) {
    if (confirm(`Are you sure you want to remove "${task.title}"?`)) {
        const params = new URLSearchParams()
        if (search.value) params.set('search', search.value)
        if (props.filters.priority) params.set('priority', props.filters.priority)
        if (props.filters.status) params.set('status', props.filters.status)
        if (props.filters.category) params.set('category', props.filters.category)
        const query = params.toString() ? `?${params.toString()}` : ''
        router.delete(`/admin/tasks/${task.id}${query}`)
    }
}

function priorityClasses(priority: string): string {
    switch (priority) {
        case 'high': return 'bg-brand-danger-soft text-brand-danger'
        case 'medium': return 'bg-brand-accent/10 text-brand-accent'
        case 'low': return 'bg-brand-surface-soft text-brand-text-soft'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

function statusLabel(status: string): string {
    switch (status) {
        case 'pending': return 'To Do'
        case 'in_progress': return 'In Progress'
        case 'completed': return 'Done'
        default: return status
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Tasks" subtitle="Launch checklist and ongoing task management" eyebrow="Admin" size="compact">
            <template #actions>
                <Link href="/admin/tasks/create">
                    <MyButtonConstructor variant="primary" size="medium" :icon="Plus">
                        Add Task
                    </MyButtonConstructor>
                </Link>
            </template>
        </PageHeader>

        <!-- Summary: compact pills on mobile, full cards on desktop -->
        <div :class="['mt-6', animClass('fade-up', 1)]">
            <!-- Mobile: inline pills -->
            <div class="flex flex-wrap gap-2 md:hidden">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-border bg-brand-surface px-3 py-1">
                    <span class="text-xs font-medium text-brand-text-soft">Total</span>
                    <span class="text-sm font-bold text-brand-text">{{ summary.total }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-border bg-brand-surface px-3 py-1">
                    <span class="text-xs font-medium text-brand-text-soft">To Do</span>
                    <span class="text-sm font-bold text-brand-accent">{{ summary.pending }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-border bg-brand-surface px-3 py-1">
                    <span class="text-xs font-medium text-brand-text-soft">In Progress</span>
                    <span class="text-sm font-bold text-brand-teal">{{ summary.in_progress }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-border bg-brand-surface px-3 py-1">
                    <span class="text-xs font-medium text-brand-text-soft">Done</span>
                    <span class="text-sm font-bold text-brand-success">{{ summary.completed }}</span>
                </span>
            </div>
            <!-- Desktop: full cards -->
            <div class="hidden md:grid md:grid-cols-4 md:gap-4">
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <p class="text-base font-medium text-brand-text-soft">Total</p>
                    <p class="mt-1 text-3xl font-bold text-brand-text">{{ summary.total }}</p>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <p class="text-base font-medium text-brand-text-soft">To Do</p>
                    <p class="mt-1 text-3xl font-bold text-brand-accent">{{ summary.pending }}</p>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <p class="text-base font-medium text-brand-text-soft">In Progress</p>
                    <p class="mt-1 text-3xl font-bold text-brand-teal">{{ summary.in_progress }}</p>
                </div>
                <div class="rounded-xl border border-brand-border bg-brand-surface p-4">
                    <p class="text-base font-medium text-brand-text-soft">Done</p>
                    <p class="mt-1 text-3xl font-bold text-brand-success">{{ summary.completed }}</p>
                </div>
            </div>
        </div>

        <!-- Search + Filters -->
        <div :class="['mt-4 md:mt-6', animClass('fade-up', 2)]">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                    <input v-model="search" type="text" placeholder="Search tasks..."
                        class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-10 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
                    <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer rounded-full p-0.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text transition-colors">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <!-- Mobile: filter toggle button -->
                <button @click="showFilters = !showFilters"
                    class="relative cursor-pointer rounded-lg border border-brand-border bg-brand-surface p-3 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text transition-colors md:hidden">
                    <SlidersHorizontal class="h-5 w-5" />
                    <span v-if="activeFilterCount > 0" class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-brand-accent text-[10px] font-bold text-brand-text-inverse">
                        {{ activeFilterCount }}
                    </span>
                </button>
            </div>

            <!-- Mobile: collapsible filter panel -->
            <div v-show="showFilters" class="mt-3 space-y-3 rounded-xl border border-brand-border bg-brand-surface p-4 md:hidden">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Status</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="s in [{ label: 'All', value: 'all' }, { label: 'Active', value: 'active' }, { label: 'Done', value: 'completed' }]" :key="s.value"
                            @click="filterByStatus(s.value)"
                            class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="filters.status === s.value ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Priority</p>
                    <div class="flex flex-wrap gap-1">
                        <button @click="filterByPriority(null)"
                            class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="!filters.priority ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                            All
                        </button>
                        <button v-for="p in priorities" :key="p"
                            @click="filterByPriority(p)"
                            class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="filters.priority === p ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                            {{ p.charAt(0).toUpperCase() + p.slice(1) }}
                        </button>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-text-soft">Category</p>
                    <div class="flex flex-wrap gap-1">
                        <button @click="filterByCategory(null)"
                            class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="!filters.category ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                            All
                        </button>
                        <button v-for="c in categories" :key="c"
                            @click="filterByCategory(c)"
                            class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="filters.category === c ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                            {{ c.charAt(0).toUpperCase() + c.slice(1) }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop: inline filter pills -->
            <div class="mt-4 hidden flex-wrap items-center gap-4 md:flex">
                <!-- Status filter -->
                <div class="flex gap-1">
                    <button v-for="s in [{ label: 'All', value: 'all' }, { label: 'Active', value: 'active' }, { label: 'Done', value: 'completed' }]" :key="s.value"
                        @click="filterByStatus(s.value)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="filters.status === s.value ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                        {{ s.label }}
                    </button>
                </div>

                <!-- Priority filter -->
                <div class="flex gap-1">
                    <button @click="filterByPriority(null)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="!filters.priority ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                        All Priority
                    </button>
                    <button v-for="p in priorities" :key="p"
                        @click="filterByPriority(p)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="filters.priority === p ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                        {{ p.charAt(0).toUpperCase() + p.slice(1) }}
                    </button>
                </div>

                <!-- Category filter -->
                <div class="flex flex-wrap gap-1">
                    <button @click="filterByCategory(null)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="!filters.category ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                        All Categories
                    </button>
                    <button v-for="c in categories" :key="c"
                        @click="filterByCategory(c)"
                        class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="filters.category === c ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                        {{ c.charAt(0).toUpperCase() + c.slice(1) }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Task list -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="tasks.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ tasks.current_page }} of {{ tasks.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="tasks.current_page > 1" :href="tasks.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="tasks.current_page < tasks.last_page" :href="tasks.links[tasks.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in tasks.links" :key="'top-' + link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>

            <!-- Task rows -->
            <div class="divide-y divide-brand-border">
                <div v-for="task in tasks.data" :key="task.id"
                    class="flex items-start gap-4 px-4 py-4 transition-all duration-700 ease-in-out hover:bg-brand-surface-soft"
                    :class="{
                        'opacity-40 bg-brand-surface-soft/50': (task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id] && !task.completed_today,
                        'bg-brand-accent/5 border-l-4 border-l-brand-accent': (task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id] && task.completed_today,
                        'bg-brand-success-soft/50': justReopened[task.id],
                    }">

                    <!-- Tick circle -->
                    <button @click="toggleTask(task)"
                        class="mt-0.5 cursor-pointer shrink-0 transition-all duration-500"
                        :disabled="!!justCompleted[task.id] || !!justReopened[task.id]">
                        <CheckCircle2 v-if="(task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id]" class="h-6 w-6 text-brand-success transition-all duration-500" />
                        <Circle v-else-if="justReopened[task.id]" class="h-6 w-6 text-brand-success transition-all duration-500" />
                        <Clock v-else-if="task.status === 'in_progress'" class="h-6 w-6 text-brand-teal" />
                        <Circle v-else class="h-6 w-6 text-brand-border hover:text-brand-accent transition-colors" />
                    </button>

                    <!-- Task content -->
                    <div class="min-w-0 flex-1 transition-all duration-500">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-lg font-semibold transition-all duration-500"
                                :class="{
                                    'text-brand-text': !(task.status === 'completed' || justCompleted[task.id]) || justReopened[task.id],
                                    'text-brand-text-soft line-through': (task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id] && !task.completed_today,
                                    'text-brand-text/70 line-through': (task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id] && task.completed_today,
                                }">
                                {{ task.title }}
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium transition-all duration-500"
                                :class="((task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id]) ? 'bg-brand-surface-soft text-brand-text-soft' : priorityClasses(task.priority)">
                                {{ task.priority.toUpperCase() }}
                            </span>
                            <!-- Completed today badge -->
                            <span v-if="task.completed_today && task.status === 'completed' && !justReopened[task.id] && !justCompleted[task.id]"
                                class="rounded-full bg-brand-accent/15 px-2 py-0.5 text-xs font-medium text-brand-accent">
                                Today
                            </span>
                            <!-- "Done!" flash text -->
                            <span v-if="justCompleted[task.id]" class="text-sm font-semibold text-brand-success animate-pulse">
                                Done!
                            </span>
                            <!-- "Reopened!" flash text -->
                            <span v-if="justReopened[task.id]" class="text-sm font-semibold text-brand-success animate-pulse">
                                Reopened!
                            </span>
                            <span v-if="task.category" class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs font-medium text-brand-text-soft">
                                {{ task.category }}
                            </span>
                            <!-- "Done!" flash text -->
                            <span v-if="justCompleted[task.id]" class="text-sm font-semibold text-brand-success animate-pulse">
                                Done!
                            </span>
                        </div>
                        <p v-if="task.detail" class="mt-1 text-base text-brand-text-soft transition-all duration-500"
                            :class="{ 'line-through': (task.status === 'completed' || justCompleted[task.id]) && !justReopened[task.id] }">
                            {{ task.detail }}
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-brand-text-soft">
                            <span>{{ task.assigned_to }}</span>
                            <span v-if="task.completed_at">Completed {{ task.completed_at }}</span>
                            <span v-else>Added {{ task.created_at }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex shrink-0 items-center gap-1">
                        <Link :href="`/admin/tasks/${task.id}/edit`" class="rounded p-1.5 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-accent">
                            <Pencil class="h-4 w-4" />
                        </Link>
                        <button @click="deleteTask(task)" class="cursor-pointer rounded p-1.5 text-brand-text-soft hover:bg-brand-danger-soft hover:text-brand-danger">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="!tasks.data.length" class="px-4 py-8 text-center text-base text-brand-text-soft">
                    No tasks found.
                </div>
            </div>

            <!-- Bottom Pagination -->
            <div v-if="tasks.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ tasks.current_page }} of {{ tasks.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="tasks.current_page > 1" :href="tasks.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="tasks.current_page < tasks.last_page" :href="tasks.links[tasks.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in tasks.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
