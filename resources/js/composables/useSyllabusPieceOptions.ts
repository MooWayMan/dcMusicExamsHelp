// resources/js/composables/useSyllabusPieceOptions.ts

import { ref, watch  } from 'vue'
import type {Ref} from 'vue';

// Pieces on the Trinity syllabus for one exam (exam type + instrument +
// grade), for the Piece tracker's piece picker. Served by
// GET /dashboard/pieces/syllabus (App\Services\PiecePlans::syllabusOptions).
// Each exam is fetched once per page visit, however many pupils share it.
export interface SyllabusPieceOption {
  value: number
  label: string
  book: string | null
}

const cache = new Map<string, Promise<SyllabusPieceOption[]>>()

function load(stream: string, instrument: string, grade: string): Promise<SyllabusPieceOption[]> {
  const key = `${stream}|${instrument}|${grade}`

  if (!cache.has(key)) {
    const qs = new URLSearchParams({ stream, instrument, grade })
    const request = fetch(`/dashboard/pieces/syllabus?${qs}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => (r.ok ? r.json() : []))
      .catch(() => [])
    request.then((list) => {
 if (!list.length) {
cache.delete(key)
} 
})
    cache.set(key, request)
  }

  return cache.get(key) as Promise<SyllabusPieceOption[]>
}

export function useSyllabusPieceOptions(stream: Ref<string>, instrument: Ref<string>, grade: Ref<string>) {
  const options = ref<SyllabusPieceOption[]>([])
  const loading = ref(false)

  watch([stream, instrument, grade], async ([s, i, g]) => {
    if (!s || !i || !g) {
      options.value = []

      return
    }

    loading.value = true
    const list = await load(s, i, g)

    if (stream.value === s && instrument.value === i && grade.value === g) {
options.value = list
}

    loading.value = false
  }, { immediate: true })

  return { options, loading }
}
