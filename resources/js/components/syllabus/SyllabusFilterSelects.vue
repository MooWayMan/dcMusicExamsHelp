<!-- resources/js/components/syllabus/SyllabusFilterSelects.vue -->
<script setup lang="ts">
import MySelectConstructor from '@/components/reusables/MySelectConstructor.vue'
import { useSyllabusFacets  } from '@/composables/useSyllabusFacets'
import type {SyllabusFacetLists} from '@/composables/useSyllabusFacets';

// Exam type → instrument → grade, cascading. The one set of syllabus
// dropdowns used by the Piece Finder, Top Ten and the Piece tracker.
const props = withDefaults(defineProps<{
  facets: SyllabusFacetLists
  tone?: 'surface' | 'glass'
  size?: 'small' | 'medium'
  allowAll?: boolean
  labelled?: boolean
}>(), {
  tone: 'surface',
  size: 'small',
  allowAll: true,
  labelled: false,
})

const stream = defineModel<string>('stream', { required: true })
const instrument = defineModel<string>('instrument', { required: true })
const grade = defineModel<string>('grade', { required: true })

const { streamOptions, instrumentOptions, gradeOptions } = useSyllabusFacets(() => props.facets, stream, instrument, grade)
</script>

<template>
  <div class="flex flex-wrap items-end gap-2">
    <div class="w-full sm:w-52">
      <MySelectConstructor
        v-model="stream"
        :options="streamOptions"
        :placeholder="allowAll ? 'All exam types' : 'Choose exam type'"
        :label="labelled ? 'Exam type' : ''"
        aria-label="Exam type"
        :tone="tone"
        :size="size"
      />
    </div>
    <div class="w-full sm:w-64">
      <MySelectConstructor
        v-model="instrument"
        :options="instrumentOptions"
        :placeholder="allowAll ? 'All instruments' : 'Choose instrument'"
        :label="labelled ? 'Instrument' : ''"
        aria-label="Instrument"
        :tone="tone"
        :size="size"
      />
    </div>
    <div class="w-full sm:w-40">
      <MySelectConstructor
        v-model="grade"
        :options="gradeOptions"
        :placeholder="allowAll ? 'All grades' : 'Choose grade'"
        :label="labelled ? 'Grade' : ''"
        aria-label="Grade"
        :tone="tone"
        :size="size"
      />
    </div>
  </div>
</template>
