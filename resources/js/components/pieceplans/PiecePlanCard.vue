<!-- resources/js/components/pieceplans/PiecePlanCard.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Plus, Trash2, X, Pencil, ListMusic } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, reactive, ref, toRef, watch } from 'vue'
import PieceChooser from '@/components/pieceplans/PieceChooser.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyProgress from '@/components/reusables/MyProgress.vue'
import MySearchPickConstructor from '@/components/reusables/MySearchPickConstructor.vue'
import type {PickItem} from '@/components/reusables/MySearchPickConstructor.vue';
import MySliderConstructor from '@/components/reusables/MySliderConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import SyllabusFilterSelects from '@/components/syllabus/SyllabusFilterSelects.vue'
import { instrumentLabel  } from '@/composables/useSyllabusFacets'
import type {SyllabusFacetLists} from '@/composables/useSyllabusFacets';
import { useSyllabusPieceOptions } from '@/composables/useSyllabusPieceOptions'
import type { SyllabusPieceOption } from '@/composables/useSyllabusPieceOptions'
import { sendJson } from '@/lib/sendJson'
import type { PiecePlan, PlanItem, PlanSection, SectionLabels, Suggestions } from '@/types/piecePlans'

// One pupil's plan on the Piece tracker: what they are preparing and how
// ready each part is. There is no Save button: every change saves itself a
// moment after the last edit (App\Services\PiecePlans::update makes the plan
// match what is sent), and a mark saves the instant it is picked
// (PiecePlans::rate). Saves go by JSON, not an Inertia visit, so nothing
// being typed is ever replaced by a reload.
const props = defineProps<{
  plan: PiecePlan
  facets: SyllabusFacetLists
  sectionLabels: SectionLabels
  suggestions: Suggestions
  maxItems: number
  maxScore: number
}>()

type DraftItem = PlanItem & { key: string }
interface Draft {
  pupil_name: string
  exam_stream: string
  instrument: string
  grade: string
  target_date: string
  items: DraftItem[]
}

const SECTION_ORDER: PlanSection[] = ['piece', 'technical', 'supporting']
const AUTOSAVE_DELAY_MS = 800

let nextKey = 0
function toDraft(plan: PiecePlan): Draft {
  return {
    pupil_name: plan.pupil_name,
    exam_stream: plan.exam_stream,
    instrument: plan.instrument,
    grade: plan.grade,
    target_date: plan.target_date ?? '',
    items: plan.items.map((item) => ({ ...item, key: `i${item.id ?? `n${nextKey++}`}` })),
  }
}

const draft = reactive<Draft>(toDraft(props.plan))
const scores = ref<Record<number, number>>(Object.fromEntries(props.plan.ratings.map((r) => [r.syllabus_piece_id, r.score])))

const stream = toRef(draft, 'exam_stream')
const instrument = toRef(draft, 'instrument')
const grade = toRef(draft, 'grade')
const { options: syllabusPieces } = useSyllabusPieceOptions(stream, instrument, grade)
const pieceItems = computed<PickItem<number>[]>(() => syllabusPieces.value.map((p) => ({ value: p.value, label: p.label })))

const labels = computed(() => props.sectionLabels[draft.exam_stream] ?? props.sectionLabels['Classical & Jazz'])

// Rows with nothing in them yet are left out of a save rather than refused.
function filled(item: DraftItem): boolean {
  return item.syllabus_piece_id !== null || item.label.trim() !== ''
}
function savedRows(): DraftItem[] {
  return SECTION_ORDER.flatMap((section) => draft.items.filter((i) => i.section === section && filled(i)))
}

function payload() {
  return {
    pupil_name: draft.pupil_name,
    exam_stream: draft.exam_stream,
    instrument: draft.instrument,
    grade: draft.grade,
    target_date: draft.target_date || null,
    items: savedRows().map(({ id, section, syllabus_piece_id, label, percent }) => ({ id, section, syllabus_piece_id, label, percent })),
  }
}

// What the plan says, ignoring row ids (a new row gets its id back from the
// save, which is not an edit). A change here is what triggers an autosave.
function contentOf(): string {
  const { items, ...rest } = payload()

  return JSON.stringify({ ...rest, items: items.map(({ section, syllabus_piece_id, label, percent }) => ({ section, syllabus_piece_id, label, percent })) })
}

