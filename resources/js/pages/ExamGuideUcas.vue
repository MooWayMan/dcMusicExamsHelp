<!-- resources/js/pages/ExamGuideUcas.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { GraduationCap, AlertCircle, CheckCircle, ArrowRight } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'UCAS Points for Music Exams — musicExams.help',
  description:
    'How many UCAS points do Trinity music exams earn? Full table for Grades 6–8 with deadlines and everything you need to know.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'UCAS Points', href: '/exam-guide/ucas-points', current: true },
]

const ucasData = [
  { grade: 'Grade 6', pass: 8, merit: 10, distinction: 12 },
  { grade: 'Grade 7', pass: 12, merit: 14, distinction: 16 },
  { grade: 'Grade 8', pass: 18, merit: 24, distinction: 30 },
]

const ucasColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'pass', title: 'Pass', sortable: false, align: 'center' as const },
  { key: 'merit', title: 'Merit', sortable: false, align: 'center' as const },
  { key: 'distinction', title: 'Distinction', sortable: false, align: 'center' as const },
]

const keyFacts = [
  'UCAS points apply to both Classical & Jazz and Rock & Pop exams equally',
  'Music Theory Grades 6–8 also earn UCAS points on the same scale',
  'Trinity automatically sends your results to UCAS — you don\'t need to request it',
  'Your exam must take place by the second Friday in June for that year\'s UCAS cycle',
  'For digital exams, you must upload your recording at least three weeks before that deadline (mid to late May)',
  'UCAS points from music exams count alongside A-levels, BTECs and other qualifications',
]

const faqs = [
  {
    question: 'Do I need to tell UCAS about my music exam?',
    answer:
      'No. Trinity sends eligible results to UCAS automatically. You just need to make sure your exam happens before the June deadline.',
  },
  {
    question: 'Can I count UCAS points from more than one music exam?',
    answer:
      'Yes, but only from different subjects. For example, you could count Grade 8 Piano and Grade 7 Music Theory — both count because they\'re different subjects. You cannot count Grade 7 Piano and Grade 8 Piano — only the result with the most UCAS points counts. In practice, a higher grade always earns equal or more points than the grade below it, even at a lower result band.',
  },
  {
    question: 'Do Rock & Pop exams earn the same UCAS points as Classical & Jazz?',
    answer:
      'Yes, exactly the same. A Grade 8 Distinction in Rock & Pop Guitar earns 30 UCAS points, the same as a Grade 8 Distinction in Classical Guitar.',
  },
  {
    question: 'What if I miss the June deadline?',
    answer:
      'Your result will still count for the following year\'s UCAS cycle. The qualification doesn\'t expire — it\'s just about when the result reaches UCAS.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-24 pb-10 md:pt-28 lg:pt-28">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>Exam Guide</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>UCAS Points</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Trinity music exams at Grades 6, 7 and 8 earn UCAS points that count towards university applications —
            just like A-levels and BTECs.
          </p>
        </div>
      </div>
    </section>

    <!-- UCAS TABLE -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Points by grade and result</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyTableConstructor
            :data="ucasData"
            :columns="ucasColumns"
            rowKey="grade"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>

        <!-- Highlight box -->
        <div :class="animClass('fade-up', 3)" class="mt-6 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <GraduationCap class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Grade 8 Distinction = 30 UCAS points</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              That's the same as an A-level grade between a D and a C. Combined with other qualifications, music exam points can make a real difference to your application.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- KEY FACTS -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Key things to know</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <CheckCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Key facts</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="fact in keyFacts" :key="fact" class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ fact }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Deadline warning -->
        <div :class="animClass('fade-up', 3)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <AlertCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Don't miss the deadline</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              Your exam must happen by the second Friday in June. For digital exams, upload your recording at least three weeks before that — around mid to late May. Plan ahead!
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>UCAS questions</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs.map((f, i) => ({ id: i + 1, question: f.question, answer: f.answer }))"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Make your music count</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book through centre 120 and your achievement gets celebrated here too — <strong>Hall of Fame</strong>, certificates and recognition.
          </p>
          <a href="/thank-you" class="mt-4 inline-block text-base font-semibold text-brand-accent underline hover:text-brand-primary sm:text-base md:text-lg">
            See the Hall of Fame →
          </a>
          <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
            <a href="/exam-guide">
              <MyButtonConstructor variant="outline" size="large">
                Back to Exam Guide
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
