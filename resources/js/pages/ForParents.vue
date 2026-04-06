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
    title: 'Book on the Trinity website using code 120',
    detail: 'Either you or the teacher books the exam directly on the Trinity website. When booking, enter centre code 120 as your referral — this connects your entry to musicExams.help so your child can be recognised on our Thank You page and <strong>Hall of Fame</strong>. The fees are the standard Trinity prices — using code 120 doesn\'t cost anything extra.',
  },
  {
    step: 3,
    title: 'Your child prepares for the exam',
    detail: 'Your child will work on their exam pieces, scales, sight-reading and other elements — usually with their teacher, but self-taught candidates can prepare independently too. The syllabus sets out exactly what\'s needed for each grade.',
  },
  {
    step: 4,
    title: 'Exam day',
    detail: 'For face-to-face exams, your child performs in front of a Trinity examiner at a local venue. For digital exams, they record anywhere — at home, school or studio — and submit the video online. Anyone can submit it, not just the teacher. Both count exactly the same.',
  },
  {
    step: 5,
    title: 'Results and recognition',
    detail: 'Results are graded as Pass (60–74), Merit (75–86) or Distinction (87–100). Every student entered through centre 120 gets listed on our Thank You page. Every Merit and Distinction makes the <strong>Hall of Fame</strong>.',
  },
]

const whatYourChildGets = [
  'Listed on our Thank You page — first name and initial, instrument and grade (full name shown only with your permission)',
  'A place in the <strong>Hall of Fame</strong> for Merit or Distinction — with a Take a Bow or Standing Ovation Certificate',
  'The highest scorers each quarter get the top spot and a gift token',
  'The same official Trinity certificate and result as any other centre — plus our own special certificates for Merit and Distinction',
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
    answer: 'Exam fees vary by grade and are set by Trinity College London. Using centre code 120 when booking doesn\'t cost anything extra — the fees are the standard Trinity prices. You can see the full fee breakdown on our Exam Fees page.',
  },
  {
    question: 'What is the difference between digital and face-to-face?',
    answer: 'Face-to-face exams take place at a local venue with an examiner in the room. Digital exams can be recorded anywhere and submitted online. Both are marked to the same standard and carry the same qualification. Digital exams are also cheaper at every grade. Many families prefer digital for the convenience.',
  },
  {
    question: 'How long until we get the result?',
    answer: 'For digital exams, results are normally provided within 14 days of submission. The exam report is sent as an attachment to the email address provided at booking — no login or portal needed. If you gave your own email when the exam was booked, you\'ll receive it directly. For face-to-face exams through centre 120, results are checked and posted to you within days of the exam — much faster than the usual turnaround. In both cases, a digital certificate is issued automatically for any Pass, Merit or Distinction. If you\'d like a printed paper certificate posted to you, that\'s an extra £5.',
  },
  {
    question: 'Do I need to be present for the exam?',
    answer: 'For face-to-face exams, parents are not usually in the exam room but should be nearby. For digital exams, you may need to help with the recording setup, but the performance itself is your child\'s. Anyone can submit the recording — it doesn\'t have to be the teacher.',
  },
  {
    question: 'What happens after we book?',
    answer: 'For digital exams, you\'ll get a confirmation email straight away. Login details for the upload portal arrive within a week (check spam). You then have four weeks to record and submit. For face-to-face exams, you\'ll receive an email closer to the exam date with the venue, time and an appointment slip that must be printed and handed to the examiner.',
  },
  {
    question: 'What does centre code 120 mean?',
    answer: 'It\'s a registered Trinity exam centre code. When you use code 120, your child\'s entry is connected to musicExams.help — which means they qualify for the Thank You page, <strong>Hall of Fame</strong>, Take a Bow and Standing Ovation Certificates and other incentives at no extra cost.',
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
    <section
      class="relative border-t border-brand-border"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50" />
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What to expect</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="item in journeySteps"
            :key="item.step"
            class="flex gap-4 rounded-2xl border-4 border-brand-accent bg-white/10 p-5 shadow-2xl backdrop-blur-sm sm:gap-6 sm:p-6"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
              {{ item.step }}
            </div>
            <div>
              <p class="text-lg font-semibold text-white sm:text-lg">{{ item.title }}</p>
              <p class="mt-1 text-base leading-snug text-white/80 sm:text-base md:text-lg" v-html="item.detail"></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT YOUR CHILD GETS -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_8.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/60" />
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What your child gets through centre 120</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-xl">
          <div class="rounded-2xl border-4 border-brand-accent bg-white/10 p-5 shadow-2xl backdrop-blur-sm sm:p-6">
            <ul class="space-y-4">
              <li
                v-for="item in whatYourChildGets"
                :key="item"
                class="flex items-start gap-3"
              >
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg lg:text-xl" v-html="item"></span>
              </li>
            </ul>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6 text-center">
          <a href="/thank-you" class="inline-block text-base font-semibold text-brand-accent underline hover:text-white sm:text-base md:text-lg">
            See the Hall of Fame →
          </a>
        </div>

        <div :class="animClass('fade-up', 4)" class="mt-8 text-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
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
