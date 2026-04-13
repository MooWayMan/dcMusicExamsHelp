<!-- resources/js/pages/admin/QuarterEnd/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Award, CheckCircle2, Circle, Download, Package,
  Trophy, Users, Clock, Star, ChevronDown, ChevronUp, Copy,
  Gift, Sparkles, Loader2, ExternalLink
} from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface Student {
  name: string
  instrument: string
  grade: string
  score: number
  result: string
  certificate: string
  method: string
}

interface Teacher {
  teacher_name: string
  applicant_email: string | null
  applicant_name: string | null
  total_entries: number
  with_results: number
  pending: number
  distinctions: number
  merits: number
  passes: number
  badge_tier: string | null
  total_all_time: number
  students: Student[]
}

interface Summary {
  total_entries: number
  with_results: number
  pending: number
  total_fees: string
  teacher_count: number
  has_pending: boolean
  showstopper: { name: string; full_name: string; score: number; instrument: string } | null
  centre_stage: { name: string; full_name: string; score: number; instrument: string } | null
}

interface EligibleTeacher {
  name: string
  entries: number
  is_registered: boolean
  eligible: boolean
  reason: string
}

interface PrizeDrawData {
  student_tickets: Array<{ name: string; instrument: string; grade: string; teacher: string }>
  teacher_tickets: Array<{ name: string; entries: number; is_registered: boolean }>
  eligible_teachers: EligibleTeacher[]
  student_ticket_count: number
  teacher_ticket_count: number
}

interface ExistingDraw {
  winner_name: string
  winner_instrument?: string
  winner_grade?: string
  winner_teacher?: string
  winner_entries?: number
  total_tickets: number
  created_at: string
}

const props = defineProps<{
  quarter: number
  year: number
  quarterLabel: string
  teachers: Teacher[]
  summary: Summary
  prizeDraw: PrizeDrawData
  existingDraws: {
    student: ExistingDraw | null
    teacher: ExistingDraw | null
  }
}>()

const page = usePage()
const batchResult = computed(() => (page.props as any).flash?.batch_result ?? null)

// Track which teachers have been "done"
const completedTeachers = ref<Record<string, boolean>>({})
const expandedTeacher = ref<string | null>(null)

function toggleTeacher(name: string) {
  expandedTeacher.value = expandedTeacher.value === name ? null : name
}

function markDone(name: string) {
  completedTeachers.value[name] = !completedTeachers.value[name]
}

const completedCount = computed(() => Object.values(completedTeachers.value).filter(Boolean).length)

// Step tracking — default to step 2 if certificates have already been generated (ZIP exists in flash or previous visit)
const currentStep = ref(batchResult.value ? 2 : 1)

// Batch generate
const batchGenerating = ref(false)
function batchGenerate() {
  batchGenerating.value = true
  router.post('/admin/certificates/batch', {
    quarter: props.quarter,
    year: props.year,
  }, {
    preserveScroll: true,
    onFinish: () => {
      batchGenerating.value = false
      currentStep.value = 2
    },
  })
}