// ── Autosave ──────────────────────────────────────────────────────
type SaveState = 'saved' | 'waiting' | 'saving' | 'error' | 'incomplete'
const saveState = ref<SaveState>('saved')
const saveError = ref('')
let lastSaved = contentOf()
let timer: ReturnType<typeof setTimeout> | undefined
let inFlight = false

// The details every save needs. Changing the exam type clears the
// instrument; saving then would only be refused.
const complete = computed(() => draft.pupil_name.trim() !== '' && draft.instrument !== '' && draft.grade !== '')

async function saveNow(keepalive = false) {
  clearTimeout(timer)
  const content = contentOf()

  if (content === lastSaved || !complete.value) {
    return
  }

  if (inFlight && !keepalive) {
    timer = setTimeout(() => saveNow(), AUTOSAVE_DELAY_MS)

    return
  }

  const sentRows = savedRows()
  inFlight = true
  saveState.value = 'saving'

  try {
    const res = await sendJson(`/dashboard/pieces/${props.plan.id}`, 'PUT', payload(), keepalive)

    if (res.status === 422) {
      const body = await res.json().catch(() => ({}))
      saveError.value = Object.values((body?.errors ?? {}) as Record<string, string[]>)[0]?.[0] ?? 'Something on this plan could not be saved.'
      saveState.value = 'error'

      return
    }

    if (!res.ok) {
      throw new Error(String(res.status))
    }

    const saved = (await res.json()) as PiecePlan
    // Give rows that were new their ids, matched by position in what was
    // sent (the server keeps that order), so the next save updates them.
    saved.items.forEach((item, index) => {
      const row = sentRows[index]

      if (row && row.id === null) {
        row.id = item.id
      }
    })
    lastSaved = content
    saveState.value = contentOf() === lastSaved ? 'saved' : 'waiting'
  } catch {
    saveError.value = 'Could not reach the site. Your changes are still here and will be tried again.'
    saveState.value = 'error'
    timer = setTimeout(() => saveNow(), AUTOSAVE_DELAY_MS * 5)
  } finally {
    inFlight = false
  }
}

watch(contentOf, (content) => {
  if (content === lastSaved) {
    return
  }

  if (!complete.value) {
    saveState.value = 'incomplete'

    return
  }

  saveState.value = 'waiting'
  clearTimeout(timer)
  timer = setTimeout(() => saveNow(), AUTOSAVE_DELAY_MS)
})

// Leaving the page or refreshing sends anything still waiting.
function flush() {
  if (contentOf() !== lastSaved) {
    saveNow(true)
  }
}
onMounted(() => window.addEventListener('pagehide', flush))
onBeforeUnmount(() => {
  window.removeEventListener('pagehide', flush)
  flush()
})

const statusText = computed(() => ({
  saved: 'All changes saved',
  waiting: 'Saving…',
  saving: 'Saving…',
  incomplete: 'Choose the pupil, instrument and grade to save',
  error: `Not saved: ${saveError.value}`,
}[saveState.value]))

// ── Rows ──────────────────────────────────────────────────────────
const rows = computed(() =>
  SECTION_ORDER.flatMap((section) => draft.items.filter((i) => i.section === section))
    .map((item) => ({ ...item, part: labels.value[item.section] })),
)
function itemFor(key: string): DraftItem | undefined {
  return draft.items.find((i) => i.key === key)
}

const ready = computed(() => {
  const counted = draft.items.filter(filled)

  return counted.length ? Math.round(counted.reduce((sum, i) => sum + i.percent, 0) / counted.length) : 0
})

const full = computed(() => draft.items.length >= props.maxItems)

// ── Choosing pieces ───────────────────────────────────────────────
const choosing = ref(false)
// Being tried = being in the Pieces rows. Nothing else records it.
const tryingIds = computed(() =>
  draft.items.filter((i) => i.section === 'piece' && i.syllabus_piece_id !== null).map((i) => i.syllabus_piece_id as number),
)

// A mark saves the instant it is picked, on its own.
const markError = ref('')
async function setScore(pieceId: number, score: number | null) {
  const previous = scores.value[pieceId]
  const next = { ...scores.value }

  if (score === null) {
    delete next[pieceId]
  } else {
    next[pieceId] = score
  }

  scores.value = next
  markError.value = ''

  try {
    const res = await sendJson(`/dashboard/pieces/${props.plan.id}/rating`, 'PUT', { syllabus_piece_id: pieceId, score })

    if (!res.ok) {
      throw new Error(String(res.status))
    }
  } catch {
    const undo = { ...scores.value }

    if (previous === undefined) {
      delete undo[pieceId]
    } else {
      undo[pieceId] = previous
    }

    scores.value = undo
    markError.value = 'That mark did not save. Please pick it again.'
  }
}

