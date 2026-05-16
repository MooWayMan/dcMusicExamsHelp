<!-- resources/js/pages/admin/Certificates/Index.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Award, Download, User, Music, Search, Eye, X, Package, Loader2,
  Copy, ExternalLink, CheckCircle2, ChevronUp, ChevronDown, Mail, Send,
} from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface StudentEntry {
  id: number
  candidate_name: string
  instrument: string
  grade: string
  score: number
  result_band: string
  certificate: string
  exam_date: string
}

interface TeacherEntry {
  id: number | null
  name: string
  candidates_count: number
  tier: string | null
}

interface WeeklyStudent {
  id: number
  name: string
  instrument: string
  grade: string
  score: number
  result: string
  certificate: string
}

interface WeeklyGroup {
  teacher_name: string
  applicant_email: string | null
  is_parent_booking: boolean
  booking_role: string | null
  unsent_count: number
  students: WeeklyStudent[]
}

const props = defineProps<{
  students: StudentEntry[]
  teachers: TeacherEntry[]
  studentTemplates: string[]
  teacherTemplates: string[]
  selectedQuarter: number
  selectedYear: number
  weeklyGroups: WeeklyGroup[]
}>()

// Flash data
const page = usePage()
const batchResult = computed(() => (page.props as any).flash?.batch_result ?? null)

// Tab state
const activeTab = ref<'student' | 'teacher'>('student')

// Batch generate — initialise from the URL (or today) so the page stays in sync
const batchQuarter = ref(props.selectedQuarter)
const batchYear = ref(props.selectedYear)
const batchGenerating = ref(false)

// Friendly quarter label for the page header, e.g. "Q1 2026"
const quarterLabel = computed(() => `Q${batchQuarter.value} ${batchYear.value}`)

// When the quarter/year changes, reload the page with the new filter so
// the student + teacher lists update. Inertia keeps scroll + state.
watch([batchQuarter, batchYear], ([q, y]) => {
  router.get(
    '/admin/certificates',
    { quarter: q, year: y },
    {
      preserveScroll: true,
      preserveState: true,
      only: ['students', 'teachers', 'selectedQuarter', 'selectedYear', 'weeklyGroups'],
    },
  )
})

// ────────────────────────────────────────────────────────────
// Weekly Send — accordion state + email helpers
// ────────────────────────────────────────────────────────────

// Which teacher row in the accordion is currently expanded.
const expandedWeeklyTeacher = ref<string | null>(null)
function toggleWeeklyTeacher(name: string) {
  expandedWeeklyTeacher.value = expandedWeeklyTeacher.value === name ? null : name
}

// Marking-in-progress flag per teacher row (disables the button while
// the POST is in flight so Paul can't double-fire and double-stamp).
const markingSent = ref<Record<string, boolean>>({})

const totalWeeklyTeachers = computed(() => props.weeklyGroups.length)
const totalWeeklyStudents = computed(() =>
  props.weeklyGroups.reduce((acc, g) => acc + g.unsent_count, 0),
)

/**
 * Strip surnames off "Mr Smith" / "Mrs Jones" / "Daniel Rogers" so the
 * Hi line reads naturally. Matches recipientGreetingName() in QuarterEnd.
 */
function recipientGreetingName(fullName: string): string {
  if (!fullName) return ''
  const stripped = fullName.replace(/^(Mr|Mrs|Ms|Miss|Dr|Mx)\.?\s+/i, '').trim()
  return stripped.split(/\s+/)[0]
}

/**
 * Last month of the currently-selected quarter, used in the weekly email
 * so the "quarter-end summary" line reads correctly for any Q1–Q4.
 *   Q1 → March   Q2 → June   Q3 → September   Q4 → December
 */
const quarterEndMonthName = computed(() => {
  return ['March', 'June', 'September', 'December'][props.selectedQuarter - 1] ?? 'June'
})

/**
 * Build the weekly cert-delivery email body.
 *
 * Mid-quarter drip — much lighter than the QuarterEnd celebration template.
 * No top-scorer announcement (premature mid-quarter), no badge promise (the
 * teacher might not qualify), no Faber/prize-draw pitch. Just: "Trinity
 * released results, here's the cert, parent script for your forwarding."
 *
 * Plural-aware so "one of your students" / "3 of your students" reads
 * right whether the teacher has 1 or many unsent results this week.
 */
