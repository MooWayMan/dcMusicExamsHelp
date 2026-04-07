<!-- resources/js/pages/ForTeachers.vue -->
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
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Award, ArrowRight, BookOpen, Gift, GraduationCap, CheckCircle, ChevronRight, Trophy } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'For Teachers — musicExams.help',
  description:
    'How musicExams.help supports music teachers with a clear booking route, recognition, Faber book discounts and more through Trinity centre code 120.',
}

const breadcrumbPages = [
  { name: 'For Teachers', href: '/for-teachers', current: true },
]

const bookingSteps = [
  {
    step: 1,
    title: 'Click Book Your Exam',
    detail: 'Hit the Book Your Exam button anywhere on this site. You will see a short menu asking which type of exam you want to book — digital, Classical & Jazz face-to-face, or Rock & Pop face-to-face. Each one goes to a different Trinity booking system.',
  },
  {
    step: 2,
    title: 'Choose your pathway',
    detail: '<span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Digital exams</span> use booking.trinitycollege.com — our link pre-fills centre code 120, but if you refresh the page or use the back button, the code can disappear. Look for a box that says "referral code" and make sure 120 is in it before you submit.<br /><br /><span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Classical &amp; Jazz face-to-face</span> use musicbooking.trinitycollege.co.uk<br /><br /><span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Rock &amp; Pop face-to-face</span> use my-trinity.trinitycollege.com',
  },
  {
    step: 3,
    title: 'Enter your candidate details',
    detail: 'Each booking system looks a little different, but you will need the student\'s name, instrument, grade and contact details. For candidates under 18, a parent or guardian email is required. A detailed step-by-step guide for each booking system is coming soon.',
  },
  {
    step: 4,
    title: 'Pay and confirm',
    detail: 'You book and pay directly with Trinity — we do not handle bookings or payments. There is no extra cost for using centre code 120. By using code 120, your student is linked to us and qualifies for all our incentives.',
  },
]

const benefits = [
  {
    icon: Award,
    title: 'Appreciation awards and digital badges',
    detail: 'Every entry through centre 120 counts towards your award — regardless of result. Totals refresh every quarter. Earn Bronze (10+), Silver (20+), Gold (30+) or Top Award (40+) status and display a digital badge on your website and social media. It doesn\'t matter who books the exam — as long as we know the student is yours, it counts towards your total.',
    link: '/for-teachers/awards',
    linkText: 'See the award tiers',
  },
  {
    icon: Gift,
    title: 'Quarterly prize draws',
    detail: 'Every exam entry through centre 120 — face-to-face or digital — earns an automatic entry into our quarterly teacher prize draw. The more students you enter, the more chances to win. If a parent books the exam themselves, just ask them to <a href="/contact" class="font-semibold text-brand-accent underline hover:text-white">let us know</a> which teacher the student is linked to — that way you still get the credit.',
    link: '/incentives?from=for-teachers',
    linkText: 'See all incentives',
  },
  {
    icon: BookOpen,
    title: 'Exam book discounts',
    detail: 'We show you how to access exclusive discounts on physical exam books published by Faber Music and Trinity College London Press. Plus access to discounted digital editions.',
    link: '/for-teachers/faber-discounts',
    linkText: 'Find out more',
  },
  {
    icon: GraduationCap,
    title: 'Your students get celebrated',
    detail: 'Every student entered through centre 120 — face-to-face or digital — gets listed on our Recognition page. Merit and Distinction students make the <strong>Hall of Fame</strong> — with a Take a Bow or Standing Ovation Certificate. The highest scorers each quarter get the top spot and a gift token (£20, or divided equally if there is a tie — minimum £5 each). Parents love it.',
    link: '/recognition?from=for-teachers',
    linkText: 'Find out more',
  },
]

/* ── Standalone full-width card ── */
const everythingCard = {
  icon: BookOpen,
  title: 'Making your life easier',
  detail: 'All the exam guidance, booking information, fees, dates and procedures your students and parents need — in one place on musicExams.help. Save yourself time answering the same questions over and over. Just point parents and students to the site.',
}

