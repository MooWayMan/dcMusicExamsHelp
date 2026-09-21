<!-- resources/js/pages/admin/SiteStats/Index.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'

interface PageRow { path: string; hits: number }
interface EventRow { path: string; event: string; detail: string; hits: number }
interface DayRow { day: string; pages: number; events: number }

const props = defineProps<{
    stats: {
        days: number
        from: string
        totals: { pages: number; events: number }
        pages: PageRow[]
        events: EventRow[]
        daily: DayRow[]
    }
    ranges: number[]
}>()

const EVENT_NAMES: Record<string, string> = {
    button: 'Button pressed',
    booking_click: 'Booking link opened',
    lead_form_submit: 'Checklist sign-up sent',
}

function eventName(event: string): string {
    return EVENT_NAMES[event] ?? event
}

function rangeLabel(days: number): string {
    return days === 365 ? '12 months' : `${days} days`
}

function showRange(days: number) {
    router.get('/admin/site-stats', { days }, { preserveScroll: true, replace: true })
}

function formatDay(day: string): string {
    return new Date(`${day}T00:00:00`).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })
}

const summary = computed(() => {
    const { pages, events } = props.stats.totals
    return `${pages.toLocaleString('en-GB')} page opens and ${events.toLocaleString('en-GB')} actions in the last ${rangeLabel(props.stats.days)}.`
})

const eventRows = computed(() => props.stats.events.map((row) => ({
    ...row,
    what: eventName(row.event),
    detail: row.detail || '—',
})))

const dailyRows = computed(() => [...props.stats.daily].reverse().map((row) => ({
    ...row,
    dayLabel: formatDay(row.day),
})))

const pageColumns = [
    { key: 'path', title: 'Page', sortable: true },
    { key: 'hits', title: 'Opens', sortable: true, align: 'right' as const },
]

const eventColumns = [
    { key: 'what', title: 'Action', sortable: true },
    { key: 'detail', title: 'Which one', sortable: true },
    { key: 'path', title: 'On page', sortable: true },
    { key: 'hits', title: 'Times', sortable: true, align: 'right' as const },
]

const dailyColumns = [
    { key: 'dayLabel', title: 'Day', sortable: false },
    { key: 'pages', title: 'Page opens', sortable: false, align: 'right' as const },
    { key: 'events', title: 'Actions', sortable: false, align: 'right' as const },
]
</script>

<template>
    <div>
        <PageHeader
            title="Site stats"
            subtitle="Page opens and button presses on the site. Anonymous: no cookies and nothing about the visitor is stored, so everyone is counted. Your own visits as admin are not."
            eyebrow="Admin"
            size="compact"
        />

        <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-2">
                <MyButtonConstructor
                    v-for="days in ranges"
                    :key="days"
                    size="small"
                    :variant="days === stats.days ? 'primary' : 'outline'"
                    @click="showRange(days)"
                >
                    {{ rangeLabel(days) }}
                </MyButtonConstructor>
            </div>

            <MyTextConstructor>
                <template #mySubTitle>{{ summary }}</template>
            </MyTextConstructor>

            <MyTableConstructor
                title="Pages opened"
                :data="stats.pages"
                :columns="pageColumns"
                row-key="path"
                default-sort-key="hits"
                default-sort-dir="desc"
            />

            <MyTableConstructor
                title="Buttons and actions"
                subtitle="Button presses are counted on the public pages only."
                :data="eventRows"
                :columns="eventColumns"
                default-sort-key="hits"
                default-sort-dir="desc"
            />

            <MyTableConstructor
                title="Day by day"
                :data="dailyRows"
                :columns="dailyColumns"
                row-key="day"
                :sortable="false"
            />
        </div>
    </div>
</template>
