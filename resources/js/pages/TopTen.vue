<!-- resources/js/pages/TopTen.vue -->
<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Search, Star, Trophy, Users, ChevronDown, ChevronUp, PlusCircle, LogIn } from 'lucide-vue-next'

interface ChartPiece {
  id: number
  stream: string
  instrument: string
  variant: string | null
  grade: string
  composer: string
  title: string
  teachers_using: number
  usage_score: number
  avg_rating: number | null
  rating_count: number
  position: number
  is_top_ten: boolean
}
interface Group {
  stream: string
  instrument: string
  grade: string
  top_ten: ChartPiece[]
  others: ChartPiece[]
}
interface MyVote { rating: number | null; used_band: number | null }

const props = defineProps<{
  groups: Group[]
  myVotes: Record<number, MyVote>
  canVote: boolean
  selectablePieces: { id: number; grade: string; label: string }[]
  streams: string[]
  streamInstruments: { stream: string; instrument: string }[]
  instrumentGrades: { instrument: string; grade: string }[]
  gradeOrder: string[]
  active: { stream: string; instrument: string; grade: string }
}>()

const page = usePage()
const flashSuccess = computed(() => (page.props as { flash?: { success?: string } }).flash?.success ?? '')
const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Top Ten — teachers’ favourite pieces',
  description:
    'The Top Ten Trinity exam pieces for every instrument and grade, voted for by music teachers. See which pieces are most loved, and log in to add your own votes.',
  canonicalUrl: 'https://musicexams.help/top-ten',
}
const breadcrumbPages = [{ name: 'Top Ten', href: '/top-ten', current: true }]

const RATING_LABELS: Record<number, string> = {
  1: 'Don’t like it',
  2: 'It’s OK',
  3: 'Quite a good piece',
  4: 'Love this piece',
}

// How often the teacher's students use the piece in exams (capped bands).
const USAGE_LABELS: Record<number, string> = {
  1: 'A few times',
  2: 'Regularly',
  3: 'Loads',
}
// Chart-facing phrasing for the aggregate usage across all teachers.
const USAGE_PHRASES: Record<number, string> = {
  1: 'Used a few times',
  2: 'Used regularly',
  3: 'Used loads',
}
function usagePhrase(p: ChartPiece): string {
  if (!p.teachers_using) return ''
  const avg = Math.round(p.usage_score / p.teachers_using)
  return USAGE_PHRASES[Math.min(3, Math.max(1, avg))] ?? ''
}

function hasVoted(pieceId: number): boolean {
  return !!props.myVotes[pieceId]
}
// Colour of the Vote/Edit button: solid while editing, tinted once voted,
// outline when not yet voted.
function voteBtnClass(pieceId: number): string {
  if (openVoteFor.value === pieceId) return 'border-brand-accent bg-brand-accent text-brand-text-inverse'
  if (hasVoted(pieceId)) return 'border-brand-accent bg-brand-accent/20 text-brand-accent hover:bg-brand-accent hover:text-brand-text-inverse'
  return 'border-brand-accent text-brand-accent hover:bg-brand-accent hover:text-brand-text-inverse'
}

const stream = ref(props.active.stream)
const instrument = ref(props.active.instrument)
const grade = ref(props.active.grade)

const instStream = computed<Record<string, string>>(() => {
  const m: Record<string, string> = {}
  props.streamInstruments.forEach((si) => { m[si.instrument] = si.stream })
  return m
})
function instLabel(inst: string): string {
  return instStream.value[inst] === 'Rock & Pop' ? `R&P ${inst}` : inst
}
const instrumentOptions = computed(() => {
  const src = stream.value ? props.streamInstruments.filter((si) => si.stream === stream.value) : props.streamInstruments
  return [...new Set(src.map((si) => si.instrument))].sort().map((v) => ({ value: v, label: instLabel(v) }))
})
const gradeOptions = computed(() => {
  let instrs: string[] | null = null
  if (instrument.value) instrs = [instrument.value]
  else if (stream.value) instrs = props.streamInstruments.filter((si) => si.stream === stream.value).map((si) => si.instrument)
  const present = new Set(
    props.instrumentGrades.filter((ig) => !instrs || instrs.includes(ig.instrument)).map((ig) => ig.grade),
  )
  return props.gradeOrder.filter((g) => present.has(g))
})

