<!-- resources/js/pages/ThankYou.vue -->
<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Heart, Trophy, Music, Star, Award, Search, ArrowUp, ChevronRight } from 'lucide-vue-next'

interface HallOfFameEntry {
  name: string
  instrument: string
  grade: string
  score: number
  result: string
  award: string
  certificate: string
}

interface ThankYouEntry {
  name: string
  instrument: string
  grade: string
  result: string
  certificate: string | null
}

interface Summary {
  distinctions: number
  merits: number
  passes: number
  total: number
}

interface QuarterOption {
  quarter: number
  year: number
}

interface QuarterData {
  quarter: number
  year: number
  label: string
  hallOfFameEntries: HallOfFameEntry[]
  thankYouEntries: ThankYouEntry[]
  summary: Summary
}

const props = defineProps<{
  defaultQuarter: number
  defaultYear: number
  availableQuarters: QuarterOption[]
  allQuartersData: QuarterData[]
}>()

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)
const searchQuery = ref('')
const showBackToTop = ref(false)

/* ── Client-side quarter switching ── */
const activeQuarter = ref(props.defaultQuarter)
const activeYear = ref(props.defaultYear)

const activeData = computed<QuarterData | null>(() =>
  props.allQuartersData.find(
    (q) => q.quarter === activeQuarter.value && q.year === activeYear.value
  ) ?? null
)

const currentQuarterLabel = computed(() => `Q${activeQuarter.value} ${activeYear.value}`)
const hallOfFameEntries = computed(() => activeData.value?.hallOfFameEntries ?? [])
const thankYouEntries = computed(() => activeData.value?.thankYouEntries ?? [])
const summary = computed(() => activeData.value?.summary ?? { distinctions: 0, merits: 0, passes: 0, total: 0 })

/* ── Fade transition for dynamic content ── */
const contentVisible = ref(true)

const switchQuarter = async (q: QuarterOption) => {
  if (q.quarter === activeQuarter.value && q.year === activeYear.value) return

  // Fade out
  contentVisible.value = false

  // Wait for fade-out to finish, then swap data and fade in
  await new Promise((resolve) => setTimeout(resolve, 200))
  searchQuery.value = ''
  activeQuarter.value = q.quarter
  activeYear.value = q.year
  await nextTick()
  contentVisible.value = true
}

/* ── Nudge to previous quarter when few entries ── */
const nudge = computed(() => {
  if ((activeData.value?.thankYouEntries.length ?? 0) >= 10) return null

  // Find the nearest quarter with data that comes before the active one
  const prev = props.availableQuarters.find(
    (q) => (q.year < activeYear.value) || (q.year === activeYear.value && q.quarter < activeQuarter.value)
  )
  if (!prev) return null

  return { quarter: prev.quarter, year: prev.year, label: `Q${prev.quarter} ${prev.year}` }
})

const goToNudgeQuarter = () => {
  if (!nudge.value) return
  switchQuarter({ quarter: nudge.value.quarter, year: nudge.value.year })
}

/* ── Search filter ── */
const filteredEntries = computed(() => {
  if (!searchQuery.value.trim()) return thankYouEntries.value
  const q = searchQuery.value.toLowerCase().trim()
  return thankYouEntries.value.filter(
    (e) => e.name.toLowerCase().includes(q) || e.instrument.toLowerCase().includes(q)
  )
})

/* ── Quarter tabs ── */
const quarterLabel = (q: QuarterOption) => `Q${q.quarter} ${q.year}`
const isActiveQuarter = (q: QuarterOption) =>
  q.quarter === activeQuarter.value && q.year === activeYear.value

/* ── Result badge colours ── */
const resultBadgeClass = (result: string) => {
  switch (result) {
    case 'Distinction': return 'bg-brand-accent text-white'
    case 'Merit': return 'bg-brand-success text-white'
    case 'Pass': return 'bg-brand-teal text-white'
    default: return 'bg-brand-surface-soft text-brand-text'
  }
}

/* ── Back to top ── */
const handleScroll = () => { showBackToTop.value = window.scrollY > 400 }
const scrollToTop = () => { window.scrollTo({ top: 0, behavior: 'smooth' }) }
onMounted(() => window.addEventListener('scroll', handleScroll))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))

const pageMeta = {
  title: 'Thank You — musicExams.help',
  description: 'Every student who enters a Trinity exam through centre 120 gets recognised here. Thank you for your hard work.',
}

const breadcrumbPages = [{ name: 'Thank You', href: '/thank-you', current: true }]

