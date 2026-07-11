<!-- resources/js/pages/admin/Labels/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Tags, FileText, UploadCloud, Plus, Trash2, AlertTriangle, Loader2, X, Printer, Table } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface GridLabel {
    text: string
    source: string
    flag: string
}

interface ApiLabel {
    name: string
    lines: string[]
    postcode: string
    source: string
    flag: string
}

const LABELS_PER_SHEET = 10

const pdfFiles = ref<File[]>([])
const csvFile = ref<File | null>(null)
const labels = ref<GridLabel[]>([])
const error = ref<string | null>(null)
const busy = ref(false)
const downloading = ref(false)
const dragOver = ref(false)
const pdfInput = ref<HTMLInputElement | null>(null)
const csvInput = ref<HTMLInputElement | null>(null)

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const hasInput = computed(() => pdfFiles.value.length > 0 || csvFile.value !== null)
const flaggedCount = computed(() => labels.value.filter((l) => l.flag).length)
const sheetCount = computed(() => Math.max(1, Math.ceil(labels.value.length / LABELS_PER_SHEET)))

function addPdfFiles(files: FileList | File[]) {
    const incoming = Array.from(files).filter((f) => /\.pdf$/i.test(f.name))
    if (incoming.length === 0) {
        error.value = 'Please choose PDF files.'
        return
    }
    pdfFiles.value = [...pdfFiles.value, ...incoming]
    error.value = null
}

function onPdfSelected(e: Event) {
    const t = e.target as HTMLInputElement
    if (t.files) addPdfFiles(t.files)
    if (t) t.value = ''
}

function onDrop(e: DragEvent) {
    e.preventDefault()
    dragOver.value = false
    if (e.dataTransfer?.files?.length) addPdfFiles(e.dataTransfer.files)
}

function removePdf(i: number) {
    pdfFiles.value.splice(i, 1)
}

function onCsvSelected(e: Event) {
    const t = e.target as HTMLInputElement
    csvFile.value = t.files && t.files[0] ? t.files[0] : null
    error.value = null
    if (t) t.value = ''
}

async function convert() {
    if (!hasInput.value) {
        error.value = 'Add a Trinity label PDF or a CSV first.'
        return
    }
    busy.value = true
    error.value = null

    const fd = new FormData()
    pdfFiles.value.forEach((f) => fd.append('files[]', f))
    if (csvFile.value) fd.append('spreadsheet', csvFile.value)

    try {
        const res = await fetch('/admin/labels/preview', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        })
        const data = await res.json().catch(() => ({}))
        if (!res.ok) {
            error.value = data.error || data.message || `Could not read those files (${res.status}).`
            return
        }
        const incoming: GridLabel[] = (data.labels as ApiLabel[]).map((l) => ({
            text: l.lines.join('\n'),
            source: l.source,
            flag: l.flag,
        }))
        // Keep any hand-added labels already in the grid, append the parsed set.
        labels.value = [...labels.value, ...incoming]
        pdfFiles.value = []
        csvFile.value = null
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Something went wrong reading the files.'
    } finally {
        busy.value = false
    }
}

function addBlank() {
    labels.value.push({ text: '', source: 'typed', flag: '' })
}

function removeLabel(i: number) {
    labels.value.splice(i, 1)
}

function clearFlag(i: number) {
    labels.value[i].flag = ''
}

function clearAll() {
    labels.value = []
    error.value = null
}