watch([stream, instrument], () => {
  if (instrument.value && !instrumentOptions.value.some((o) => o.value === instrument.value)) instrument.value = ''
  if (grade.value && !gradeOptions.value.includes(grade.value)) grade.value = ''
})

let timer: ReturnType<typeof setTimeout> | undefined
const loading = ref(false)
function go() {
  const data: Record<string, string> = {}
  if (stream.value) data.stream = stream.value
  if (instrument.value) data.instrument = instrument.value
  if (grade.value) data.grade = grade.value
  router.get('/top-ten', data, {
    preserveState: true, preserveScroll: true, replace: true,
    only: ['groups', 'myVotes', 'selectablePieces', 'active'],
    onStart: () => { loading.value = true },
    onFinish: () => { loading.value = false },
  })
}
function schedule(ms = 150) { clearTimeout(timer); timer = setTimeout(go, ms) }
watch([stream, instrument, grade], () => schedule(120))

function resetFilters() { stream.value = ''; instrument.value = ''; grade.value = '' }

// Send guests to login, then back to this chart (filters preserved).
const loginHref = computed(() => {
  const params = new URLSearchParams()
  if (stream.value) params.set('stream', stream.value)
  if (instrument.value) params.set('instrument', instrument.value)
  if (grade.value) params.set('grade', grade.value)
  const qs = params.toString()
  return '/login?redirect=' + encodeURIComponent('/top-ten' + (qs ? '?' + qs : ''))
})

function groupTitle(g: Group): string {
  const inst = g.stream === 'Rock & Pop' ? `R&P ${g.instrument}` : g.instrument
  return `${inst} · ${g.grade}`
}

// Joint-position detection: a position shared by more than one piece in a group.
function sharedPositions(list: ChartPiece[]): Set<number> {
  const counts: Record<number, number> = {}
  list.forEach((p) => { counts[p.position] = (counts[p.position] ?? 0) + 1 })
  return new Set(Object.entries(counts).filter(([, n]) => n > 1).map(([pos]) => Number(pos)))
}

const hasGroups = computed(() => props.groups.length > 0)

// ── Inline voting ──────────────────────────────────────────────
const openVoteFor = ref<number | null>(null)
const draft = reactive<{ rating: number | null; band: number | null }>({ rating: null, band: null })

function openVote(pieceId: number) {
  if (!props.canVote) return
  const mine = props.myVotes[pieceId]
  draft.rating = mine?.rating ?? null
  draft.band = mine?.used_band ?? null
  openVoteFor.value = openVoteFor.value === pieceId ? null : pieceId
}
function setRating(n: number) { draft.rating = draft.rating === n ? null : n }
function setBand(n: number) { draft.band = draft.band === n ? null : n }

const saving = ref(false)
function submitVote(pieceId: number) {
  saving.value = true
  router.post('/top-ten/vote',
    { syllabus_piece_id: pieceId, rating: draft.rating, used_band: draft.band },
    {
      preserveScroll: true,
      onFinish: () => { saving.value = false },
      onSuccess: () => { openVoteFor.value = null },
    },
  )
}

// ── Rate a piece not yet on the chart ──────────────────────────
const ratePieceId = ref<number | ''>('')
const rateRating = ref<number | null>(null)
const rateBand = ref<number | null>(null)
const rateSearch = ref('')
const showList = ref(false)

const filteredSelectable = computed(() => {
  const term = rateSearch.value.trim().toLowerCase()
  if (!term) return props.selectablePieces
  return props.selectablePieces.filter((p) => p.label.toLowerCase().includes(term) || p.grade.toLowerCase().includes(term))
})
const selectedPieceLabel = computed(() => {
  const p = props.selectablePieces.find((x) => x.id === ratePieceId.value)
  return p ? `${p.grade} — ${p.label}` : ''
})
function pickPiece(p: { id: number; grade: string; label: string }) {
  ratePieceId.value = p.id
  rateSearch.value = `${p.grade} — ${p.label}`
  showList.value = false
}
function onSearchInput() {
  if (ratePieceId.value !== '') ratePieceId.value = ''
  showList.value = true
}
function closeListSoon() { setTimeout(() => { showList.value = false }, 150) }

