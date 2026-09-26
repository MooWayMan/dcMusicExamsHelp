<!-- resources/js/components/pieceplans/PieceChooser.vue -->
<script setup lang="ts">
import { Headphones } from 'lucide-vue-next'
import { computed } from 'vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyCheckboxConstructor from '@/components/reusables/MyCheckboxConstructor.vue'
import MySelectConstructor from '@/components/reusables/MySelectConstructor.vue'
import type {SelectOption} from '@/components/reusables/MySelectConstructor.vue';
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import type { SyllabusPieceOption } from '@/composables/useSyllabusPieceOptions'

// "Choose pieces": every syllabus piece for the pupil's exam. The teacher
// plays each one (or opens it on YouTube), the pupil gives it a mark, and
// the list sorts by mark, highest first, so the favourites rise to the top
// together. Ticking "Trying it" puts the piece into the plan's Pieces rows;
// that is the only record of it, so the tick and the rows never disagree.
const props = defineProps<{
  pieces: SyllabusPieceOption[]
  scores: Record<number, number>
  tryingIds: number[]
  maxScore: number
  pieceWord: string
  full: boolean
}>()

const emit = defineEmits<{
  score: [pieceId: number, score: number | null]
  trying: [piece: SyllabusPieceOption, on: boolean]
}>()

const markOptions = computed<SelectOption<number>[]>(() =>
  Array.from({ length: props.maxScore + 1 }, (_, i) => props.maxScore - i).map((n) => ({ value: n, label: `${n}` })),
)

// Highest mark first; unmarked pieces after every marked one; the
// syllabus's own order breaks ties, so equal marks stay put.
const rows = computed(() =>
  props.pieces
    .map((piece, index) => ({
      ...piece,
      key: piece.value,
      index,
      score: props.scores[piece.value] ?? null,
      trying: props.tryingIds.includes(piece.value),
    }))
    .sort((a, b) => (b.score ?? -1) - (a.score ?? -1) || a.index - b.index),
)

const markedCount = computed(() => rows.value.filter((r) => r.score !== null).length)
const tryingCount = computed(() => rows.value.filter((r) => r.trying).length)

function open(url: string) {
  window.open(url, '_blank', 'noopener')
}

const columns = computed(() => [
  { key: 'label', title: props.pieceWord },
  { key: 'mark', title: `Mark /${props.maxScore}`, width: '7rem' },
  { key: 'listen', title: '', width: '6.5rem' },
  { key: 'trying', title: 'Trying it', width: '6rem' },
])
</script>

<template>
  <div class="flex flex-col gap-3">
    <div class="text-sm">
      <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">
        {{ markedCount }} of {{ pieces.length }} marked · {{ tryingCount }} being tried. Highest marks at the top.
      </MyTextConstructor>
    </div>

    <MyTableConstructor
      :data="rows"
      :columns="columns"
      row-key="key"
      size="small"
      :sortable="false"
    >
      <template #cell-label="{ row }">
        <div class="flex flex-col gap-0.5">
          <MyTextConstructor bodyVariant="inherit" spacing="none">{{ row.label }}</MyTextConstructor>
          <MyTextConstructor v-if="row.book" bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">{{ row.book }}</MyTextConstructor>
        </div>
      </template>

      <template #cell-mark="{ row }">
        <div class="w-20">
          <MySelectConstructor
            :model-value="row.score"
            :options="markOptions"
            placeholder="–"
            size="small"
            :aria-label="`Mark for ${row.label}`"
            @update:model-value="(v) => emit('score', row.value, v === '' || v === null ? null : Number(v))"
          />
        </div>
      </template>

      <template #cell-listen="{ row }">
        <MyButtonConstructor
          v-if="row.listen"
          size="small"
          variant="outline"
          :icon="Headphones"
          :aria-label="`Listen to ${row.label}`"
          @click="open(row.listen)"
        >
          Listen
        </MyButtonConstructor>
      </template>

      <template #cell-trying="{ row }">
        <MyCheckboxConstructor
          :model-value="row.trying"
          :disabled="full && !row.trying"
          :aria-label="`Trying ${row.label}`"
          @update:model-value="(on) => emit('trying', row, on)"
        />
      </template>
    </MyTableConstructor>
  </div>
</template>
