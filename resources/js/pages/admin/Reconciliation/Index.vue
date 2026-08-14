<!-- resources/js/pages/admin/Reconciliation/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Receipt, FileText, CheckCircle2, AlertCircle, Loader2, X, UploadCloud } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface RecentRun {
    id: number
    filename: string | null
    summary: Record<string, unknown> | null
    created_at: string | null
    user_name: string | null
}

type RowStatus = 'matched' | 'mismatch' | 'already_paid' | 'not_found'

interface PreviewRow {
    order_number: string
    transaction_date: string | null
    description: string
    paid_amount: number
    expected_amount: number | null
    status: RowStatus
    order_id: number | null
    duplicates: number | null
}

interface Preview {
    remittance_date: string | null
    account_code: string | null
    recipient_email: string | null
    statement_total: number | null
    matched_sum: number
    counts: Record<RowStatus, number>
    rows: PreviewRow[]
    warnings: string[]
    can_commit: boolean
}

const props = defineProps<{
    recent: RecentRun[]
}>()

const file = ref<File | null>(null)
const preview = ref<Preview | null>(null)
const error = ref<string | null>(null)
const busy = ref(false)
const dragOver = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

function setFile(f: File | null) {
    file.value = f
    preview.value = null
    error.value = null
}

function onFileSelected(e: Event) {
    const target = e.target as HTMLInputElement
    setFile(target.files && target.files[0] ? target.files[0] : null)
    if (target) target.value = ''
}

function onDrop(e: DragEvent) {
    e.preventDefault()
    dragOver.value = false
    const f = e.dataTransfer?.files?.[0]
    if (!f) return
    if (!/\.pdf$/i.test(f.name)) {
        error.value = 'Please drop a .pdf file.'
        return
    }
    setFile(f)
}
function onDragOver(e: DragEvent) {
    e.preventDefault()
    dragOver.value = true
}
function onDragLeave() {
    dragOver.value = false
}
function openPicker() {
    inputRef.value?.click()
}
function onDropZoneKey(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        openPicker()
    }
}
function clearFile() {
    setFile(null)
}

async function submitPreview() {
    if (!file.value) {
        error.value = 'Please choose a remittance PDF first.'
        return
    }
    busy.value = true
    error.value = null
    preview.value = null

    const fd = new FormData()
    fd.append('file', file.value)

    try {
        const res = await fetch('/admin/reconciliation/preview', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            error.value = data.error || data.message || `Preview failed (${res.status}).`
        } else {
            preview.value = await res.json()
        }
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Preview failed.'
    } finally {
        busy.value = false
    }
}

function commit() {
    if (!file.value || !preview.value?.can_commit) return
    const form = useForm({ file: file.value })
    form.post('/admin/reconciliation/commit', {
        forceFormData: true,
        onSuccess: () => {
            file.value = null
            preview.value = null
        },
    })
}

const money = (n: number | null | undefined) =>
    n === null || n === undefined ? '—' : `£${Number(n).toFixed(2)}`

const statusMeta: Record<RowStatus, { label: string; rowClass: string; badgeClass: string }> = {
    matched: {
        label: 'Matched',
        rowClass: '',
        badgeClass: 'border-brand-success/40 bg-brand-success/10 text-brand-success',
    },
    mismatch: {
        label: 'Amount differs',
        rowClass: 'bg-brand-warning/5',
        badgeClass: 'border-brand-warning/40 bg-brand-warning/10 text-brand-warning',
    },
    already_paid: {
        label: 'Already paid',
        rowClass: 'opacity-70',
        badgeClass: 'border-brand-border bg-brand-surface text-brand-text-soft',
    },
    not_found: {
        label: 'Not found',
        rowClass: 'bg-brand-danger/5',
        badgeClass: 'border-brand-danger/40 bg-brand-danger/10 text-brand-danger',
    },
}

const commitCount = computed(() =>
    preview.value ? preview.value.counts.matched + preview.value.counts.mismatch : 0,
)