// Copy email template to clipboard
function copyEmailTemplate(teacher: Teacher) {
  const studentList = teacher.students
    .map(s => `  • ${s.name} — ${s.instrument} Grade ${s.grade} — ${s.score} (${s.result}) — ${s.certificate}`)
    .join('\n')

  const badgeText = teacher.badge_tier
    ? `\n\nI'm also pleased to award you a ${teacher.badge_tier} Certificate of Appreciation for entering ${teacher.total_all_time}+ candidates through centre 120. Thank you for your continued support!\n`
    : ''

  // Top scorer mentions (Showstopper + Centre Stage)
  const showstopperText = props.summary.showstopper
    ? `Showstopper (highest Distinction): ${props.summary.showstopper.name} with ${props.summary.showstopper.score} marks on ${props.summary.showstopper.instrument}`
    : ''
  const centreStageText = props.summary.centre_stage
    ? `Centre Stage (highest Merit): ${props.summary.centre_stage.name} with ${props.summary.centre_stage.score} marks on ${props.summary.centre_stage.instrument}`
    : ''
  const topScorerParts = [showstopperText, centreStageText].filter(Boolean)
  const topScorerText = topScorerParts.length
    ? `\n\nQuarterly award winners:\n  • ${topScorerParts.join('\n  • ')}\nBoth receive a gift token and a personalised certificate.\n`
    : ''

  // Student prize draw winner — teacher needs to pass the gift token on
  // Use first name + initial only (GDPR — no full names without consent)
  const winnerShortName = studentWinner.value
    ? studentWinner.value.name.split(' ').length > 1
      ? `${studentWinner.value.name.split(' ')[0]} ${studentWinner.value.name.split(' ').slice(-1)[0][0].toUpperCase()}`
      : studentWinner.value.name
    : ''
  const studentDrawText = studentWinner.value
    ? `\n\nStudent Prize Draw\nThe winner of the £50 gift token this quarter is ${winnerShortName} (${studentWinner.value.instrument} Grade ${studentWinner.value.grade}) — congratulations! Every student entered through centre 120 was in the draw.${studentWinner.value.teacher === teacher.teacher_name ? ' As their teacher, I\'ll be in touch with you separately about getting the prize to them.' : ''}\n`
    : ''

  const firstName = teacher.teacher_name.split(' ')[0]

  const template = `Hi ${firstName},

Quick heads-up — I've moved to a new email address: musicexams@musicexams.help. Please save this for future correspondence.

Before I get to the good stuff: do you have any students who booked their exam through centre 120 in 2026 but booked directly or through a parent? If so, reply with their names so I can link them to you — it counts towards your Teacher Appreciation badge and extra tickets in the teacher prize draw!

---

Your Students' Results

Your students have done brilliantly! Here are the results:

${studentList}${teacher.pending > 0 ? `\n\nNote: ${teacher.pending} of your students are still awaiting results — I'll be in touch as soon as they come through.\n` : ''}

Everything is in the attached ZIP file — just double-click to open it. Inside you'll find:
  • Personalised certificates for each student
  • A results report (PDF)
  • A spreadsheet (CSV)

Every student receives at least a Bravo Certificate, with Merit earning a Take a Bow Certificate and Distinction earning a Standing Ovation Certificate.${badgeText}${topScorerText}

---

Prize Draws

Every quarter we run two prize draws — one for students, one for teachers. Every student entry through centre 120 earns one ticket.
${studentDrawText}
Teacher draw: taking place in the coming weeks. The prize is a £50 gift token to help buy musical instruments for your school. The more students linked to you, the more tickets you have — that's why the question at the top matters!

The teacher draw result won't be published on the website — no competition between teachers. Winners can see their result privately by logging in, and you're welcome to promote it on your own channels if you win!

Top Scorer awards and gift tokens are announced around 6 weeks after the quarter ends, once all results (including digital) are in. Keep an eye on musicExams.help!

---

Introducing musicExams.help

I've recently launched musicExams.help — a free resource for teachers, parents and students booking Trinity exams through centre 120. If parents ever ask things like "what's the difference between digital and face-to-face?" — point them straight to the site.

Highlights:
  • Student recognition — Hall of Fame, certificates and quarterly prize draws
  • Teacher awards — Bronze, Silver, Gold and Top Award badges
  • Faber music book discounts for teachers
  • Booking made easy across all 3 Trinity systems

Have a look: https://musicexams.help

We'd love any feedback — even a quick "looks good" helps!

---

Thank you for everything you do for your students — and for choosing to enter them through centre 120. It really is appreciated.

Best wishes,
Paul

P.S. Here's a message you can send to parents with their child's certificate:

"Hi [Parent Name], I've recently partnered with musicExams.help, a platform that supports teachers, parents and students taking Trinity exams. Your child now receives a personalised certificate — please find it attached. They also appear on the Recognition page at https://musicexams.help/recognition (first name and surname initial only). If you'd like their full name displayed, just email musicexams@musicexams.help."`

  navigator.clipboard.writeText(template)
  alert('Email template copied to clipboard! Now click "Open in Gmail" to compose.')
}

function openGmailCompose(teacher: Teacher) {
  const subject = encodeURIComponent(`${props.quarterLabel} Exam Results — Your Students Did Brilliantly!`)
  const to = encodeURIComponent(teacher.applicant_email || '')
  window.open(`https://mail.google.com/mail/?view=cm&to=${to}&su=${subject}`, '_blank')
}

