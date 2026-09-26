// resources/js/composables/useSyllabusFacets.ts

import { computed, watch  } from 'vue'
import type {Ref} from 'vue';
import type { SelectOption } from '@/components/reusables/MySelectConstructor.vue'

// The exam type / instrument / grade lists behind every syllabus dropdown,
// as served by App\Services\SyllabusFacets::forDropdowns(). Picking a
// parent narrows its children, and a child that no longer fits is cleared.
export interface SyllabusFacetLists {
  streams: string[]
  streamInstruments: { stream: string; instrument: string }[]
  instrumentGrades: { instrument: string; grade: string }[]
  gradeOrder: string[]
}

export function instrumentLabel(stream: string | null | undefined, instrument: string): string {
  return stream === 'Rock & Pop' ? `R&P ${instrument}` : instrument
}

export function useSyllabusFacets(
  facets: () => SyllabusFacetLists,
  stream: Ref<string>,
  instrument: Ref<string>,
  grade: Ref<string>,
) {
  const streamOf = computed<Record<string, string>>(() => {
    const m: Record<string, string> = {}
    facets().streamInstruments.forEach((si) => {
 m[si.instrument] = si.stream 
})

    return m
  })

  const streamOptions = computed<SelectOption<string>[]>(() => facets().streams.map((s) => ({ value: s, label: s })))

  const instrumentOptions = computed<SelectOption<string>[]>(() => {
    const src = stream.value ? facets().streamInstruments.filter((si) => si.stream === stream.value) : facets().streamInstruments

    return [...new Set(src.map((si) => si.instrument))]
      .sort()
      .map((v) => ({ value: v, label: instrumentLabel(streamOf.value[v], v) }))
  })

  const gradeOptions = computed<SelectOption<string>[]>(() => {
    let instruments: string[] | null = null

    if (instrument.value) {
instruments = [instrument.value]
} else if (stream.value) {
instruments = facets().streamInstruments.filter((si) => si.stream === stream.value).map((si) => si.instrument)
}

    const present = new Set(
      facets().instrumentGrades.filter((ig) => !instruments || instruments.includes(ig.instrument)).map((ig) => ig.grade),
    )

    return facets().gradeOrder.filter((g) => present.has(g)).map((g) => ({ value: g, label: g }))
  })

  watch([stream, instrument], () => {
    if (instrument.value && !instrumentOptions.value.some((o) => o.value === instrument.value)) {
instrument.value = ''
}

    if (grade.value && !gradeOptions.value.some((o) => o.value === grade.value)) {
grade.value = ''
}
  })

  return { streamOptions, instrumentOptions, gradeOptions }
}