function buildWeeklyEmail(group: WeeklyGroup): string {
  const firstName = recipientGreetingName(group.teacher_name)
  const count = group.students.length

  const intro = count === 1
    ? `Quick update — Trinity has released results for one of your students:`
    : `Quick update — Trinity has released results for ${count} of your students:`

  const studentList = group.students
    .map(s => `  • ${s.name} — ${s.instrument} Grade ${s.grade} — ${s.score} (${s.result}) — ${s.certificate}`)
    .join('\n')

  const certSentence = count === 1
    ? `The personalised musicExams.help certificate is attached (Trinity have already sent the official certificate separately).`
    : `The personalised musicExams.help certificates are attached (Trinity have already sent the official certificates separately).`

  const recognitionSentence = count === 1
    ? `They'll also appear on the Recognition page at https://musicexams.help/recognition (first name + surname initial only, as per GDPR).`
    : `They'll also appear on the Recognition page at https://musicexams.help/recognition (first names + surname initial only, as per GDPR).`

  const parentPsLead = count === 1
    ? `P.S. Here's a message you can send to the parent with their child's certificate:`
    : `P.S. Here's a message you can send to parents with their children's certificates:`

  // Parent-forward script — includes the Top Scorer carrot so parents know
  // their child is in the running for a gift token. Timing matches the
  // QuarterEnd email policy: 6 weeks after the quarter ends, once digital
  // results are in.
  const parentScript = `"Hi [Parent Name], I've recently partnered with musicExams.help, a platform that supports teachers, parents and students taking Trinity exams. Your child now receives a personalised certificate — please find it attached. They also appear on the Recognition page at https://musicexams.help/recognition (first name and surname initial only). They're also in the running for our quarterly Top Scorer award — winners receive a gift token, announced around 6 weeks after the quarter ends once all results (including digital) are in. If you'd like their full name displayed, just email musicexams@musicexams.help."`

  return `Hi ${firstName},

${intro}

${studentList}

${certSentence}

${recognitionSentence}

I'll send the full quarter-end summary at the end of ${quarterEndMonthName.value} with prize draw results and top scorer awards.

Best wishes,
Paul

${parentPsLead}

${parentScript}`
}

function copyWeeklyEmail(group: WeeklyGroup) {
  // Parent / self-bookers get the parent-direct variant — no "your students"
  // language, no teacher-prize-draw talk, addressed straight to the booker.
  const body = group.is_parent_booking
    ? buildWeeklyParentEmail(group)
    : buildWeeklyEmail(group)
  navigator.clipboard.writeText(body)
  alert('Weekly email copied to clipboard! Now click "Open in Gmail" to compose.')
}

/**
 * Direct-to-parent weekly variant — for parent/self bookings where the
 * recipient IS the candidate's parent (or the candidate themselves), not
 * the teacher. Mirrors the parent-direct logic in QuarterEnd Step 2 but
 * trimmed for mid-quarter drip.
 */
function buildWeeklyParentEmail(group: WeeklyGroup): string {
  const firstName = recipientGreetingName(group.teacher_name)
  const count = group.students.length

  const candidateFirstNames = group.students.map(s => s.name.split(' ')[0])
  const namesSentence = count === 1
    ? candidateFirstNames[0]
    : count === 2
      ? `${candidateFirstNames[0]} and ${candidateFirstNames[1]}`
      : `${candidateFirstNames.slice(0, -1).join(', ')} and ${candidateFirstNames.slice(-1)[0]}`

  const studentList = group.students
    .map(s => `  • ${s.name} — ${s.instrument} Grade ${s.grade} — ${s.score} (${s.result}) — ${s.certificate}`)
    .join('\n')

  const certSentence = count === 1
    ? `The personalised musicExams.help certificate is attached (Trinity have already sent the official certificate separately).`
    : `The personalised musicExams.help certificates are attached (Trinity have already sent the official certificates separately).`

  const recognitionSentence = count === 1
    ? `${namesSentence} will also appear on the Recognition page at https://musicexams.help/recognition — first name and surname initial only, for GDPR. If you'd like the full name shown, just reply and say the word.`
    : `${namesSentence} will also appear on the Recognition page at https://musicexams.help/recognition — first names and surname initial only, for GDPR. If you'd like full names shown, just reply and say the word.`

  const topScorerLine = count === 1
    ? `${namesSentence} is also in the running for our quarterly Top Scorer award — winners receive a gift token, announced around 6 weeks after the quarter ends once all results (including digital) are in.`
    : `${namesSentence} are also in the running for our quarterly Top Scorer award — winners receive a gift token, announced around 6 weeks after the quarter ends once all results (including digital) are in.`

  return `Hi ${firstName},

Quick update — Trinity has released results for ${namesSentence}:

${studentList}

${certSentence}

${recognitionSentence}

${topScorerLine}

I'll send the full quarter-end summary at the end of ${quarterEndMonthName.value} with prize draw results and top scorer awards.

Thanks for choosing centre 120.

Best wishes,
Paul Sheridan`
}