// Copy prize winner email (to send to the winning student's teacher)
function copyWinnerEmail(teacher: Teacher) {
  const firstName = teacher.teacher_name.split(' ')[0]
  const winner = studentWinner.value
  if (!winner) return

  const winnerFirst = winner.name.split(' ')[0]
  const winnerInitial = winner.name.split(' ').length > 1
    ? `${winnerFirst} ${winner.name.split(' ').slice(-1)[0][0].toUpperCase()}`
    : winnerFirst

  const template = `Hi ${firstName},

Great news — one of your students, ${winnerInitial}, has won the ${props.quarterLabel} student prize draw! They'll be receiving a £50 Amazon gift token.

Here's the gift card code for you to pass on to their parent/guardian:

[PASTE GIFT CARD CODE HERE]

They can add this to any Amazon account — it's not tied to a name or email.

Their name will appear on the musicExams.help Recognition page as "${winnerInitial}". If they or their parent would like us to display their full name instead, just let me know and I'll update it.

Congratulations to them — and well done to you for entering them through centre 120!`

  navigator.clipboard.writeText(template)
  alert('Winner email copied to clipboard!')
}

// Copy heads-up email (to send from OLD email address)
function copyHeadsUpEmail(teacher: Teacher) {
  const firstName = teacher.teacher_name.split(' ')[0]

  const template = `Hi ${firstName},

Just a quick note — I've sent you your ${props.quarterLabel} exam results and certificates from my new email address: musicexams@musicexams.help

If you can't see it, please check your junk/spam folder and mark it as safe. This is the address I'll be using going forward for everything related to Trinity exams through centre 120.

Thanks,
Paul`

  navigator.clipboard.writeText(template)
  alert('Heads-up email copied to clipboard!')
}

// Sample email for teachers to forward certificates to parents
function copyParentEmailSample() {
  const template = `Here's a suggested email you can send to parents along with their child's certificate:

---

Dear [Parent Name],

I'm pleased to let you know that [Student Name] has received their ${props.quarterLabel} Trinity College London exam result!

They achieved a score of [Score] — [Result (Pass/Merit/Distinction)] — in [Instrument] Grade [Grade]. Well done to them!

I've attached their personalised certificate from musicExams.help, which recognises their achievement through centre 120.

Their result will also appear on the musicExams.help Recognition page at https://musicexams.help/recognition — have a look when you get a chance!

If you have any questions about future exams or would like to book their next grade, just let me know.

Best wishes,
[Your Name]`

  navigator.clipboard.writeText(template)
  alert('Sample parent email copied to clipboard!')
}

// Quarter selector
function changeQuarter(q: number, y: number) {
  router.get('/admin/quarter-end', { quarter: q, year: y }, { preserveState: false })
}

// --- PRIZE DRAW ---
const drawingStudent = ref(false)
const drawingTeacher = ref(false)
const studentTestWinner = ref<{ name: string; instrument: string; grade: string; teacher: string } | null>(null)
const teacherTestWinner = ref<{ name: string; entries: number; is_registered: boolean } | null>(null)
const studentRealDone = ref(!!props.existingDraws.student)
const teacherRealDone = ref(!!props.existingDraws.teacher)
const studentRealWinner = ref(props.existingDraws.student)
const teacherRealWinner = ref(props.existingDraws.teacher)
const testDrawCount = ref({ student: 0, teacher: 0 })

function getXsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function runDraw(type: 'student' | 'teacher', mode: 'test' | 'real') {
  if (type === 'student') drawingStudent.value = true
  else drawingTeacher.value = true

  // Real draw confirmation
  if (mode === 'real') {
    const confirmed = confirm(`This is the REAL ${type} prize draw. The result will be permanently recorded and cannot be changed. Continue?`)
    if (!confirmed) {
      if (type === 'student') drawingStudent.value = false
      else drawingTeacher.value = false
      return
    }
  }

  try {
    const res = await fetch('/admin/quarter-end/draw', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getXsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ type, quarter: props.quarter, year: props.year, mode }),
    })

    if (!res.ok) {
      const err = await res.json()
      alert(err.error || `Could not run ${type} draw`)
      return
    }

    const data = await res.json()

    if (type === 'student') {
      if (mode === 'test') {
        studentTestWinner.value = data.winner
        testDrawCount.value.student++
      } else {
        studentRealWinner.value = { winner_name: data.winner.name, winner_instrument: data.winner.instrument, winner_grade: data.winner.grade, winner_teacher: data.winner.teacher, total_tickets: data.total_tickets, created_at: new Date().toISOString() }
        studentRealDone.value = true
      }
    } else {
      if (mode === 'test') {
        teacherTestWinner.value = data.winner
        testDrawCount.value.teacher++
      } else {
        teacherRealWinner.value = { winner_name: data.winner.name, winner_entries: data.winner.entries, total_tickets: data.total_tickets, created_at: new Date().toISOString() }
        teacherRealDone.value = true
      }
    }
  } catch (e) {
    alert(`Could not run ${type} draw`)
  } finally {
    if (type === 'student') drawingStudent.value = false
    else drawingTeacher.value = false
  }
}

