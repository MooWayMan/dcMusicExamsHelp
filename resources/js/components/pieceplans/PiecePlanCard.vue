<!-- resources/js/components/pieceplans/PiecePlanCard.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Plus, Trash2, X, Pencil, Save, ListMusic } from 'lucide-vue-next'
import { computed, reactive, ref, toRef, watch } from 'vue'
import PieceChooser from '@/components/pieceplans/PieceChooser.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyProgress from '@/components/reusables/MyProgress.vue'
import MySearchPickConstructor from '@/components/reusables/MySearchPickConstructor.vue'
import type {PickItem} from '@/components/reusables/MySearchPickConstructor.vue';
import MySelectConstructor from '@/components/reusables/MySelectConstructor.vue'
import type {SelectOption} from '@/components/reusables/MySelectConstructor.vue';
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import SyllabusFilterSelects from '@/components/syllabus/SyllabusFilterSelects.vue'
import { instrumentLabel  } from '@/composables/useSyllabusFacets'
import type {SyllabusFacetLists} from '@/composables/useSyllabusFacets';
import { useSyllabusPieceOptions } from '@/composables/useSyllabusPieceOptions'
import type { SyllabusPieceOption } from '@/composables/useSyllabusPieceOptions'
import type { PiecePlan, PlanItem, PlanRating, PlanSection, SectionLabels, Suggestions } from '@/types/piecePlans'

// One pupil's plan on the Piece tracker: what they are preparing and how
// ready each part is. Edits stay on the card until Save; Save sends every
// field back (App\Services\PiecePlans::update makes the plan match it).
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
  scores: Record<number, number>
}

const SECTION_ORDER: PlanSection[] = ['piece', 'technical', 'supporting']
const PERCENT_OPTIONS: SelectOption<number>[] = Array.from({ length: 11 }, (_, i) => ({ value: i * 10, label: `${i * 10}%` }))

let nextKey = 0
function toDraft(plan: PiecePlan): Draft {
  return {
    pupil_name: plan.pupil_name,
    exam_stream: plan.exam_stream,
    instrument: plan.instrument,
    grade: plan.grade,
    target_date: plan.target_date ?? '',
    items: plan.items.map((item) => ({ ...item, key: `i${item.id ?? `n${nextKey++}`}` })),
    scores: Object.fromEntries(plan.ratings.map((r) => [r.syllabus_piece_id, r.score])),
  }
}

// Marks go back sorted by piece, the order the server serves them in, so an
// untouched plan compares equal to what was saved.
function ratingsOf(scores: Record<number, number>): PlanRating[] {
  return Object.entries(scores)
    .map(([id, score]) => ({ syllabus_piece_id: Number(id), score }))
    .sort((a, b) => a.syllabus_piece_id - b.syllabus_piece_id)
}

const draft = reactive<Draft>(toDraft(props.plan))
watch(() => props.plan, (plan) => Object.assign(draft, toDraft(plan)))

const stream = toRef(draft, 'exam_stream')
const instrument = toRef(draft, 'instrument')
const grade = toRef(draft, 'grade')
const { options: syllabusPieces } = useSyllabusPieceOptions(stream, instrument, grade)
const pieceItems = computed<PickItem<number>[]>(() => syllabusPieces.value.map((p) => ({ value: p.value, label: p.label })))

const labels = computed(() => props.sectionLabels[draft.exam_stream] ?? props.sectionLabels['Classical & Jazz'])

// Rows with nothing in them are dropped on save rather than refused.
function filled(item: DraftItem): boolean {
  return item.syllabus_piece_id !== null || item.label.trim() !== ''
}

function payload() {
  return {
    pupil_name: draft.pupil_name,
    exam_stream: draft.exam_stream,
    instrument: draft.instrument,
    grade: draft.grade,
    target_date: draft.target_date || null,
    items: SECTION_ORDER.flatMap((section) => draft.items.filter((i) => i.section === section && filled(i)))
      .map(({ id, section, syllabus_piece_id, label, percent }) => ({ id, section, syllabus_piece_id, label, percent })),
    ratings: ratingsOf(draft.scores),
  }
}

const saved = computed(() => JSON.stringify(toDraftPayload(props.plan)))
function toDraftPayload(plan: PiecePlan) {
  return {
    pupil_name: plan.pupil_name,
    exam_stream: plan.exam_stream,
    instrument: plan.instrument,
    grade: plan.grade,
    target_date: plan.target_date,
    items: plan.items.map(({ id, section, syllabus_piece_id, label, percent }) => ({ id, section, syllabus_piece_id, label, percent })),
    ratings: plan.ratings.map(({ syllabus_piece_id, score }) => ({ syllabus_piece_id, score })),
  }
}
const dirty = computed(() => JSON.stringify(payload()) !== saved.value)

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
function setScore(pieceId: number, score: number | null) {
  if (score === null) {
    delete draft.scores[pieceId]
  } else {
    draft.scores[pieceId] = score
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
const saving = ref(false)
const confirmRemove = ref(false)

function save() {
  saving.value = true
  router.put(`/dashboard/pieces/${props.plan.id}`, payload(), {
    preserveScroll: true,
    onSuccess: () => {
 editingDetails.value = false 
},
    onFinish: () => {
 saving.value = false 
},
  })
}
function discard() {
  Object.assign(draft, toDraft(props.plan))
  editingDetails.value = false
}
function remove() {
  router.delete(`/dashboard/pieces/${props.plan.id}`, { preserveScroll: true })
}

const subtitle = computed(() => {
  const parts = [`${instrumentLabel(props.plan.exam_stream, props.plan.instrument)} · ${props.plan.grade}`]

  if (props.plan.target_date) {
    parts.push(`exam around ${new Date(`${props.plan.target_date}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`)
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
    <MyTableConstructor
      :title="plan.pupil_name"
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
            :placeholder="`Find a ${labels.piece.toLowerCase().replace(/s$/, '')} on the ${plan.grade} list…`"
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
        <div class="flex items-center gap-3">
          <div class="w-24 shrink-0">
            <MySelectConstructor
              :model-value="row.percent"
              :options="PERCENT_OPTIONS"
              size="small"
              aria-label="How ready"
              @update:model-value="(v) => { const r = itemFor(row.key); if (r) r.percent = Number(v) }"
            />
          </div>
          <div class="min-w-0 flex-1">
            <MyProgress :percentage="row.percent" compact />
          </div>
        </div>
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
        :scores="draft.scores"
        :trying-ids="tryingIds"
        :max-score="maxScore"
        :piece-word="labels.piece.replace(/s$/, '')"
        :full="full"
        @score="setScore"
        @trying="setTrying"
      />
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
        <MyButtonConstructor size="small" variant="primary" :icon="Save" :disabled="!dirty || saving" @click="save">
          Save
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :disabled="!dirty || saving" @click="discard">
          Undo changes
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :icon="Pencil" @click="editingDetails = !editingDetails">
          Pupil and exam
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" :icon="Trash2" :disabled="confirmRemove" @click="confirmRemove = true">
          Remove plan
        </MyButtonConstructor>
      </div>

      <div v-if="confirmRemove" class="flex flex-wrap items-center gap-2">
        <MyButtonConstructor size="small" variant="danger" :icon="Trash2" @click="remove">
          Yes, remove {{ plan.pupil_name }}'s plan
        </MyButtonConstructor>
        <MyButtonConstructor size="small" variant="outline" @click="confirmRemove = false">Keep it</MyButtonConstructor>
      </div>
    </div>
  </div>
</template>
