<!-- resources/js/pages/ExamGuideGrades.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Award, CheckCircle, GraduationCap, ArrowRight, Music } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Grades Explained — musicExams.help',
  description:
    'What each Trinity music exam grade means, from Initial to Grade 8 and beyond. How results are scored, what Pass, Merit and Distinction mean, and where diplomas fit in.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Grades Explained', href: '/exam-guide/grades-explained', current: true },
]

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

/* ── Grade levels ── */
const gradeLevels = [
  {
    grade: 'Initial',
    description: 'The very first step. Perfect for beginners who\'ve been learning for a few months. A confidence-building introduction to exams with simple, short pieces.',
    typical: 'A few months of lessons',
  },
  {
    grade: 'Grade 1',
    description: 'The traditional starting point. You\'ll play simple pieces and demonstrate basic technique. Scales are straightforward and supporting tests are gentle.',
    typical: 'Around 6–12 months of lessons',
  },
  {
    grade: 'Grade 2',
    description: 'Building on the basics. Pieces are a little longer, technique becomes more varied, and you start developing musical expression beyond just playing the right notes.',
    typical: 'Around 1–2 years of lessons',
  },
  {
    grade: 'Grade 3',
    description: 'The first real step up. Pieces have more detail, rhythms get more complex, and you\'re expected to show more independence and musical understanding.',
    typical: 'Around 2–3 years of lessons',
  },
  {
    grade: 'Grade 4',
    description: 'A solid intermediate level. You\'re now playing music that requires real control, expression and technical ability. Many students find this grade a satisfying milestone.',
    typical: 'Around 3–4 years of lessons',
  },
  {
    grade: 'Grade 5',
    description: 'Upper intermediate. The jump from Grade 4 to 5 is often the biggest step. Pieces are more demanding and you\'re expected to show genuine musical maturity.',
    typical: 'Around 4–5 years of lessons',
  },
  {
    grade: 'Grade 6',
    description: 'The first grade to earn UCAS points. Technically demanding with a need for consistent tone, control and expression. This is where serious musicianship begins to show.',
    typical: 'Around 5–7 years of lessons',
  },
  {
    grade: 'Grade 7',
    description: 'Advanced level. Repertoire is challenging and requires strong technique, deep musical understanding and confident stage presence. A real achievement.',
    typical: 'Around 7–9 years of lessons',
  },
  {
    grade: 'Grade 8',
    description: 'The highest graded exam. Concert-level repertoire requiring professional-standard technique, interpretation and artistry. A Grade 8 Distinction is worth 30 UCAS points.',
    typical: 'Around 8–10+ years of lessons',
  },
]

/* ── Result bands ── */
const resultBands = [
  { result: 'Distinction', marks: '87–100', description: 'Outstanding. An exceptional performance demonstrating advanced musicianship and artistry.' },
  { result: 'Merit', marks: '75–86', description: 'Very good. A strong performance showing confident technique and musical understanding.' },
  { result: 'Pass', marks: '60–74', description: 'Good. A solid performance meeting the standard required at that grade level.' },
  { result: 'Below Pass', marks: '0–59', description: 'Not yet at the required standard. Detailed feedback helps identify what to work on.' },
]

const resultColumns = [
  { key: 'result', title: 'Result', sortable: false },
  { key: 'marks', title: 'Marks', sortable: false, align: 'center' as const },
  { key: 'description', title: 'What it means', sortable: false },
]

/* ── Diplomas ── */
const diplomas = [
  {
    title: 'ATCL — Associate',
    level: 'Equivalent to the first year of a music degree',
    description: 'The first diploma level, for performers who\'ve passed Grade 8 and want to continue developing. You perform a 25–35 minute recital programme.',
  },
  {
    title: 'LTCL — Licentiate',
    level: 'Equivalent to a full undergraduate music degree',
    description: 'A demanding diploma requiring a 35–45 minute recital with a high level of technical mastery and interpretive depth.',
  },
  {
    title: 'FTCL — Fellowship',
    level: 'Equivalent to a postgraduate music degree',
    description: 'The highest performance diploma Trinity offers. A 50–60 minute recital at professional concert standard. Extremely prestigious.',
  },
]

