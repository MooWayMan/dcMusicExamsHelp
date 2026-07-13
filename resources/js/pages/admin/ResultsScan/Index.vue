<!-- resources/js/pages/admin/ResultsScan/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { ClipboardCheck, UploadCloud, AlertTriangle, CheckCircle2, Loader2, X, FileJson, Info, Save } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface Section {
    label: string
    mark: number | null
    max: number | null
    comment: string
}

interface MatchStatus {
    order_found: boolean
    entry_found: boolean
    has_result: boolean
}

interface Candidate {
    candidate_name: string
    candidate_id: string
    order_number: string
    subject: string
    family: string
    grade: string
    instrument: string | null
    exam_date: string | null
    examiner_number: string
    general_comments: string
    sections: Section[]
    examiner_total: number | null
    tol_total: number | null
    match?: MatchStatus
}

const candidates = ref<Candidate[]>([])
const error = ref<string | null>(null)
const busy = ref(false)
const importing = ref(false)
const dragOver = ref(false)
const jsonInput = ref<HTMLInputElement | null>(null)
const result = ref<{ updated: number; created: number; skipped: number; warnings: string[] } | null>(null)

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const flaggedCount = computed(() => candidates.value.filter((c) => liveFlags(c).length > 0).length)
const passCount = computed(() => candidates.value.filter((c) => passes(c)).length)

// ── Live checks — mirror the server ResultsScanParser so editing a misread
// mark updates the totals and flags instantly. The server re-runs the same
// checks on import; this is just for immediate feedback. ──
function sumOf(c: Candidate): number {
    return c.sections.reduce((a, s) => a + (typeof s.mark === 'number' ? s.mark : 0), 0)
}

function verifiedOf(c: Candidate): number {
    const sum = sumOf(c)
    return c.examiner_total !== null && c.examiner_total === sum ? c.examiner_total : sum
}

function bandOf(c: Candidate): string {
    const t = verifiedOf(c)
    return t >= 87 ? 'Distinction' : t >= 75 ? 'Merit' : t >= 60 ? 'Pass' : 'Below Pass'
}

function liveFlags(c: Candidate): string[] {
    const flags: string[] = []
    for (const s of c.sections) {
        if (s.mark === null) flags.push(`Couldn't read the mark for “${s.label}” — type it in.`)
        else if (s.max !== null && s.mark > s.max) flags.push(`“${s.label}” reads ${s.mark} but is out of ${s.max} — likely a misread.`)
    }
    const sum = sumOf(c)
    if (c.examiner_total !== null && c.examiner_total !== sum)
        flags.push(`Sections add up to ${sum}, but the examiner's total says ${c.examiner_total} — check the addition.`)
    if (c.tol_total !== null && c.tol_total !== sum)
        flags.push(`Trinity's recorded total is ${c.tol_total}, but the sections add up to ${sum}.`)
    return flags
}

function passes(c: Candidate): boolean {
    return liveFlags(c).length === 0 && c.examiner_total !== null
}

function totalMatches(c: Candidate): boolean {
    return c.examiner_total !== null && c.examiner_total === sumOf(c)
}

// ── Load the transcription JSON (produced by the Cowork vision pass) ──
function addJson(files: FileList | File[]) {
    const file = Array.from(files).find((f) => /\.json$/i.test(f.name))
    if (!file) {
        error.value = 'Please choose the transcription .json file.'
        return
    }
    const reader = new FileReader()
    reader.onload = () => {
        try {
            const parsed = JSON.parse(String(reader.result))
            const rows = Array.isArray(parsed) ? parsed : parsed.candidates
            if (!Array.isArray(rows) || rows.length === 0) throw new Error('empty')
            checkOnServer(rows)
        } catch {
            error.value = "That doesn't look like a valid transcription file (expected a JSON list of candidates)."
        }
    }
    reader.readAsText(file)
}

function onJsonSelected(e: Event) {
    const t = e.target as HTMLInputElement
    if (t.files) addJson(t.files)
    if (t) t.value = ''
}

function onDrop(e: DragEvent) {
    e.preventDefault()
    dragOver.value = false
    if (e.dataTransfer?.files?.length) addJson(e.dataTransfer.files)
}

