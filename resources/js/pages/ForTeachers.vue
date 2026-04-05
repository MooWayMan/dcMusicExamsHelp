<!-- resources/js/pages/ForTeachers.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Award, BookOpen, Gift, GraduationCap, CheckCircle, ArrowRight } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'For Teachers — musicExams.help',
  description:
    'How musicExams.help supports music teachers with a clear booking route, recognition, Faber book discounts and more through Trinity centre code 120.',
}

const breadcrumbPages = [
  { name: 'For Teachers', href: '/for-teachers', current: true },
]

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

const bookingSteps = [
  {
    step: 1,
    title: 'Go to the Trinity booking page',
    detail: 'Click the Book Your Exam button on this site — it takes you directly to the official Trinity College London booking page with centre code 120 already applied.',
  },
  {
    step: 2,
    title: 'Choose digital or face-to-face',
    detail: 'Digital exams let students record at home and submit online — no travel needed. Face-to-face sessions run in Liverpool and Wirral at scheduled dates throughout the year.',
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
    detail: 'Teachers who enter 10 or more successful candidates earn a Certificate of Appreciation and a tiered digital badge (10+, 20+, 30+) to display on your website and social media.',
    iconBg: 'bg-brand-success-soft',
    iconColor: 'text-brand-success',
  },
  {
    icon: Gift,
    title: 'Quarterly prize draws',
    detail: 'Every exam entry through centre 120 earns an automatic entry into our quarterly teacher prize draw. The more students you enter, the more chances to win.',
    iconBg: 'bg-brand-teal/10',
    iconColor: 'text-brand-teal',
  },
  {
    icon: BookOpen,
    title: 'Exam book discounts',
    detail: 'We show you how to set up a Faber Music trade account for 33% off physical exam books published by Faber and Trinity College London Press. Plus access to discounted digital editions.',
    iconBg: 'bg-brand-primary/10',
    iconColor: 'text-brand-primary',
    link: '/for-teachers/faber-discounts',
  },
  {
    icon: GraduationCap,
    title: 'Your students get celebrated',
    detail: 'Every student entered through centre 120 gets listed on our Thank You page. Top scorers each quarter make the Hall of Fame with a certificate and gift token. Parents love it.',
    iconBg: 'bg-brand-accent/10',
    iconColor: 'text-brand-accent',
  },
]

