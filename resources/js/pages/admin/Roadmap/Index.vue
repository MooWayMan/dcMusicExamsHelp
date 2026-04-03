<!-- resources/js/pages/admin/Roadmap/Index.vue -->
<script setup lang="ts">
import { CheckCircle2, Circle, Rocket, Target, TrendingUp, Zap, Crown } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface Milestone {
    title: string
    done: boolean
}

interface Phase {
    number: number
    title: string
    status: 'complete' | 'active' | 'upcoming'
    subtitle: string
    milestones: Milestone[]
}

const props = defineProps<{
    phases: Phase[]
}>()

const { animClass } = usePageAnimation()

const phaseIcons = [Rocket, Target, Zap, TrendingUp, Crown]

function phaseProgress(phase: Phase): number {
    if (phase.milestones.length === 0) return 0
    const done = phase.milestones.filter(m => m.done).length
    return Math.round((done / phase.milestones.length) * 100)
}

function statusBadge(status: string): { label: string; classes: string } {
    switch (status) {
        case 'complete':
            return { label: 'Complete', classes: 'bg-brand-success-soft text-brand-success' }
        case 'active':
            return { label: 'In Progress', classes: 'bg-brand-accent/15 text-brand-accent' }
        default:
            return { label: 'Upcoming', classes: 'bg-brand-surface-soft text-brand-text-soft' }
    }
}

function phaseCardClasses(status: string): string {
    switch (status) {
        case 'complete':
            return 'border-brand-success/30 bg-brand-success-soft/20'
        case 'active':
            return 'border-brand-accent/30 bg-brand-accent/5 ring-2 ring-brand-accent/20'
        default:
            return 'border-brand-border bg-brand-surface'
    }
}

function progressBarColor(status: string): string {
    switch (status) {
        case 'complete': return 'bg-brand-success'
        case 'active': return 'bg-brand-accent'
        default: return 'bg-brand-text-soft'
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Roadmap" subtitle="musicExams.help — from foundation to scale" eyebrow="Admin" size="compact" />

        <!-- Phase timeline connector (desktop) -->
        <div :class="['mt-8', animClass('fade-up', 1)]">
            <!-- Progress overview bar -->
            <div class="mb-8 hidden items-center justify-between md:flex">
                <template v-for="(phase, i) in phases" :key="'bar-' + phase.number">
                    <div class="flex flex-col items-center gap-2">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full transition-all"
                            :class="{
                                'bg-brand-success text-brand-text-inverse': phase.status === 'complete',
                                'bg-brand-accent text-brand-text-inverse ring-4 ring-brand-accent/30': phase.status === 'active',
                                'bg-brand-surface-soft text-brand-text-soft border-2 border-brand-border': phase.status === 'upcoming',
                            }">
                            <component :is="phaseIcons[i]" class="h-5 w-5" />
                        </div>
                        <span class="text-xs font-semibold" :class="{
                            'text-brand-success': phase.status === 'complete',
                            'text-brand-accent': phase.status === 'active',
                            'text-brand-text-soft': phase.status === 'upcoming',
                        }">Phase {{ phase.number }}</span>
                    </div>
                    <div v-if="i < phases.length - 1" class="mx-2 h-1 flex-1 rounded-full bg-brand-surface-soft">
                        <div class="h-full rounded-full transition-all duration-700"
                            :class="phase.status === 'complete' ? 'bg-brand-success' : phase.status === 'active' ? 'bg-brand-accent w-1/2' : ''"
                            :style="{ width: phase.status === 'complete' ? '100%' : phase.status === 'active' ? '50%' : '0%' }">
                        </div>
                    </div>
                </template>
            </div>

            <!-- Phase cards -->
            <div class="space-y-6">
                <div v-for="(phase, i) in phases" :key="phase.number"
                    :class="['rounded-2xl border p-6 transition-all', phaseCardClasses(phase.status), animClass('fade-up', i + 1)]">

                    <!-- Phase header -->
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl"
                                :class="{
                                    'bg-brand-success text-brand-text-inverse': phase.status === 'complete',
                                    'bg-brand-accent text-brand-text-inverse': phase.status === 'active',
                                    'bg-brand-surface-soft text-brand-text-soft': phase.status === 'upcoming',
                                }">
                                <component :is="phaseIcons[i]" class="h-7 w-7" />
                            </div>
                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl font-bold text-brand-text sm:text-2xl">
                                        Phase {{ phase.number }}: {{ phase.title }}
                                    </h2>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadge(phase.status).classes">
                                        {{ statusBadge(phase.status).label }}
                                    </span>
                                </div>
                                <p class="mt-1 text-base text-brand-text-soft">{{ phase.subtitle }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold" :class="{
                                'text-brand-success': phase.status === 'complete',
                                'text-brand-accent': phase.status === 'active',
                                'text-brand-text-soft': phase.status === 'upcoming',
                            }">{{ phaseProgress(phase) }}%</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-brand-surface-soft">
                        <div class="h-full rounded-full transition-all duration-1000 ease-out"
                            :class="progressBarColor(phase.status)"
                            :style="{ width: phaseProgress(phase) + '%' }">
                        </div>
                    </div>

                    <!-- Milestones grid -->
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-for="milestone in phase.milestones" :key="milestone.title"
                            class="flex items-start gap-3 rounded-lg p-2 transition-colors"
                            :class="milestone.done ? 'opacity-70' : ''">
                            <CheckCircle2 v-if="milestone.done" class="mt-0.5 h-5 w-5 shrink-0 text-brand-success" />
                            <Circle v-else class="mt-0.5 h-5 w-5 shrink-0 text-brand-border" />
                            <span class="text-base" :class="milestone.done ? 'text-brand-text-soft line-through' : 'text-brand-text'">
                                {{ milestone.title }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