function submitNewRating() {
  if (ratePieceId.value === '') return
  saving.value = true
  router.post('/top-ten/vote',
    { syllabus_piece_id: ratePieceId.value, rating: rateRating.value, used_band: rateBand.value },
    {
      preserveScroll: true,
      onFinish: () => { saving.value = false },
      onSuccess: () => { ratePieceId.value = ''; rateRating.value = null; rateBand.value = null; rateSearch.value = ''; showList.value = false },
    },
  )
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-8 md:pt-40">
      <div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="mb-6"><Breadcrumbs :pages="breadcrumbPages" home-href="/" /></div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Teachers’ Top Ten</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor variant="heading" fontFamily="display" alignment="center" spacing="tight" titleTag="h1" class="mt-3 md:!text-3xl lg:!text-4xl">
              <template #myTitle>The pieces teachers love most</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft md:text-lg">
              For every instrument and grade, teachers vote on the Trinity exam pieces their students use — how often, and how much they love them. The result: a Top Ten, chosen by the people who teach them.
            </p>
            <p class="mx-auto mt-3 text-sm text-brand-text-soft">
              Looking for the full repertoire list?
              <a href="/syllabus?from=top-ten" class="font-semibold text-brand-accent underline hover:opacity-70">Open the Piece Finder →</a>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- BODY -->
    <section class="relative bg-cover bg-center bg-fixed" style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg')">
      <div class="absolute inset-0 bg-brand-primary/60"></div>
      <div class="relative">
        <!-- CONTROLS -->
        <div class="sticky top-0 z-20 border-b border-white/10 bg-brand-primary/85 backdrop-blur">
          <div class="mx-auto max-w-5xl px-4 py-3 sm:px-6">
            <div class="flex flex-wrap items-center gap-2">
              <select v-model="stream" class="rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white backdrop-blur-sm focus:border-brand-accent focus:outline-none">
                <option class="text-brand-text" value="">All exam types</option>
                <option class="text-brand-text" v-for="s in props.streams" :key="s" :value="s">{{ s }}</option>
              </select>
              <select v-model="instrument" class="rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white backdrop-blur-sm focus:border-brand-accent focus:outline-none">
                <option class="text-brand-text" value="">All instruments</option>
                <option class="text-brand-text" v-for="i in instrumentOptions" :key="i.value" :value="i.value">{{ i.label }}</option>
              </select>
              <select v-model="grade" class="rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white backdrop-blur-sm focus:border-brand-accent focus:outline-none">
                <option class="text-brand-text" value="">All grades</option>
                <option class="text-brand-text" v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
              </select>
              <button v-if="stream || instrument || grade" type="button" class="ml-auto text-sm font-semibold text-brand-accent hover:opacity-70" @click="resetFilters">Clear filters</button>
            </div>
            <div class="mt-2 text-sm text-white/70">
              <span v-if="loading">Loading…</span>
              <span v-else-if="props.canVote">You’re logged in — tap a piece to add or change your vote.</span>
              <span v-else class="inline-flex items-center gap-1">
                <LogIn class="h-4 w-4 text-brand-accent" />
                Are you a teacher?
                <a :href="loginHref" class="font-semibold text-brand-accent underline hover:opacity-70">Log in to vote</a>
              </span>
            </div>
          </div>
        </div>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
          <!-- Flash confirmation -->
          <div v-if="flashSuccess" class="mb-6 rounded-xl border border-brand-accent/50 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm">
            {{ flashSuccess }}
          </div>

          <!-- Rate-a-piece panel (teachers only) -->
          <div v-if="props.canVote" :class="animClass('fade-up', 0)" class="mb-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 backdrop-blur-sm">
            <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
              <PlusCircle class="h-5 w-5 text-brand-accent" />
              <span class="text-lg font-bold text-white sm:text-xl">Rate a piece</span>
            </div>
            <div class="p-5 sm:p-6">
              <p v-if="!instrument" class="text-sm text-white/80">Choose an instrument above to pick from its pieces, then rate it or record how many of your students have used it in an exam.</p>
              <div v-else class="space-y-4">
                <div>
                  <div class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-brand-accent" />
                    <input v-model="rateSearch" type="text" autocomplete="off" placeholder="Type to find a piece by title or composer…"
                      @focus="showList = true" @input="onSearchInput" @blur="closeListSoon"
                      class="w-full rounded-xl border border-white/20 bg-white/10 py-2.5 pr-3 pl-9 text-base text-white placeholder:text-white/50 focus:border-brand-accent focus:outline-none" />
                  </div>
                  <ul v-if="showList && filteredSelectable.length"
                    class="mt-1 max-h-80 w-full overflow-y-auto rounded-xl border border-white/20 bg-white/5">
                    <li v-for="p in filteredSelectable.slice(0, 50)" :key="p.id">
                      <button type="button" @mousedown.prevent="pickPiece(p)"
                        class="block w-full px-3 py-2.5 text-left text-sm text-white transition hover:bg-white/10"
                        :class="p.id === ratePieceId ? 'bg-white/10' : ''">
                        <span class="text-white/60">{{ p.grade }}</span> — {{ p.label }}
                      </button>
                    </li>
                  </ul>
                  <p v-else-if="showList && rateSearch.trim() && !filteredSelectable.length"
                    class="mt-1 w-full rounded-xl border border-white/20 bg-white/5 px-3 py-2 text-sm text-white/70">
                    No matching pieces
                  </p>
                </div>

                <!-- Rating controls only appear once a piece is chosen. -->
                <p v-if="ratePieceId === ''" class="text-sm text-white/60">Find and choose a piece above, then add your rating.</p>

                <div v-else>
                  <p class="mb-3 text-sm text-white/80">
                    Rating: <span class="font-semibold text-white">{{ selectedPieceLabel }}</span>
                  </p>
                  <div class="flex flex-wrap items-end gap-4">
                    <div class="w-56">
                      <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Your rating</span>
                      <div class="flex items-center gap-1">
                        <button v-for="n in 4" :key="n" type="button" @click="rateRating = rateRating === n ? null : n"
                          :title="RATING_LABELS[n]" class="transition hover:scale-110">
                          <Star class="h-7 w-7" :class="rateRating !== null && n <= rateRating ? 'fill-brand-accent text-brand-accent' : 'text-white/40'" />
                        </button>
                      </div>
                      <span class="mt-1 block h-4 text-sm text-white/80">{{ rateRating ? RATING_LABELS[rateRating] : '' }}</span>
                    </div>
                    <div>
                      <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Used in exams</span>
                      <div class="flex flex-wrap gap-2">
                        <button v-for="n in 3" :key="n" type="button" @click="rateBand = rateBand === n ? null : n"
                          class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                          :class="rateBand === n ? 'border-brand-accent bg-brand-accent text-brand-text-inverse' : 'border-white/20 bg-white/10 text-white hover:border-brand-accent'">
                          {{ USAGE_LABELS[n] }}
                        </button>
                      </div>
                    </div>
                    <button type="button" :disabled="saving" @click="submitNewRating"
                      class="rounded-xl bg-brand-accent px-5 py-2.5 text-sm font-bold text-brand-text-inverse transition hover:opacity-90 disabled:opacity-40">
                      Save vote
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="!hasGroups" :class="animClass('fade-up', 1)" class="rounded-2xl border-4 border-brand-accent bg-white/10 p-8 text-center backdrop-blur-sm">
            <Trophy class="mx-auto h-10 w-10 text-brand-accent" />
            <p class="mt-4 text-lg font-bold text-white">No votes here yet</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-white/80">
              Be the first to rank the pieces for this instrument and grade. Teachers, log in and add the pieces your students use.
            </p>
          </div>

          <!-- Chart groups -->
          <div v-else class="space-y-8">
            <div v-for="(g, gi) in props.groups" :key="g.stream + g.instrument + g.grade" :class="animClass('fade-up', Math.min(gi + 1, 3))"
              class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 backdrop-blur-sm">
              <!-- Group header -->
              <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
                <Trophy class="h-5 w-5 text-brand-accent" />
                <span class="text-lg font-bold text-white sm:text-xl">{{ groupTitle(g) }}</span>
              </div>

              <!-- Top Ten -->
              <ul class="divide-y divide-white/10">
                <li v-for="p in g.top_ten" :key="p.id" class="px-4 py-3 transition-colors sm:px-6"
                  :class="openVoteFor === p.id ? 'bg-brand-accent/10' : (hasVoted(p.id) ? 'bg-brand-accent/5' : '')">
                  <div class="flex items-center gap-3 sm:gap-4">
                    <div class="flex h-9 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-accent text-sm font-extrabold text-brand-text-inverse">
                      <span v-if="sharedPositions(g.top_ten).has(p.position)" class="mr-0.5 text-xs">=</span>{{ p.position }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-sm font-bold text-white sm:text-base">{{ p.title }}</p>
                      <p class="truncate text-xs text-white/70 sm:text-sm">{{ p.composer }}</p>
                      <p v-if="usagePhrase(p)" class="mt-0.5 text-xs font-semibold text-brand-accent">{{ usagePhrase(p) }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                      <div class="flex items-center justify-end gap-1">
                        <Star v-for="n in 4" :key="n" class="h-4 w-4"
                          :class="p.avg_rating !== null && n <= Math.round(p.avg_rating) ? 'fill-brand-accent text-brand-accent' : 'text-white/30'" />
                      </div>
                      <p class="mt-0.5 text-xs text-white/60">
                        <span v-if="p.avg_rating !== null">{{ p.avg_rating.toFixed(1) }} · {{ p.rating_count }} vote{{ p.rating_count === 1 ? '' : 's' }}</span>
                        <span v-else>Not yet rated</span>
                      </p>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                      <p class="inline-flex items-center gap-1 text-sm font-bold text-white"><Users class="h-4 w-4 text-brand-accent" />{{ p.teachers_using }}</p>
                      <p class="text-xs text-white/60">{{ p.teachers_using === 1 ? 'teacher uses it' : 'teachers use it' }}</p>
                    </div>
                    <button v-if="props.canVote" type="button" @click="openVote(p.id)"
                      class="shrink-0 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                      :class="voteBtnClass(p.id)">
                      {{ hasVoted(p.id) ? 'Edit' : 'Vote' }}
                    </button>
                  </div>

                  <!-- Mobile "used" line -->
                  <p class="mt-1 pl-14 text-xs text-white/60 sm:hidden">Used by {{ p.teachers_using }} {{ p.teachers_using === 1 ? 'teacher' : 'teachers' }}</p>

                  <!-- Inline vote editor -->
                  <div v-if="openVoteFor === p.id" class="mt-3 ml-14 rounded-xl border border-white/20 bg-black/40 p-4">
                    <div class="flex flex-wrap items-end gap-4">
                      <div>
                        <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Your rating</span>
                        <div class="flex items-center gap-1">
                          <button v-for="n in 4" :key="n" type="button" @click="setRating(n)" :title="RATING_LABELS[n]" class="transition hover:scale-110">
                            <Star class="h-6 w-6" :class="draft.rating !== null && n <= draft.rating ? 'fill-brand-accent text-brand-accent' : 'text-white/40'" />
                          </button>
                          <span v-if="draft.rating" class="ml-2 text-sm text-white/80">{{ RATING_LABELS[draft.rating] }}</span>
                        </div>
                      </div>
                      <div>
                        <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Used in exams</span>
                        <div class="flex flex-wrap gap-2">
                          <button v-for="n in 3" :key="n" type="button" @click="setBand(n)"
                            class="rounded-lg border px-3 py-1.5 text-sm font-semibold transition"
                            :class="draft.band === n ? 'border-brand-accent bg-brand-accent text-brand-text-inverse' : 'border-white/20 bg-white/10 text-white hover:border-brand-accent'">
                            {{ USAGE_LABELS[n] }}
                          </button>
                        </div>
                      </div>
                      <button type="button" :disabled="saving" @click="submitVote(p.id)"
                        class="rounded-xl bg-brand-accent px-4 py-2 text-sm font-bold text-brand-text-inverse transition hover:opacity-90 disabled:opacity-40">Save</button>
                    </div>
                    <p class="mt-2 text-xs text-white/50">Clear the stars and usage, then save, to remove your vote.</p>
                  </div>
                </li>
              </ul>

              <!-- Other pieces -->
              <details v-if="g.others.length" class="group border-t border-white/10">
                <summary class="flex cursor-pointer list-none items-center gap-2 bg-black/30 px-5 py-3 text-sm font-semibold text-white/80 hover:text-white sm:px-6">
                  <ChevronDown class="h-4 w-4 text-brand-accent group-open:hidden" />
                  <ChevronUp class="hidden h-4 w-4 text-brand-accent group-open:block" />
                  {{ g.others.length }} more piece{{ g.others.length === 1 ? '' : 's' }} outside the Top Ten
                </summary>
                <ul class="divide-y divide-white/5">
                  <li v-for="p in g.others" :key="p.id" class="px-5 py-2.5 transition-colors sm:px-6"
                    :class="openVoteFor === p.id ? 'bg-brand-accent/10' : (hasVoted(p.id) ? 'bg-brand-accent/5' : '')">
                    <div class="flex items-center gap-3">
                      <div class="w-11 shrink-0 text-center text-xs font-bold text-white/50">
                        <span v-if="sharedPositions(g.others).has(p.position)">=</span>{{ p.position }}
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white/90">{{ p.title }}</p>
                        <p class="truncate text-xs text-white/60">{{ p.composer }}</p>
                        <p v-if="usagePhrase(p)" class="mt-0.5 text-xs font-medium text-brand-accent">{{ usagePhrase(p) }}</p>
                      </div>
                      <div class="flex shrink-0 items-center gap-1">
                        <Star v-for="n in 4" :key="n" class="h-3.5 w-3.5"
                          :class="p.avg_rating !== null && n <= Math.round(p.avg_rating) ? 'fill-brand-accent text-brand-accent' : 'text-white/25'" />
                      </div>
                      <div class="w-20 shrink-0 text-right text-xs text-white/60">{{ p.teachers_using }} {{ p.teachers_using === 1 ? 'teacher' : 'teachers' }}</div>
                      <button v-if="props.canVote" type="button" @click="openVote(p.id)"
                        class="shrink-0 rounded-lg border px-2.5 py-1 text-xs font-semibold transition"
                        :class="voteBtnClass(p.id)">
                        {{ hasVoted(p.id) ? 'Edit' : 'Vote' }}
                      </button>
                    </div>

                    <!-- Inline vote editor -->
                    <div v-if="openVoteFor === p.id" class="mt-3 ml-14 rounded-xl border border-white/20 bg-black/40 p-4">
                      <div class="flex flex-wrap items-end gap-4">
                        <div>
                          <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Your rating</span>
                          <div class="flex items-center gap-1">
                            <button v-for="n in 4" :key="n" type="button" @click="setRating(n)" :title="RATING_LABELS[n]" class="transition hover:scale-110">
                              <Star class="h-6 w-6" :class="draft.rating !== null && n <= draft.rating ? 'fill-brand-accent text-brand-accent' : 'text-white/40'" />
                            </button>
                          </div>
                          <span class="mt-1 block h-4 text-sm text-white/80">{{ draft.rating ? RATING_LABELS[draft.rating] : '' }}</span>
                        </div>
                        <div>
                          <span class="mb-1 block text-xs font-semibold tracking-wide text-white/70 uppercase">Used in exams</span>
                          <div class="flex flex-wrap gap-2">
                            <button v-for="n in 3" :key="n" type="button" @click="setBand(n)"
                              class="rounded-lg border px-3 py-1.5 text-sm font-semibold transition"
                              :class="draft.band === n ? 'border-brand-accent bg-brand-accent text-brand-text-inverse' : 'border-white/20 bg-white/10 text-white hover:border-brand-accent'">
                              {{ USAGE_LABELS[n] }}
                            </button>
                          </div>
                        </div>
                        <button type="button" :disabled="saving" @click="submitVote(p.id)"
                          class="rounded-xl bg-brand-accent px-4 py-2 text-sm font-bold text-brand-text-inverse transition hover:opacity-90 disabled:opacity-40">Save</button>
                      </div>
                      <p class="mt-2 text-xs text-white/50">Clear the stars and usage, then save, to remove your vote.</p>
                    </div>
                  </li>
                </ul>
              </details>
            </div>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>
</template>