const faqs = [
  {
    question: 'Do my students pay more through centre 120?',
    answer: 'No. Exam fees are exactly the same as booking directly with Trinity. Centre 120 simply connects your entries to additional benefits and recognition.',
  },
  {
    question: 'Can I still use my own centre code?',
    answer: 'If you already have a centre code, you can keep using it. But your students will miss out on the Hall of Fame, certificates, prize draws and other incentives that come with centre 120.',
  },
  {
    question: 'What if I already book directly for a digital exam discount?',
    answer: 'Teachers can set up their own digital centre and receive a 33% ebook discount from Trinity. That\'s a valid option. The difference is that centre 120 offers your students public recognition, certificates, a Hall of Fame and gift tokens — things you can\'t provide on your own.',
  },
  {
    question: 'How do the Faber book discounts work?',
    answer: 'Faber Music offers a 33% trade discount on physical books to registered music teachers. We walk you through the simple setup process. Once registered, the discount applies to all Faber and Trinity College London Press publications — not just exam books. Visit our Exam Book Discounts page for the full breakdown.',
  },
  {
    question: 'How does the teacher badge system work?',
    answer: 'When your students pass exams through centre 120, your total builds up. At 10 successful candidates you earn a bronze badge and Certificate of Appreciation. At 20 you earn silver, and at 30 you earn gold. Badges are digital — display them on your website, email signature or social media.',
  },
  {
    question: 'Do you offer face-to-face and digital exams?',
    answer: 'Yes, both. Face-to-face sessions run at venues in Liverpool and Wirral throughout the year. Digital exams can be taken from home — the student records their performance and submits it online through Trinity.',
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
            <template #myTitle>How booking works</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Four steps. No extra cost. Your students are entered through centre 120 and qualify for everything.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="item in bookingSteps"
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

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
            <MyButtonConstructor variant="primary" size="large">
              Book Your Exam
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- WHAT YOU GET -->
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
            <template #myTitle>What you get as a teacher</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="benefit in benefits"
            :key="benefit.title"
            class="rounded-2xl border border-brand-border bg-brand-bg p-5 shadow-sm sm:p-6"
          >
            <div class="flex items-start gap-4">
              <div :class="[benefit.iconBg, 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12']">
                <component :is="benefit.icon" :class="[benefit.iconColor, 'h-5 w-5 sm:h-6 sm:w-6']" />
              </div>
              <div>
                <p class="text-lg font-semibold text-brand-text sm:text-lg">{{ benefit.title }}</p>
                <p class="mt-1 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">{{ benefit.detail }}</p>
                <a
                  v-if="benefit.link"
                  :href="benefit.link"
                  class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition hover:opacity-70"
                >
                  Find out more
                  <ArrowRight class="h-4 w-4" />
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL vs FACE TO FACE -->
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
            <template #myTitle>Digital or face-to-face?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Trinity offers both options for Classical &amp; Jazz graded exams and diplomas. Both carry the same Ofqual-regulated certificate and UCAS points — the difference is how the exam is taken and what it includes.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Digital -->
          <div class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
            <p class="text-xl font-bold text-brand-accent sm:text-xl">Digital exams</p>
            <ul class="mt-4 space-y-3">
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Record at home on your own schedule — 28 days to submit after booking</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Submit your video through Trinity's MyTrinity portal</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Use a recorded accompaniment instead of a live pianist — no scrambling to find an accompanist for exam day</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">No sight reading, aural or improvisation tests — digital replaces these with Overall Performance Criteria</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Repertoire-only pathway available — four pieces, no technical work at all</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Same certificate and UCAS points as face-to-face</span>
              </li>
            </ul>
          </div>

          <!-- F2F -->
          <div class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
            <p class="text-xl font-bold text-brand-accent sm:text-xl">Face-to-face exams</p>
            <ul class="mt-4 space-y-3">
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Exam sessions in Liverpool and Wirral at scheduled dates throughout the year</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Live with a Trinity examiner in the room — traditional exam experience</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Three pieces plus technical work and two supporting tests (sight reading, aural, improvisation or musical knowledge)</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">Live accompanist required for accompanied pieces from Grade 4 onwards — backing tracks are only permitted up to Grade 3</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Accompaniment callout -->
        <div :class="animClass('fade-up', 3)" class="mt-8 rounded-2xl border border-brand-accent/30 bg-brand-accent/5 p-5 sm:p-6">
          <p class="text-lg font-semibold text-brand-accent sm:text-lg">Why teachers love the digital option</p>
          <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            Many instruments require a piano accompaniment for at least two of the three exam pieces. Finding a reliable pianist who can learn the parts, attend on the specific exam day, and perform under pressure is one of the biggest headaches in exam preparation. With digital exams, students can use a high-quality recorded accompaniment instead — removing the stress for both teacher and student.
          </p>
          <p class="mt-3 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            <span class="font-semibold text-brand-text">In face-to-face exams</span> across brass, woodwind and singing, backing tracks are only permitted up to Grade 3. From Grade 4 onwards, a live accompanist must be in the room. <span class="font-semibold text-brand-text">Digital exams</span> remove this restriction entirely, letting candidates at any grade use a recorded accompaniment. For teachers who play their students' accompaniments themselves, digital also takes away the pressure of performing live in front of the examiner.
          </p>
        </div>

        <!-- Face-to-face callout -->
        <div :class="animClass('fade-up', 4)" class="mt-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/5 p-5 sm:p-6">
          <p class="text-lg font-semibold text-brand-accent sm:text-lg">Why face-to-face still matters</p>
          <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            Face-to-face exams develop skills that digital simply cannot replicate. Students learn to perform in front of someone they have never met, in an unfamiliar room, under real pressure — and that experience is invaluable preparation for concerts, auditions and live performance. The supporting tests (sight reading, aural, improvisation) also build well-rounded musicianship that a repertoire-only digital exam does not assess.
          </p>
          <p class="mt-3 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            There is also a real benefit to having a fixed exam date. With digital exams, students can spend months re-recording, chasing perfection — and the process can drift indefinitely. A face-to-face date creates focus: the student knows they have to be ready, and the teacher can plan a preparation schedule around it. Many teachers find their students actually perform better with that deadline in place.
          </p>
          <p class="mt-3 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            <span class="font-semibold text-brand-text">A tip from experience:</span> with digital exams, do not pay until your student has actually recorded their performance. Once you pay, the 28-day submission window starts. If the recording is not ready, you are under pressure from day one. Get the recording done first, then book and submit.
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
            <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
              <MyButtonConstructor variant="primary" size="large">
                Book Your Exam
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