function formatRunSummary(run: { summary: Record<string, unknown> | null }): string {
    const s = run.summary ?? {}
    const date = (s.remittance_date as string) || '?'
    const marked = (s.marked as number) ?? 0
    const already = (s.already_paid as number) ?? 0
    const nf = (s.not_found as number) ?? 0
    let out = `${date}: ${marked} marked paid`
    if (already) out += `, ${already} already paid`
    if (nf) out += `, ${nf} not found`
    return out
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <Receipt class="h-8 w-8 text-brand-accent" />
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Reconciliation</h1>
                <p class="mt-1 text-sm text-brand-text-soft">Drop a Trinity Remittance Advice PDF to mark the orders it lists as commission-paid.</p>
            </div>
        </div>

        <section class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <FileText class="h-6 w-6 text-brand-accent" />
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">Remittance Advice PDF</h2>
                    <p class="text-sm text-brand-text-soft">Reads the order numbers and amounts, matches them against your orders, then marks them paid on the remittance date. Handles digital and face-to-face rows.</p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-brand-text">Remittance PDF</label>
                <div
                    role="button"
                    tabindex="0"
                    :aria-label="file ? `Selected ${file.name}. Press Enter to replace.` : 'Drop remittance PDF here or press Enter to browse'"
                    :class="[
                        'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-8 text-center transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-accent',
                        dragOver
                            ? 'border-brand-accent bg-brand-accent/10'
                            : 'border-brand-border bg-brand-surface-soft hover:border-brand-accent/60',
                    ]"
                    @click="openPicker"
                    @keydown="onDropZoneKey"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                >
                    <UploadCloud class="h-7 w-7 text-brand-accent" />
                    <p v-if="!file" class="mt-2 text-sm text-brand-text">
                        Drop a remittance PDF here, or <span class="font-semibold text-brand-accent">browse</span>
                    </p>
                    <p v-if="!file" class="mt-1 text-xs text-brand-text-soft">.pdf only · single file</p>
                    <div v-else class="mt-2 flex items-center gap-2 text-sm text-brand-text">
                        <FileText class="h-4 w-4 text-brand-accent" />
                        <span class="font-mono">{{ file.name }}</span>
                        <button
                            type="button"
                            class="ml-1 inline-flex items-center gap-1 rounded-md border border-brand-border bg-brand-surface px-2 py-0.5 text-xs text-brand-text-soft hover:text-brand-danger"
                            @click.stop="clearFile"
                        >
                            <X class="h-3 w-3" /> Remove
                        </button>
                    </div>
                </div>
                <input
                    ref="inputRef"
                    type="file"
                    accept=".pdf,application/pdf"
                    class="hidden"
                    @change="onFileSelected"
                />
            </div>

            <div v-if="error" class="mt-4 flex items-start gap-2 rounded-lg border border-brand-danger/40 bg-brand-danger/10 p-3 text-sm text-brand-danger">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ error }}</span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <MyButtonConstructor variant="primary" size="medium" :disabled="!file || busy" @click="submitPreview">
                    <Loader2 v-if="busy" class="h-4 w-4 animate-spin" />
                    <span v-else>Preview</span>
                </MyButtonConstructor>
                <MyButtonConstructor v-if="preview" variant="success" size="medium" :disabled="!preview.can_commit" @click="commit">
                    Mark {{ commitCount }} order(s) paid
                </MyButtonConstructor>
                <span v-if="preview && !preview.can_commit" class="text-sm text-brand-text-soft">
                    Nothing to commit from this statement.
                </span>
            </div>

            <!-- ───────── Preview ───────── -->
            <div v-if="preview" class="mt-5 rounded-lg border border-brand-border bg-brand-surface-soft p-4">
                <div v-if="preview.warnings.length" class="mb-4 rounded-lg border-2 border-brand-danger bg-brand-danger p-4 text-white shadow-md">
                    <p class="flex items-center gap-2 text-base font-bold uppercase tracking-wide">
                        <span aria-hidden="true">⚠</span> Warnings
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-6 text-sm font-medium">
                        <li v-for="(w, i) in preview.warnings" :key="i">{{ w }}</li>
                    </ul>
                </div>

                <h3 class="mb-3 text-base font-semibold text-brand-text">Preview</h3>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div><span class="text-brand-text-soft">Remittance date:</span> {{ preview.remittance_date || '—' }}</div>
                    <div><span class="text-brand-text-soft">Account code:</span> {{ preview.account_code || '—' }}</div>
                    <div><span class="text-brand-text-soft">Statement total:</span> {{ money(preview.statement_total) }}</div>
                    <div><span class="text-brand-text-soft">Will mark paid:</span> {{ money(preview.matched_sum) }}</div>
                </div>

                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    <span class="text-brand-success">{{ preview.counts.matched }} matched</span>
                    <span class="text-brand-warning">{{ preview.counts.mismatch }} amount differs</span>
                    <span class="text-brand-text-soft">{{ preview.counts.already_paid }} already paid</span>
                    <span class="text-brand-danger">{{ preview.counts.not_found }} not found</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wider text-brand-text-soft">
                            <tr>
                                <th class="px-3 py-2">Order #</th>
                                <th class="px-3 py-2">Txn date</th>
                                <th class="px-3 py-2">Description</th>
                                <th class="px-3 py-2 text-right">Paid</th>
                                <th class="px-3 py-2 text-right">Expected</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in preview.rows" :key="i" :class="['border-t border-brand-border', statusMeta[row.status].rowClass]">
                                <td class="px-3 py-2 font-mono text-brand-text">
                                    {{ row.order_number }}
                                    <span v-if="row.duplicates" class="ml-1 text-xs text-brand-warning">×{{ row.duplicates }}</span>
                                </td>
                                <td class="px-3 py-2 text-brand-text-soft">{{ row.transaction_date || '—' }}</td>
                                <td class="px-3 py-2 text-brand-text-soft">{{ row.description || '—' }}</td>
                                <td class="px-3 py-2 text-right font-mono text-brand-text">{{ money(row.paid_amount) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-brand-text-soft">{{ money(row.expected_amount) }}</td>
                                <td class="px-3 py-2">
                                    <span :class="['inline-block rounded-md border px-2 py-0.5 text-xs font-medium', statusMeta[row.status].badgeClass]">
                                        {{ statusMeta[row.status].label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ───────── Recent reconciliations ───────── -->
        <section class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <CheckCircle2 class="h-6 w-6 text-brand-accent" />
                <h2 class="text-xl font-semibold text-brand-text">Recent reconciliations</h2>
            </div>

            <div v-if="props.recent.length === 0" class="text-sm text-brand-text-soft">No remittances reconciled yet.</div>
            <table v-else class="stacked-table w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-brand-text-soft">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">File</th>
                        <th class="px-3 py-2">Result</th>
                        <th class="px-3 py-2">By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="run in props.recent" :key="run.id" class="border-t border-brand-border">
                        <td :data-label="'When'" class="px-3 py-2 font-mono text-brand-text">{{ run.created_at }}</td>
                        <td :data-label="'File'" class="px-3 py-2 text-brand-text-soft">{{ run.filename || '—' }}</td>
                        <td :data-label="'Result'" class="px-3 py-2 text-brand-text">{{ formatRunSummary(run) }}</td>
                        <td :data-label="'By'" class="px-3 py-2 text-brand-text-soft">{{ run.user_name || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
