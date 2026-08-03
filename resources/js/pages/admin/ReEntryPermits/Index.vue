<!-- resources/js/pages/admin/ReEntryPermits/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { UploadCloud, FileText, CheckCircle2, AlertCircle, XCircle, Info } from 'lucide-vue-next'

interface Row {
    filename: string
    status: 'ready' | 'already' | 'not_found' | 'not_a_permit'
    candidate_name: string | null
    candidate_number: string | null
    exam: string | null
    code: string | null
    valid_until: string | null
    entry_id: number | null
    order_number: string | null
    current_notes: string | null
    note: string | null
}

interface RecentRun {
    id: number
    filename: string
    summary: Record<string, unknown> | null
    user: string | null
    created_at: string | null
}

defineProps<{ recent: RecentRun[] }>()

const page = usePage()
const flashSuccess = computed(() => (page.props.flash as any)?.success)

const files = ref<File[]>([])
const rows = ref<Row[]>([])
const dragging = ref(false)
const busy = ref(false)
const error = ref<string | null>(null)

const readyCount = computed(() => rows.value.filter((r) => r.status === 'ready').length)
const problemCount = computed(() => rows.value.filter((r) => r.status === 'not_found' || r.status === 'not_a_permit').length)

function accept(list: FileList | null) {
    if (!list?.length) return
    files.value = Array.from(list).filter((f) => f.type === 'application/pdf')
    rows.value = []
    error.value = files.value.length ? null : 'Those files are not PDFs.'
    if (files.value.length) preview()
}

function onDrop(e: DragEvent) {
    dragging.value = false
    accept(e.dataTransfer?.files ?? null)
}

function payload(): FormData {
    const fd = new FormData()
    files.value.forEach((f) => fd.append('files[]', f))
    return fd
}

async function preview() {
    busy.value = true
    error.value = null
    try {
        const res = await fetch('/admin/re-entry-permits/preview', {
            method: 'POST',
            body: payload(),
            headers: { Accept: 'application/json' },
        })
        const data = await res.json()
        if (!res.ok) {
            error.value = data.error ?? 'Could not read those files.'
            return
        }
        rows.value = data.rows ?? []
    } catch {
        error.value = 'Something went wrong reading those files.'
    } finally {
        busy.value = false
    }
}

function commit() {
    busy.value = true
    router.post('/admin/re-entry-permits/commit', payload(), {
        forceFormData: true,
        onFinish: () => {
            busy.value = false
            files.value = []
            rows.value = []
        },
    })
}

function reset() {
    files.value = []
    rows.value = []
    error.value = null
}

const statusChip: Record<Row['status'], { label: string; cls: string }> = {
    ready: { label: 'Will be marked', cls: 'bg-brand-teal-soft text-brand-teal' },
    already: { label: 'Already marked', cls: 'bg-brand-surface-soft text-brand-text-soft' },
    not_found: { label: 'No matching entry', cls: 'bg-brand-danger-soft text-brand-danger' },
    not_a_permit: { label: 'Not a permit', cls: 'bg-brand-danger-soft text-brand-danger' },
}
</script>