// Send the raw records to the server, which runs the checks, maps the
// instrument and reports each candidate's match status.
async function checkOnServer(rows: unknown[]) {
    busy.value = true
    error.value = null
    result.value = null
    try {
        const res = await fetch('/admin/results-scan/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ candidates: rows }),
        })
        const data = await res.json().catch(() => ({}))
        if (!res.ok) {
            error.value = data.error || data.message || `Could not read that file (${res.status}).`
            return
        }
        candidates.value = (data.candidates as Candidate[]).map((c) => ({
            candidate_name: c.candidate_name,
            candidate_id: c.candidate_id,
            order_number: c.order_number,
            subject: c.subject,
            family: c.family,
            grade: (c as Candidate & { grade_raw?: string }).grade_raw ?? c.grade ?? '',
            instrument: c.instrument,
            exam_date: c.exam_date,
            examiner_number: c.examiner_number,
            general_comments: c.general_comments ?? '',
            sections: c.sections.map((s) => ({ label: s.label, mark: s.mark, max: s.max, comment: s.comment ?? '' })),
            examiner_total: c.examiner_total,
            tol_total: c.tol_total,
            match: c.match,
        }))
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Something went wrong reading the file.'
    } finally {
        busy.value = false
    }
}

function setMark(s: Section, val: string) {
    const t = val.trim()
    const n = Number(t)
    s.mark = t === '' || Number.isNaN(n) ? null : n
}

function setExaminerTotal(c: Candidate, val: string) {
    const t = val.trim()
    const n = Number(t)
    c.examiner_total = t === '' || Number.isNaN(n) ? null : n
}

function removeCandidate(i: number) {
    candidates.value.splice(i, 1)
}

function clearAll() {
    candidates.value = []
    error.value = null
    result.value = null
}