function setTrying(piece: SyllabusPieceOption, on: boolean) {
  if (on) {
    if (full.value || tryingIds.value.includes(piece.value)) {
      return
    }

    draft.items.push({ id: null, section: 'piece', syllabus_piece_id: piece.value, label: piece.label, percent: 0, book: piece.book, key: `n${nextKey++}` })
  } else {
    const index = draft.items.findIndex((i) => i.section === 'piece' && i.syllabus_piece_id === piece.value)

    if (index >= 0) {
      draft.items.splice(index, 1)
    }
  }
}

function addItem(section: PlanSection, label = '') {
  if (full.value) {
    return
  }

  draft.items.push({ id: null, section, syllabus_piece_id: null, label, percent: 0, book: null, key: `n${nextKey++}` })
}
function removeItem(key: string) {
  const index = draft.items.findIndex((i) => i.key === key)

  if (index >= 0) {
    draft.items.splice(index, 1)
  }
}
function pickPiece(key: string, item: PickItem<number>) {
  const row = itemFor(key)

  if (!row) {
    return
  }

  row.syllabus_piece_id = item.value
  row.label = item.label
  row.book = syllabusPieces.value.find((p) => p.value === item.value)?.book ?? null
}
function clearPiece(key: string) {
  const row = itemFor(key)

  if (!row) {
    return
  }

  row.syllabus_piece_id = null
  row.label = ''
  row.book = null
}

function suggestionsFor(section: 'technical' | 'supporting'): string[] {
  const list = props.suggestions[draft.exam_stream]?.[section] ?? []

  return list.filter((label) => !draft.items.some((i) => i.section === section && i.label === label))
}

const editingDetails = ref(false)
const confirmRemove = ref(false)

function remove() {
  clearTimeout(timer)
  lastSaved = contentOf()
  router.delete(`/dashboard/pieces/${props.plan.id}`, { preserveScroll: true })
}

