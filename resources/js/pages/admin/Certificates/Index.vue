<!-- resources/js/pages/admin/Certificates/Index.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Award, Download, User, Music, Search, Eye, X } from 'lucide-vue-next'
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
  id: number
  name: string
  orders_count: number
  tier: string | null
}

const props = defineProps<{
  students: StudentEntry[]
  teachers: TeacherEntry[]
  studentTemplates: string[]
  teacherTemplates: string[]
}>()

// Tab state
const activeTab = ref<'student' | 'teacher'>('student')

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
  if (!teacherSearch.value) return props.teachers.filter(t => t.tier)
  const q = teacherSearch.value.toLowerCase()
  return props.teachers.filter(t => t.tier && t.name.toLowerCase().includes(q))
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
  // Default to current quarter for teachers
  const now = new Date()
  const q = Math.ceil((now.getMonth() + 1) / 3)
  const suffix = ['1st','2nd','3rd','4th'][q - 1]
  teacherQuarter.value = `${suffix} Quarter ${now.getFullYear()}`
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
          <span class="text-sm text-brand-text-soft">{{ students.length }} entries · {{ teachers.filter(t => t.tier).length }} eligible teachers</span>
        </div>
      </template>
    </PageHeader>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
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
                <td class="px-3 py-2 font-medium">{{ entry.candidate_name }}</td>
                <td class="px-3 py-2">{{ entry.instrument }}</td>
                <td class="px-3 py-2 text-center">{{ entry.grade }}</td>
                <td class="px-3 py-2 text-center">{{ entry.score }}</td>
                <td class="px-3 py-2">
                  <span class="inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                    {{ entry.certificate }}
                  </span>
                </td>
                <td class="px-3 py-2 text-brand-text-soft">{{ entry.exam_date }}</td>
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
                :key="teacher.id"
                class="cursor-pointer border-t border-brand-border transition hover:bg-brand-surface-soft"
                :class="{ 'bg-brand-accent/10 ring-1 ring-brand-accent': selectedTeacher === teacher.id }"
                @click="selectTeacherRow(teacher)"
              >
                <td class="px-3 py-2 font-medium">{{ teacher.name }}</td>
                <td class="px-3 py-2 text-center">{{ teacher.orders_count }}</td>
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
