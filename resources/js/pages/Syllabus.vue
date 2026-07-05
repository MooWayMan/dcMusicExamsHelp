<!-- resources/js/pages/Syllabus.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Search, Youtube, ShoppingCart, ExternalLink, Monitor } from 'lucide-vue-next'

interface Audio { youtube_search?: string; youtube_music?: string; spotify?: string; apple_music?: string; amazon_music?: string }
interface Piece {
  id: number; stream: string; instrument: string; variant: string | null; grade: string
  composer: string; title: string; book: string | null; publisher_code: string | null
  technical_focus: boolean; voice_range: string | null
  buy_kind: string; buy_url: string | null; buy_edition: string | null
  buy_alt_url: string | null; buy_alt_edition: string | null; buy_ebook_url: string | null
  curated_video_url: string | null; audio: Audio | null; also_in: string[]
}

const props = defineProps<{
  pieces: Piece[]
  count: number
  hasQuery: boolean
  streams: string[]
  streamInstruments: { stream: string; instrument: string }[]
  instrumentGrades: { instrument: string; grade: string }[]
  gradeOrder: string[]
  active: { stream: string; instrument: string; grade: string; q: string }
}>()

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Piece Finder — Trinity exam pieces',
  description:
    'Search Trinity College London exam repertoire by instrument, grade and exam type. See the book each piece is in, listen on YouTube, and buy the exam book.',
  canonicalUrl: 'https://musicexams.help/syllabus',
}
const breadcrumbPages = [{ name: 'Piece Finder', href: '/syllabus', current: true }]

const q = ref(props.active.q)
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

// Keep child filters valid when a parent changes.
watch([stream, instrument], () => {
  if (instrument.value && !instrumentOptions.value.some((o) => o.value === instrument.value)) instrument.value = ''
  if (grade.value && !gradeOptions.value.includes(grade.value)) grade.value = ''
})

// Server-side fetch (debounced so cascading resets collapse into one request).
let timer: ReturnType<typeof setTimeout> | undefined
const loading = ref(false)
function go() {
  const data: Record<string, string> = {}
  if (stream.value) data.stream = stream.value
  if (instrument.value) data.instrument = instrument.value
  if (grade.value) data.grade = grade.value
  if (q.value.trim()) data.q = q.value.trim()
  router.get('/syllabus', data, {
    preserveState: true, preserveScroll: true, replace: true,
    only: ['pieces', 'count', 'hasQuery', 'active'],
    onStart: () => { loading.value = true },
    onFinish: () => { loading.value = false },
  })
}
function schedule(ms = 250) { clearTimeout(timer); timer = setTimeout(go, ms) }
watch([stream, instrument, grade], () => schedule(120))
watch(q, () => schedule(350))