const faqs = [
  {
    question: 'Do my students pay more through centre 120?',
    answer: 'No. Exam fees are exactly the same as booking directly with Trinity. Centre 120 simply connects your entries to additional benefits and recognition.',
  },
  {
    question: 'Can I still use a different centre code?',
    answer: 'If you already have a different centre code, you can keep using it. But your students will miss out on the Recognition page, the <strong>Hall of Fame</strong>, Take a Bow and Standing Ovation Certificates, prize draws and other incentives that come with centre 120.',
  },
  {
    question: 'How do the Faber book discounts work?',
    answer: 'Faber Music offers an exclusive trade discount on physical books to registered music teachers. We walk you through the simple setup process. Once registered, the discount applies to all Faber and Trinity College London Press publications — not just exam books. Visit our Exam Book Discounts page for the full breakdown.',
  },
  {
    question: 'How does the teacher badge system work?',
    answer: 'Every entry through centre 120 counts — regardless of result. Totals refresh every quarter. At 10 entries you earn the <strong>Bronze Award</strong>, at 20 the <strong>Silver Award</strong>, at 30 the <strong>Gold Award</strong>, and at 40+ the <strong>Top Award</strong>. Badges are digital — display them on your website, email signature or social media and call yourself an award-winning music teacher.',
  },
  {
    question: 'Do you offer face-to-face and digital exams?',
    answer: 'Yes, both. Face-to-face sessions run at venues in Liverpool and Wirral throughout the year. Digital exams can be recorded anywhere — at home, school or studio — and submitted online through Trinity. Anyone can submit the recording, not just the teacher.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

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
              <template #myTitle>For Teachers</template>
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
              <template #myTitle>A clear route to booking exams — and getting recognised for it</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              musicExams.help takes the admin confusion out of Trinity exam entries.
              Book through centre code 120 and <span class="inline-block rounded-full bg-brand-accent/15 px-3 py-0.5 font-bold text-brand-accent">your students get celebrated</span> while you earn recognition, discounts and prize draw entries.
            </p>
          </div>
        </div>

        <!-- Quick links — same style as Exam Guide banner on homepage -->
        <div :class="animClass('fade-up', 3)" class="mx-auto mt-8 max-w-4xl space-y-3">
          <a
            href="/for-teachers/awards"
            class="flex items-center justify-between rounded-xl border border-brand-accent/30 bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-3 text-white shadow-lg transition hover:scale-[1.01] hover:shadow-xl sm:px-6 sm:py-4"
          >
            <div class="flex items-center gap-3">
              <Trophy class="h-5 w-5 shrink-0 text-white/80 sm:h-6 sm:w-6" />
              <div>
                <span class="text-sm font-semibold sm:text-base lg:text-lg">Teacher & School Awards</span>
                <span class="ml-2 text-xs text-white/70 sm:text-sm">Bronze, Silver, Gold and Top Award tiers</span>
              </div>
            </div>
            <ChevronRight class="h-5 w-5 shrink-0 text-white/60" />
          </a>

          <a
            href="/for-teachers/faber-discounts"
            class="flex items-center justify-between rounded-xl border border-brand-accent/30 bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-3 text-white shadow-lg transition hover:scale-[1.01] hover:shadow-xl sm:px-6 sm:py-4"
          >
            <div class="flex items-center gap-3">
              <BookOpen class="h-5 w-5 shrink-0 text-white/80 sm:h-6 sm:w-6" />
              <div>
                <span class="text-sm font-semibold sm:text-base lg:text-lg">Exam Book Discounts</span>
                <span class="ml-2 text-xs text-white/70 sm:text-sm">Faber Music and Trinity Press discounts for teachers</span>
              </div>
            </div>
            <ChevronRight class="h-5 w-5 shrink-0 text-white/60" />
          </a>
        </div>
      </div>
    </section>

    <!-- HOW TO BOOK -->
    <section
      class="relative border-t border-brand-border"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>How booking works</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Four steps. No extra cost. Your students are entered through centre 120 and qualify for everything.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="item in bookingSteps"
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

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- WHAT YOU GET -->
    <section
      class="relative border-t border-brand-border"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/20" />

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
            <template #myTitle>What you get as a teacher</template>
          </MyTextConstructor>
        </div>

        <!-- Full-width "Everything in one place" card — inverted colours -->
        <div :class="animClass('fade-up', 2)" class="mt-10">
          <div class="overflow-hidden rounded-2xl border-4 border-brand-accent shadow-2xl transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_25px_60px_-12px_rgba(0,0,0,0.5)]">
            <!-- Header — blue gradient bar -->
            <div class="flex w-full items-center gap-3 bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-3 sm:px-6">
              <component :is="everythingCard.icon" class="h-5 w-5 shrink-0 text-white sm:h-6 sm:w-6" />
              <p class="text-lg font-semibold text-white sm:text-xl">{{ everythingCard.title }}</p>
            </div>
            <!-- Body — black -->
            <div class="bg-black p-5 sm:p-6">
              <p class="text-lg leading-relaxed text-white/90 sm:text-lg md:text-xl" v-html="everythingCard.detail"></p>
            </div>
            <!-- Footer — blue gradient bar (no CTA, just a visual close) -->
            <div class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-2"></div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6">
          <MyGlassCardConstructor :cards="benefits" :columns="2" />
        </div>
      </div>
    </section>

    <!-- DIGITAL vs FACE TO FACE -->
    <section
      class="relative border-t border-brand-border"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_6.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>Digital or face-to-face?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Trinity offers both options for Classical &amp; Jazz and Rock &amp; Pop graded exams and diplomas. Both carry the same Ofqual-regulated certificate and UCAS points — the difference is how the exam is taken and what it includes.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Digital -->
          <div class="rounded-2xl border-4 border-brand-accent bg-white/10 p-6 shadow-2xl backdrop-blur-sm">
            <p class="text-xl font-bold text-white sm:text-xl">Digital exams</p>
            <ul class="mt-4 space-y-3">
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Record anywhere on your own schedule — 28 days to submit after booking</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Submit your video through Trinity's MyTrinity portal</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Use a recorded accompaniment instead of a live pianist — no scrambling to find an accompanist for exam day</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">No sight reading, aural or improvisation tests — digital replaces these with Overall Performance Criteria</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Repertoire-only pathway available — four pieces, no technical work at all</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Same certificate and UCAS points as face-to-face</span>
              </li>
            </ul>
          </div>

          <!-- F2F -->
          <div class="rounded-2xl border-4 border-brand-accent bg-white/10 p-6 shadow-2xl backdrop-blur-sm">
            <p class="text-xl font-bold text-white sm:text-xl">Face-to-face exams</p>
            <ul class="mt-4 space-y-3">
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Exam sessions in Liverpool and Wirral at scheduled dates throughout the year</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Live with a Trinity examiner in the room — traditional exam experience</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Three pieces plus technical work and two supporting tests (sight reading, aural, improvisation or musical knowledge)</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg">Live accompanist required for accompanied pieces from Grade 4 onwards — backing tracks are only permitted up to Grade 3</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Accompaniment callout -->
        <div :class="animClass('fade-up', 3)" class="mt-8 rounded-2xl border-4 border-brand-accent bg-white/10 p-5 backdrop-blur-sm sm:p-6">
          <p class="text-lg font-semibold text-white sm:text-lg">Why teachers love the digital option</p>
          <p class="mt-2 text-base leading-snug text-white/80 sm:text-base md:text-lg">
            Many instruments require a piano accompaniment for at least two of the three exam pieces. Finding a reliable pianist who can learn the parts, attend on the specific exam day, and perform under pressure is one of the biggest headaches in exam preparation. With digital exams, students can use a high-quality recorded accompaniment instead — removing the stress for both teacher and student.
          </p>
          <p class="mt-3 text-base leading-snug text-white/80 sm:text-base md:text-lg">
            <span class="font-semibold text-white">In face-to-face exams</span> across brass, woodwind and singing, backing tracks are only permitted up to Grade 3. From Grade 4 onwards, a live accompanist must be in the room. <span class="font-semibold text-white">Digital exams</span> remove this restriction entirely, letting candidates at any grade use a recorded accompaniment. For teachers who play their students' accompaniments themselves, digital also takes away the pressure of performing live in front of the examiner.
          </p>
        </div>

        <!-- Face-to-face callout -->
        <div :class="animClass('fade-up', 4)" class="mt-6 rounded-2xl border-4 border-brand-accent bg-white/10 p-5 backdrop-blur-sm sm:p-6">
          <p class="text-lg font-semibold text-white sm:text-lg">Why face-to-face still matters</p>
          <p class="mt-2 text-base leading-snug text-white/80 sm:text-base md:text-lg">
            Face-to-face exams develop skills that digital simply cannot replicate. Students learn to perform in front of someone they have never met, in an unfamiliar room, under real pressure — and that experience is invaluable preparation for concerts, auditions and live performance. The supporting tests (sight reading, aural, improvisation) also build well-rounded musicianship that a repertoire-only digital exam does not assess.
          </p>
          <p class="mt-3 text-base leading-snug text-white/80 sm:text-base md:text-lg">
            There is also a real benefit to having a fixed exam date. With digital exams, students can spend months re-recording, chasing perfection — and the process can drift indefinitely. A face-to-face date creates focus: the student knows they have to be ready, and the teacher can plan a preparation schedule around it. Many teachers find their students actually perform better with that deadline in place.
          </p>
          <p class="mt-3 text-base leading-snug text-white/80 sm:text-base md:text-lg">
            <span class="font-semibold text-white">A tip from experience:</span> with digital exams, do not pay until your student has actually recorded their performance. Once you pay, the 28-day submission window starts. If the recording is not ready, you are under pressure from day one. Get the recording done first, then book and submit.
          </p>
        </div>
      </div>
    </section>

    <!-- TEACHER FAQ -->
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
            <template #myTitle>Common questions from teachers</template>
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
            <template #myTitle>Ready to get your students recognised?</template>
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
