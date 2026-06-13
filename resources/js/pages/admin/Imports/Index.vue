<!-- resources/js/pages/admin/Imports/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Upload, FileText, CheckCircle2, AlertCircle, Loader2, Database, X, UploadCloud } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface RecentRun {
    id: number
    type: string
    filename: string | null
    summary: Record<string, unknown> | null
    created_at: string | null
    user_name: string | null
}

const props = defineProps<{
    defaults: { year: number; quarter: number }
    recent: RecentRun[]
}>()

// ──────────────────────────────────────────────────────────────────
// Section 1 — Bulk Orders
// ──────────────────────────────────────────────────────────────────

const ordersFile = ref<File | null>(null)
const ordersYear = ref<number>(props.defaults.year)
const ordersQuarter = ref<number>(props.defaults.quarter)
const ordersPreview = ref<{
    totals: { rows_in_csv: number; in_quarter: number; filtered_out: number; to_create: number; to_update: number }
    toCreate: Array<Record<string, string | number | null>>
    toUpdate: Array<Record<string, string | number | null>>
} | null>(null)
const ordersError = ref<string | null>(null)
const ordersBusy = ref(false)

const yearOptions = [2025, 2026, 2027, 2028]
const quarterOptions = [1, 2, 3, 4]

const ordersDragOver = ref(false)
const ordersInputRef = ref<HTMLInputElement | null>(null)

function setOrdersFile(file: File | null) {
    ordersFile.value = file
    ordersPreview.value = null
    ordersError.value = null
}

function onOrdersFileSelected(e: Event) {
    const target = e.target as HTMLInputElement
    setOrdersFile(target.files && target.files[0] ? target.files[0] : null)
    // Reset the native input so re-selecting the same file fires change again.
    if (target) target.value = ''
}

function onOrdersDrop(e: DragEvent) {
    e.preventDefault()
    ordersDragOver.value = false
    const f = e.dataTransfer?.files?.[0]
    if (!f) return
    if (!/\.csv$/i.test(f.name)) {
        ordersError.value = 'Please drop a .csv file.'
        return
    }
    setOrdersFile(f)
}

function onOrdersDragOver(e: DragEvent) {
    e.preventDefault()
    ordersDragOver.value = true
}
function onOrdersDragLeave() {
    ordersDragOver.value = false
}
function openOrdersPicker() {
    ordersInputRef.value?.click()
}
function onOrdersDropZoneKey(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        openOrdersPicker()
    }
}
function clearOrdersFile() {
    setOrdersFile(null)
}

async function submitOrdersPreview() {
    if (!ordersFile.value) {
        ordersError.value = 'Please choose a CSV file first.'
        return
    }
    ordersBusy.value = true
    ordersError.value = null
    ordersPreview.value = null

    const fd = new FormData()
    fd.append('file', ordersFile.value)
    fd.append('year', String(ordersYear.value))
    fd.append('quarter', String(ordersQuarter.value))

    try {
        const res = await fetch('/admin/imports/preview-orders', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            ordersError.value = data.error || data.message || `Preview failed (${res.status}).`
        } else {
            ordersPreview.value = await res.json()
        }
    } catch (err: unknown) {
        ordersError.value = err instanceof Error ? err.message : 'Preview failed.'
    } finally {
        ordersBusy.value = false
    }
}

function commitOrders() {
    if (!ordersFile.value) return
    const form = useForm({
        file: ordersFile.value,
        year: ordersYear.value,
        quarter: ordersQuarter.value,
    })
    form.post('/admin/imports/commit-orders', {
        forceFormData: true,
        onSuccess: () => {
            ordersFile.value = null
            ordersPreview.value = null
        },
    })
}

// ──────────────────────────────────────────────────────────────────
// Section 2 — Per-candidate triple
// ──────────────────────────────────────────────────────────────────

const enrolmentFile = ref<File | null>(null)
const summaryFile = ref<File | null>(null)
const marksheetFile = ref<File | null>(null)
const dob = ref<string>('')
const applicantEmail = ref<string>('')
// Tracks whether the user has manually typed in the email field, so we
// don't overwrite their value when they swap CSVs.
const applicantEmailUserEdited = ref(false)
// Auto-derived from Submitter Email in the Enrolment CSV when names match.
const autoApplicantEmail = ref<string>('')

// Used to decide whether to show the Applicant Email field. Compare
// Submitter and Applicant names from the Enrolment CSV header preview.
const submitterName = ref<string>('')
const applicantName = ref<string>('')

