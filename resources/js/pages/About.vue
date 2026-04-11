<!-- resources/js/pages/About.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Music, GraduationCap, Users, Heart, Piano, Guitar } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'About — musicExams.help',
  description:
    'musicExams.help is run by Paul Sheridan — a music teacher with 35 years of experience working with Trinity College London. Supporting teachers, students and parents across Liverpool, Wirral and beyond.',
}

const breadcrumbPages = [
  { name: 'About', href: '/about', current: true },
]

const highlights = [
  {
    icon: Music,
    title: '35 years with Trinity',
    detail: 'Paul has worked with Trinity College London for over three decades — entering candidates, supporting teachers and building a reputation as one of the most experienced Trinity representatives in the region.',
  },
  {
    icon: GraduationCap,
    title: 'Classical, jazz and Rock & Pop',
    detail: 'Trained in both jazz and classical music, Paul teaches brass, piano and guitar. He understands the exam syllabus inside out across all three Trinity pathways — Classical & Jazz, Rock & Pop and Theory.',
  },
  {
    icon: Users,
    title: 'Teacher, not just an admin',
    detail: 'Paul is a working music teacher in Liverpool and Wirral, teaching around 40 students a week. musicExams.help is built by someone who sits in the same chair as you — dealing with the same booking systems, the same paperwork, the same questions from parents.',
  },
  {
    icon: Heart,
    title: 'Built for the music community',
    detail: 'This site exists to make Trinity exams easier for everyone involved. Teachers get support and recognition. Parents get clear guidance. Students get celebrated. Nobody pays a penny more for any of it.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-6 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>About musicExams.help</template>
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
              <template #myTitle>Run by a teacher, for teachers</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              musicExams.help is run by Paul Sheridan — a music teacher with over 35 years of experience
              working with Trinity College London. Based in Liverpool and Wirral, supporting teachers,
              students and parents across the region and beyond.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- GRADIENT STRIP -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 sm:py-6">
        <div :class="animClass('fade-up', 3)">
          <p class="text-center text-base leading-snug text-white sm:text-base md:text-lg lg:text-xl">
            <span class="font-semibold">Centre 120</span> — a registered Trinity exam centre
            offering the same exam fees, the same certificates, and the same UCAS points as any other centre.
            Plus recognition, support and incentives for everyone involved.
          </p>
        </div>
      </div>
    </section>

    <!-- HIGHLIGHTS -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_10.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50" />
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyGlassCardConstructor :cards="highlights" :columns="2" :showFooter="false" />
        </div>
      </div>
    </section>

    <!-- THE STORY -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <div class="mx-auto mb-4 h-1 w-16 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent"></div>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Why this site exists</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-5">
          <p class="text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
            After 35 years of entering candidates for Trinity exams, I've seen the same problems come up
            again and again. Teachers don't know which booking system to use. Parents don't understand how
            the exam works. Students don't get recognised for their achievements.
          </p>
          <p class="text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
            musicExams.help is my answer to all of that. It's a single place where teachers can find
            clear guidance, parents can understand the process, and students get celebrated —
            regardless of whether they pass with a Distinction or just get through the door on exam day.
          </p>
          <p class="text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
            I teach brass, piano and guitar across schools in Liverpool and Wirral. I deal with
            the same booking systems, the same exam paperwork, and the same questions that you do.
            This site is built from the teaching side — by someone who understands the day-to-day
            reality of preparing students for exams.
          </p>
          <p class="text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
            Everything on this site is free. The exam fees are set by Trinity and are exactly the same
            whether you use centre 120 or book directly. The only difference is that your students
            get at least a <strong>Bravo Certificate</strong>, access to the <a href="/recognition?from=about" class="font-semibold text-brand-accent underline hover:text-brand-primary">Recognition page</a>, the <a href="/recognition?from=about" class="font-semibold text-brand-accent underline hover:text-brand-primary">Hall of Fame</a>,
            prize draws and other incentives — at no extra cost to anyone.
          </p>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto max-w-4xl px-4 py-10 text-center sm:px-6">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-brand-text-inverse"
          >
            <template #myTitle>Ready to get started?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-2 max-w-xl text-base text-white/80 sm:text-base md:text-lg">
            Book through centre 120 — same fees, plus all the extras. For digital and theory exams you enter the code at booking; for face-to-face it's automatic.
          </p>
        </div>
        <div :class="animClass('fade-up', 2)" class="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
          <a href="/contact" class="text-sm font-medium text-white/80 underline decoration-white/40 hover:text-white hover:decoration-white sm:text-base">
            Or get in touch
          </a>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>

  <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
</template>