const hallOfFameLogo = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Highest+score+In5.png'
const certStudent = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/certAceiveStu_Dis.png'
const thankYouHero = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Thank_You_card.png'
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-brand-bg text-brand-text">
    <Navbar />

    <!-- HEADER — Canva hero card on light background -->
    <section class="bg-brand-bg pt-36 pb-2 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-4">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl shadow-2xl">
          <img
            :src="thankYouHero"
            alt="Thank You — Every student. Every exam. Recognised. Centre 120"
            class="h-auto w-full object-contain"
          />
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-4 text-center">
          <span class="inline-flex items-center gap-2 rounded-full bg-brand-accent/10 px-4 py-2 ring-1 ring-brand-accent/20">
            <Heart class="h-4 w-4 text-brand-accent" />
            <span class="text-sm font-semibold text-brand-accent">{{ currentQuarterLabel }}</span>
          </span>
        </div>
      </div>
    </section>

    <!-- HALL OF FAME SPOTLIGHT — star constellation background -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_stars_bg.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-black/50"></div>

      <!-- Shooting stars -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="shooting-star shooting-star-1"></div>
        <div class="shooting-star shooting-star-2"></div>
        <div class="shooting-star shooting-star-5"></div>
        <div class="shooting-star shooting-star-9"></div>
        <div class="shooting-star shooting-star-3"></div>
        <div class="shooting-star shooting-star-7"></div>
      </div>

      <div class="relative mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-20">
        <!-- Hall of Fame banner — animates on page load only -->
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl shadow-2xl">
          <img
            :src="hallOfFameLogo"
            alt="Hall of Fame — Highest Score"
            class="h-auto w-full object-contain"
          />
        </div>

        <!-- Quarter label — animates on page load only -->
        <div :class="animClass('fade-up', 2)" class="mt-8 text-center">
          <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-2 backdrop-blur-sm ring-1 ring-white/20">
            <Trophy class="h-4 w-4 text-brand-accent" />
            <span class="text-sm font-semibold text-white">{{ currentQuarterLabel }} — Top Scorers</span>
          </span>
        </div>

        <!-- ═══ DYNAMIC CONTENT — fades on tab switch, no full-page animation ═══ -->
        <div
          class="transition-all duration-200 ease-in-out"
          :class="contentVisible ? 'opacity-100' : 'opacity-0 translate-y-2'"
        >
          <!-- Hall of Fame winner cards — spotlight style -->
          <div v-if="hallOfFameEntries.length > 0" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div
              v-for="entry in hallOfFameEntries"
              :key="entry.name + entry.award"
              class="relative overflow-hidden rounded-2xl border-4 border-yellow-400 bg-white/10 p-6 shadow-2xl shadow-yellow-400/20 ring-2 ring-yellow-300/40 backdrop-blur-sm"
            >
              <div class="absolute right-4 top-4">
                <Trophy class="h-10 w-10 text-brand-accent/30" />
              </div>
              <div class="flex items-center gap-2">
                <Star class="h-4 w-4 text-brand-accent" />
                <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ entry.award }}</p>
              </div>
              <p class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ entry.name }}</p>
              <p class="mt-1 text-base text-white/70 sm:text-base">
                {{ entry.instrument }} · {{ entry.grade }}
              </p>
              <div class="mt-4 flex items-center gap-3">
                <span :class="[resultBadgeClass(entry.result), 'rounded-full px-4 py-1.5 text-sm font-bold shadow-lg']">
                  {{ entry.result }} — {{ entry.score }}
                </span>
              </div>
              <p v-if="entry.certificate" class="mt-3 text-xs font-semibold text-white/60">
                <Award class="mb-0.5 mr-1 inline h-3.5 w-3.5 text-brand-accent" />{{ entry.certificate }}
              </p>
            </div>
          </div>

          <!-- Hall of Fame empty state — two motivational cards -->
          <div v-else class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Distinction card -->
            <div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-yellow-400/40 bg-white/5 p-6 text-center backdrop-blur-sm">
              <div class="absolute right-4 top-4">
                <Trophy class="h-10 w-10 text-brand-accent/20" />
              </div>
              <div class="flex items-center justify-center gap-2">
                <Star class="h-4 w-4 text-brand-accent" />
                <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">Highest Distinction</p>
              </div>
              <p class="mt-4 text-lg font-bold text-white sm:text-xl">Could this be you?</p>
              <p class="mx-auto mt-2 max-w-xs text-sm text-white/60">
                The highest scoring Distinction each quarter gets the spotlight here. Aim for the top mark.
              </p>
              <div class="mt-4">
                <span class="inline-block rounded-full bg-brand-accent/20 px-4 py-1.5 text-sm font-bold text-brand-accent ring-1 ring-brand-accent/30">
                  Distinction
                </span>
              </div>
            </div>

            <!-- Merit card -->
            <div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-brand-success/40 bg-white/5 p-6 text-center backdrop-blur-sm">
              <div class="absolute right-4 top-4">
                <Award class="h-10 w-10 text-brand-success/20" />
              </div>
              <div class="flex items-center justify-center gap-2">
                <Star class="h-4 w-4 text-brand-success" />
                <p class="text-xs font-bold uppercase tracking-widest text-brand-success">Highest Merit</p>
              </div>
              <p class="mt-4 text-lg font-bold text-white sm:text-xl">Could this be you?</p>
              <p class="mx-auto mt-2 max-w-xs text-sm text-white/60">
                The highest scoring Merit each quarter earns their place here. Play your best and shine.
              </p>
              <div class="mt-4">
                <span class="inline-block rounded-full bg-brand-success/20 px-4 py-1.5 text-sm font-bold text-brand-success ring-1 ring-brand-success/30">
                  Merit
                </span>
              </div>
            </div>
          </div>

          <!-- EVERY ENTRY COUNTS — student table -->
          <div class="mt-14">
            <div>
              <MyTextConstructor
                variant="subheading"
                fontFamily="display"
                alignment="center"
                spacing="tight"
                textColor="text-white"
                class="md:!text-2xl lg:!text-3xl"
              >
                <template #myTitle>Every entry counts</template>
              </MyTextConstructor>
              <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/70 sm:text-base md:text-lg lg:text-xl">
                Every student who enters through centre 120 is a star to us. You sat the exam, you did the work,
                and your name belongs here.
              </p>
            </div>

            <!-- Search bar -->
            <div class="mx-auto mt-6 max-w-md">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/40" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by name or instrument..."
                  class="w-full rounded-xl border-0 bg-white/10 py-2.5 pl-10 pr-4 text-sm text-white placeholder-white/40 ring-1 ring-white/20 backdrop-blur-sm transition-all duration-200 focus:bg-white/15 focus:outline-none focus:ring-2 focus:ring-brand-accent/50"
                />
              </div>
            </div>

            <!-- Table with integrated quarter tabs -->
            <div class="mt-6">
              <div class="overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/20 backdrop-blur-md">

                <!-- Quarter tabs — built into the top of the table -->
                <div v-if="availableQuarters.length > 1" class="flex border-b border-white/20">
                  <button
                    v-for="(q, i) in availableQuarters"
                    :key="`${q.quarter}-${q.year}`"
                    @click="switchQuarter(q)"
                    class="flex-1 py-3 text-sm font-bold transition-all duration-200"
                    :class="[
                      isActiveQuarter(q)
                        ? 'bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary text-white'
                        : 'bg-white/5 text-white/50 hover:bg-white/15 hover:text-white',
                      i > 0 ? 'border-l border-white/20' : ''
                    ]"
                  >
                    {{ quarterLabel(q) }}
                  </button>
                </div>

                <!-- Table column headers -->
                <div class="bg-white/25 px-4 py-3 backdrop-blur-md sm:px-6">
                  <div class="grid grid-cols-12 gap-2">
                    <div class="col-span-4 sm:col-span-3">
                      <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Student</p>
                    </div>
                    <div class="col-span-3 sm:col-span-3">
                      <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Instrument</p>
                    </div>
                    <div class="col-span-2 sm:col-span-3">
                      <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Grade</p>
                    </div>
                    <div class="col-span-3 sm:col-span-3">
                      <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Result</p>
                    </div>
                  </div>
                </div>

                <!-- Table rows -->
                <div v-if="filteredEntries.length > 0" class="divide-y divide-white/10">
                  <div
                    v-for="(entry, index) in filteredEntries"
                    :key="entry.name + entry.instrument"
                    class="grid grid-cols-12 items-center gap-2 px-4 py-3 sm:px-6"
                    :class="index % 2 === 1 ? 'bg-white/15' : 'bg-white/10'"
                  >
                    <div class="col-span-4 sm:col-span-3">
                      <div class="flex items-center gap-2">
                        <Music class="hidden h-4 w-4 shrink-0 text-brand-accent sm:block" />
                        <p class="text-sm font-semibold text-white sm:text-base">{{ entry.name }}</p>
                      </div>
                      <p v-if="entry.certificate" class="mt-0.5 text-[10px] font-medium text-brand-accent sm:text-xs sm:ml-6">
                        <Award class="mb-0.5 mr-0.5 inline h-3 w-3" />{{ entry.certificate }}
                      </p>
                    </div>
                    <div class="col-span-3 sm:col-span-3">
                      <p class="text-sm text-white/70 sm:text-base">{{ entry.instrument }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-3">
                      <p class="text-sm text-white/70 sm:text-base">{{ entry.grade }}</p>
                    </div>
                    <div class="col-span-3 sm:col-span-3">
                      <span :class="[resultBadgeClass(entry.result), 'inline-block rounded-full px-2.5 py-0.5 text-xs font-bold sm:text-sm']">
                        {{ entry.result }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Empty state — no results for this quarter -->
                <div v-else-if="thankYouEntries.length === 0" class="bg-white/5 px-4 py-12 text-center">
                  <Star class="mx-auto mb-3 h-8 w-8 text-white/30" />
                  <p class="text-sm font-semibold text-white/60">No results yet for {{ currentQuarterLabel }}</p>
                  <button
                    v-if="nudge"
                    @click="goToNudgeQuarter"
                    class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition-colors hover:text-white"
                  >
                    Check out {{ nudge.label }} <ChevronRight class="h-4 w-4" />
                  </button>
                </div>

                <!-- Empty state — search returned no matches -->
                <div v-else-if="searchQuery.trim()" class="bg-white/5 px-4 py-8 text-center">
                  <p class="text-sm text-white/50">No results found for "{{ searchQuery }}"</p>
                </div>

                <!-- Nudge when few entries -->
                <div v-if="nudge && thankYouEntries.length > 0 && thankYouEntries.length < 10" class="border-t border-white/10 bg-white/5 px-4 py-3 text-center">
                  <button
                    @click="goToNudgeQuarter"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-white/60 transition-colors hover:text-white"
                  >
                    Results are still coming in — check out <span class="ml-1 text-brand-accent">{{ nudge.label }}</span>
                    <ChevronRight class="h-3 w-3 text-brand-accent" />
                  </button>
                </div>
              </div>

              <!-- Summary counts -->
              <div v-if="summary.total > 0" class="mt-4 flex flex-wrap justify-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-accent/20 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm ring-1 ring-white/10">
                  <Trophy class="h-3 w-3" /> {{ summary.distinctions }} Distinction{{ summary.distinctions !== 1 ? 's' : '' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-success/20 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm ring-1 ring-white/10">
                  <Award class="h-3 w-3" /> {{ summary.merits }} Merit{{ summary.merits !== 1 ? 's' : '' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-teal/20 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm ring-1 ring-white/10">
                  <Star class="h-3 w-3" /> {{ summary.passes }} Pass{{ summary.passes !== 1 ? 'es' : '' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm ring-1 ring-white/10">
                  <Music class="h-3 w-3" /> {{ summary.total }} entries
                </span>
              </div>
            </div>
          </div>
        </div>
        <!-- ═══ END DYNAMIC CONTENT ═══ -->

      </div>
    </section>

    <!-- CERTIFICATE PREVIEW -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="mx-auto max-w-xl space-y-3">
          <div class="flex items-center gap-3 rounded-xl bg-brand-surface p-4 shadow-sm border-4 border-brand-accent">
            <Award class="h-6 w-6 shrink-0 text-brand-success" />
            <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Do well and earn a <span class="text-brand-success">Take a Bow Certificate</span></p>
          </div>
          <div class="flex items-center gap-3 rounded-xl bg-brand-surface p-4 shadow-sm border-4 border-brand-accent">
            <Trophy class="h-6 w-6 shrink-0 text-brand-accent" />
            <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Aim high and earn a <span class="text-brand-accent">Standing Ovation Certificate</span></p>
          </div>
        </div>

        <div :class="animClass('zoom-in', 2)" class="mx-auto mt-8 max-w-md">
          <div class="overflow-hidden rounded-2xl bg-brand-surface p-3 shadow-2xl border-4 border-brand-accent">
            <img
              :src="certStudent"
              alt="Standing Ovation Certificate — Distinction"
              class="block w-full rounded-2xl"
            />
          </div>
          <p class="mt-3 text-center text-xs text-brand-text-soft italic">
            Example certificate — your name, instrument, grade and result will appear on yours
          </p>
        </div>
      </div>
    </section>

    <!-- DIVIDER — atmospheric piano keys -->
    <section class="relative bg-brand-bg">
      <div class="mx-auto max-w-5xl px-4 py-2 sm:px-6">
        <div class="relative overflow-hidden rounded-2xl">
          <img
            src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2226.jpeg"
            alt=""
            class="h-24 w-full object-cover opacity-30 sm:h-32"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-brand-bg via-transparent to-brand-bg"></div>
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Ready to get your name on the board?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book your Trinity exam through centre 120 and your result will be celebrated here.
          </p>
          <div class="mt-6">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />

    <!-- Back to top button -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <button
        v-if="showBackToTop"
        @click="scrollToTop"
        class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-brand-accent text-white shadow-lg transition-transform duration-200 hover:scale-110 hover:bg-brand-accent-dark"
        aria-label="Back to top"
      >
        <ArrowUp class="h-5 w-5" />
      </button>
    </Transition>
  </div>
</template>
