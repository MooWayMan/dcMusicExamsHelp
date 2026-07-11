<!-- resources/js/pages/admin/Labels/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Tags, FileText, UploadCloud, Plus, Trash2, AlertTriangle, Loader2, X, Printer, Table, Save, FolderOpen } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface GridLabel {
    text: string
    source: string
    flag: string
    dupeKey: string
}

interface ApiLabel {
    name: string
    lines: string[]
    postcode: string
    source: string
    flag: string
    dupeKey: string
}

const LABELS_PER_SHEET = 10

const pdfFiles = ref<File[]>([])
const csvFile = ref<File | null>(null)
const labels = ref<GridLabel[]>([])
const error = ref<string | null>(null)
const busy = ref(false)
const downloading = ref(false)
const previewing = ref(false)
const dragOver = ref(false)
const pdfInput = ref<HTMLInputElement | null>(null)
const csvInput = ref<HTMLInputElement | null>(null)
const workingInput = ref<HTMLInputElement | null>(null)

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
            dupeKey: l.dupeKey ?? '',
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
    labels.value.push({ text: '', source: 'typed', flag: '', dupeKey: '' })
}

// Highlight linked possible-duplicates: hovering (or tapping) one lights up
// the other(s) that share its group key.
const activeDupeKey = ref<string | null>(null)

function highlightDupe(label: GridLabel) {
    activeDupeKey.value = label.dupeKey || null
}

function toggleDupe(label: GridLabel) {
    if (!label.dupeKey) return
    activeDupeKey.value = activeDupeKey.value === label.dupeKey ? null : label.dupeKey
}

function isLinked(label: GridLabel): boolean {
    return !!label.dupeKey && label.dupeKey === activeDupeKey.value
}

// Roughly how many lines comfortably fit an Avery L7173 label. Above this the
// PDF shrinks the font, but it's better to trim — so warn in the grid.
const MAX_LABEL_LINES = 7

function lineCount(label: GridLabel): number {
    return label.text.split('\n').map((s) => s.trim()).filter(Boolean).length
}

// Compare a possible-duplicate group in a modal, so the match doesn't have to
// be scrolled to. Textareas bind straight to the grid, so edits/deletes here
// apply to the real labels.
const matchModalKey = ref<string | null>(null)

const matchGroup = computed(() =>
    matchModalKey.value ? labels.value.filter((l) => l.dupeKey === matchModalKey.value) : [],
)

function openMatch(label: GridLabel) {
    if (label.dupeKey) matchModalKey.value = label.dupeKey
}

function closeMatch() {
    matchModalKey.value = null
}

function removeLabelObj(label: GridLabel) {
    const i = labels.value.indexOf(label)
    if (i !== -1) labels.value.splice(i, 1)
    pruneOrphanFlags()
    if (matchGroup.value.length < 2) closeMatch()
}

function removeLabel(i: number) {
    labels.value.splice(i, 1)
    pruneOrphanFlags()
}

// Once a possible-duplicate has no partner left (its match was deleted), it
// isn't a duplicate any more — clear its flag and group link.
function pruneOrphanFlags() {
    const counts: Record<string, number> = {}
    labels.value.forEach((l) => {
        if (l.dupeKey) counts[l.dupeKey] = (counts[l.dupeKey] ?? 0) + 1
    })
    labels.value.forEach((l) => {
        if (l.dupeKey && (counts[l.dupeKey] ?? 0) < 2) {
            l.dupeKey = ''
            l.flag = ''
        }
    })
}

function clearFlag(i: number) {
    labels.value[i].flag = ''
}

function clearAll() {
    labels.value = []
    error.value = null
}

const stamp = () => new Date().toISOString().slice(0, 10)

function gridPayload(): string[][] {
    return labels.value
        .map((l) => l.text.split('\n').map((s) => s.trim()).filter(Boolean))
        .filter((lines) => lines.length > 0)
}

// Ask the server to render the L7173 PDF and hand back the blob (or null on
// error). Shared by both Preview (open in a new tab) and Download.
async function buildPdfBlob(): Promise<Blob | null> {
    const payload = gridPayload()
    if (payload.length === 0) {
        error.value = 'There are no labels to print.'
        return null
    }
    const res = await fetch('/admin/labels/pdf', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/pdf', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ labels: payload }),
    })
    if (!res.ok) {
        error.value = `Could not build the PDF (${res.status}).`
        return null
    }
    return res.blob()
}

