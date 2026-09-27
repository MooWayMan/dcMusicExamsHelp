<!-- resources/js/pages/dashboard/PiecePlans.vue -->
<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import { computed, reactive, ref } from 'vue'
import PiecePlanCard from '@/components/pieceplans/PiecePlanCard.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MySearchPickConstructor from '@/components/reusables/MySearchPickConstructor.vue'
import type {PickItem} from '@/components/reusables/MySearchPickConstructor.vue';
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'
import SyllabusFilterSelects from '@/components/syllabus/SyllabusFilterSelects.vue'
import type { SyllabusFacetLists } from '@/composables/useSyllabusFacets'
import type { PiecePlan, PlanCandidate, PlanSummary, SectionLabels, Suggestions } from '@/types/piecePlans'

// The teacher's Piece tracker: a plan per pupil's next exam. Private to the
// signed-in teacher; served and saved by App\Services\PiecePlans.
const props = defineProps<SyllabusFacetLists & {
  plans: PiecePlan[]
  candidates: PlanCandidate[]
  sectionLabels: SectionLabels
  suggestions: Suggestions
  maxItems: number
  maxScore: number
}>()

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Piece tracker', href: '/dashboard/pieces' }] } })

const page = usePage()
const flashSuccess = computed(() => (page.props as { flash?: { success?: string } }).flash?.success ?? '')
const firstError = computed(() => Object.values((page.props as { errors?: Record<string, string> }).errors ?? {})[0] ?? '')

const facets = computed<SyllabusFacetLists>(() => ({
  streams: props.streams,
  streamInstruments: props.streamInstruments,
  instrumentGrades: props.instrumentGrades,
  gradeOrder: props.gradeOrder,
}))

// Names only in the picker. Picking (or typing) a candidate's name shows
// their previous exams below it, so the teacher can choose the next one.
const candidateItems = computed<PickItem<string>[]>(() => props.candidates.map((c) => ({ value: c.name, label: c.name })))

const form = reactive({ pupil_name: '', exam_stream: '', instrument: '', grade: '', target_date: '' })
const adding = ref(false)
const pickedExams = computed(() => {
  const name = form.pupil_name.trim().toLowerCase()

  return name ? (props.candidates.find((c) => c.name.toLowerCase() === name)?.exams ?? []) : []
})
const examColumns = [
  { key: 'date', title: 'Date' },
  { key: 'instrument', title: 'Instrument' },
  { key: 'grade', title: 'Grade' },
]

const canAdd = computed(() => form.pupil_name.trim() !== '' && form.exam_stream !== '' && form.instrument !== '' && form.grade !== '')

// One closed row per pupil; opening it shows their plan. Each card reports
// its own name, exam and % ready, so a row stays true while it is edited.
const summaries = ref<Record<number, PlanSummary>>({})
function setSummary(id: number, summary: PlanSummary) {
  summaries.value = { ...summaries.value, [id]: summary }
}
const planRows = computed(() =>
  props.plans.map((plan) => {
    const s = summaries.value[plan.id]

    return { id: plan.id, question: s ? `${s.name} · ${s.subtitle} · ${s.ready}% ready` : plan.pupil_name }
  }),
)

function useCandidate(item: PickItem<string>) {
  form.pupil_name = item.value
}

function addPlan() {
  if (!canAdd.value) {
return
}

  adding.value = true
  router.post('/dashboard/pieces', { ...form, target_date: form.target_date || null, items: [] }, {
    preserveScroll: true,
    onSuccess: () => {
 Object.assign(form, { pupil_name: '', target_date: '' }) 
},
    onFinish: () => {
 adding.value = false 
},
  })
}
</script>

<template>
  <Head title="Piece tracker" />

  <div>
    <PageHeader
      title="Piece tracker"
      subtitle="Plan each pupil's next exam: pieces from the Trinity syllabus, technical work and supporting tests, and how ready each one is. Only you can see this."
      eyebrow="Your dashboard"
      size="compact"
    />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8">
      <div v-if="flashSuccess">
        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-success" spacing="none">{{ flashSuccess }}</MyTextConstructor>
      </div>
      <div v-if="firstError">
        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-danger" spacing="none">{{ firstError }}</MyTextConstructor>
      </div>

      <MyCardConstructor title="Add a pupil">

        <div v-if="candidateItems.length" class="w-full max-w-xl">
          <MySearchPickConstructor
            :model-value="null"
            :items="candidateItems"
            placeholder="Pick one of your centre 120 candidates…"
            @pick="useCandidate"
          />
        </div>

        <div class="flex flex-wrap items-end gap-3">
          <div class="w-full sm:w-80">
            <MyInputConstructor
              v-model="form.pupil_name"
              size="small"
              label="Pupil"
              label-size="small"
              :placeholder="candidateItems.length ? 'Or type a name' : 'Pupil name'"
            />
          </div>
          <div class="w-full sm:w-56">
            <MyInputConstructor
              v-model="form.target_date"
              type="date"
              size="small"
              label="Exam around (optional)"
              label-size="small"
            />
          </div>
        </div>

        <div v-if="pickedExams.length" class="flex w-full max-w-xl flex-col gap-1">
          <MyTextConstructor variant="button-sm" textColor="text-brand-text-soft" spacing="none">
            <template #myTitle>Previous exams</template>
          </MyTextConstructor>
          <MyTableConstructor
            :data="pickedExams"
            :columns="examColumns"
            size="small"
            :sortable="false"
            bare
          />
        </div>

        <SyllabusFilterSelects
          v-model:stream="form.exam_stream"
          v-model:instrument="form.instrument"
          v-model:grade="form.grade"
          :facets="facets"
          :allow-all="false"
          size="medium"
          labelled
        />

        <div>
          <MyButtonConstructor size="small" variant="primary" :icon="Plus" :disabled="!canAdd || adding" @click="addPlan">
            Add pupil
          </MyButtonConstructor>
        </div>
      </MyCardConstructor>

      <MyCardConstructor title="Your pupils">

        <div v-if="!plans.length">
          <MyTextConstructor bodyVariant="muted" spacing="none">
            No pupils yet. Add one above, then choose their pieces and mark how ready each part is.
          </MyTextConstructor>
        </div>

        <MyAccordionConstructor v-if="plans.length" :items="planRows" size="small" title-variant="button">
          <template v-for="plan in plans" :key="plan.id" #[`content-${plan.id}`]>
            <PiecePlanCard
              :plan="plan"
              :facets="facets"
              :section-labels="sectionLabels"
              :suggestions="suggestions"
              :max-items="maxItems"
              :max-score="maxScore"
              @summary="(s) => setSummary(plan.id, s)"
            />
          </template>
        </MyAccordionConstructor>
      </MyCardConstructor>
    </div>
  </div>
</template>