/* ── FAQ ── */
const faqs = [
  {
    question: 'Do I have to start at Grade 1?',
    answer:
      'No. You can enter at any grade. If your teacher thinks you\'re ready for Grade 3, you can go straight to Grade 3. There\'s no requirement to pass earlier grades first. The only exception is diplomas — you need Grade 8 before entering for ATCL.',
  },
  {
    question: 'Can I skip grades?',
    answer:
      'Yes. It\'s very common to skip grades, especially in the early stages. Many students go from Grade 1 to Grade 3, or Grade 5 to Grade 7. Your teacher will guide you on when you\'re ready for the next step.',
  },
  {
    question: 'What happens if I don\'t pass?',
    answer:
      'You\'ll receive detailed feedback from the examiner explaining what went well and what to work on. You can retake the exam at the next available session. There\'s no limit on retakes and no penalty — it\'s just another chance to show what you can do.',
  },
  {
    question: 'Are Rock & Pop grades the same level as Classical & Jazz?',
    answer:
      'Yes. Both are Ofqual-regulated at the same level. A Grade 5 in Rock & Pop Guitar is the same qualification level as a Grade 5 in Classical Guitar. The skills assessed are different, but the standard is equivalent.',
  },
  {
    question: 'How long should I prepare between grades?',
    answer:
      'It varies hugely depending on the student, the instrument, and how much they practise. As a rough guide: Grades 1–3 might be one grade per year, Grades 4–5 might take 1–2 years each, and Grades 6–8 often take 2+ years each. But there\'s no rush — go at your own pace.',
  },
  {
    question: 'Do I need Grade 5 Theory to take higher grades?',
    answer:
      'No — Trinity does not require a theory pass before taking any grade. You can go straight to Grade 8 on your instrument without any theory exams. (This is different from some other exam boards.)',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-brand-bg text-brand-text">
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
            <template #myTitle>Grades Explained</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Trinity graded music exams run from Initial (complete beginner) through to Grade 8 (advanced),
            with diplomas beyond for those who want to keep going. Here's what each level means.
          </p>
        </div>
      </div>
    </section>

    <!-- GRADE LEVELS -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>The grade journey</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-6">
          <div
            v-for="level in gradeLevels"
            :key="level.grade"
            class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-base font-bold text-brand-primary sm:text-base md:text-lg">{{ level.grade }}</p>
                <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">{{ level.description }}</p>
              </div>
            </div>
            <p class="mt-2 text-sm text-brand-text-soft italic sm:text-sm md:text-base">
              Typical: {{ level.typical }}
            </p>
          </div>
        </div>

        <!-- UCAS callout -->
        <div :class="animClass('fade-up', 3)" class="mt-6 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <GraduationCap class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Grades 6, 7 and 8 earn UCAS points</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                These count towards university applications, just like A-levels and BTECs.
                A Grade 8 Distinction is worth 30 UCAS points.
              </p>
              <a href="/exam-guide/ucas-points" class="mt-2 inline-flex items-center gap-1 text-base font-semibold text-brand-accent hover:underline sm:text-base md:text-lg">
                See full UCAS points table
                <ArrowRight class="h-4 w-4" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- REPERTOIRE LIST -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>A growing repertoire list</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-6 space-y-4">
          <p class="text-base text-brand-text sm:text-base md:text-lg">
            One of the biggest advantages of Trinity's Classical &amp; Jazz exams is that the repertoire list has no end date. Pieces stay valid indefinitely, and Trinity adds new ones regularly — so the list keeps growing.
          </p>
          <p class="text-base text-brand-text sm:text-base md:text-lg">
            For example, a Grade 3 Piano exam currently has around 56 pieces to choose from. Compare that to some other boards where you might only have 10 or 12 options, and those change every two to three years — meaning teachers have to buy new books and learn new pieces all over again.
          </p>
          <p class="text-base text-brand-text sm:text-base md:text-lg">
            With Trinity, you can keep playing a piece you love for as long as it's on the list. No rushing to take an exam before the syllabus changes. No wasted books. Just more choice, with new pieces added over time.
          </p>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <Music class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Own composition welcome</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                In Classical &amp; Jazz exams, one of your pieces can be your own composition. It's assessed the same way as listed repertoire — marks are based on your performance, not the quality of the composition itself. It must be comparable in technical and musical demand to the pieces listed for your grade, and it should be your own unaided work.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- RESULT BANDS -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>How results work</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg">
            All Trinity graded exams are marked out of 100. Your score determines your result.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyTableConstructor
            :data="resultBands"
            :columns="resultColumns"
            rowKey="result"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>

        <!-- Certificate highlight -->
        <div :class="animClass('fade-up', 3)" class="mt-6 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <Award class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Celebrating your achievement</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                Book through centre 120 and you'll receive extra recognition: a Take a Bow Certificate for Merit or a Standing Ovation Certificate for Distinction, plus your name on our Hall of Fame.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DIPLOMAS -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Beyond Grade 8 — Diplomas</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg">
            For those who want to keep going after Grade 8, Trinity offers three levels of performance diploma.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-6">
          <div
            v-for="diploma in diplomas"
            :key="diploma.title"
            class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm"
          >
            <p class="text-base font-bold text-brand-primary sm:text-base md:text-lg">{{ diploma.title }}</p>
            <p class="mt-1 text-sm font-medium text-brand-accent sm:text-sm md:text-base">{{ diploma.level }}</p>
            <p class="mt-2 text-base text-brand-text-soft sm:text-base md:text-lg">{{ diploma.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- NO THEORY REQUIREMENT -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>No theory requirement</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-6 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <Music class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Go straight to any grade</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                Unlike some other exam boards, Trinity does not require you to pass a music theory exam before taking higher grades on your instrument. You can go straight to Grade 8 without any theory exams at all. Music Theory is available as a separate qualification if you want it, and it earns UCAS points at Grades 6–8 too.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Grade questions</template>
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
            <template #myTitle>Start your grade journey</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book through centre 120 and your achievement gets celebrated here too — Hall of Fame, certificates and recognition.
          </p>
          <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
            <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
              <MyButtonConstructor variant="primary" size="large">
                Book Your Exam
              </MyButtonConstructor>
            </a>
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
  </div>
</template>