// Drop-zone state for the per-candidate triple
const candidateDragOver = ref(false)
const candidateInputRef = ref<HTMLInputElement | null>(null)
const candidateClassifyNote = ref<string>('')
const candidateExtrasWarning = ref<string>('')

const candidatePreview = ref<{
    warnings: string[]
    candidate: Record<string, string | null>
    order: Record<string, string | number | null> | null
    derivedRole: string
    roleSuggestion: {
        role: string | null
        reason: string
        matched_contact: { id: number; name: string; types: string[]; matched_by: string; who: string } | null
    }
    derivedEmail: string | null
    fee: number
    instrument: { id: number; name: string } | null
    grade: string | null
    delivery_method: string
    score: number
    result: string | null
    exam_date: string | null
    teacher_name: string | null
    school_name: string | null
    subject_area: string | null
    digital_certificate_id: string | null
} | null>(null)
const candidateError = ref<string | null>(null)
const candidateBusy = ref(false)

// Human-confirmed booking role for the current preview. Trinity gives no
// teacher field, so the role is confirmed every time before commit rather
// than guessed. Pre-filled from the preview's suggestion.
const chosenRole = ref<string | null>(null)
const teacherName = ref<string>('')
const teacherEmail = ref<string>('')
const teacherContactId = ref<number | null>(null)

const roleIsTeacherish = computed(
    () => chosenRole.value === 'teacher' || chosenRole.value === 'school_admin',
)

const canCommit = computed(() => {
    if (!chosenRole.value) return false
    if (roleIsTeacherish.value && !teacherName.value.trim()) return false
    return true
})

const namesDiffer = computed(() =>
    submitterName.value && applicantName.value &&
    submitterName.value.trim().toLowerCase() !== applicantName.value.trim().toLowerCase()
)

const namesMatch = computed(() =>
    submitterName.value && applicantName.value &&
    submitterName.value.trim().toLowerCase() === applicantName.value.trim().toLowerCase()
)

// Decode a CSV file's bytes into text, handling UTF-16 LE/BE BOMs that
// Trinity exports sometimes use.
async function decodeCsv(file: File): Promise<string> {
    const buf = await file.arrayBuffer()
    const bytes = new Uint8Array(buf)
    if (bytes.length >= 2 && bytes[0] === 0xff && bytes[1] === 0xfe) {
        return new TextDecoder('utf-16le').decode(bytes.slice(2))
    }
    if (bytes.length >= 2 && bytes[0] === 0xfe && bytes[1] === 0xff) {
        return new TextDecoder('utf-16be').decode(bytes.slice(2))
    }
    return new TextDecoder('utf-8').decode(bytes)
}

// Read just the first non-empty line of a CSV, BOM-aware. Cheaper than
// decoding the whole file when we only need the header for classification.
async function readFirstLine(file: File): Promise<string> {
    const text = await decodeCsv(file)
    const firstLine = text.split(/\r\n|\n/).find(l => l.trim() !== '') ?? ''
    return firstLine.trim()
}

type CandidateSlot = 'enrolment' | 'summary' | 'marksheet' | null

function classifyHeader(header: string): CandidateSlot {
    // Trinity exports default to Tab Delimited Text File but allow Comma
    // Separated. Real exports also wrap each column in double-quotes.
    // Normalise: tabs→commas, strip outer quotes per cell, so the user can
    // drop either format.
    const cleaned = header
        .replace(/^﻿/, '')
        .replace(/\t/g, ',')
        .split(',')
        .map(c => c.trim().replace(/^"(.*)"$/s, '$1').trim())
        .join(',')
    if (cleaned.startsWith('Examination,Subject,Candidate Number')) return 'enrolment'
    if (cleaned.startsWith('Subject Area,Syllabus,Examination Date')) return 'summary'
    if (cleaned === 'Section #,Mark,Section,Max') return 'marksheet'
    return null
}