function resetFilters() {
  q.value = ''; stream.value = ''; instrument.value = ''; grade.value = ''
}
function instrumentLabel(p: Piece): string {
  const base = p.stream === 'Rock & Pop' ? `R&P ${p.instrument}` : p.instrument
  return p.variant ? `${base} (${p.variant})` : base
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
              <template #myTitle>Piece Finder</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor variant="heading" fontFamily="display" alignment="center" spacing="tight" titleTag="h1" class="mt-3 md:!text-3xl lg:!text-4xl">
              <template #myTitle>Find your exam pieces</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft md:text-lg">
              Search Trinity repertoire by instrument, grade and exam type. See which book each piece is in, listen to it, and buy the official exam book.
            </p>
            <p class="mx-auto mt-3 text-sm text-brand-text-soft">
              Need scales, technical work and full requirements?
              <a href="/exam-guide/syllabuses?from=syllabus" class="font-semibold text-brand-accent underline hover:opacity-70">See the official syllabus →</a>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FINDER -->
    <section class="relative bg-cover bg-center bg-fixed" style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg')">
      <div class="absolute inset-0 bg-brand-primary/55"></div>
      <div class="relative">
        <!-- CONTROLS -->
        <div class="sticky top-0 z-20 border-b border-white/10 bg-brand-primary/85 backdrop-blur">
          <div class="mx-auto max-w-5xl px-4 py-3 sm:px-6">
            <div class="flex flex-wrap items-center gap-2">
              <label class="relative min-w-[200px] flex-1">
                <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-brand-accent" />
                <input v-model="q" type="search" placeholder="Search piece, composer/artist or book…"
                  class="w-full rounded-xl border border-white/20 bg-white/10 py-2.5 pr-3 pl-9 text-base text-white placeholder:text-white/50 backdrop-blur-sm focus:border-brand-accent focus:outline-none" />
              </label>
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
            </div>
            <div class="mt-2 flex items-center justify-between text-sm text-white/70">
              <span>{{ loading ? 'Searching…' : (props.hasQuery ? props.count + ' piece' + (props.count === 1 ? '' : 's') : 'Choose an exam type or search to begin') }}</span>
              <button v-if="q || stream || instrument || grade" type="button" class="font-semibold text-brand-accent hover:opacity-70" @click="resetFilters">Clear filters</button>
            </div>
          </div>
        </div>

        <!-- RESULTS -->
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
          <div v-if="!props.hasQuery" class="rounded-2xl border border-white/15 bg-white/10 py-12 text-center text-white/85 backdrop-blur-sm">
            Pick an <strong class="text-white">exam type</strong> or <strong class="text-white">instrument</strong> above, or type a search, to find pieces.
          </div>

          <div v-for="p in props.pieces" :key="p.id" class="mb-3 rounded-2xl border border-brand-accent/40 bg-white/10 p-4 shadow-xl backdrop-blur-sm">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <div>
                <span class="text-base font-semibold text-white">{{ p.title }}</span>
                <span class="text-sm text-white/60"> — {{ p.composer }}</span>
              </div>
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-0.5 text-xs text-white/80">{{ instrumentLabel(p) }}</span>
                <span class="rounded-full bg-brand-accent px-2.5 py-0.5 text-xs font-semibold text-white">{{ p.grade }}</span>
                <span v-if="p.technical_focus" class="rounded-full bg-brand-success/30 px-2.5 py-0.5 text-xs font-semibold text-white">Technical focus</span>
                <span v-if="p.voice_range" class="rounded-full border border-white/20 px-2.5 py-0.5 text-xs text-white/70">Range {{ p.voice_range }}</span>
              </div>
            </div>

            <p v-if="p.book" class="mt-2 text-sm text-white/90">{{ p.book }}</p>
            <p v-if="p.publisher_code && p.stream !== 'Rock & Pop'" class="text-xs text-white/55">{{ p.publisher_code }}</p>
            <p v-if="p.also_in && p.also_in.length" class="mt-2 text-xs text-brand-accent">↺ Also used in Trinity: {{ p.also_in.join(' · ') }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-2">
              <a v-if="p.curated_video_url" :href="p.curated_video_url" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg border border-brand-success/50 bg-brand-success/20 px-3 py-1.5 text-xs font-semibold text-white hover:opacity-80">
                <Youtube class="h-4 w-4 text-brand-accent" /> Watch exam performance
              </a>
              <a v-else-if="p.audio?.youtube_search" :href="p.audio.youtube_search" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg border border-white/20 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/10">
                <Youtube class="h-4 w-4 text-brand-accent" /> Find performance (YouTube)
              </a>
              <a v-if="p.audio?.spotify" :href="p.audio.spotify" target="_blank" rel="noopener noreferrer" class="rounded-lg px-2 py-1 text-xs text-white/60 hover:text-white">Spotify</a>
              <a v-if="p.audio?.apple_music" :href="p.audio.apple_music" target="_blank" rel="noopener noreferrer" class="rounded-lg px-2 py-1 text-xs text-white/60 hover:text-white">Apple</a>
              <a v-if="p.audio?.amazon_music" :href="p.audio.amazon_music" target="_blank" rel="noopener noreferrer" class="rounded-lg px-2 py-1 text-xs text-white/60 hover:text-white">Amazon Music</a>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
              <!-- Ebook = primary (blue), shown first when available -->
              <a v-if="p.buy_ebook_url" :href="p.buy_ebook_url" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">
                <Monitor class="h-4 w-4" /> Buy ebook (10% off)
              </a>
              <!-- Amazon print = primary when no ebook, secondary when ebook present -->
              <a v-if="p.buy_kind === 'exact' && p.buy_url" :href="p.buy_url" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white"
                :class="p.buy_ebook_url ? 'border border-white/25 bg-white/5 hover:bg-white/10' : 'bg-brand-accent hover:opacity-90'">
                <ShoppingCart class="h-4 w-4" /> Buy print<span v-if="p.buy_edition && p.buy_edition !== 'ISBN'"> ({{ p.buy_edition }})</span>
              </a>
              <span v-if="p.buy_kind !== 'exact' && !p.buy_ebook_url" class="text-xs text-white/55">{{ p.publisher_code || 'Other publisher' }} — not stocked on Amazon</span>
              <a v-if="p.buy_kind === 'exact' && p.buy_alt_url" :href="p.buy_alt_url" target="_blank" rel="noopener noreferrer"
                class="text-xs text-white/70 underline hover:text-white">Also in the cheaper {{ p.buy_alt_edition }} book →</a>
            </div>
          </div>

          <div v-if="props.hasQuery && props.count === 0 && !loading" class="rounded-2xl border border-white/15 bg-white/10 py-12 text-center text-white/80 backdrop-blur-sm">
            No pieces match your filters.
            <button type="button" class="font-semibold text-brand-accent hover:opacity-70" @click="resetFilters">Clear filters</button>
          </div>
          <p v-else-if="props.count > props.pieces.length" class="py-4 text-center text-sm text-white/70">
            Showing the first {{ props.pieces.length }} of {{ props.count }} — narrow your search to see more.
          </p>

          <div class="mt-6 rounded-2xl border border-brand-accent/40 bg-white/10 p-5 text-center backdrop-blur-sm">
            <p class="text-sm text-white/85 sm:text-base">
              The pieces are only part of the exam. For scales, technical work, supporting tests and how it's marked, head to the official Trinity syllabus for your instrument.
            </p>
            <a href="/exam-guide/syllabuses?from=syllabus" class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-white/25 bg-white/5 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
              View the official syllabuses <ExternalLink class="h-4 w-4 text-brand-accent" />
            </a>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>
</template>