// For the email template — use real winner if available, otherwise null
const studentWinner = computed(() => {
  if (studentRealWinner.value) return {
    name: studentRealWinner.value.winner_name,
    instrument: studentRealWinner.value.winner_instrument ?? '',
    grade: studentRealWinner.value.winner_grade ?? '',
    teacher: studentRealWinner.value.winner_teacher ?? '',
  }
  return null
})
const teacherWinner = computed(() => {
  if (teacherRealWinner.value) return {
    name: teacherRealWinner.value.winner_name,
    entries: teacherRealWinner.value.winner_entries ?? 0,
    is_registered: false,
  }
  return null
})
</script>

<template>
  <div>
    <PageHeader
      :title="`Quarter End — ${quarterLabel}`"
      subtitle="Step-by-step guide to sending certificates, badges and emails to teachers"
      eyebrow="Admin"
      size="compact"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <select
            :value="quarter"
            class="rounded-lg border border-brand-border bg-brand-surface px-2 py-1 text-sm"
            @change="changeQuarter(Number(($event.target as HTMLSelectElement).value), year)"
          >
            <option :value="1">Q1</option>
            <option :value="2">Q2</option>
            <option :value="3">Q3</option>
            <option :value="4">Q4</option>
          </select>
          <select
            :value="year"
            class="rounded-lg border border-brand-border bg-brand-surface px-2 py-1 text-sm"
            @change="changeQuarter(quarter, Number(($event.target as HTMLSelectElement).value))"
          >
            <option :value="2026">2026</option>
            <option :value="2027">2027</option>
          </select>
        </div>
      </template>
    </PageHeader>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

      <!-- SUMMARY CARDS -->
      <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Users class="mx-auto mb-2 h-6 w-6 text-brand-accent" />
          <p class="text-2xl font-bold text-brand-text">{{ summary.teacher_count }}</p>
          <p class="text-xs text-brand-text-soft">Teachers</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Award class="mx-auto mb-2 h-6 w-6 text-brand-accent" />
          <p class="text-2xl font-bold text-brand-text">{{ summary.with_results }}</p>
          <p class="text-xs text-brand-text-soft">Certificates to send</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Clock class="mx-auto mb-2 h-6 w-6 text-amber-500" />
          <p class="text-2xl font-bold text-brand-text">{{ summary.pending }}</p>
          <p class="text-xs text-brand-text-soft">Still awaiting results</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Trophy class="mx-auto mb-2 h-6 w-6 text-yellow-500" />
          <p class="text-2xl font-bold text-brand-text" v-if="summary.has_pending">—</p>
          <p class="text-2xl font-bold text-brand-text" v-else-if="summary.showstopper">{{ summary.showstopper.score }}</p>
          <p class="text-2xl font-bold text-brand-text" v-else>—</p>
          <p class="text-xs text-brand-text-soft" v-if="summary.has_pending">Results pending</p>
          <p class="text-xs text-brand-text-soft" v-else-if="summary.showstopper">Showstopper: {{ summary.showstopper.name }}</p>
          <p class="text-xs text-brand-text-soft" v-else>Top Scorers</p>
        </div>
      </div>

      <!-- PROGRESS BAR -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-brand-text">Progress: {{ completedCount }} / {{ teachers.length }} teachers done</span>
          <span class="text-sm text-brand-text-soft">{{ Math.round((completedCount / Math.max(teachers.length, 1)) * 100) }}%</span>
        </div>
        <div class="h-3 rounded-full bg-brand-surface-soft overflow-hidden">
          <div
            class="h-full rounded-full bg-brand-accent transition-all duration-500"
            :style="{ width: `${(completedCount / Math.max(teachers.length, 1)) * 100}%` }"
          />
        </div>
      </div>

      <!-- STEP TABS -->
      <div class="mb-6 flex gap-2">
        <button
          v-for="(stepLabel, idx) in ['1. Generate Certificates', '2. Email Teachers', '3. Prize Draws']"
          :key="idx"
          class="rounded-lg px-4 py-2 text-sm font-semibold transition"
          :class="currentStep === idx + 1
            ? 'bg-brand-accent text-white'
            : 'bg-brand-surface-soft text-brand-text-soft hover:bg-brand-surface hover:text-brand-text'"
          @click="currentStep = idx + 1"
        >
          {{ stepLabel }}
        </button>
      </div>

      <!-- STEPS -->
      <div class="mb-8 space-y-4">

        <!-- STEP 1: Generate certificates -->
        <div v-show="currentStep === 1" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold bg-brand-accent text-white">
              <CheckCircle2 v-if="batchResult" class="h-5 w-5" />
              <span v-else>1</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Generate All Certificates</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            This creates a ZIP file per teacher containing all their students' certificates, plus a master ZIP with everything.
          </p>
          <MyButtonConstructor
            v-if="!batchResult"
            size="medium"
            variant="primary"
            :icon="batchGenerating ? Clock : Package"
            :disabled="batchGenerating"
            @click="batchGenerate"
          >
            {{ batchGenerating ? 'Generating... please wait' : `Generate All ${quarterLabel} Certificates` }}
          </MyButtonConstructor>

          <!-- Results -->
          <div v-if="batchResult" class="mt-3 rounded-lg border border-brand-success bg-brand-success-soft p-4">
            <p class="text-sm font-bold text-brand-text mb-3">{{ batchResult.total }} certificates generated</p>
            <div class="flex flex-wrap gap-2">
              <a
                v-if="batchResult.master_zip"
                :href="`/admin/certificates/download/${batchResult.master_zip}`"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-bold text-white hover:opacity-90 transition"
              >
                <Download class="h-4 w-4" /> Download All (Master ZIP)
              </a>
              <MyButtonConstructor size="small" variant="outline" @click="currentStep = 2">
                Next: Email Teachers →
              </MyButtonConstructor>
            </div>
          </div>
        </div>

        <!-- STEP 2: Email each teacher -->
        <div v-show="currentStep === 2" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
              :class="completedCount === teachers.length ? 'bg-brand-success text-white' : currentStep >= 2 ? 'bg-brand-accent text-white' : 'bg-brand-border text-brand-text-soft'">
              <CheckCircle2 v-if="completedCount === teachers.length" class="h-5 w-5" />
              <span v-else>2</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Email Each Teacher</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            Go through each teacher below. Download their ZIP, copy the email template, paste into Gmail, attach the ZIP, and send. Tick them off as you go.
          </p>

          <div v-if="currentStep >= 2" class="space-y-3">
            <!-- Advance to Step 3 -->
            <div class="flex justify-end">
              <MyButtonConstructor size="small" variant="outline" @click="currentStep = 3">
                Next: Prize Draws →
              </MyButtonConstructor>
            </div>
            <div
              v-for="teacher in teachers"
              :key="teacher.teacher_name"
              class="rounded-lg border transition"
              :class="completedTeachers[teacher.teacher_name]
                ? 'border-brand-success bg-brand-success-soft/30'
                : 'border-brand-border bg-white'"
            >
              <!-- Teacher header -->
              <div
                class="flex items-center justify-between px-4 py-3 cursor-pointer"
                @click="toggleTeacher(teacher.teacher_name)"
              >
                <div class="flex items-center gap-3">
                  <button
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 transition"
                    :class="completedTeachers[teacher.teacher_name]
                      ? 'border-brand-success bg-brand-success text-white'
                      : 'border-brand-border hover:border-brand-accent'"
                    @click.stop="markDone(teacher.teacher_name)"
                  >
                    <CheckCircle2 v-if="completedTeachers[teacher.teacher_name]" class="h-4 w-4" />
                  </button>
                  <div>
                    <span class="font-bold text-brand-text">{{ teacher.teacher_name }}</span>
                    <span v-if="teacher.badge_tier" class="ml-2 inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                      {{ teacher.badge_tier }} Badge
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-3 text-sm text-brand-text-soft">
                  <span>{{ teacher.with_results }} cert{{ teacher.with_results !== 1 ? 's' : '' }}</span>
                  <span v-if="teacher.pending > 0" class="text-amber-500">{{ teacher.pending }} pending</span>
                  <ChevronUp v-if="expandedTeacher === teacher.teacher_name" class="h-4 w-4" />
                  <ChevronDown v-else class="h-4 w-4" />
                </div>
              </div>

              <!-- Expanded detail -->
              <div v-if="expandedTeacher === teacher.teacher_name" class="border-t border-brand-border px-4 py-4 space-y-4">
                <!-- Contact -->
                <div v-if="teacher.applicant_email" class="text-sm">
                  <span class="text-brand-text-soft">Email:</span>
                  <a :href="`mailto:${teacher.applicant_email}`" class="ml-1 font-medium text-brand-accent hover:underline">{{ teacher.applicant_email }}</a>
                </div>

                <!-- Student results table -->
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
                      <tr v-for="(student, i) in teacher.students" :key="i" class="border-t border-brand-border">
                        <td class="px-3 py-2 font-medium">{{ student.name }}</td>
                        <td class="px-3 py-2">{{ student.instrument }}</td>
                        <td class="px-3 py-2 text-center">{{ student.grade }}</td>
                        <td class="px-3 py-2 text-center font-bold" :class="{
                          'text-yellow-600': student.score >= 87,
                          'text-brand-accent': student.score >= 75 && student.score < 87,
                          'text-brand-text': student.score < 75,
                        }">{{ student.score }}</td>
                        <td class="px-3 py-2">{{ student.result }}</td>
                        <td class="px-3 py-2">
                          <span class="inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                            {{ student.certificate }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Summary -->
                <div class="flex flex-wrap gap-4 text-sm">
                  <span v-if="teacher.distinctions" class="font-semibold text-yellow-600">{{ teacher.distinctions }} Distinction{{ teacher.distinctions > 1 ? 's' : '' }}</span>
                  <span v-if="teacher.merits" class="font-semibold text-brand-accent">{{ teacher.merits }} Merit{{ teacher.merits > 1 ? 's' : '' }}</span>
                  <span v-if="teacher.passes" class="font-semibold text-brand-text">{{ teacher.passes }} Pass{{ teacher.passes > 1 ? 'es' : '' }}</span>
                  <span v-if="teacher.badge_tier" class="font-semibold text-brand-success">🏆 {{ teacher.badge_tier }} Badge ({{ teacher.total_all_time }} entries all-time)</span>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-2">
                  <a
                    v-if="batchResult?.download_links?.[teacher.teacher_name]"
                    :href="`/admin/certificates/download/${batchResult.download_links[teacher.teacher_name]}`"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                  >
                    <Download class="h-4 w-4" /> Download ZIP
                  </a>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-primary px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="copyEmailTemplate(teacher)"
                  >
                    <Copy class="h-4 w-4" /> Copy Email Template
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="openGmailCompose(teacher)"
                  >
                    <ExternalLink class="h-4 w-4" /> Open in Gmail
                  </button>
                  <button
                    v-if="studentWinner && studentWinner.teacher === teacher.teacher_name"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="copyWinnerEmail(teacher)"
                  >
                    <Gift class="h-4 w-4" /> Copy Winner Email
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-surface border border-brand-border px-3 py-2 text-sm font-semibold text-brand-text hover:bg-brand-surface-soft transition"
                    @click="copyHeadsUpEmail(teacher)"
                  >
                    <Copy class="h-4 w-4" /> Copy Heads-Up (Old Email)
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="completedTeachers[teacher.teacher_name]
                      ? 'bg-brand-success text-white'
                      : 'bg-brand-success-soft text-brand-success border border-brand-success hover:bg-brand-success hover:text-white'"
                    @click="markDone(teacher.teacher_name)"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                    {{ completedTeachers[teacher.teacher_name] ? 'Done!' : 'Mark as Sent' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: Prize Draws -->
        <div v-show="currentStep === 3" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
              :class="(studentWinner && teacherWinner) ? 'bg-brand-success text-white' : currentStep >= 3 ? 'bg-brand-accent text-white' : 'bg-brand-border text-brand-text-soft'">
              <CheckCircle2 v-if="studentWinner && teacherWinner" class="h-5 w-5" />
              <span v-else>3</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Prize Draws & Top Scorer Awards</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            Run the quarterly prize draws. Every student entry = one ticket. Eligible teachers get one ticket per entry they submitted.
          </p>

          <div class="space-y-6">

            <!-- Top Scorers — only shown when all results are in -->
            <div v-if="summary.has_pending" class="rounded-lg border border-brand-border bg-brand-surface-soft p-4">
              <div class="flex items-center gap-2">
                <Clock class="h-5 w-5 text-brand-text-soft" />
                <span class="font-semibold text-brand-text-soft">Top scorer awards will appear once all results are in ({{ summary.pending }} still pending)</span>
              </div>
            </div>

            <div v-else-if="summary.showstopper || summary.centre_stage" class="space-y-3">
              <div v-if="summary.showstopper" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Star class="h-5 w-5 text-yellow-600" />
                  <span class="font-bold text-yellow-800">Showstopper — Highest Distinction — {{ quarterLabel }}</span>
                </div>
                <p class="text-sm text-yellow-700">
                  {{ summary.showstopper.name }} — {{ summary.showstopper.instrument }} — {{ summary.showstopper.score }} marks
                </p>
                <p class="mt-1 text-xs text-yellow-600">Full name (admin only): {{ summary.showstopper.full_name }}</p>
              </div>
              <div v-if="summary.centre_stage" class="rounded-lg border border-brand-accent/30 bg-brand-accent/5 p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Trophy class="h-5 w-5 text-brand-accent" />
                  <span class="font-bold text-brand-text">Centre Stage — Highest Merit — {{ quarterLabel }}</span>
                </div>
                <p class="text-sm text-brand-text-soft">
                  {{ summary.centre_stage.name }} — {{ summary.centre_stage.instrument }} — {{ summary.centre_stage.score }} marks
                </p>
                <p class="mt-1 text-xs text-brand-text-soft">Full name (admin only): {{ summary.centre_stage.full_name }}</p>
              </div>
            </div>

            <!-- Student Prize Draw -->
            <div class="rounded-lg border border-brand-border p-4">
              <div class="flex items-center gap-2 mb-2">
                <Gift class="h-5 w-5 text-brand-accent" />
                <span class="font-bold text-brand-text">Student Prize Draw</span>
                <span class="ml-auto text-xs text-brand-text-soft">{{ prizeDraw.student_ticket_count }} tickets in the draw</span>
              </div>

              <p class="text-sm text-brand-text-soft mb-3">Every student who entered through centre 120 this quarter has one ticket per entry.</p>

              <!-- Already drawn (real) -->
              <div v-if="studentRealDone && studentRealWinner" class="rounded-lg border-2 border-brand-success bg-brand-success-soft p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Trophy class="h-5 w-5 text-brand-success" />
                  <span class="font-bold text-brand-text">Official Winner (recorded)</span>
                </div>
                <p class="text-lg font-bold text-brand-text">{{ studentRealWinner.winner_name }}</p>
                <p class="text-sm text-brand-text-soft">{{ studentRealWinner.winner_instrument }} Grade {{ studentRealWinner.winner_grade }} — Teacher: {{ studentRealWinner.winner_teacher }}</p>
                <p class="mt-2 text-xs text-brand-text-soft">Drawn from {{ studentRealWinner.total_tickets }} tickets. This result is permanently recorded.</p>
              </div>

              <!-- Not yet drawn -->
              <div v-else class="space-y-3">
                <!-- Practice draw result -->
                <div v-if="studentTestWinner" class="rounded-lg border border-dashed border-brand-accent bg-brand-accent/5 p-3">
                  <div class="flex items-center gap-2 mb-1">
                    <Sparkles class="h-4 w-4 text-brand-accent" />
                    <span class="text-sm font-semibold text-brand-accent">Practice Draw #{{ testDrawCount.student }}</span>
                  </div>
                  <p class="font-bold text-brand-text">{{ studentTestWinner.name }}</p>
                  <p class="text-xs text-brand-text-soft">{{ studentTestWinner.instrument }} Grade {{ studentTestWinner.grade }} — Teacher: {{ studentTestWinner.teacher }}</p>
                  <p class="mt-1 text-xs text-brand-text-soft italic">This is just a practice — not recorded.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <MyButtonConstructor
                    size="small"
                    variant="outline"
                    :icon="drawingStudent ? Loader2 : Sparkles"
                    :disabled="drawingStudent"
                    @click="runDraw('student', 'test')"
                  >
                    {{ drawingStudent ? 'Drawing...' : 'Practice Draw' }}
                  </MyButtonConstructor>

                  <MyButtonConstructor
                    size="medium"
                    variant="primary"
                    :icon="drawingStudent ? Loader2 : Trophy"
                    :disabled="drawingStudent"
                    @click="runDraw('student', 'real')"
                  >
                    {{ drawingStudent ? 'Drawing...' : 'Run REAL Student Draw' }}
                  </MyButtonConstructor>
                </div>
              </div>
            </div>

            <!-- Teacher Prize Draw -->
            <div class="rounded-lg border border-brand-border p-4">
              <div class="flex items-center gap-2 mb-2">
                <Award class="h-5 w-5 text-brand-accent" />
                <span class="font-bold text-brand-text">Teacher Prize Draw</span>
                <span class="ml-auto text-xs text-brand-text-soft">{{ prizeDraw.teacher_ticket_count }} tickets in the draw</span>
              </div>

              <!-- Eligibility table -->
              <div class="mb-3 overflow-x-auto rounded-lg border border-brand-border">
                <table class="w-full text-sm">
                  <thead class="bg-brand-surface-soft">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Applicant/Teacher</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Entries</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Eligible?</th>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in prizeDraw.eligible_teachers" :key="t.name" class="border-t border-brand-border">
                      <td class="px-3 py-2 font-medium">
                        {{ t.name }}
                        <span v-if="t.is_registered" class="ml-1 inline-block rounded-full bg-brand-accent/10 px-1.5 py-0.5 text-xs text-brand-accent">registered</span>
                      </td>
                      <td class="px-3 py-2 text-center">{{ t.entries }}</td>
                      <td class="px-3 py-2 text-center">
                        <CheckCircle2 v-if="t.eligible" class="inline h-4 w-4 text-brand-success" />
                        <span v-else class="text-brand-text-soft">—</span>
                      </td>
                      <td class="px-3 py-2 text-brand-text-soft">{{ t.reason }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Already drawn (real) -->
              <div v-if="teacherRealDone && teacherRealWinner" class="rounded-lg border-2 border-brand-success bg-brand-success-soft p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Trophy class="h-5 w-5 text-brand-success" />
                  <span class="font-bold text-brand-text">Official Winner (recorded)</span>
                </div>
                <p class="text-lg font-bold text-brand-text">{{ teacherRealWinner.winner_name }}</p>
                <p class="text-sm text-brand-text-soft">{{ teacherRealWinner.winner_entries }} entries this quarter — from {{ teacherRealWinner.total_tickets }} tickets</p>
                <p class="mt-2 text-xs text-brand-text-soft">This result is permanently recorded.</p>
              </div>

              <!-- Not yet drawn -->
              <div v-else class="space-y-3">
                <!-- Practice draw result -->
                <div v-if="teacherTestWinner" class="rounded-lg border border-dashed border-brand-accent bg-brand-accent/5 p-3">
                  <div class="flex items-center gap-2 mb-1">
                    <Sparkles class="h-4 w-4 text-brand-accent" />
                    <span class="text-sm font-semibold text-brand-accent">Practice Draw #{{ testDrawCount.teacher }}</span>
                  </div>
                  <p class="font-bold text-brand-text">{{ teacherTestWinner.name }}</p>
                  <p class="text-xs text-brand-text-soft">{{ teacherTestWinner.entries }} entries this quarter</p>
                  <p class="mt-1 text-xs text-brand-text-soft italic">This is just a practice — not recorded.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <MyButtonConstructor
                    size="small"
                    variant="outline"
                    :icon="drawingTeacher ? Loader2 : Sparkles"
                    :disabled="drawingTeacher"
                    @click="runDraw('teacher', 'test')"
                  >
                    {{ drawingTeacher ? 'Drawing...' : 'Practice Draw' }}
                  </MyButtonConstructor>

                  <MyButtonConstructor
                    size="medium"
                    variant="primary"
                    :icon="drawingTeacher ? Loader2 : Trophy"
                    :disabled="drawingTeacher"
                    @click="runDraw('teacher', 'real')"
                  >
                    {{ drawingTeacher ? 'Drawing...' : 'Run REAL Teacher Draw' }}
                  </MyButtonConstructor>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</template>