function openWeeklyGmail(group: WeeklyGroup) {
  if (!group.applicant_email) return
  const subjectText = group.is_parent_booking
    ? `musicExams.help Certificate${group.students.length > 1 ? 's' : ''} — ${group.students.map(s => s.name.split(' ')[0]).join(' & ')}`
    : `Trinity Exam Result${group.students.length > 1 ? 's' : ''} — Your Student${group.students.length > 1 ? 's' : ''} Did Brilliantly!`
  const subject = encodeURIComponent(subjectText)
  const to = encodeURIComponent(group.applicant_email)
  window.open(`https://mail.google.com/mail/?view=cm&to=${to}&su=${subject}`, '_blank')
}

/**
 * Flip certificate_sent_at = now() for every student in this teacher's
 * group, then reload the weeklyGroups payload so the row disappears.
 */
function markWeeklyGroupSent(group: WeeklyGroup) {
  if (markingSent.value[group.teacher_name]) return
  markingSent.value[group.teacher_name] = true

  const entryIds = group.students.map(s => s.id)
  fetch('/admin/certificates/mark-sent', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
    },
    body: JSON.stringify({ entry_ids: entryIds }),
  })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => {
      // Refresh the weeklyGroups payload so the marked-sent teacher
      // disappears from the list and the counts update.
      router.reload({ only: ['weeklyGroups'] })
    })
    .catch(() => alert('Could not mark as sent. Try again.'))
    .finally(() => { markingSent.value[group.teacher_name] = false })
}

async function batchGenerate() {
  batchGenerating.value = true
  router.post('/admin/certificates/batch', {
    quarter: batchQuarter.value,
    year: batchYear.value,
  }, {
    preserveScroll: true,
    onFinish: () => { batchGenerating.value = false },
  })
}

// Student form
const selectedEntry = ref<number | null>(null)
const studentTemplate = ref('')
const studentCustomName = ref('')
const studentQuarter = ref('')
const studentSearch = ref('')
const generatingStudent = ref(false)

// Teacher form
const selectedTeacher = ref<number | null>(null)
const teacherTemplate = ref('')
const teacherCustomName = ref('')
const teacherQuarter = ref('')
const teacherSearch = ref('')
const generatingTeacher = ref(false)

// Auto-detect quarter from date string like "7 March 2026"
function getQuarterFromDate(dateStr: string): string {
  const months = ['january','february','march','april','may','june','july','august','september','october','november','december']
  const parts = dateStr.toLowerCase().split(' ')
  const monthIdx = months.findIndex(m => parts.some(p => p.startsWith(m)))
  if (monthIdx === -1) return ''
  const q = Math.ceil((monthIdx + 1) / 3)
  const suffix = ['1st','2nd','3rd','4th'][q - 1]
  const year = parts.find(p => /^\d{4}$/.test(p)) || new Date().getFullYear()
  return `${suffix} Quarter ${year}`
}

// Filtered lists
const filteredStudents = () => {
  if (!studentSearch.value) return props.students
  const q = studentSearch.value.toLowerCase()
  return props.students.filter(s =>
    s.candidate_name.toLowerCase().includes(q) ||
    s.instrument.toLowerCase().includes(q) ||
    s.certificate.toLowerCase().includes(q)
  )
}

const filteredTeachers = () => {
  // Only teachers with a tier (≥10 this quarter) and a linked User record are selectable.
  const base = props.teachers.filter(t => t.tier && t.id !== null)
  if (!teacherSearch.value) return base
  const q = teacherSearch.value.toLowerCase()
  return base.filter(t => t.name.toLowerCase().includes(q))
}

