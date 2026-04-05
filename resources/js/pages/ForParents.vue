<!-- resources/js/pages/ForParents.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { CheckCircle } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'For Parents — musicExams.help',
  description:
    'A parent\'s guide to Trinity music exams. What to expect, how booking works, and how your child gets recognised through centre code 120.',
}

const breadcrumbPages = [
  { name: 'For Parents', href: '/for-parents', current: true },
]

const journeySteps = [
  {
    step: 1,
    title: 'Decide when your child is ready',
    detail: 'Usually a teacher will recommend the right grade and instrument when the time is right. If your child doesn\'t have a teacher, that\'s fine — you can still enter them for an exam. Get in touch and we\'ll help you choose the right starting point.',
  },
  {
    step: 2,
    title: 'The exam gets booked through centre 120',
    detail: 'Either you or the teacher books the exam on the Trinity website using centre code 120. The fees are the same as booking anywhere else — no extra cost.',
  },
  {
    step: 3,
    title: 'Your child prepares with their teacher',
    detail: 'Your child will work on their exam pieces, scales, sight-reading and other elements with their teacher. The syllabus sets out exactly what\'s needed for each grade.',
  },
  {
    step: 4,
    title: 'Exam day',
    detail: 'For face-to-face exams, your child performs in front of a Trinity examiner at a local venue. For digital exams, they record anywhere — at home, school or studio — and submit the video online. Anyone can submit it, not just the teacher. Both count exactly the same.',
  },
  {
    step: 5,
    title: 'Results and recognition',
    detail: 'Results are graded as Pass (60–74), Merit (75–86) or Distinction (87–100). Every student entered through centre 120 gets listed on our Thank You page. Top scorers make the Hall of Fame.',
  },
]

const whatYourChildGets = [
  'Listed on our Thank You page — first name and initial, instrument and grade (full name shown only with your permission)',
  'A Take a Bow Certificate for Merit or a Standing Ovation Certificate for Distinction',
  'A chance to make the quarterly Hall of Fame with a gift token',
  'The same official Trinity certificate and result as any other centre',
]

const faqs = [
  {
    question: 'What is a music exam?',
    answer: 'A graded music exam is an assessment of your child\'s playing ability on their instrument. They perform prepared pieces, scales and other tests in front of a qualified examiner. It\'s a milestone that shows progress and gives them something to work towards.',
  },
  {
    question: 'What grades are there?',
    answer: 'Trinity offers grades from Initial (beginner) through to Grade 8 (advanced), plus diplomas beyond that. Most students work through the grades over several years, guided by their teacher.',
  },
  {
    question: 'How much does it cost?',
    answer: 'Exam fees vary by grade and are set by Trinity College London. The cost is the same whether you book through centre 120 or anywhere else. Your child\'s teacher can tell you the exact fee for their grade.',
  },
  {
    question: 'What is the difference between digital and face-to-face?',
    answer: 'Face-to-face exams take place at a local venue with an examiner in the room. Digital exams can be recorded anywhere and submitted online. Both are marked to the same standard and carry the same qualification. Many families prefer digital for the convenience.',
  },
  {
    question: 'Do I need to be present for the exam?',
    answer: 'For face-to-face exams, parents are not usually in the exam room but should be nearby. For digital exams, you may need to help with the recording setup, but the performance itself is your child\'s. Anyone can submit the recording — it doesn\'t have to be the teacher.',
  },
  {
    question: 'What does centre code 120 mean?',
    answer: 'It\'s a registered Trinity exam centre code. When you use code 120, your child\'s entry is connected to musicExams.help — which means they qualify for the Thank You page, Hall of Fame, Take a Bow and Standing Ovation Certificates and other incentives at no extra cost.',
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
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>For Parents</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              class="mt-3 md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>Your child's exam journey — step by step</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Music exams can feel confusing if you've never been through the process.
              This page explains what happens, what it costs, and how your child gets recognised along the way.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- THE JOURNEY -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What to expect</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="item in journeySteps"
            :key="item.step"
            class="flex gap-4 rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm sm:gap-6 sm:p-6"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-accent text-lg font-bold text-white sm:h-12 sm:w-12 sm:text-xl">
              {{ item.step }}
            </div>
            <div>
              <p class="text-lg font-semibold text-brand-text sm:text-lg">{{ item.title }}</p>
              <p class="mt-1 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">{{ item.detail }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT YOUR CHILD GETS -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What your child gets through centre 120</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-xl">
          <ul class="space-y-4">
            <li
              v-for="item in whatYourChildGets"
              :key="item"
              class="flex items-start gap-3"
            >
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <span class="text-base text-brand-text sm:text-base md:text-lg lg:text-xl">{{ item }}</span>
            </li>
          </ul>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- PARENT FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Common questions from parents</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs.map((faq, index) => ({
              id: index + 1,
              question: faq.question,
              answer: faq.answer,
            }))"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>

        <!-- CTA -->
        <div :class="animClass('fade-up', 3)" class="mt-10 text-center">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-white"
          >
            <template #myTitle>Ready to book your child's exam?</template>
          </MyTextConstructor>
          <div class="mt-4">
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
  </div>
</template>