// Quick best-effort parse of the Enrolment CSV in-browser so we know
// whether to show the Applicant Email field. Real parsing happens
// server-side; this is just a UX hint.
async function tryReadEnrolmentNames(file: File) {
    submitterName.value = ''
    applicantName.value = ''
    autoApplicantEmail.value = ''
    try {
        const text = await decodeCsv(file)
        const lines = text.split(/\r\n|\n/).filter(l => l.trim() !== '')
        if (lines.length < 2) return
        // Auto-detect delimiter — Trinity exports default to tab-delimited.
        const delimiter = lines[0].includes('\t') ? '\t' : ','
        // Strip outer double-quotes that real Trinity exports wrap text fields with.
        const stripQuotes = (s: string) => s.trim().replace(/^"(.*)"$/s, '$1').trim()
        const headers = lines[0].split(delimiter).map(h => stripQuotes(h).replace(/^﻿/, ''))
        // Skip the first data row only if its candidate number is empty (Centre Commission row).
        for (let i = 1; i < lines.length; i++) {
            const cols = lines[i].split(delimiter)
            if (cols.length < headers.length) continue
            const map: Record<string, string> = {}
            headers.forEach((h, idx) => { map[h] = stripQuotes(cols[idx] ?? '') })
            if (!map['Candidate Number']) continue
            submitterName.value = `${map['Submitter First Name'] ?? ''} ${map['Submitter Last Name'] ?? ''}`.trim()
            applicantName.value = `${map['Applicant First Name'] ?? ''} ${map['Applicant Last Name'] ?? ''}`.trim()
            autoApplicantEmail.value = (map['Submitter Email Address'] ?? '').trim()
            break
        }
    } catch {
        // best-effort only
    }

    // Only auto-fill when names match AND user hasn't typed their own value.
    if (namesMatch.value && autoApplicantEmail.value && !applicantEmailUserEdited.value) {
        applicantEmail.value = autoApplicantEmail.value
    } else if (namesDiffer.value && !applicantEmailUserEdited.value) {
        // Names differ — clear any previous auto-fill so the user pastes the right one.
        applicantEmail.value = ''
    }
}

function onApplicantEmailInput(e: Event) {
    const target = e.target as HTMLInputElement
    applicantEmail.value = target.value
    applicantEmailUserEdited.value = target.value.trim() !== ''
}

async function assignCandidateFile(file: File): Promise<{ ok: boolean; slot: CandidateSlot; replaced: boolean; error?: string }> {
    let header = ''
    try {
        header = await readFirstLine(file)
    } catch {
        return { ok: false, slot: null, replaced: false, error: `Could not read ${file.name}.` }
    }
    const slot = classifyHeader(header)
    if (!slot) {
        return {
            ok: false,
            slot: null,
            replaced: false,
            error: `${file.name}: header doesn't match Enrolment / Summary / Marksheet format.`,
        }
    }
    let replaced = false
    if (slot === 'enrolment') {
        replaced = !!enrolmentFile.value
        enrolmentFile.value = file
        await tryReadEnrolmentNames(file)
    } else if (slot === 'summary') {
        replaced = !!summaryFile.value
        summaryFile.value = file
    } else if (slot === 'marksheet') {
        replaced = !!marksheetFile.value
        marksheetFile.value = file
    }
    return { ok: true, slot, replaced }
}

async function handleCandidateFiles(fileList: FileList | File[]) {
    candidateError.value = null
    candidateClassifyNote.value = ''
    candidateExtrasWarning.value = ''
    candidatePreview.value = null

    const files = Array.from(fileList)
    if (files.length === 0) return

    const errors: string[] = []
    const replacedSlots: string[] = []
    let acceptedCount = 0
    const extras: string[] = []

    for (const f of files) {
        if (acceptedCount >= 3) {
            extras.push(f.name)
            continue
        }
        const res = await assignCandidateFile(f)
        if (!res.ok) {
            if (res.error) errors.push(res.error)
            continue
        }
        acceptedCount++
        if (res.replaced && res.slot) {
            const label = res.slot.charAt(0).toUpperCase() + res.slot.slice(1)
            replacedSlots.push(`Replaced previous ${label} file`)
        }
    }

    if (errors.length) candidateError.value = errors.join(' ')
    if (replacedSlots.length) candidateClassifyNote.value = replacedSlots.join(' · ')
    if (extras.length) candidateExtrasWarning.value = `Ignored ${extras.length} extra file(s): ${extras.join(', ')}. Drop one file per slot.`
}

function onCandidateFilesSelected(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length) {
        handleCandidateFiles(target.files)
    }
    // Reset so picking the same files again fires change.
    if (target) target.value = ''
}

function onCandidateDrop(e: DragEvent) {
    e.preventDefault()
    candidateDragOver.value = false
    if (e.dataTransfer?.files?.length) {
        handleCandidateFiles(e.dataTransfer.files)
    }
}
function onCandidateDragOver(e: DragEvent) {
    e.preventDefault()
    candidateDragOver.value = true
}
function onCandidateDragLeave() {
    candidateDragOver.value = false
}
function openCandidatePicker() {
    candidateInputRef.value?.click()
}
function onCandidateDropZoneKey(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        openCandidatePicker()
    }
}