// Auto-select template based on entry
function selectStudentEntry(entry: StudentEntry) {
  selectedEntry.value = entry.id
  studentTemplate.value = entry.certificate
  studentCustomName.value = ''
  studentQuarter.value = getQuarterFromDate(entry.exam_date)
}

function selectTeacherRow(teacher: TeacherEntry) {
  selectedTeacher.value = teacher.id
  // Auto-select matching template
  const tierMap: Record<string, string> = {
    'Bronze': 'Bronze Appreciation Certificate',
    'Silver': 'Silver Appreciation Certificate',
    'Gold': 'Gold Appreciation Certificate',
    'Top Award': 'Top Award Appreciation Certificate',
  }
  teacherTemplate.value = tierMap[teacher.tier ?? ''] ?? ''
  teacherCustomName.value = ''
  // Default to the quarter selected at the top of the page, not "now"
  const suffix = ['1st','2nd','3rd','4th'][batchQuarter.value - 1]
  teacherQuarter.value = `${suffix} Quarter ${batchYear.value}`
}

// Preview state
const previewUrl = ref<string | null>(null)
const previewFilename = ref('')

function closePreview() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = null
  previewFilename.value = ''
}

function downloadFromPreview() {
  if (!previewUrl.value) return
  const a = document.createElement('a')
  a.href = previewUrl.value
  a.download = previewFilename.value || 'certificate.png'
  a.click()
}

async function generateStudentCert(mode: 'preview' | 'download' = 'preview') {
  if (!selectedEntry.value || !studentTemplate.value) return
  generatingStudent.value = true
  closePreview()

  try {
    const response = await fetch('/admin/certificates/student', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        entry_id: selectedEntry.value,
        template: studentTemplate.value,
        custom_name: studentCustomName.value || null,
        quarter: studentQuarter.value || null,
        format: mode === 'preview' ? 'png' : 'pdf',
      }),
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({ error: 'Unknown error' }))
      throw new Error(err.error || 'Failed to generate certificate')
    }

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const filename = response.headers.get('Content-Disposition')?.split('filename="')[1]?.replace('"', '') || 'certificate.png'

    if (mode === 'download') {
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      a.click()
      URL.revokeObjectURL(url)
    } else {
      previewUrl.value = url
      previewFilename.value = filename
    }
  } catch (e: any) {
    alert(e.message || 'Error generating certificate.')
  } finally {
    generatingStudent.value = false
  }
}

