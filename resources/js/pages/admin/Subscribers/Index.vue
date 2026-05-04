<!-- resources/js/pages/admin/Subscribers/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import {
    Search,
    Mail,
    UserCheck,
    FileDown,
    Users,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

const { animClass } = usePageAnimation()

interface SubscriberRow {
    id: number
    name: string
    email: string
    role: string | null
    source: string | null
    subscribed_at: string | null
    unsubscribed_at: string | null
    marketing_consent_at: string | null
    has_marketing_consent: boolean
    linked_user: { id: number; name: string } | null
}

interface PaginatedData {
    data: SubscriberRow[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    subscribers: PaginatedData
    summary: {
        total: number
        active: number
        marketing_consented: number
        lead_magnet: number
    }
    sources: string[]
    filters: { search: string | null; source: string | null; marketing_consent: string | null }
}>()

const search = ref(props.filters.search ?? '')
let searchTimeout: ReturnType<typeof setTimeout>

const activeSource = ref<string | null>(props.filters.source ?? null)
const activeConsent = ref<string | null>(props.filters.marketing_consent ?? null)

function currentFilters(overrides: Record<string, string | undefined> = {}) {
    return {
        search: search.value || undefined,
        source: activeSource.value || undefined,
        marketing_consent: activeConsent.value || undefined,
        ...overrides,
    }
}

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/subscribers', currentFilters({ search: value || undefined }), {
            preserveState: true,
            replace: true,
        })
    }, 300)
})

function filterSource(source: string | null) {
    activeSource.value = source
    router.get('/admin/subscribers', currentFilters({ source: source || undefined }), {
        preserveState: true,
        replace: true,
    })
}

function filterConsent(value: string | null) {
    activeConsent.value = value
    router.get('/admin/subscribers', currentFilters({ marketing_consent: value || undefined }), {
        preserveState: true,
        replace: true,
    })
}

function sourceLabel(source: string | null): string {
    if (!source) return '—'
    if (source === 'trinity_checklist') return 'Trinity checklist'
    if (source === 'website') return 'Website'
    if (source === 'popup') return 'Popup'
    if (source === 'hero') return 'Hero'
    return source.replace(/_/g, ' ')
}
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader title="Subscribers" subtitle="Newsletter + lead-magnet sign-ups" eyebrow="Admin" size="compact" />

        <!-- Summary pills -->
        <div :class="['mt-6 flex flex-wrap gap-3', animClass('fade-up', 1)]">
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Users class="h-4 w-4 text-brand-text-soft" />
                <span class="text-sm font-medium text-brand-text-soft">Total</span>
                <span class="text-xl font-bold text-brand-text">{{ summary.total }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <Mail class="h-4 w-4 text-brand-accent" />
                <span class="text-sm font-medium text-brand-text-soft">Active</span>
                <span class="text-xl font-bold text-brand-accent">{{ summary.active }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <UserCheck class="h-4 w-4 text-brand-success" />
                <span class="text-sm font-medium text-brand-text-soft">Marketing consent</span>
                <span class="text-xl font-bold text-brand-success">{{ summary.marketing_consented }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-4 py-2">
                <FileDown class="h-4 w-4 text-brand-teal" />
                <span class="text-sm font-medium text-brand-text-soft">Lead magnet</span>
                <span class="text-xl font-bold text-brand-teal">{{ summary.lead_magnet }}</span>
            </div>
        </div>

        <!-- Search -->
        <div :class="['mt-4', animClass('fade-up', 2)]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" type="text" placeholder="Search by name or email..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" />
            </div>
        </div>

        <!-- Source filter pills -->
        <div :class="['mt-3 flex flex-wrap gap-1', animClass('fade-up', 2)]">
            <button @click="filterSource(null)"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="!activeSource ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                All sources
            </button>
            <button v-for="src in sources" :key="src"
                @click="filterSource(src)"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeSource === src ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                {{ sourceLabel(src) }}
            </button>
        </div>

        <!-- Marketing-consent filter pills -->
        <div :class="['mt-2 flex flex-wrap gap-1', animClass('fade-up', 2)]">
            <span class="self-center text-sm text-brand-text-soft">Marketing consent:</span>
            <button @click="filterConsent(null)"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="!activeConsent ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                All
            </button>
            <button @click="filterConsent('yes')"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeConsent === 'yes' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                Yes
            </button>
            <button @click="filterConsent('no')"
                class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeConsent === 'no' ? 'bg-brand-accent text-brand-text-inverse' : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text'">
                No
            </button>
        </div>

        <!-- Table -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 3)]">
            <!-- Top Pagination -->
            <div v-if="subscribers.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ subscribers.current_page }} of {{ subscribers.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="subscribers.current_page > 1" :href="subscribers.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="subscribers.current_page < subscribers.last_page" :href="subscribers.links[subscribers.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in subscribers.links" :key="'top-' + link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="rounded px-3 py-1 text-base transition-colors"
                            :class="link.active ? 'bg-brand-accent text-brand-text-inverse font-semibold' : 'text-brand-text-soft hover:bg-brand-surface-soft'"
                            v-html="link.label" preserve-state />
                        <span v-else class="rounded px-3 py-1 text-base text-brand-border" v-html="link.label" />
                    </template>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[700px] w-full text-left text-base">
                    <thead class="border-b border-brand-border bg-brand-surface-soft">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-brand-text">Name</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Email</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Source</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Subscribed</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">Marketing consent?</th>
                            <th class="px-4 py-3 font-semibold text-brand-text">User account?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <tr v-for="sub in subscribers.data" :key="sub.id"
                            class="transition-colors hover:bg-brand-surface-soft">
                            <td class="px-4 py-3">
                                <span class="font-medium text-brand-text">{{ sub.name }}</span>
                            </td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ sub.email }}</span></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm font-medium text-brand-text-soft">
                                    {{ sourceLabel(sub.source) }}
                                </span>
                            </td>
                            <td class="px-4 py-3"><span class="text-sm text-brand-text-soft">{{ sub.subscribed_at ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <span v-if="sub.has_marketing_consent" class="rounded-full bg-brand-success-soft px-2 py-0.5 text-sm font-medium text-brand-success">
                                    Yes
                                </span>
                                <span v-else class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm text-brand-text-soft">
                                    No
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Link v-if="sub.linked_user"
                                    :href="`/admin/users/${sub.linked_user.id}`"
                                    class="rounded-full bg-brand-accent/10 px-2 py-0.5 text-sm font-medium text-brand-accent hover:underline">
                                    Yes — {{ sub.linked_user.name }}
                                </Link>
                                <span v-else class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-sm text-brand-text-soft">
                                    No
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!subscribers.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-base text-brand-text-soft">No subscribers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination -->
            <div v-if="subscribers.last_page > 1" class="flex items-center justify-between border-t border-brand-border px-4 py-3">
                <p class="text-base text-brand-text-soft">Page {{ subscribers.current_page }} of {{ subscribers.last_page }}</p>
                <div class="flex items-center gap-2 sm:hidden">
                    <Link v-if="subscribers.current_page > 1" :href="subscribers.links[0].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronLeft class="h-5 w-5" /></span>
                    <Link v-if="subscribers.current_page < subscribers.last_page" :href="subscribers.links[subscribers.links.length - 1].url!" class="rounded p-2 text-brand-text-soft hover:bg-brand-surface-soft" preserve-state>
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <span v-else class="rounded p-2 text-brand-border"><ChevronRight class="h-5 w-5" /></span>
                </div>
                <div class="hidden gap-1 sm:flex">
                    <template v-for="link in subscribers.links" :key="link.label">
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
