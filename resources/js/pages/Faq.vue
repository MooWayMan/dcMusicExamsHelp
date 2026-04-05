<!-- resources/js/pages/Faq.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const breadcrumbPages = [
  { name: 'FAQ', href: '/faq', current: true },
]

const pageMeta = {
  title: 'FAQ — musicExams.help',
  description:
    'Frequently asked questions about booking Trinity music exams through Centre 120. Guidance for teachers, parents and students.',
}

const faqs = [
  {
    question: 'Do I book through this website?',
    answer:
      'No — we guide you through the process and then link you directly to the official Trinity booking page. When you get there, centre code 120 will already be applied.',
  },
  {
    question: 'Who is this for?',
    answer:
      'Anyone involved in music exams. Teachers looking for a smoother booking process and recognition. Parents wanting clear guidance on how exams work. Students preparing for their next grade.',
  },
  {
    question: 'What is centre code 120?',
    answer:
      'Centre 120 is our registered Trinity College London exam centre code. When you use it at booking, your entry is connected to musicExams.help — which means your student gets listed on our Thank You page, qualifies for the Hall of Fame, and you get access to teacher recognition badges, prize draws and ongoing support.',
  },
  {
    question: 'Does it cost anything extra?',
    answer:
      'No. The exam fees are the same regardless of which centre code you use. Code 120 simply gives you access to the extra benefits and support offered through musicExams.help.',
  },
  {
    question: 'Can I use this if I already have a teacher?',
    answer:
      'Absolutely. This site supports your existing teacher, not replaces them. Your teacher stays central to everything — we just make the booking and admin side easier for everyone.',
  },
  {
    question: 'What are digital exams?',
    answer:
      'Digital exams let you record your performance anywhere — at home, at school, in a studio — and submit it online through Trinity. No need to travel to an exam venue. Anyone can submit the recording, not just the teacher. Results come back the same way as face-to-face exams.',
  },
  {
    question: 'What instruments can I take exams on?',
    answer:
      'Trinity offers graded exams across a wide range of instruments including piano, guitar, drums, bass, vocals, brass, woodwind, strings and more — in both classical/jazz and rock/pop styles.',
  },
  {
    question: 'How do I prepare for my exam?',
    answer:
      'Work with your teacher on the pieces, technical exercises and supporting tests for your grade. Trinity publishes syllabuses and books for each instrument and grade — we can point you in the right direction.',
  },
  {
    question: 'What results can I achieve?',
    answer:
      'Trinity graded exams are marked out of 100. A Pass is 60–74, Merit is 75–86, and Distinction is 87–100. The highest scorers each quarter through centre 120 make our Hall of Fame.',
  },
  {
    question: 'Do I need to provide sheet music for the examiner?',
    answer:
      'For pieces not published by Trinity, yes — the examiner needs a copy to follow along. Your teacher should provide printed copies or clear photos on a tablet in aeroplane mode.',
  },
  {
    question: 'How do teachers benefit from using centre 120?',
    answer:
      'Teachers who enter candidates through centre 120 earn recognition through our tiered badge system (10+, 20+, 30+ candidates), receive Certificates of Appreciation, and are entered into quarterly prize draws.',
  },
  {
    question: 'What is the Hall of Fame?',
    answer:
      'Each quarter, the students with the highest Distinction and highest Merit scores through centre 120 are celebrated on our Hall of Fame page. Winners receive a certificate and a gift token.',
  },
  {
    question: 'Can I book face-to-face exams through centre 120?',
    answer:
      'Yes. We offer face-to-face exam sessions in Liverpool and Wirral, as well as digital exams that can be recorded anywhere and submitted online. Centre code 120 works for both.',
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
            <template #myTitle>Help &amp; Support</template>
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
            <template #myTitle>Frequently Asked Questions</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)">
          <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Everything you need to know about booking Trinity music exams through centre code 120.
            Can't find your answer? Get in touch and we'll help.
          </p>
        </div>
        </div>
      </div>
    </section>

    <!-- FAQ ACCORDION -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 2)">
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
        <div :class="animClass('fade-up', 3)" class="mt-12 text-center">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-brand-primary"
          >
            <template #myTitle>Ready to book your exam?</template>
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