async function generateTeacherCert(mode: 'preview' | 'download' = 'preview') {
  if (!selectedTeacher.value || !teacherTemplate.value) return
  generatingTeacher.value = true
  closePreview()

  try {
    const response = await fetch('/admin/certificates/teacher', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        teacher_id: selectedTeacher.value,
        template: teacherTemplate.value,
        custom_name: teacherCustomName.value || null,
        quarter: teacherQuarter.value || null,
        format: mode === 'preview' ? 'png' : 'pdf',
      }),
    })

    if (!response.ok) throw new Error('Failed to generate certificate')

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const filename = response.headers.get('Content-Disposition')?.split('filename="')[1]?.replace('"', '') || 'certificate.png'

    if (mode === 'download') {
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      a.click()
      URL.revokeObjectURL(url)
    } else {
      previewUrl.value = url
      previewFilename.value = filename
    }
  } catch (e) {
    alert('Error generating certificate. Make sure Intervention Image is installed.')
  } finally {
    generatingTeacher.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader
      title="Certificate Generator"
      subtitle="Generate personalised certificates for students and teachers"
      eyebrow="Admin"
      size="compact"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <Award class="h-5 w-5 text-brand-accent" />
          <span class="text-sm text-white/80">{{ quarterLabel }} · {{ students.length }} entries · {{ teachers.filter(t => t.tier).length }} eligible teachers</span>
        </div>
      </template>
    </PageHeader>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- BATCH GENERATE SECTION -->
      <div class="mb-8 rounded-xl border-2 border-brand-accent bg-brand-surface p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <Package class="h-6 w-6 text-brand-accent" />
          <h2 class="text-lg font-bold text-brand-text">Batch Generate All Certificates</h2>
        </div>
        <p class="text-sm text-brand-text-soft mb-4">
          Generate all student certificates for a quarter in one go. Creates a ZIP per teacher plus a master ZIP with everything.
        </p>
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Quarter</label>
            <select
              v-model="batchQuarter"
              class="rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none"
            >
              <option :value="1">Q1 (Jan–Mar)</option>
              <option :value="2">Q2 (Apr–Jun)</option>
              <option :value="3">Q3 (Jul–Sep)</option>
              <option :value="4">Q4 (Oct–Dec)</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Year</label>
            <select
              v-model="batchYear"
              class="rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none"
            >
              <option :value="2026">2026</option>
              <option :value="2027">2027</option>
            </select>
          </div>
          <MyButtonConstructor
            size="medium"
            variant="primary"
            :icon="batchGenerating ? Loader2 : Package"
            :disabled="batchGenerating"
            @click="batchGenerate"
          >
            {{ batchGenerating ? 'Generating...' : 'Generate All Certificates' }}
          </MyButtonConstructor>
        </div>

        <!-- Batch results -->
        <div v-if="batchResult" class="mt-6 rounded-lg border border-brand-success bg-brand-success-soft p-4">
          <h3 class="text-sm font-bold text-brand-text mb-2">
            ✅ {{ batchResult.total }} certificates generated for {{ batchResult.quarter_label }}
          </h3>
          <div class="space-y-2 mb-4">
            <div v-for="(count, teacher) in batchResult.teachers" :key="teacher" class="flex items-center justify-between text-sm">
              <span class="font-medium text-brand-text">{{ teacher }}</span>
              <div class="flex items-center gap-3">
                <span class="text-brand-text-soft">{{ count }} cert{{ count > 1 ? 's' : '' }}</span>
                <a
                  v-if="batchResult.download_links[teacher]"
                  :href="`/admin/certificates/download/${batchResult.download_links[teacher]}`"
                  class="inline-flex items-center gap-1 rounded bg-brand-accent px-2 py-1 text-xs font-semibold text-white hover:opacity-90 transition"
                >
                  <Download class="h-3 w-3" /> ZIP
                </a>
              </div>
            </div>
          </div>
          <a
            v-if="batchResult.master_zip"
            :href="`/admin/certificates/download/${batchResult.master_zip}`"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-bold text-white hover:opacity-90 transition"
          >
            <Download class="h-4 w-4" /> Download All (Master ZIP)
          </a>
        </div>
      </div>

      <!-- ─────────────────────────────────────────────────── -->
      <!-- WEEKLY SEND — mid-quarter drip of new results       -->
      <!-- Only shows when there are unsent scored entries in  -->
      <!-- the selected quarter.                               -->
      <!-- ─────────────────────────────────────────────────── -->
      <div v-if="weeklyGroups.length" class="mb-8 rounded-xl border-2 border-brand-accent bg-brand-surface p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <Send class="h-6 w-6 text-brand-accent" />
          <h2 class="text-lg font-bold text-brand-text">Send This Week's Results</h2>
        </div>
        <p class="text-sm text-brand-text-soft mb-4">
          Trinity has released results for {{ totalWeeklyStudents }} student{{ totalWeeklyStudents !== 1 ? 's' : '' }} across
          {{ totalWeeklyTeachers }} teacher{{ totalWeeklyTeachers !== 1 ? 's' : '' }}/parent{{ totalWeeklyTeachers !== 1 ? 's' : '' }} this quarter.
          Email each one their certificate, then mark them as sent so they don't reappear next week.
        </p>

        <div class="space-y-3">
          <div
            v-for="group in weeklyGroups"
            :key="group.teacher_name"
            class="rounded-lg border border-brand-border bg-white transition"
          >
            <!-- Header / toggle row -->
            <div
              class="flex items-center justify-between px-4 py-3 cursor-pointer"
              @click="toggleWeeklyTeacher(group.teacher_name)"
            >
              <div class="flex items-center gap-3">
                <div>
                  <span class="font-bold text-brand-text">{{ group.teacher_name }}</span>
                  <span v-if="group.booking_role === 'self'" class="ml-2 inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                    Self booking
                  </span>
                  <span v-else-if="group.is_parent_booking" class="ml-2 inline-block rounded-full bg-brand-success/10 px-2 py-0.5 text-xs font-semibold text-brand-success">
                    Parent booking
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-3 text-sm text-brand-text-soft">
                <span>{{ group.unsent_count }} cert{{ group.unsent_count !== 1 ? 's' : '' }} to send</span>
                <ChevronUp v-if="expandedWeeklyTeacher === group.teacher_name" class="h-4 w-4" />
                <ChevronDown v-else class="h-4 w-4" />
              </div>
            </div>

            <!-- Expanded detail -->
            <div v-if="expandedWeeklyTeacher === group.teacher_name" class="border-t border-brand-border px-4 py-4 space-y-4">
              <!-- Email -->
              <div v-if="group.applicant_email" class="text-sm">
                <span class="text-brand-text-soft">Email:</span>
                <a :href="`mailto:${group.applicant_email}`" class="ml-1 font-medium text-brand-accent hover:underline">{{ group.applicant_email }}</a>
              </div>

              <!-- Students table -->
              <div class="overflow-x-auto rounded-lg border border-brand-border">
                <table class="w-full text-sm">
                  <thead class="bg-brand-surface-soft">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Student</th>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Instrument</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Grade</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Score</th>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Result</th>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Certificate</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="student in group.students" :key="student.id" class="border-t border-brand-border">
                      <td class="px-3 py-2"><span class="font-medium text-brand-text">{{ student.name }}</span></td>
                      <td class="px-3 py-2"><span class="text-sm text-brand-text-soft">{{ student.instrument }}</span></td>
                      <td class="px-3 py-2 text-center"><span class="text-sm text-brand-text-soft">{{ student.grade }}</span></td>
                      <td class="px-3 py-2 text-center text-sm font-bold text-brand-text">{{ student.score }}</td>
                      <td class="px-3 py-2">
                        <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                          :class="{
                            'bg-brand-success-soft text-brand-success': student.result === 'Distinction',
                            'bg-brand-accent/10 text-brand-accent': student.result === 'Merit',
                            'bg-brand-surface-soft text-brand-text-soft': student.result === 'Pass',
                          }">
                          {{ student.result }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                          {{ student.certificate }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Orphaned bucket guidance -->
              <div v-if="!group.applicant_email" class="rounded-lg border border-dashed border-amber-400 bg-amber-50 p-3 text-sm text-amber-900">
                <p class="font-semibold mb-1">No contact linked yet</p>
                <p>These candidates were booked without a named teacher or parent. Look up the correspondence email on Trinity's candidate page and link the contact before emailing.</p>
              </div>

              <!-- Action buttons -->
              <div v-if="group.applicant_email" class="flex flex-wrap gap-2">
                <button
                  class="inline-flex items-center gap-1.5 rounded-lg bg-brand-primary px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                  @click="copyWeeklyEmail(group)"
                >
                  <Copy class="h-4 w-4" /> Copy Weekly Email
                </button>
                <button
                  class="inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                  @click="openWeeklyGmail(group)"
                >
                  <ExternalLink class="h-4 w-4" /> Open in Gmail
                </button>
                <button
                  class="inline-flex items-center gap-1.5 rounded-lg border border-brand-accent bg-brand-accent/10 px-3 py-2 text-sm font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition disabled:opacity-50"
                  :disabled="markingSent[group.teacher_name]"
                  @click="markWeeklyGroupSent(group)"
                >
                  <CheckCircle2 class="h-4 w-4" />
                  {{ markingSent[group.teacher_name] ? 'Marking…' : 'Mark as Sent' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- "All caught up" — keeps the page useful even when nothing's queued -->
      <div v-else class="mb-8 rounded-xl border border-brand-border bg-brand-surface-soft p-6 text-center">
        <Mail class="mx-auto h-8 w-8 text-brand-text-soft mb-2" />
        <p class="text-sm font-semibold text-brand-text">All caught up — no new results to email this week</p>
        <p class="text-xs text-brand-text-soft mt-1">As soon as Trinity releases more {{ quarterLabel }} results, the affected teachers will appear here.</p>
      </div>

      <!-- Tabs -->
      <div class="mb-6 flex gap-2">
        <button
          class="rounded-lg px-4 py-2 text-sm font-semibold transition"
          :class="activeTab === 'student'
            ? 'bg-brand-accent text-white'
            : 'bg-brand-surface text-brand-text hover:bg-brand-surface-soft'"
          @click="activeTab = 'student'"
        >
          <Music class="mr-1.5 inline h-4 w-4" />
          Student Certificates
        </button>
        <button
          class="rounded-lg px-4 py-2 text-sm font-semibold transition"
          :class="activeTab === 'teacher'
            ? 'bg-brand-accent text-white'
            : 'bg-brand-surface text-brand-text hover:bg-brand-surface-soft'"
          @click="activeTab = 'teacher'"
        >
          <User class="mr-1.5 inline h-4 w-4" />
          Teacher Certificates
        </button>
      </div>

      <!-- STUDENT TAB -->
      <div v-if="activeTab === 'student'" class="space-y-6">
        <!-- Generate panel — always visible at top when a student is selected -->
        <div v-if="selectedEntry" class="rounded-lg border border-brand-accent bg-brand-surface p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-bold text-brand-text">Generate Certificate</h3>
          <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Template</label>
              <select
                v-model="studentTemplate"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none"
              >
                <option value="" disabled>Select template</option>
                <option v-for="t in studentTemplates" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="flex-1">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Custom name (optional)</label>
              <input
                v-model="studentCustomName"
                type="text"
                placeholder="Leave blank for candidate name"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none"
              />
            </div>
            <div class="w-48">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Quarter</label>
              <input
                v-model="studentQuarter"
                type="text"
                placeholder="e.g. 1st Quarter 2026"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none"
              />
            </div>
            <div class="flex items-end gap-2">
              <MyButtonConstructor
                size="small"
                variant="primary"
                :icon="Eye"
                :disabled="!studentTemplate || generatingStudent"
                @click="generateStudentCert('preview')"
              >
                {{ generatingStudent ? 'Generating...' : 'Preview' }}
              </MyButtonConstructor>
              <MyButtonConstructor
                size="small"
                variant="outline"
                :icon="Download"
                :disabled="!studentTemplate || generatingStudent"
                @click="generateStudentCert('download')"
              >
                Download
              </MyButtonConstructor>
            </div>
          </div>
        </div>

        <!-- Preview panel -->
        <div v-if="previewUrl && activeTab === 'student'" class="rounded-lg border border-brand-border bg-brand-surface p-4 shadow-sm">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-brand-text">Certificate Preview</h3>
            <div class="flex items-center gap-2">
              <button
                class="rounded-lg bg-brand-accent px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 transition"
                @click="downloadFromPreview"
              >
                <Download class="mr-1 inline h-3 w-3" /> Download this
              </button>
              <button
                class="rounded p-1 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text transition"
                @click="closePreview"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>
          <div class="flex justify-center rounded-lg bg-brand-bg p-4">
            <img :src="previewUrl" :alt="previewFilename" class="max-h-[600px] w-auto rounded shadow-lg" />
          </div>
        </div>

        <!-- Search -->
        <div class="relative">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
          <input
            v-model="studentSearch"
            type="text"
            placeholder="Search by name, instrument or certificate type..."
            class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
          />
        </div>

        <!-- Entries table -->
        <div class="overflow-x-auto rounded-lg border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary text-white">
              <tr>
                <th class="px-3 py-2 text-left font-semibold">Name</th>
                <th class="px-3 py-2 text-left font-semibold">Instrument</th>
                <th class="px-3 py-2 text-center font-semibold">Grade</th>
                <th class="px-3 py-2 text-center font-semibold">Score</th>
                <th class="px-3 py-2 text-left font-semibold">Certificate</th>
                <th class="px-3 py-2 text-left font-semibold">Date</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in filteredStudents()"
                :key="entry.id"
                class="cursor-pointer border-t border-brand-border transition hover:bg-brand-surface-soft"
                :class="{ 'bg-brand-accent/10 ring-1 ring-brand-accent': selectedEntry === entry.id }"
                @click="selectStudentEntry(entry)"
              >
                <td class="px-3 py-2"><span class="font-medium text-brand-text">{{ entry.candidate_name }}</span></td>
                <td class="px-3 py-2"><span class="text-sm text-brand-text-soft">{{ entry.instrument }}</span></td>
                <td class="px-3 py-2 text-center"><span class="text-sm text-brand-text-soft">{{ entry.grade }}</span></td>
                <td class="px-3 py-2 text-center"><span class="text-sm font-medium text-brand-text">{{ entry.score }}</span></td>
                <td class="px-3 py-2">
                  <span class="inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                    {{ entry.certificate }}
                  </span>
                </td>
                <td class="px-3 py-2"><span class="text-sm text-brand-text-soft">{{ entry.exam_date }}</span></td>
              </tr>
              <tr v-if="filteredStudents().length === 0">
                <td colspan="6" class="px-3 py-8 text-center text-brand-text-soft">No entries found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TEACHER TAB -->
      <div v-if="activeTab === 'teacher'" class="space-y-6">
        <!-- Generate panel — always visible at top when a teacher is selected -->
        <div v-if="selectedTeacher" class="rounded-lg border border-brand-accent bg-brand-surface p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-bold text-brand-text">Generate Certificate</h3>
          <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Template</label>
              <select
                v-model="teacherTemplate"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text focus:border-brand-accent focus:outline-none"
              >
                <option value="" disabled>Select template</option>
                <option v-for="t in teacherTemplates" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="flex-1">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Custom name (optional)</label>
              <input
                v-model="teacherCustomName"
                type="text"
                placeholder="Leave blank for teacher's registered name"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none"
              />
            </div>
            <div class="w-48">
              <label class="mb-1 block text-xs font-semibold text-brand-text-soft">Quarter</label>
              <input
                v-model="teacherQuarter"
                type="text"
                placeholder="e.g. 1st Quarter 2026"
                class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none"
              />
            </div>
            <div class="flex items-end gap-2">
              <MyButtonConstructor
                size="small"
                variant="primary"
                :icon="Eye"
                :disabled="!teacherTemplate || generatingTeacher"
                @click="generateTeacherCert('preview')"
              >
                {{ generatingTeacher ? 'Generating...' : 'Preview' }}
              </MyButtonConstructor>
              <MyButtonConstructor
                size="small"
                variant="outline"
                :icon="Download"
                :disabled="!teacherTemplate || generatingTeacher"
                @click="generateTeacherCert('download')"
              >
                Download
              </MyButtonConstructor>
            </div>
          </div>
        </div>

        <!-- Preview panel -->
        <div v-if="previewUrl && activeTab === 'teacher'" class="rounded-lg border border-brand-border bg-brand-surface p-4 shadow-sm">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-brand-text">Certificate Preview</h3>
            <div class="flex items-center gap-2">
              <button
                class="rounded-lg bg-brand-accent px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 transition"
                @click="downloadFromPreview"
              >
                <Download class="mr-1 inline h-3 w-3" /> Download this
              </button>
              <button
                class="rounded p-1 text-brand-text-soft hover:bg-brand-surface-soft hover:text-brand-text transition"
                @click="closePreview"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>
          <div class="flex justify-center rounded-lg bg-brand-bg p-4">
            <img :src="previewUrl" :alt="previewFilename" class="max-h-[600px] w-auto rounded shadow-lg" />
          </div>
        </div>

        <!-- Search -->
        <div class="relative">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
          <input
            v-model="teacherSearch"
            type="text"
            placeholder="Search by teacher name..."
            class="w-full rounded-lg border border-brand-border bg-brand-surface py-2 pl-10 pr-4 text-sm text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
          />
        </div>

        <!-- Teachers table -->
        <div class="overflow-x-auto rounded-lg border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary text-white">
              <tr>
                <th class="px-3 py-2 text-left font-semibold">Teacher</th>
                <th class="px-3 py-2 text-center font-semibold">Entries</th>
                <th class="px-3 py-2 text-left font-semibold">Tier</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="teacher in filteredTeachers()"
                :key="teacher.name"
                class="cursor-pointer border-t border-brand-border transition hover:bg-brand-surface-soft"
                :class="{ 'bg-brand-accent/10 ring-1 ring-brand-accent': selectedTeacher === teacher.id }"
                @click="selectTeacherRow(teacher)"
              >
                <td class="px-3 py-2"><span class="font-medium text-brand-text">{{ teacher.name }}</span></td>
                <td class="px-3 py-2 text-center"><span class="text-sm font-medium text-brand-text">{{ teacher.candidates_count }}</span></td>
                <td class="px-3 py-2">
                  <span
                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="{
                      'bg-amber-700/10 text-amber-700': teacher.tier === 'Bronze',
                      'bg-slate-400/10 text-slate-500': teacher.tier === 'Silver',
                      'bg-yellow-500/10 text-yellow-600': teacher.tier === 'Gold',
                      'bg-brand-accent/10 text-brand-accent': teacher.tier === 'Top Award',
                    }"
                  >
                    {{ teacher.tier }}
                  </span>
                </td>
              </tr>
              <tr v-if="filteredTeachers().length === 0">
                <td colspan="3" class="px-3 py-8 text-center text-brand-text-soft">No eligible teachers found (10+ entries required)</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