function clearEnrolment() {
    enrolmentFile.value = null
    submitterName.value = ''
    applicantName.value = ''
    autoApplicantEmail.value = ''
    if (!applicantEmailUserEdited.value) applicantEmail.value = ''
    candidatePreview.value = null
    candidateError.value = null
}
function clearSummary() {
    summaryFile.value = null
    candidatePreview.value = null
    candidateError.value = null
}
function clearMarksheet() {
    marksheetFile.value = null
    candidatePreview.value = null
    candidateError.value = null
}
function clearAllCandidate() {
    enrolmentFile.value = null
    summaryFile.value = null
    marksheetFile.value = null
    submitterName.value = ''
    applicantName.value = ''
    autoApplicantEmail.value = ''
    if (!applicantEmailUserEdited.value) applicantEmail.value = ''
    candidatePreview.value = null
    candidateError.value = null
    candidateClassifyNote.value = ''
    candidateExtrasWarning.value = ''
}

const applicantEmailHelp = computed(() => {
    if (!enrolmentFile.value) {
        return 'Auto-fills when you select an Enrolment CSV.'
    }
    if (namesDiffer.value) {
        return `Submitter (${submitterName.value}) and Applicant (${applicantName.value}) differ — paste the applicant email from the Trinity order screen.`
    }
    if (namesMatch.value) {
        return 'Pre-filled from Submitter (names match). Edit if the applicant is actually a different person.'
    }
    return ''
})

const allCandidateFilesPicked = computed(() =>
    !!enrolmentFile.value && !!summaryFile.value && !!marksheetFile.value
)

async function submitCandidatePreview() {
    if (!allCandidateFilesPicked.value) {
        candidateError.value = 'All three CSVs are required.'
        return
    }
    candidateBusy.value = true
    candidateError.value = null
    candidatePreview.value = null

    const fd = new FormData()
    fd.append('enrolment', enrolmentFile.value!)
    fd.append('summary', summaryFile.value!)
    fd.append('marksheet', marksheetFile.value!)
    if (dob.value) fd.append('date_of_birth', dob.value)
    if (applicantEmail.value) fd.append('applicant_email', applicantEmail.value)

    try {
        const res = await fetch('/admin/imports/preview-candidate', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            candidateError.value = data.error || data.message || `Preview failed (${res.status}).`
        } else {
            candidatePreview.value = await res.json()

            // Pre-fill the role selector from the suggestion (the human still
            // confirms). When the suggestion matched an existing teacher /
            // school admin, carry its id so we reuse that exact contact;
            // otherwise pre-fill the teacher fields from the applicant.
            const sug = candidatePreview.value?.roleSuggestion
            chosenRole.value = sug?.role ?? null
            const teacherish = sug?.role === 'teacher' || sug?.role === 'school_admin'
            if (teacherish && sug?.matched_contact) {
                teacherContactId.value = sug.matched_contact.id
                teacherName.value = sug.matched_contact.name
                teacherEmail.value = ''
            } else {
                teacherContactId.value = null
                teacherName.value = candidatePreview.value?.candidate.applicant_name ?? ''
                teacherEmail.value = candidatePreview.value?.derivedEmail ?? ''
            }
        }
    } catch (err: unknown) {
        candidateError.value = err instanceof Error ? err.message : 'Preview failed.'
    } finally {
        candidateBusy.value = false
    }
}

function commitCandidate() {
    if (!allCandidateFilesPicked.value || !canCommit.value) return
    const form = useForm({
        enrolment: enrolmentFile.value as File,
        summary: summaryFile.value as File,
        marksheet: marksheetFile.value as File,
        date_of_birth: dob.value || null,
        applicant_email: applicantEmail.value || null,
        booking_role: chosenRole.value,
        teacher_contact_id: teacherContactId.value,
        teacher_name: roleIsTeacherish.value ? teacherName.value || null : null,
        teacher_email: roleIsTeacherish.value ? teacherEmail.value || null : null,
    })
    form.post('/admin/imports/commit-candidate', {
        forceFormData: true,
        onSuccess: () => {
            enrolmentFile.value = null
            summaryFile.value = null
            marksheetFile.value = null
            dob.value = ''
            applicantEmail.value = ''
            applicantEmailUserEdited.value = false
            autoApplicantEmail.value = ''
            candidatePreview.value = null
            submitterName.value = ''
            applicantName.value = ''
            candidateClassifyNote.value = ''
            candidateExtrasWarning.value = ''
            chosenRole.value = null
            teacherName.value = ''
            teacherEmail.value = ''
            teacherContactId.value = null
        },
    })
}

function inputClass() {
    return 'w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent'
}