async function previewPdf() {
    // Open the tab NOW, inside the click gesture, so popup blockers allow it;
    // we point it at the PDF once it's built.
    const win = window.open('', '_blank')
    previewing.value = true
    error.value = null
    try {
        const blob = await buildPdfBlob()
        if (!blob) {
            win?.close()
            return
        }
        // The real PDF inline in a new tab — a true-to-print preview.
        const url = URL.createObjectURL(blob)
        if (win) {
            win.location.href = url
        } else {
            // Popup blocked — fall back to a normal download.
            window.location.href = url
        }
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (err: unknown) {
        win?.close()
        error.value = err instanceof Error ? err.message : 'Something went wrong building the preview.'
    } finally {
        previewing.value = false
    }
}

async function downloadPdf() {
    downloading.value = true
    error.value = null
    try {
        const blob = await buildPdfBlob()
        if (!blob) return
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `address-labels-${stamp()}.pdf`
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

// ── Working file — save the grid so it can be reopened and edited later
// without re-uploading the PDFs. It's just the grid state as JSON. ──

function saveWorkingFile() {
    if (labels.value.length === 0) {
        error.value = 'There are no labels to save yet.'
        return
    }
    const data = JSON.stringify({ version: 1, savedAt: new Date().toISOString(), labels: labels.value }, null, 2)
    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `address-labels-${stamp()}.working.json`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

function openWorkingFile(e: Event) {
    const t = e.target as HTMLInputElement
    const file = t.files && t.files[0] ? t.files[0] : null
    if (t) t.value = ''
    if (!file) return

    const reader = new FileReader()
    reader.onload = () => {
        try {
            const parsed = JSON.parse(String(reader.result))
            const incoming = Array.isArray(parsed) ? parsed : parsed.labels
            if (!Array.isArray(incoming)) throw new Error('Not a labels file.')
            labels.value = incoming.map((l: Partial<GridLabel>) => ({
                text: String(l.text ?? ''),
                source: String(l.source ?? 'saved'),
                flag: String(l.flag ?? ''),
                dupeKey: String(l.dupeKey ?? ''),
            }))
            error.value = null
        } catch {
            error.value = "That doesn't look like a saved labels file."
        }
    }
    reader.readAsText(file)
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
                <MyButtonConstructor size="medium" variant="ghost" @click="workingInput?.click()">
                    <span class="inline-flex items-center gap-2"><FolderOpen class="h-4 w-4" /> Open a saved working file</span>
                </MyButtonConstructor>
                <input ref="workingInput" type="file" accept=".json,application/json" class="hidden" @change="openWorkingFile" />
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

            <div v-if="flaggedCount" class="mb-4 flex items-start gap-2 rounded-lg border border-brand-accent/40 bg-brand-accent/10 px-4 py-3 text-sm text-brand-accent">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ flaggedCount }} possible duplicate{{ flaggedCount === 1 ? '' : 's' }} flagged — check the highlighted boxes and delete any you don't want.</span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(label, i) in labels"
                    :key="i"
                    :class="[
                        'flex flex-col rounded-lg border bg-brand-surface p-3 transition',
                        label.flag ? 'border-brand-accent/60' : 'border-brand-border',
                        isLinked(label)
                            ? 'ring-2 ring-brand-accent'
                            : label.flag
                                ? 'ring-1 ring-brand-accent/40'
                                : '',
                        label.dupeKey ? 'cursor-pointer' : '',
                    ]"
                    @mouseenter="highlightDupe(label)"
                    @mouseleave="activeDupeKey = null"
                    @click="toggleDupe(label)"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <span class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs text-brand-text-soft">{{ label.source }}</span>
                        <button type="button" class="text-brand-text-soft hover:text-brand-danger" :aria-label="`Delete label ${i + 1}`" @click.stop="removeLabel(i)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <textarea
                        v-model="label.text"
                        :rows="Math.max(4, label.text.split('\n').length)"
                        spellcheck="false"
                        class="w-full resize-y rounded-md border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        @click.stop
                    ></textarea>
                    <p
                        v-if="lineCount(label) > MAX_LABEL_LINES"
                        class="mt-1 flex items-start gap-1 text-xs text-brand-accent"
                    >
                        <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        {{ lineCount(label) }} lines — may be tight on the label. Trim one, or it'll be shrunk to fit.
                    </p>
                    <div v-if="label.flag" class="mt-2 text-xs text-brand-accent">
                        <span class="flex items-start gap-1"><AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />{{ label.flag }}</span>
                        <div class="mt-1 flex gap-3">
                            <button type="button" class="font-medium underline hover:opacity-70" @click.stop="openMatch(label)">Show match</button>
                            <button type="button" class="font-medium underline hover:opacity-70" @click.stop="clearFlag(i)">Keep</button>
                        </div>
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
                <MyButtonConstructor size="large" variant="outline" :disabled="previewing" @click="previewPdf">
                    <span class="inline-flex items-center gap-2">
                        <Loader2 v-if="previewing" class="h-5 w-5 animate-spin" />
                        <FileText v-else class="h-5 w-5" />
                        {{ previewing ? 'Building…' : 'Preview' }}
                    </span>
                </MyButtonConstructor>
                <MyButtonConstructor size="large" variant="ghost" @click="saveWorkingFile">
                    <span class="inline-flex items-center gap-2"><Save class="h-5 w-5" /> Save working file</span>
                </MyButtonConstructor>
                <p class="w-full text-sm text-brand-text-soft sm:w-auto">
                    Print at Actual&nbsp;Size / 100% — turn off "fit to page". Save a working file to reopen and edit later without re-uploading.
                </p>
            </div>
        </section>

        <!-- Compare-a-duplicate modal -->
        <div
            v-if="matchModalKey"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeMatch"
        >
            <div class="max-h-[85vh] w-full max-w-3xl overflow-auto rounded-xl bg-brand-surface p-5 shadow-xl">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-brand-accent" />
                        <h3 class="text-lg font-semibold text-brand-text">Possible duplicate — {{ matchGroup.length }} labels</h3>
                    </div>
                    <button type="button" class="text-brand-text-soft hover:text-brand-text" aria-label="Close" @click="closeMatch"><X class="h-5 w-5" /></button>
                </div>
                <p class="mb-4 text-sm text-brand-text-soft">Compare them here. Edit or delete any one — changes apply straight to your grid.</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-for="(label, gi) in matchGroup" :key="gi" class="flex flex-col rounded-lg border border-brand-border p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs text-brand-text-soft">{{ label.source }}</span>
                            <button type="button" class="text-brand-text-soft hover:text-brand-danger" aria-label="Delete this label" @click="removeLabelObj(label)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                        <textarea
                            v-model="label.text"
                            :rows="Math.max(5, label.text.split('\n').length)"
                            spellcheck="false"
                            class="w-full resize-y rounded-md border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <MyButtonConstructor size="medium" variant="primary" @click="closeMatch">Done</MyButtonConstructor>
                </div>
            </div>
        </div>
    </div>
</template>