async function downloadPdf() {
    const payload = labels.value
        .map((l) => l.text.split('\n').map((s) => s.trim()).filter(Boolean))
        .filter((lines) => lines.length > 0)

    if (payload.length === 0) {
        error.value = 'There are no labels to print.'
        return
    }

    downloading.value = true
    error.value = null
    try {
        const res = await fetch('/admin/labels/pdf', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/pdf', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ labels: payload }),
        })
        if (!res.ok) {
            error.value = `Could not build the PDF (${res.status}).`
            return
        }
        const blob = await res.blob()
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `address-labels-${new Date().toISOString().slice(0, 10)}.pdf`
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(url)
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Something went wrong building the PDF.'
    } finally {
        downloading.value = false
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <Tags class="h-8 w-8 text-brand-accent" />
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Address Labels</h1>
                <p class="mt-1 text-sm text-brand-text-soft">
                    Turn Trinity's messy 8-up label PDFs into clean, de-duplicated Avery&nbsp;L7173 sheets. Check every label below, then print at Actual Size.
                </p>
            </div>
        </div>

        <div v-if="error" class="mb-4 flex items-start gap-2 rounded-lg border border-brand-danger/40 bg-brand-danger/10 px-4 py-3 text-sm text-brand-danger">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{{ error }}</span>
        </div>

        <!-- Inputs -->
        <section class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <FileText class="h-6 w-6 text-brand-accent" />
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">Add addresses</h2>
                    <p class="text-sm text-brand-text-soft">Drop one or more Trinity label PDFs (from any day), upload a CSV, or add labels by hand.</p>
                </div>
            </div>

            <div
                role="button"
                tabindex="0"
                aria-label="Drop label PDFs here or press Enter to browse"
                :class="[
                    'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-8 text-center transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-accent',
                    dragOver ? 'border-brand-accent bg-brand-accent/10' : 'border-brand-border hover:border-brand-accent/60',
                ]"
                @click="pdfInput?.click()"
                @keydown.enter.prevent="pdfInput?.click()"
                @keydown.space.prevent="pdfInput?.click()"
                @drop="onDrop"
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
            >
                <UploadCloud class="mb-2 h-8 w-8 text-brand-accent" />
                <p class="text-sm font-medium text-brand-text">Drop Trinity label PDFs here, or click to browse</p>
                <p class="mt-1 text-xs text-brand-text-soft">You can add several days at once — duplicate teachers are merged automatically.</p>
                <input ref="pdfInput" type="file" accept="application/pdf" multiple class="hidden" @change="onPdfSelected" />
            </div>

            <ul v-if="pdfFiles.length" class="mt-3 space-y-1">
                <li v-for="(f, i) in pdfFiles" :key="i" class="flex items-center justify-between rounded-md bg-brand-surface-soft px-3 py-2 text-sm text-brand-text">
                    <span class="flex items-center gap-2 truncate"><FileText class="h-4 w-4 shrink-0 text-brand-accent" />{{ f.name }}</span>
                    <button type="button" class="text-brand-text-soft hover:text-brand-danger" :aria-label="`Remove ${f.name}`" @click="removePdf(i)"><X class="h-4 w-4" /></button>
                </li>
            </ul>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-brand-accent hover:opacity-70">
                    <Table class="h-4 w-4" />
                    <span>{{ csvFile ? csvFile.name : 'Upload a CSV instead' }}</span>
                    <input ref="csvInput" type="file" accept=".csv,text/csv" class="hidden" @change="onCsvSelected" />
                </label>
                <button v-if="csvFile" type="button" class="text-brand-text-soft hover:text-brand-danger" aria-label="Remove CSV" @click="csvFile = null"><X class="h-4 w-4" /></button>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <MyButtonConstructor size="medium" variant="primary" :disabled="busy || !hasInput" @click="convert">
                    <span class="inline-flex items-center gap-2">
                        <Loader2 v-if="busy" class="h-4 w-4 animate-spin" />
                        {{ busy ? 'Reading…' : 'Convert & clean up' }}
                    </span>
                </MyButtonConstructor>
                <MyButtonConstructor size="medium" variant="outline" @click="addBlank">
                    <span class="inline-flex items-center gap-2"><Plus class="h-4 w-4" /> Add a label by hand</span>
                </MyButtonConstructor>
            </div>
        </section>

        <!-- Editable grid -->
        <section v-if="labels.length" class="mt-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">
                        {{ labels.length }} label{{ labels.length === 1 ? '' : 's' }}
                        <span class="text-brand-text-soft">· {{ sheetCount }} sheet{{ sheetCount === 1 ? '' : 's' }} of {{ LABELS_PER_SHEET }}</span>
                    </h2>
                    <p class="text-sm text-brand-text-soft">Edit any address, delete the ones you don't need, then download. One box = one sticker.</p>
                </div>
                <div class="flex gap-2">
                    <MyButtonConstructor size="small" variant="ghost" @click="clearAll">Clear all</MyButtonConstructor>
                    <MyButtonConstructor size="small" variant="outline" @click="addBlank">
                        <span class="inline-flex items-center gap-1"><Plus class="h-4 w-4" /> Add</span>
                    </MyButtonConstructor>
                </div>
            </div>

            <div v-if="flaggedCount" class="mb-4 flex items-start gap-2 rounded-lg border border-brand-warning/40 bg-brand-warning/10 px-4 py-3 text-sm text-brand-warning">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ flaggedCount }} possible duplicate{{ flaggedCount === 1 ? '' : 's' }} flagged — check the highlighted boxes and delete any you don't want.</span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(label, i) in labels"
                    :key="i"
                    :class="[
                        'flex flex-col rounded-lg border bg-brand-surface p-3 transition',
                        label.flag ? 'border-brand-warning/60 ring-1 ring-brand-warning/40' : 'border-brand-border',
                    ]"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <span class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs text-brand-text-soft">{{ label.source }}</span>
                        <button type="button" class="text-brand-text-soft hover:text-brand-danger" :aria-label="`Delete label ${i + 1}`" @click="removeLabel(i)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <textarea
                        v-model="label.text"
                        rows="6"
                        spellcheck="false"
                        class="w-full resize-y rounded-md border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                    ></textarea>
                    <div v-if="label.flag" class="mt-2 flex items-start justify-between gap-2 text-xs text-brand-warning">
                        <span class="flex items-start gap-1"><AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />{{ label.flag }}</span>
                        <button type="button" class="shrink-0 font-medium underline hover:opacity-70" @click="clearFlag(i)">Keep</button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <MyButtonConstructor size="large" variant="primary" :disabled="downloading" @click="downloadPdf">
                    <span class="inline-flex items-center gap-2">
                        <Loader2 v-if="downloading" class="h-5 w-5 animate-spin" />
                        <Printer v-else class="h-5 w-5" />
                        {{ downloading ? 'Building…' : 'Download L7173 PDF' }}
                    </span>
                </MyButtonConstructor>
                <p class="text-sm text-brand-text-soft">Print at Actual&nbsp;Size / 100% — turn off "fit to page".</p>
            </div>
        </section>
    </div>
</template>
