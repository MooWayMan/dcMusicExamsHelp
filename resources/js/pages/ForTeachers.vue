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
import { Award, BookOpen, Gift, GraduationCap, CheckCircle } from 'lucide-vue-next'

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
    title: 'Go to the Trinity booking page',
    detail: 'Click the Book Your Exam button on this site — it takes you directly to the official Trinity College London booking page with centre code 120 already applied.',
  },
  {
    step: 2,
    title: 'Choose digital or face-to-face',
    detail: 'Digital exams let students record anywhere — at home, school or studio — and submit online. Anyone can submit the recording, not just the teacher. Face-to-face sessions run in Liverpool and Wirral throughout the year.',
  },
  {
    step: 3,
    title: 'Enter your candidate details',
    detail: 'Fill in the student\'s name, instrument, grade and contact details. For candidates under 18, a parent or guardian email is required.',
  },
  {
    step: 4,
    title: 'Pay and confirm',
    detail: 'Exam fees are the same as booking directly — no extra cost. Once confirmed, your student is automatically entered through centre 120 and qualifies for all our incentives.',
  },
]

const benefits = [
  {
    icon: Award,
    title: 'Recognition and digital badges',
    detail: 'Teachers who are linked to 10 or more exam entries earn a Certificate of Appreciation and a tiered digital badge to display on your website and social media. The more entries, the higher the tier. Every entry counts, regardless of result. It doesn\'t matter who books the exam — as long as we know the student is yours, it counts towards your total.',
    link: '/contact',
    linkText: 'Find out more',
  },
  {
    icon: Gift,
    title: 'Quarterly prize draws',
    detail: 'Every exam entry through centre 120 earns an automatic entry into our quarterly teacher prize draw. The more students you enter, the more chances to win. If a parent books the exam themselves, just ask them to <a href="/contact" class="font-semibold text-brand-accent underline hover:text-white">let us know</a> which teacher the student is linked to — that way you still get the credit.',
    link: '/contact',
    linkText: 'Find out more',
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
    detail: 'Every student entered through centre 120 gets listed on our Thank You page. Merit and Distinction students make the <strong>Hall of Fame</strong> — with a Take a Bow or Standing Ovation Certificate. The highest scorers each quarter get the top spot and a gift token. Parents love it.',
    link: '/thank-you',
    linkText: 'Find out more',
  },
]

const faqs = [
  {
    question: 'Do my students pay more through centre 120?',
    answer: 'No. Exam fees are exactly the same as booking directly with Trinity. Centre 120 simply connects your entries to additional benefits and recognition.',
  },
  {
    question: 'Can I still use a different centre code?',
    answer: 'If you already have a different centre code, you can keep using it. But your students will miss out on the Thank You page, the <strong>Hall of Fame</strong>, Take a Bow and Standing Ovation Certificates, prize draws and other incentives that come with centre 120.',
  },
  {
    question: 'How do the Faber book discounts work?',
    answer: 'Faber Music offers an exclusive trade discount on physical books to registered music teachers. We walk you through the simple setup process. Once registered, the discount applies to all Faber and Trinity College London Press publications — not just exam books. Visit our Exam Book Discounts page for the full breakdown.',
  },
  {
    question: 'How does the teacher badge system work?',
    answer: 'When your students pass exams through centre 120, your total builds up. At 10 successful candidates you earn a bronze badge and Certificate of Appreciation. At 20 you earn silver, and at 30 you earn gold. Badges are digital — display them on your website, email signature or social media.',
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
              Book through centre code 120 and your students get celebrated while you earn recognition, discounts and prize draw entries.
            </p>
          </div>
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
              <p class="mt-1 text-base leading-snug text-white/80 sm:text-base md:text-lg">{{ item.detail }}</p>
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

        <div :class="animClass('fade-up', 2)" class="mt-10">
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