// Save the current grid (edits, comments and all) so a batch can be reopened
// and finished later — it's the same JSON shape the page loads, so a saved
// working file drops straight back in via "Load another batch".
function saveWorkingFile() {
    if (candidates.value.length === 0) return
    const stamp = new Date().toISOString().slice(0, 10)
    const blob = new Blob([JSON.stringify(candidates.value, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `results-scan-${stamp}.working.json`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

function matchLabel(c: Candidate): { text: string; tone: 'ok' | 'flag' | 'muted' } {
    if (!c.match) return { text: '', tone: 'muted' }
    if (!c.match.order_found) return { text: 'Order not found', tone: 'flag' }
    if (!c.match.entry_found) return { text: 'Will create a new entry', tone: 'muted' }
    if (c.match.has_result) return { text: 'Entry already has a result', tone: 'flag' }
    return { text: 'Will update the matching entry', tone: 'ok' }
}

async function importResults() {
    if (candidates.value.length === 0) return
    importing.value = true
    error.value = null
    result.value = null
    try {
        const res = await fetch('/admin/results-scan/commit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ candidates: candidates.value }),
        })
        const data = await res.json().catch(() => ({}))
        if (!res.ok) {
            error.value = data.error || data.message || `Import failed (${res.status}).`
            return
        }
        result.value = data
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Something went wrong importing.'
    } finally {
        importing.value = false
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <ClipboardCheck class="h-8 w-8 text-brand-accent" />
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Results Scan</h1>
                <p class="mt-1 text-sm text-brand-text-soft">
                    Check handwritten F2F exam reports — every candidate's section marks are added up and compared to the examiner's written total, so addition slips and unreadable totals get flagged before anything is imported.
                </p>
            </div>
        </div>

        <div v-if="error" class="mb-4 flex items-start gap-2 rounded-lg border border-brand-danger/40 bg-brand-danger/10 px-4 py-3 text-sm text-brand-danger">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{{ error }}</span>
        </div>

        <!-- Upload -->
        <section v-if="candidates.length === 0" class="rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-start gap-2 rounded-lg border border-brand-accent/30 bg-brand-accent/5 px-4 py-3 text-sm text-brand-text-soft">
                <Info class="mt-0.5 h-4 w-4 shrink-0 text-brand-accent" />
                <span>The scans are transcribed to a small JSON file first (the identity block + each section mark + the examiner's total). Drop that file here — this screen does the checking and the import.</span>
            </div>

            <div
                role="button"
                tabindex="0"
                aria-label="Drop the transcription JSON here or press Enter to browse"
                :class="[
                    'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-10 text-center transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-accent',
                    dragOver ? 'border-brand-accent bg-brand-accent/10' : 'border-brand-border hover:border-brand-accent/60',
                ]"
                @click="jsonInput?.click()"
                @keydown.enter.prevent="jsonInput?.click()"
                @keydown.space.prevent="jsonInput?.click()"
                @drop="onDrop"
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
            >
                <UploadCloud class="mb-2 h-8 w-8 text-brand-accent" />
                <p class="text-sm font-medium text-brand-text">Drop the transcription JSON here, or click to browse</p>
                <p class="mt-1 text-xs text-brand-text-soft">One file per exam-type batch. Nothing is imported until you've checked the grid and pressed Import.</p>
                <input ref="jsonInput" type="file" accept=".json,application/json" class="hidden" @change="onJsonSelected" />
            </div>
        </section>

        <!-- Grid -->
        <section v-else>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">
                        {{ candidates.length }} candidate{{ candidates.length === 1 ? '' : 's' }}
                        <span class="text-brand-text-soft">·
                            <span :class="passCount === candidates.length ? 'text-brand-success' : ''">{{ passCount }} checked out</span>
                            <span v-if="flaggedCount"> · <span class="text-brand-accent">{{ flaggedCount }} to review</span></span>
                        </span>
                    </h2>
                    <p class="text-sm text-brand-text-soft">Fix any flagged marks, then import. Totals recompute as you type.</p>
                </div>
                <MyButtonConstructor size="small" variant="ghost" @click="clearAll">Start over</MyButtonConstructor>
            </div>

            <div v-if="flaggedCount" class="mb-4 flex items-start gap-2 rounded-lg border border-brand-accent/40 bg-brand-accent/10 px-4 py-3 text-sm text-brand-accent">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ flaggedCount }} candidate{{ flaggedCount === 1 ? '' : 's' }} need a second look — the section marks don't match the examiner's total, or a mark couldn't be read.</span>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div
                    v-for="(c, i) in candidates"
                    :key="i"
                    :class="[
                        'flex flex-col rounded-lg border bg-brand-surface p-4 transition',
                        liveFlags(c).length ? 'border-brand-accent/60 ring-1 ring-brand-accent/40' : 'border-brand-border',
                    ]"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-base font-semibold text-brand-text">{{ c.candidate_name || 'Unnamed candidate' }}</p>
                            <p class="mt-0.5 text-sm text-brand-text-soft">
                                {{ c.subject }} · {{ c.grade || '—' }}
                                <span class="ml-1 rounded-full bg-brand-surface-soft px-2 py-0.5 text-xs">{{ c.family }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-brand-text-soft">
                                Order {{ c.order_number || '—' }} · ID {{ c.candidate_id || '—' }}
                                <span v-if="c.exam_date"> · {{ c.exam_date }}</span>
                            </p>
                        </div>
                        <button type="button" class="text-brand-text-soft hover:text-brand-danger" :aria-label="`Remove ${c.candidate_name}`" @click="removeCandidate(i)">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Sections: piece/song name, mark, and the examiner's comment -->
                    <div class="space-y-2.5">
                        <div v-for="(s, j) in c.sections" :key="j">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="s.label"
                                    class="flex-1 rounded-md border border-transparent bg-transparent px-1 py-0.5 text-sm font-medium text-brand-text hover:border-brand-border focus:border-brand-accent focus:outline-none"
                                />
                                <input
                                    :value="s.mark ?? ''"
                                    inputmode="numeric"
                                    :class="[
                                        'w-16 rounded-md border px-2 py-1 text-right text-sm text-brand-text focus:outline-none focus:ring-1 focus:ring-brand-accent',
                                        s.mark === null || (s.max !== null && s.mark > s.max) ? 'border-brand-accent bg-brand-accent/5' : 'border-brand-border bg-brand-surface',
                                    ]"
                                    @input="setMark(s, ($event.target as HTMLInputElement).value)"
                                />
                                <span class="w-10 text-left text-xs text-brand-text-soft">/ {{ s.max ?? '?' }}</span>
                            </div>
                            <textarea
                                v-model="s.comment"
                                rows="2"
                                spellcheck="true"
                                placeholder="Examiner's comment…"
                                class="mt-1 w-full resize-y rounded-md border border-brand-border bg-brand-surface-soft/40 px-2 py-1 text-xs text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="mt-3 border-t border-brand-border pt-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-brand-text-soft">Marks add up to</span>
                            <span class="text-lg font-bold text-brand-text">{{ sumOf(c) }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between gap-2 text-sm">
                            <span class="text-brand-text-soft">Examiner's total</span>
                            <div class="flex items-center gap-2">
                                <CheckCircle2 v-if="totalMatches(c)" class="h-4 w-4 text-brand-success" />
                                <AlertTriangle v-else class="h-4 w-4 text-brand-accent" />
                                <input
                                    :value="c.examiner_total ?? ''"
                                    inputmode="numeric"
                                    :class="[
                                        'w-16 rounded-md border px-2 py-1 text-right text-sm text-brand-text focus:outline-none focus:ring-1 focus:ring-brand-accent',
                                        totalMatches(c) ? 'border-brand-success/50' : 'border-brand-accent bg-brand-accent/5',
                                    ]"
                                    @input="setExaminerTotal(c, ($event.target as HTMLInputElement).value)"
                                />
                                <span class="w-10 text-left text-xs text-brand-text-soft">/ 100</span>
                            </div>
                        </div>
                        <div v-if="c.tol_total !== null" class="mt-1.5 flex items-center justify-between text-sm">
                            <span class="text-brand-text-soft">Trinity's total</span>
                            <span :class="c.tol_total === sumOf(c) ? 'text-brand-success' : 'text-brand-accent'">{{ c.tol_total }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="passes(c) ? 'bg-brand-success-soft text-brand-success' : 'bg-brand-surface-soft text-brand-text-soft'"
                            >{{ bandOf(c) }}</span>
                            <span
                                v-if="matchLabel(c).text"
                                class="text-xs"
                                :class="{
                                    'text-brand-success': matchLabel(c).tone === 'ok',
                                    'text-brand-accent': matchLabel(c).tone === 'flag',
                                    'text-brand-text-soft': matchLabel(c).tone === 'muted',
                                }"
                            >{{ matchLabel(c).text }}</span>
                        </div>
                    </div>

                    <!-- Flags -->
                    <ul v-if="liveFlags(c).length" class="mt-3 space-y-1">
                        <li v-for="(f, k) in liveFlags(c)" :key="k" class="flex items-start gap-1 text-xs text-brand-accent">
                            <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />{{ f }}
                        </li>
                    </ul>
                    <p v-else class="mt-3 flex items-center gap-1 text-xs text-brand-success">
                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" /> Totals agree — ready to import.
                    </p>

                    <!-- General comments -->
                    <div class="mt-3">
                        <label class="text-xs font-medium text-brand-text-soft">General comments</label>
                        <textarea
                            v-model="c.general_comments"
                            rows="2"
                            spellcheck="true"
                            placeholder="Any overall comments from the foot of the report…"
                            class="mt-1 w-full resize-y rounded-md border border-brand-border bg-brand-surface-soft/40 px-2 py-1 text-xs text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Import -->
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <MyButtonConstructor size="large" variant="primary" :disabled="importing" @click="importResults">
                    <span class="inline-flex items-center gap-2">
                        <Loader2 v-if="importing" class="h-5 w-5 animate-spin" />
                        <Save v-else class="h-5 w-5" />
                        {{ importing ? 'Importing…' : 'Import verified results' }}
                    </span>
                </MyButtonConstructor>
                <MyButtonConstructor size="large" variant="ghost" @click="saveWorkingFile">
                    <span class="inline-flex items-center gap-2"><FileJson class="h-5 w-5" /> Save working file</span>
                </MyButtonConstructor>
                <p class="w-full text-sm text-brand-text-soft sm:w-auto">
                    Import fills the score, band and exam date onto each matching entry. It never overwrites a result an entry already has.
                </p>
            </div>

            <div v-if="result" class="mt-4 rounded-lg border border-brand-success/40 bg-brand-success-soft/40 px-4 py-3 text-sm text-brand-text">
                <p class="flex items-center gap-2 font-semibold text-brand-success">
                    <CheckCircle2 class="h-4 w-4" /> Imported: {{ result.updated }} updated, {{ result.created }} created, {{ result.skipped }} skipped.
                </p>
                <ul v-if="result.warnings.length" class="mt-2 space-y-1">
                    <li v-for="(w, wi) in result.warnings" :key="wi" class="flex items-start gap-1 text-xs text-brand-accent">
                        <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />{{ w }}
                    </li>
                </ul>
            </div>

            <div class="mt-4">
                <button type="button" class="inline-flex items-center gap-2 text-sm font-medium text-brand-accent hover:opacity-70" @click="jsonInput?.click()">
                    <FileJson class="h-4 w-4" /> Load another batch
                </button>
                <input ref="jsonInput" type="file" accept=".json,application/json" class="hidden" @change="onJsonSelected" />
            </div>
        </section>
    </div>
</template>
