<!-- resources/js/pages/admin/QuarterEnd/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Award, CheckCircle2, Circle, Download, Mail, Package,
  Trophy, Users, Clock, Star, ChevronDown, ChevronUp, Copy
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
  top_scorer: { name: string; score: number; instrument: string } | null
}

const props = defineProps<{
  quarter: number
  year: number
  quarterLabel: string
  teachers: Teacher[]
  summary: Summary
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

// Step tracking
const currentStep = ref(1)

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

  const template = `Hi ${teacher.applicant_name || teacher.teacher_name},

I hope you're well! I'm writing with great news — the ${props.quarterLabel} exam results are in and your students have done brilliantly.

Here are the results:
${studentList}

I've attached their personalised certificates for you to pass on. Every student receives at least a Bravo Certificate, with Merit earning a Take a Bow Certificate and Distinction earning a Standing Ovation Certificate.${badgeText}

As a quick heads-up — I've recently launched musicExams.help, a free resource for teachers, parents and students booking Trinity exams through centre 120. It has:
  • Exam guides, fees and session dates all in one place
  • Student recognition — Hall of Fame, certificates and quarterly prize draws
  • Teacher awards — Bronze, Silver, Gold and Top Award badges
  • Faber music book discounts for teachers
  • Booking made easy across all 3 Trinity systems

Have a look when you get a chance: https://musicexams.help

If you have any questions or need help with upcoming entries, just reply to this email.

Best wishes,
Paul Sheridan
musicExams.help — Centre 120`

  navigator.clipboard.writeText(template)
  alert('Email template copied to clipboard!')
}

// Quarter selector
function changeQuarter(q: number, y: number) {
  router.get('/admin/quarter-end', { quarter: q, year: y }, { preserveState: false })
}
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
          <p class="text-2xl font-bold text-brand-text" v-if="summary.top_scorer">{{ summary.top_scorer.score }}</p>
          <p class="text-2xl font-bold text-brand-text" v-else>—</p>
          <p class="text-xs text-brand-text-soft" v-if="summary.top_scorer">Top: {{ summary.top_scorer.name }}</p>
          <p class="text-xs text-brand-text-soft" v-else>Top Scorer</p>
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

      <!-- STEPS -->
      <div class="mb-8 space-y-4">

        <!-- STEP 1: Generate certificates -->
        <div class="rounded-xl border-2 p-5" :class="currentStep >= 1 ? 'border-brand-accent bg-brand-surface' : 'border-brand-border bg-brand-surface-soft'">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
              :class="batchResult || currentStep > 1 ? 'bg-brand-success text-white' : 'bg-brand-accent text-white'">
              <CheckCircle2 v-if="batchResult || currentStep > 1" class="h-5 w-5" />
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
        <div class="rounded-xl border-2 p-5" :class="currentStep >= 2 ? 'border-brand-accent bg-brand-surface' : 'border-brand-border bg-brand-surface-soft opacity-60'">
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
                  <a
                    v-if="teacher.applicant_email"
                    :href="`mailto:${teacher.applicant_email}?subject=${encodeURIComponent(quarterLabel + ' Exam Results & Certificates — musicExams.help')}`"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-surface border border-brand-border px-3 py-2 text-sm font-semibold text-brand-text hover:bg-brand-surface-soft transition"
                  >
                    <Mail class="h-4 w-4" /> Open in Gmail
                  </a>
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

        <!-- STEP 3: Prize draws (future) -->
        <div class="rounded-xl border-2 border-brand-border bg-brand-surface-soft p-5 opacity-60">
          <div class="flex items-center gap-3 mb-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-border text-sm font-bold text-brand-text-soft">3</div>
            <h3 class="text-lg font-bold text-brand-text-soft">Prize Draws & Top Scorer Awards</h3>
          </div>
          <p class="text-sm text-brand-text-soft">
            Coming soon — after the 6-week cut-off, run the quarterly prize draws and assign Centre Stage / Showstopper certificates to top scorers.
          </p>
        </div>

      </div>
    </div>
  </div>
</template>
