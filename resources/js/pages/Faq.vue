<!-- resources/js/pages/Faq.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useAccordionHashOpen } from '@/composables/useAccordionHashOpen'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const breadcrumbPages = [
  { name: 'FAQ', href: '/faq', current: true },
]

const pageMeta = {
  title: 'Trinity Music Exam FAQ',
  description:
    'Answers to common questions about booking Trinity music exams through centre 120: digital, face-to-face and theory exams, results, certificates and benefits.',
  canonicalUrl: 'https://musicexams.help/faq',
}

const faqs = [
  {
    question: 'Do I book through this website?',
    answer:
      'No — when you click Book Your Exam, you will see a short menu asking which type of exam you want. Each option takes you to the correct Trinity booking system. For digital exams and theory exams, our link pre-fills centre 120 automatically, but if you refresh or go back it can disappear — always check the referral code box says 120 before you book. For face-to-face exams, your entry is automatically connected to centre 120.',
  },
  {
    question: 'Who is this for?',
    answer:
      'Anyone involved in music exams. Teachers looking for a smoother booking process and recognition. Parents wanting clear guidance on how exams work. Students preparing for their next grade.',
  },
  {
    question: 'What is centre 120?',
    answer:
      'Centre 120 is our registered Trinity College London exam centre code — it covers both our digital centre and our face-to-face centres in Liverpool and Wirral. When you use it at booking, your entry is connected to musicExams.help — which means every candidate receives at least a <strong>Bravo Certificate</strong>, a place on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a> (with the Hall of Fame for Merit and Distinction results), and entry into our quarterly student prize draw. Teachers also get their own prize draw, recognition badges and ongoing support.',
  },
  {
    question: 'Does it cost anything extra?',
    answer:
      'No. The exam fees are the same regardless of which centre you use. Centre 120 simply gives you access to the extra benefits and support offered through musicExams.help.',
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
      'Trinity offers graded exams across a wide range of instruments. <strong>Classical &amp; Jazz</strong> covers piano, brass, woodwind, strings, singing, guitar, percussion and more. <strong>Rock &amp; Pop</strong> covers guitar, bass, drums, keyboards and vocals. See our <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabuses page</a> for the full list.',
  },
  {
    question: 'How do I prepare for my exam?',
    answer:
      'Start by checking the <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabus</a> for your instrument and grade — it sets out exactly what you need to prepare, including pieces, technical work and supporting tests. If you have a teacher, they will guide you through what to work on and help you choose your pieces. If you are preparing on your own, the syllabus and Trinity\'s published books and resources have everything you need.',
  },
  {
    question: 'Do I have to do scales and arpeggios?',
    answer:
      'For most Classical &amp; Jazz instruments and grades you can choose between scales &amp; arpeggios OR exercises — they are not both required. Brass at Grades 1–5, for example, lets you pick one or the other. At higher grades the alternative may be orchestral or brass band extracts instead. Always check the <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabus</a> for your specific instrument and grade. If you go with scales they must be played from memory — exercises (or extracts) can be read from sheet music.',
  },
  {
    question: 'What results can I achieve?',
    answer:
      'Trinity graded exams are marked out of 100. A Pass is 60–74, Merit is 75–86, and Distinction is 87–100. Every student entered through centre 120 — whether face-to-face, digital or theory — receives at least a <strong>Bravo Certificate</strong> and is listed on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a>. Merit earns a Take a Bow Certificate and Distinction earns a Standing Ovation Certificate — plus a place in the <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Hall of Fame</a>. The highest scorers each quarter earn a Showstopper or Centre Stage Certificate and a gift token.',
  },
  {
    question: 'Do I need to provide sheet music for the examiner?',
    answer:
      'The examiner has all Trinity-published pieces on their laptop, so they don\'t need a copy of those — but you must still bring the original book to prove it was purchased (copyright rules). For any pieces not published by Trinity, you\'ll need to provide a copy for the examiner to follow — a photocopy or a tablet in aeroplane mode is fine. Your teacher can help make sure everything is in order.',
  },
  {
    question: 'How do teachers benefit from using centre 120?',
    answer:
      'Teachers who enter candidates through centre 120 earn recognition through our tiered badge system (10+, 20+, 30+ candidates), receive Certificates of Appreciation, and get an entry into the quarterly teacher prize draw (£50 gift token) for every exam booked — the more students entered, the more chances to win. They also get the added benefit of having all the guidance and exam information in one place on musicExams.help — saving time explaining procedures to parents and students.',
  },
  {
    question: 'What is the Hall of Fame?',
    answer:
      'Every student entered through centre 120 receives at least a <strong>Bravo Certificate</strong> and is listed on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a> — everyone is recognised, not just the highest scorers. Merit earns a Take a Bow Certificate and Distinction earns a Standing Ovation Certificate — plus a place in the Hall of Fame. Each quarter we award the top scores in two grade groups — Initial–5 and 6–8 — so the highest Distinction in each group earns a Showstopper Certificate, and the highest Merit in each group earns a Centre Stage Certificate, plus a gift token (£20, or divided equally if there is a tie — minimum £5 each). Every entry also goes into a quarterly student prize draw, and teachers have their own separate prize draw too.',
  },
  {
    question: 'Can I book face-to-face exams through centre 120?',
    answer:
      'Yes. We offer face-to-face exam sessions in Liverpool and Wirral, as well as digital practical exams and digital theory exams that can be taken anywhere and submitted online. Centre 120 covers all three — for digital exams you enter the code when booking, and for face-to-face exams your entry is connected automatically.',
  },
  {
    question: 'Can I order a paper certificate, and how much?',
    answer:
      'Every Trinity exam comes with a free digital certificate that you can download and share online. If you would also like a printed paper certificate, you can order one directly from Trinity for £5 (UK delivery) at <a href="https://mycertificates.trinitycollege.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">mycertificates.trinitycollege.com</a> — use the “Paper certificates” option. The same page can also order a replacement if a certificate is ever lost or damaged.',
  },
]

useAccordionHashOpen()

const searchQuery = ref('')
const submitSearch = () => {
  const q = searchQuery.value.trim()
  router.visit('/search' + (q ? '?q=' + encodeURIComponent(q) : ''))
}

const faqJsonLd = JSON.stringify({
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  'mainEntity': faqs.map((f) => ({
    '@type': 'Question',
    'name': f.question,
    'acceptedAnswer': {
      '@type': 'Answer',
      'text': f.answer.replace(/<[^>]*>/g, ''),
    },
  })),
})
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />
  <component is="script" type="application/ld+json" v-text="faqJsonLd" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-10 md:pt-40 lg:pt-40">
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
            titleTag="h1"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>Frequently Asked Questions</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)">
          <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Everything you need to know about booking Trinity music exams through centre 120.
            Can't find your answer? Get in touch and we'll help.
          </p>
        </div>

        <div :class="animClass('fade-up', 3)" class="mx-auto mt-6 max-w-xl">
          <form class="relative" role="search" @submit.prevent="submitSearch">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search"
              aria-label="Search the site"
              class="h-14 w-full rounded-2xl border border-brand-border bg-white pl-12 pr-4 text-lg text-slate-800 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-accent"
            />
          </form>
        </div>
        </div>
      </div>
    </section>

    <!-- FAQ ACCORDION -->
    <section class="bg-black">
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
            textColor="text-white"
          >
            <template #myTitle>Ready to book your exam?</template>
          </MyTextConstructor>
          <div class="mt-4">
            <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
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