// Title and subtitle follow what is on the card, which is what gets saved.
const subtitle = computed(() => {
  const parts = [`${instrumentLabel(draft.exam_stream, draft.instrument)} · ${draft.grade}`]

  if (draft.target_date) {
    parts.push(`exam around ${new Date(`${draft.target_date}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`)
  }

  return parts.join(' · ')
})

const columns = [
  { key: 'part', title: 'Part', width: '9rem' },
  { key: 'label', title: 'What' },
  { key: 'percent', title: 'Ready', width: '14rem' },
  { key: 'remove', title: '', width: '3.5rem' },
]
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="text-sm" aria-live="polite">
      <MyTextConstructor
        bodyVariant="inherit"
        :textColor="saveState === 'error' ? 'text-brand-danger' : 'text-brand-text-soft'"
        spacing="none"
      >
        {{ statusText }}
      </MyTextConstructor>
    </div>

    <MyTableConstructor
      :title="draft.pupil_name"
      :subtitle="subtitle"
      :data="rows"
      :columns="columns"
      row-key="key"
      size="small"
      :sortable="false"
    >
      <template #cell-label="{ row }">
        <div v-if="row.section === 'piece' && row.syllabus_piece_id === null" class="flex flex-col gap-2">
          <MySearchPickConstructor
            v-if="pieceItems.length"
            :model-value="null"
            :items="pieceItems"
            :placeholder="`Find a ${labels.piece.toLowerCase().replace(/s$/, '')} on the ${draft.grade} list…`"
            @pick="(item) => pickPiece(row.key, item)"
          />
          <MyInputConstructor
            :model-value="row.label"
            size="small"
            :placeholder="pieceItems.length ? 'Or type one that is not on the list' : 'Type the piece'"
            @update:model-value="(v) => { const r = itemFor(row.key); if (r) r.label = String(v) }"
          />
        </div>
        <div v-else-if="row.section === 'piece'" class="flex items-start justify-between gap-2">
          <div class="flex flex-col gap-0.5">
            <MyTextConstructor bodyVariant="inherit" spacing="none">{{ row.label }}</MyTextConstructor>
            <MyTextConstructor v-if="row.book" bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">{{ row.book }}</MyTextConstructor>
          </div>
          <MyButtonConstructor size="small" variant="outline" @click="clearPiece(row.key)">Change</MyButtonConstructor>
        </div>
        <MyInputConstructor
          v-else
          :model-value="row.label"
          size="small"
          placeholder="What is being prepared"
          @update:model-value="(v) => { const r = itemFor(row.key); if (r) r.label = String(v) }"
        />
      </template>

      <template #cell-percent="{ row }">
        <MySliderConstructor
          :model-value="row.percent"
          :step="5"
          suffix="%"
          :aria-label="`How ready: ${row.label || row.part}`"
          @update:model-value="(v) => { const r = itemFor(row.key); if (r) r.percent = v }"
        />
      </template>

      <template #cell-remove="{ row }">
        <MyButtonConstructor size="small" variant="outline" :icon="X" aria-label="Remove this row" @click="removeItem(row.key)" />
      </template>
    </MyTableConstructor>

    <div v-if="!rows.length">
      <MyTextConstructor bodyVariant="muted" spacing="none">
        Nothing added yet. Start with the {{ labels.piece.toLowerCase() }} below.
      </MyTextConstructor>
    </div>

    <div v-if="syllabusPieces.length" class="flex flex-col gap-3">
      <div>
        <MyButtonConstructor size="small" variant="outline" :icon="ListMusic" @click="choosing = !choosing">
          Choose {{ labels.piece.toLowerCase() }} ({{ syllabusPieces.length }})
        </MyButtonConstructor>
      </div>
      <PieceChooser
        v-if="choosing"
        :pieces="syllabusPieces"
        :scores="scores"
        :trying-ids="tryingIds"
        :max-score="maxScore"
        :piece-word="labels.piece.replace(/s$/, '')"
        :full="full"
        @score="setScore"
        @trying="setTrying"
      />
      <div v-if="markError" class="text-sm">
        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-danger" spacing="none">{{ markError }}</MyTextConstructor>
      </div>
    </div>

    <div class="flex flex-col gap-3">
      <div class="w-full max-w-md">
        <MyProgress :percentage="ready" label="Ready overall" compact />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <MyButtonConstructor size="small" variant="outline" :icon="Plus" :disabled="full" @click="addItem('piece')">
          {{ labels.piece.replace(/s$/, '') }}
        </MyButtonConstructor>
        <MyButtonConstructor
          v-for="label in suggestionsFor('technical')"
          :key="`t-${label}`"
          size="small"
          variant="outline"
          :icon="Plus"
          :disabled="full"
          @click="addItem('technical', label)"
        >
          {{ label }}
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :icon="Plus" :disabled="full" @click="addItem('technical')">
          Other {{ labels.technical.toLowerCase() }}
        </MyButtonConstructor>
        <MyButtonConstructor
          v-for="label in suggestionsFor('supporting')"
          :key="`s-${label}`"
          size="small"
          variant="outline"
          :icon="Plus"
          :disabled="full"
          @click="addItem('supporting', label)"
        >
          {{ label }}
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :icon="Plus" :disabled="full" @click="addItem('supporting')">
          Other {{ labels.supporting.toLowerCase().replace(/s$/, '') }}
        </MyButtonConstructor>
      </div>

      <div v-if="editingDetails" class="flex flex-col gap-3">
        <div class="w-full max-w-md">
          <MyInputConstructor v-model="draft.pupil_name" size="small" label="Pupil" label-size="small" />
        </div>
        <SyllabusFilterSelects
          v-model:stream="draft.exam_stream"
          v-model:instrument="draft.instrument"
          v-model:grade="draft.grade"
          :facets="facets"
          :allow-all="false"
          size="medium"
          labelled
        />
        <div class="w-full max-w-xs">
          <MyInputConstructor v-model="draft.target_date" type="date" size="small" label="Exam around (optional)" label-size="small" />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <MyButtonConstructor size="small" variant="outline" :icon="Pencil" @click="editingDetails = !editingDetails">
          Pupil and exam
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :icon="Trash2" :disabled="confirmRemove" @click="confirmRemove = true">
          Remove plan
        </MyButtonConstructor>
      </div>

      <div v-if="confirmRemove" class="flex flex-wrap items-center gap-2">
        <MyButtonConstructor size="small" variant="danger" :icon="Trash2" @click="remove">
          Yes, remove {{ draft.pupil_name }}'s plan
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" @click="confirmRemove = false">Keep it</MyButtonConstructor>
      </div>
    </div>
  </div>
</template>