// Human-readable summary for the Recent Imports table — replaces the raw
// JSON dump so the table reads at a glance.
function formatRunSummary(run: { type: string; summary: Record<string, unknown> | null }): string {
    const s = run.summary ?? {}
    if (run.type === 'candidate_triple') {
        const name = (s.candidate_name as string) || 'Candidate'
        const verb = s.created_entry ? 'Created' : (s.updated_entry ? 'Updated' : 'No change')
        return `${verb} — ${name}`
    }
    if (run.type === 'bulk_orders') {
        const year = s.year ?? '?'
        const quarter = s.quarter ?? '?'
        const created = s.created ?? 0
        const updated = s.updated ?? 0
        return `Q${quarter} ${year}: ${created} created, ${updated} updated`
    }
    return JSON.stringify(s)
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-4">
            <Upload class="h-8 w-8 text-brand-accent" />
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-text-soft">Admin</p>
                <h1 class="text-2xl font-bold text-brand-text">Import</h1>
                <p class="mt-1 text-sm text-brand-text-soft">Bulk-load Trinity CSV exports — quarter orders or per-candidate triples.</p>
            </div>
        </div>

        <!-- ───────── Section 1: Bulk Orders ───────── -->
        <section class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <Database class="h-6 w-6 text-brand-accent" />
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">1. Bulk Orders CSV</h2>
                    <p class="text-sm text-brand-text-soft">Upload a Trinity orders export and pick the quarter. Filters by Requested Start Date. Idempotent on Order #.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-brand-text">Orders CSV</label>
                    <div
                        role="button"
                        tabindex="0"
                        :aria-label="ordersFile ? `Selected ${ordersFile.name}. Press Enter to replace.` : 'Drop CSV here or press Enter to browse'"
                        :class="[
                            'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 text-center transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-accent',
                            ordersDragOver
                                ? 'border-brand-accent bg-brand-accent/10'
                                : 'border-brand-border bg-brand-surface-soft hover:border-brand-accent/60',
                        ]"
                        @click="openOrdersPicker"
                        @keydown="onOrdersDropZoneKey"
                        @dragover="onOrdersDragOver"
                        @dragleave="onOrdersDragLeave"
                        @drop="onOrdersDrop"
                    >
                        <UploadCloud class="h-6 w-6 text-brand-accent" />
                        <p v-if="!ordersFile" class="mt-2 text-sm text-brand-text">
                            Drop a CSV here, or <span class="font-semibold text-brand-accent">browse</span>
                        </p>
                        <p v-if="!ordersFile" class="mt-1 text-xs text-brand-text-soft">.csv only · single file</p>
                        <div v-else class="mt-2 flex items-center gap-2 text-sm text-brand-text">
                            <FileText class="h-4 w-4 text-brand-accent" />
                            <span class="font-mono">{{ ordersFile.name }}</span>
                            <button
                                type="button"
                                class="ml-1 inline-flex items-center gap-1 rounded-md border border-brand-border bg-brand-surface px-2 py-0.5 text-xs text-brand-text-soft hover:text-brand-danger"
                                @click.stop="clearOrdersFile"
                            >
                                <X class="h-3 w-3" /> Remove
                            </button>
                        </div>
                    </div>
                    <input
                        ref="ordersInputRef"
                        type="file"
                        accept=".csv,.CSV,.txt,.TXT,.tsv,.TSV"
                        class="hidden"
                        @change="onOrdersFileSelected"
                    />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Year</label>
                        <select v-model.number="ordersYear" :class="inputClass()">
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-brand-text">Quarter</label>
                        <select v-model.number="ordersQuarter" :class="inputClass()">
                            <option v-for="q in quarterOptions" :key="q" :value="q">Q{{ q }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="ordersError" class="mt-4 flex items-start gap-2 rounded-lg border border-brand-danger/40 bg-brand-danger/10 p-3 text-sm text-brand-danger">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ ordersError }}</span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <MyButtonConstructor variant="primary" size="medium" :disabled="!ordersFile || ordersBusy" @click="submitOrdersPreview">
                    <Loader2 v-if="ordersBusy" class="h-4 w-4 animate-spin" />
                    <span v-else>Preview</span>
                </MyButtonConstructor>
                <MyButtonConstructor v-if="ordersPreview" variant="success" size="medium" @click="commitOrders">
                    Commit ({{ ordersPreview.totals.to_create }} new, {{ ordersPreview.totals.to_update }} update)
                </MyButtonConstructor>
            </div>

            <div v-if="ordersPreview" class="mt-5 rounded-lg border border-brand-border bg-brand-surface-soft p-4">
                <h3 class="mb-3 text-base font-semibold text-brand-text">Preview</h3>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                    <div><span class="text-brand-text-soft">Rows in CSV:</span> {{ ordersPreview.totals.rows_in_csv }}</div>
                    <div><span class="text-brand-text-soft">In quarter:</span> {{ ordersPreview.totals.in_quarter }}</div>
                    <div><span class="text-brand-text-soft">Filtered out:</span> {{ ordersPreview.totals.filtered_out }}</div>
                    <div class="font-semibold"><span class="text-brand-text-soft">To create:</span> {{ ordersPreview.totals.to_create }}</div>
                    <div class="font-semibold"><span class="text-brand-text-soft">To update:</span> {{ ordersPreview.totals.to_update }}</div>
                </div>

                <details v-if="ordersPreview.toCreate.length" class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-brand-accent">Show new orders ({{ ordersPreview.toCreate.length }})</summary>
                    <ul class="mt-2 space-y-1 text-sm text-brand-text">
                        <li v-for="(o, i) in ordersPreview.toCreate" :key="i" class="font-mono">
                            {{ o.order_number }} — {{ o.delivery_method }} — {{ o.requested_start_date }} — {{ o.candidates }} candidates
                        </li>
                    </ul>
                </details>
                <details v-if="ordersPreview.toUpdate.length" class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-brand-accent">Show updates ({{ ordersPreview.toUpdate.length }})</summary>
                    <ul class="mt-2 space-y-1 text-sm text-brand-text">
                        <li v-for="(o, i) in ordersPreview.toUpdate" :key="i" class="font-mono">
                            {{ o.order_number }} — {{ o.delivery_method }} — {{ o.requested_start_date }}
                        </li>
                    </ul>
                </details>
            </div>
        </section>

        <!-- ───────── Section 2: Per-candidate Triple ───────── -->
        <section class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <FileText class="h-6 w-6 text-brand-accent" />
                <div>
                    <h2 class="text-xl font-semibold text-brand-text">2. Per-Candidate Triple</h2>
                    <p class="text-sm text-brand-text-soft">Upload Enrolment + Summary + Marksheet CSVs for a single candidate. Auto-derives booking role, instrument, grade, score.</p>
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="block text-sm font-medium text-brand-text">Candidate CSVs (Enrolment + Summary + Marksheet)</label>
                    <button
                        v-if="enrolmentFile || summaryFile || marksheetFile"
                        type="button"
                        class="text-xs font-medium text-brand-text-soft hover:text-brand-danger"
                        @click="clearAllCandidate"
                    >
                        Clear all
                    </button>
                </div>
                <div
                    role="button"
                    tabindex="0"
                    aria-label="Drop up to three candidate CSVs here or press Enter to browse"
                    :class="[
                        'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-8 text-center transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-accent',
                        candidateDragOver
                            ? 'border-brand-accent bg-brand-accent/10'
                            : 'border-brand-border bg-brand-surface-soft hover:border-brand-accent/60',
                    ]"
                    @click="openCandidatePicker"
                    @keydown="onCandidateDropZoneKey"
                    @dragover="onCandidateDragOver"
                    @dragleave="onCandidateDragLeave"
                    @drop="onCandidateDrop"
                >
                    <UploadCloud class="h-7 w-7 text-brand-accent" />
                    <p class="mt-2 text-sm text-brand-text">
                        Drop up to 3 CSVs here, or <span class="font-semibold text-brand-accent">browse</span>
                    </p>
                    <p class="mt-1 text-xs text-brand-text-soft">Files are auto-classified into Enrolment, Summary, and Marksheet by their headers.</p>
                </div>
                <input
                    ref="candidateInputRef"
                    type="file"
                    accept=".csv,.CSV,.txt,.TXT,.tsv,.TSV"
                    multiple
                    class="hidden"
                    @change="onCandidateFilesSelected"
                />

                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div
                        :class="[
                            'flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm',
                            enrolmentFile
                                ? 'border-brand-success/40 bg-brand-success/10 text-brand-text'
                                : 'border-brand-border bg-brand-surface text-brand-text-soft',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <FileText :class="enrolmentFile ? 'h-4 w-4 shrink-0 text-brand-success' : 'h-4 w-4 shrink-0 text-brand-text-soft'" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide">Enrolment</p>
                                <p class="truncate font-mono text-xs">{{ enrolmentFile ? enrolmentFile.name : '—' }}</p>
                            </div>
                        </div>
                        <button
                            v-if="enrolmentFile"
                            type="button"
                            class="shrink-0 text-brand-text-soft hover:text-brand-danger"
                            aria-label="Remove Enrolment file"
                            @click.stop="clearEnrolment"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div
                        :class="[
                            'flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm',
                            summaryFile
                                ? 'border-brand-success/40 bg-brand-success/10 text-brand-text'
                                : 'border-brand-border bg-brand-surface text-brand-text-soft',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <FileText :class="summaryFile ? 'h-4 w-4 shrink-0 text-brand-success' : 'h-4 w-4 shrink-0 text-brand-text-soft'" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide">Summary</p>
                                <p class="truncate font-mono text-xs">{{ summaryFile ? summaryFile.name : '—' }}</p>
                            </div>
                        </div>
                        <button
                            v-if="summaryFile"
                            type="button"
                            class="shrink-0 text-brand-text-soft hover:text-brand-danger"
                            aria-label="Remove Summary file"
                            @click.stop="clearSummary"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div
                        :class="[
                            'flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm',
                            marksheetFile
                                ? 'border-brand-success/40 bg-brand-success/10 text-brand-text'
                                : 'border-brand-border bg-brand-surface text-brand-text-soft',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <FileText :class="marksheetFile ? 'h-4 w-4 shrink-0 text-brand-success' : 'h-4 w-4 shrink-0 text-brand-text-soft'" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide">Marksheet</p>
                                <p class="truncate font-mono text-xs">{{ marksheetFile ? marksheetFile.name : '—' }}</p>
                            </div>
                        </div>
                        <button
                            v-if="marksheetFile"
                            type="button"
                            class="shrink-0 text-brand-text-soft hover:text-brand-danger"
                            aria-label="Remove Marksheet file"
                            @click.stop="clearMarksheet"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <p v-if="candidateClassifyNote" class="mt-2 text-xs text-brand-text-soft">{{ candidateClassifyNote }}</p>
                <p v-if="candidateExtrasWarning" class="mt-2 text-xs text-brand-warning">{{ candidateExtrasWarning }}</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-brand-text">Date of Birth</label>
                    <input
                        v-model="dob"
                        type="text"
                        inputmode="numeric"
                        placeholder="DD/MM/YYYY"
                        pattern="\d{1,2}[/\-.]\d{1,2}[/\-.]\d{4}"
                        :class="inputClass()"
                    />
                    <p class="mt-1 text-xs text-brand-text-soft">Paste from Trinity (e.g. 10/01/2015) or type manually.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-brand-text">Applicant Email</label>
                    <input
                        :value="applicantEmail"
                        type="email"
                        :class="inputClass()"
                        placeholder="parent@example.com"
                        @input="onApplicantEmailInput"
                    />
                    <p v-if="applicantEmailHelp" class="mt-1 text-xs text-brand-text-soft">{{ applicantEmailHelp }}</p>
                </div>
            </div>

            <div v-if="candidateError" class="mt-4 flex items-start gap-2 rounded-lg border border-brand-danger/40 bg-brand-danger/10 p-3 text-sm text-brand-danger">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ candidateError }}</span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <MyButtonConstructor variant="primary" size="medium" :disabled="!allCandidateFilesPicked || candidateBusy" @click="submitCandidatePreview">
                    <Loader2 v-if="candidateBusy" class="h-4 w-4 animate-spin" />
                    <span v-else>Preview</span>
                </MyButtonConstructor>
                <MyButtonConstructor v-if="candidatePreview" variant="success" size="medium" :disabled="!canCommit" @click="commitCandidate">
                    Commit
                </MyButtonConstructor>
                <span v-if="candidatePreview && !canCommit" class="text-sm text-brand-text-soft">
                    Confirm the role below before committing.
                </span>
            </div>

            <div v-if="candidatePreview" class="mt-5 rounded-lg border border-brand-border bg-brand-surface-soft p-4">
                <h3 class="mb-3 text-base font-semibold text-brand-text">Preview</h3>

                <div v-if="candidatePreview.warnings.length" class="mb-4 rounded-lg border-2 border-brand-danger bg-brand-danger p-4 text-white shadow-md">
                    <p class="flex items-center gap-2 text-base font-bold uppercase tracking-wide">
                        <span aria-hidden="true">⚠</span> Warnings
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-6 text-sm font-medium">
                        <li v-for="(w, i) in candidatePreview.warnings" :key="i">{{ w }}</li>
                    </ul>
                </div>

                <!-- Role confirmation. Trinity gives us no teacher field, so we
                     confirm who the applicant is every time before committing,
                     pre-filled from an evidence-based suggestion. -->
                <div class="mb-4 rounded-lg border border-brand-border bg-brand-surface p-4">
                    <p class="mb-1 text-sm font-semibold text-brand-text">Who is this applicant?</p>
                    <p class="mb-3 text-xs text-brand-text-soft">{{ candidatePreview.roleSuggestion.reason }}</p>

                    <select v-model="chosenRole" class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                        <option :value="null" disabled>— Choose a role —</option>
                        <option value="self">Self — candidate entered themselves (not in draw)</option>
                        <option value="teacher">Teacher — in the prize draw</option>
                        <option value="school_admin">School admin — in the prize draw</option>
                        <option value="parent">Parent — not in the draw</option>
                    </select>

                    <div v-if="roleIsTeacherish" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs text-brand-text-soft">Teacher name</label>
                            <input
                                v-model="teacherName"
                                type="text"
                                placeholder="Teacher's name"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                @input="teacherContactId = null"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-brand-text-soft">Teacher email (optional)</label>
                            <input
                                v-model="teacherEmail"
                                type="email"
                                placeholder="teacher@example.com"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                @input="teacherContactId = null"
                            />
                        </div>
                        <p v-if="teacherContactId" class="text-xs text-brand-accent sm:col-span-2">
                            ✓ Using registered contact “{{ teacherName }}”. Edit the name to use a different teacher.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><span class="text-brand-text-soft">Candidate:</span> {{ candidatePreview.candidate.candidate_name }}</div>
                    <div><span class="text-brand-text-soft">Candidate #:</span> {{ candidatePreview.candidate.candidate_number }}</div>
                    <div><span class="text-brand-text-soft">Applicant:</span> {{ candidatePreview.candidate.applicant_name }}</div>
                    <div><span class="text-brand-text-soft">Applicant Email:</span> {{ candidatePreview.derivedEmail || '—' }}</div>
                    <div><span class="text-brand-text-soft">Booking Role:</span> {{ chosenRole || '(choose above)' }}</div>
                    <div><span class="text-brand-text-soft">Order:</span> {{ candidatePreview.order?.trinity_order_number || 'NOT FOUND' }}</div>
                    <div><span class="text-brand-text-soft">Instrument:</span> {{ candidatePreview.instrument?.name || '(unmapped)' }}</div>
                    <div><span class="text-brand-text-soft">Grade:</span> {{ candidatePreview.grade || '—' }}</div>
                    <div><span class="text-brand-text-soft">Delivery:</span> {{ candidatePreview.delivery_method }}</div>
                    <div><span class="text-brand-text-soft">Fee:</span> £{{ candidatePreview.fee.toFixed(2) }}</div>
                    <div><span class="text-brand-text-soft">Score:</span> {{ candidatePreview.score }}</div>
                    <div><span class="text-brand-text-soft">Result:</span> {{ candidatePreview.result || '—' }}</div>
                    <div><span class="text-brand-text-soft">Exam Date:</span> {{ candidatePreview.exam_date || '—' }}</div>
                    <div><span class="text-brand-text-soft">Teacher:</span> {{ candidatePreview.teacher_name || '—' }}</div>
                    <div><span class="text-brand-text-soft">Subject Area:</span> {{ candidatePreview.subject_area || '—' }}</div>
                    <div><span class="text-brand-text-soft">School:</span> {{ candidatePreview.school_name || '—' }}</div>
                </div>
            </div>
        </section>

        <!-- ───────── Recent Imports ───────── -->
        <section class="mt-6 rounded-xl border border-brand-border bg-brand-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <CheckCircle2 class="h-6 w-6 text-brand-accent" />
                <h2 class="text-xl font-semibold text-brand-text">Recent Imports</h2>
            </div>

            <div v-if="recent.length === 0" class="text-sm text-brand-text-soft">No imports recorded yet.</div>
            <table v-else class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-brand-text-soft">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">File</th>
                        <th class="px-3 py-2">Summary</th>
                        <th class="px-3 py-2">By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="run in recent" :key="run.id" class="border-t border-brand-border">
                        <td class="px-3 py-2 font-mono text-brand-text">{{ run.created_at }}</td>
                        <td class="px-3 py-2 text-brand-text">{{ run.type }}</td>
                        <td class="px-3 py-2 text-brand-text-soft">{{ run.filename || '—' }}</td>
                        <td class="px-3 py-2 text-brand-text">
                            {{ formatRunSummary(run) }}
                        </td>
                        <td class="px-3 py-2 text-brand-text-soft">{{ run.user_name || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