<template>
    <Head title="Re-entry permits" />

    <div class="mx-auto max-w-5xl px-4 py-6">
        <h1 class="text-2xl font-semibold text-brand-text">Re-entry permits</h1>
        <p class="mt-1 text-sm text-brand-text-soft">
            Drop the permit PDFs Trinity sends when a booked candidate doesn't sit. Each one marks its
            candidate as withdrawn and stores the voucher code, so booked and sat add up again.
        </p>

        <div v-if="flashSuccess" class="mt-4 rounded-xl border border-brand-teal bg-brand-teal-soft px-4 py-3 text-sm font-medium text-brand-teal">
            {{ flashSuccess }}
        </div>

        <div class="mt-4 flex items-start gap-2 rounded-xl border border-brand-border bg-brand-surface-soft px-4 py-3 text-sm text-brand-text-soft">
            <Info class="mt-0.5 h-4 w-4 shrink-0 text-brand-accent" />
            <p>
                Import the order's <strong class="text-brand-text">enrolment list first</strong>. A permit is
                matched on candidate number, so a candidate with no entry yet will come back as
                &ldquo;no matching entry&rdquo;.
            </p>
        </div>

        <div
            class="mt-5 rounded-xl border-2 border-dashed p-10 text-center transition-colors"
            :class="dragging ? 'border-brand-accent bg-brand-accent/5' : 'border-brand-border bg-brand-surface'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <UploadCloud class="mx-auto h-8 w-8 text-brand-accent" />
            <p class="mt-3 font-medium text-brand-text">Drop permit PDFs here</p>
            <p class="mt-1 text-sm text-brand-text-soft">or</p>
            <label class="mt-2 inline-block cursor-pointer rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-text-inverse hover:opacity-90">
                Choose files
                <input type="file" accept="application/pdf" multiple class="hidden" @change="accept(($event.target as HTMLInputElement).files)" />
            </label>
            <p v-if="files.length" class="mt-3 text-sm text-brand-text-soft">
                {{ files.length }} file{{ files.length === 1 ? '' : 's' }} selected
            </p>
        </div>

        <p v-if="busy" class="mt-4 text-sm text-brand-text-soft">Reading…</p>
        <p v-if="error" class="mt-4 rounded-lg bg-brand-danger-soft px-4 py-3 text-sm font-medium text-brand-danger">{{ error }}</p>

        <div v-if="rows.length" class="mt-6 rounded-xl border border-brand-border bg-brand-surface">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-border px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-brand-text">Preview</h2>
                    <p class="text-sm text-brand-text-soft">
                        {{ readyCount }} to mark<span v-if="problemCount">, {{ problemCount }} need attention</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-text-soft hover:text-brand-text" @click="reset">
                        Clear
                    </button>
                    <button
                        type="button"
                        :disabled="busy || readyCount === 0"
                        class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90 disabled:opacity-40"
                        @click="commit"
                    >
                        Mark {{ readyCount }} candidate{{ readyCount === 1 ? '' : 's' }}
                    </button>
                </div>
            </div>

            <ul class="divide-y divide-brand-border">
                <li v-for="r in rows" :key="r.filename" class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 font-medium text-brand-text">
                                <CheckCircle2 v-if="r.status === 'ready'" class="h-4 w-4 text-brand-teal" />
                                <AlertCircle v-else-if="r.status === 'already'" class="h-4 w-4 text-brand-text-soft" />
                                <XCircle v-else class="h-4 w-4 text-brand-danger" />
                                {{ r.candidate_name ?? r.filename }}
                            </p>
                            <p class="mt-0.5 text-sm text-brand-text-soft">
                                <span v-if="r.exam">{{ r.exam }}</span>
                                <span v-if="r.order_number"> &middot; order {{ r.order_number }}</span>
                                <span v-if="r.valid_until"> &middot; valid until {{ r.valid_until }}</span>
                            </p>
                            <p v-if="r.code" class="mt-0.5 font-mono text-xs text-brand-text-soft">{{ r.code }}</p>
                            <p v-if="r.note" class="mt-1 text-xs text-brand-text-soft">{{ r.note }}</p>
                            <p class="mt-1 flex items-center gap-1 text-xs text-brand-text-soft">
                                <FileText class="h-3 w-3" /> {{ r.filename }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold" :class="statusChip[r.status].cls">
                            {{ statusChip[r.status].label }}
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <div v-if="recent.length" class="mt-8">
            <h2 class="text-sm font-semibold text-brand-text">Recent uploads</h2>
            <ul class="mt-2 divide-y divide-brand-border rounded-xl border border-brand-border bg-brand-surface">
                <li v-for="r in recent" :key="r.id" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                    <span class="text-brand-text">{{ r.filename }}</span>
                    <span class="text-brand-text-soft">
                        {{ (r.summary as any)?.marked ?? 0 }} marked &middot; {{ r.user ?? 'Unknown' }} &middot; {{ r.created_at }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
