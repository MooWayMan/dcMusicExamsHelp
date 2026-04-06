<!-- resources/js/pages/Welcome.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useSubscription } from '@/composables/useSubscription'
import { GraduationCap, Users, Trophy, Gift, Award, HeartHandshake, BookOpen } from 'lucide-vue-next'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyRunnerConstructor from '@/components/reusables/MyRunnerConstructor.vue'
import MyRunnerListTextInfo from '@/components/reusables/MyRunnerListTextInfo.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import EmailCapture from '@/components/EmailCapture.vue'

const { isSubscribed } = useSubscription()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'musicExams.help — Your Guide to Trinity Music Exams',
  description:
    'Free guidance for teachers, parents and students booking Trinity music exams through Centre 120. Incentives, recognition and support.',
}

const referralCode = '120'

const heroLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'

const trinityCentreLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity_Centre_120_logo_purple.png'

const profilePic =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/paulsprofilepic.png'

const venuePhotos = {
  steinwayGrand: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_7500.jpeg',
  kawaiRoom: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2223.jpeg',
  kawaiRoomOpen: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2233.jpeg',
  kawaiKeysCloseup: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2226.jpeg',
  kawaiFullKeyboard: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2230.jpeg',
  guitarStudent: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_0434.jpeg',
  pianoStudent: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_7460.jpeg',
  sajeevanPiano: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_7820.jpg',
  keerthanaaPiano: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_7819.jpg',
}

const whyCards = [
  {
    id: 1,
    title: 'For Teachers',
    subTitle: 'Find out more →',
    descript: 'Save time with a clear booking route, get recognised for the work you do, and access resources and discounts to help grow your music teaching business.',
    icon: GraduationCap,
    showIcon: true,
    url: '/for-teachers',
  },
  {
    id: 2,
    title: 'For Parents',
    subTitle: 'Find out more →',
    descript: 'No more confusion about how exams work. Step-by-step guidance from choosing the right exam to understanding results day.',
    icon: Users,
    showIcon: true,
    url: '/for-parents',
  },
  {
    id: 3,
    title: 'For Students',
    subTitle: 'Find out more →',
    descript: 'Your hard work deserves to be celebrated. Incentives, prize draws and a Hall of Fame for students who go the distance.',
    icon: Trophy,
    showIcon: true,
    url: '/for-students',
  },
]

const incentives = [
  {
    title: 'Students featured on our website',
    detail: 'Every student who enters through centre 120 gets listed on our <a href="/thank-you" class="font-semibold text-white underline hover:text-brand-accent">Thank You page</a>. Score a Merit or Distinction and you make the <strong>Hall of Fame</strong> — with a Take a Bow or Standing Ovation Certificate. The highest scorers each quarter get the top spot and a gift token.',
    icon: Trophy,
    iconBg: 'bg-brand-accent/10',
    iconColor: 'text-brand-accent',
    borderColor: 'border-l-brand-accent',
  },
  {
    title: 'Teacher recognition and digital badges',
    detail: 'Teachers who enter 10 or more candidates earn a Certificate of Appreciation and a digital badge to display on their website. Tiered badges for 10+, 20+ and 30+ candidates.',
    icon: Award,
    iconBg: 'bg-brand-success-soft',
    iconColor: 'text-brand-success',
    borderColor: 'border-l-brand-success',
  },
  {
    title: 'Quarterly prize draws',
    detail: 'Teachers earn one prize draw entry for every exam booked through centre code 120. The more students entered, the more chances to win — with gift tokens to help invest back into their teaching. Parents and students — let your teacher know about code 120 and help them win too.',
    icon: Gift,
    iconBg: 'bg-brand-teal/10',
    iconColor: 'text-brand-teal',
    borderColor: 'border-l-brand-teal',
  },
  {
    title: 'Exam book discounts',
    detail: 'Teachers — get in touch to learn about exclusive discounts on exam books from Faber Music and Trinity College London Press. Parents and students save too — use our links to get 10% off ebooks for any grade or instrument.',
    icon: BookOpen,
    iconBg: 'bg-brand-primary/10',
    iconColor: 'text-brand-primary',
    borderColor: 'border-l-brand-primary',
  },
]

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
      'It is a Trinity College London registered exam centre code. Using code 120 when booking connects your entry to this centre, which unlocks access to incentives, recognition and ongoing support.',
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
]

const landingFaqCount = 4
const landingFaqs = faqs.slice(0, landingFaqCount)

const usefulLinks = [
  {
    id: 1,
    title: 'Book your exam now',
    subTitle: 'Ready to go? Head straight to the official Trinity booking page with code 120 already applied.',
    url: '#booking-modal',
  },
  {
    id: 2,
    title: 'See how it works',
    subTitle: 'Find out why teachers, parents and students are choosing to book through centre 120.',
    url: '#why',
  },
  {
    id: 3,
    title: 'Explore the incentives',
    subTitle: 'Prize draws, teacher recognition, student funding and discounts on exam materials.',
    url: '#incentives',
  },
]

const handleRunnerClick = (card: { url?: string; isExternal?: boolean }) => {
  if (!card.url) return

  if (card.url === '#booking-modal') {
    showBookingModal.value = true
    return
  }

  if (card.url.startsWith('#')) {
    const element = document.querySelector(card.url)
    element?.scrollIntoView({ behavior: 'smooth' })
    return
  }

  if (card.isExternal) {
    window.open(card.url, '_blank', 'noopener,noreferrer')
    return
  }

  router.visit(card.url)
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HERO — split layout with photo mosaic -->
    <section class="relative overflow-hidden bg-brand-surface">
      <div class="mx-auto max-w-7xl px-4 pt-24 pb-10 sm:px-6 md:pt-28 lg:px-8 lg:pt-28">
        <div class="lg:grid lg:grid-cols-12 lg:gap-x-8 lg:gap-y-10">

          <!-- Left — text content -->
          <div class="lg:col-span-6 lg:flex lg:flex-col lg:justify-center">
            <img
              :src="heroLogo"
              alt="musicExams.help"
              class="h-auto w-64 object-contain sm:w-80 md:w-96"
            />

            <MyTextConstructor
              variant="display"
              alignment="left"
              fontFamily="display"
              spacing="tight"
              class="mt-6 max-w-xl md:!text-4xl lg:!text-5xl xl:!text-6xl"
            >
              <template #myTitle>
                Your guide to Trinity music exams
              </template>
            </MyTextConstructor>

            <p class="mt-4 max-w-lg text-lg text-brand-text-soft sm:text-lg md:text-xl lg:text-2xl">
              Free guidance, incentives and support for teachers, parents and students.
              Use centre code
              <span class="font-semibold text-brand-text">{{ referralCode }}</span>
              when booking to unlock it all.
            </p>

            <div class="mt-6 flex flex-wrap gap-4">
              <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
                Book Your Exam
              </MyButtonConstructor>

              <a href="#why">
                <MyButtonConstructor variant="outline" size="large">
                  Why use this page
                </MyButtonConstructor>
              </a>
            </div>

            <!-- EMAIL CAPTURE — hero (hidden once subscribed) -->
            <div v-if="!isSubscribed" class="mt-8 w-full max-w-md">
              <EmailCapture source="hero" variant="light" :compact="true" />
            </div>
          </div>

          <!-- Right — photo mosaic (desktop) / row of 3 (mobile) -->
          <div class="mt-10 lg:col-span-6 lg:mt-0">
            <!-- Mobile — row of 3 key photos -->
            <div class="grid grid-cols-3 gap-3 lg:hidden">
              <div class="overflow-hidden rounded-xl shadow-lg">
                <img
                  :src="venuePhotos.steinwayGrand"
                  alt="Steinway grand piano"
                  class="h-28 w-full object-cover sm:h-36"
                />
              </div>
              <div class="overflow-hidden rounded-xl shadow-lg">
                <img
                  :src="venuePhotos.guitarStudent"
                  alt="Guitar student"
                  class="h-28 w-full object-cover sm:h-36"
                />
              </div>
              <div class="overflow-hidden rounded-xl shadow-lg">
                <img
                  :src="venuePhotos.pianoStudent"
                  alt="Piano student"
                  class="h-28 w-full object-cover sm:h-36"
                />
              </div>
            </div>

            <!-- Desktop — full staggered mosaic -->
            <div class="hidden lg:grid lg:grid-cols-2 lg:gap-4">
              <!-- Column 1 — offset down -->
              <div class="space-y-4 pt-8">
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.steinwayGrand"
                    alt="Steinway grand piano at our Wirral exam centre"
                    class="h-64 w-full object-cover"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.guitarStudent"
                    alt="Guitar student in a school music room"
                    class="h-56 w-full object-cover"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.sajeevanPiano"
                    alt="Sajeevan at the piano during a lesson"
                    class="h-48 w-full object-cover"
                  />
                </div>
              </div>
              <!-- Column 2 — flush top -->
              <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.pianoStudent"
                    alt="Piano student at a keyboard"
                    class="h-56 w-full object-cover"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.kawaiRoom"
                    alt="Kawai piano at our Liverpool exam centre"
                    class="h-64 w-full object-cover"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-xl">
                  <img
                    :src="venuePhotos.keerthanaaPiano"
                    alt="Keerthanaa practising piano at home"
                    class="h-48 w-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- TRUST STRIP -->
    <div class="w-full border-y border-brand-border bg-brand-surface-soft py-6 md:py-12">
      <div class="mx-auto max-w-5xl px-6 py-5">
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between">
          <div class="flex items-center gap-5">
            <img
              :src="profilePic"
              alt="Paul Sheridan — music teacher and Trinity exam centre coordinator"
              class="h-20 w-20 shrink-0 rounded-full object-cover object-[center_20%] shadow-lg ring-2 ring-brand-accent/30 sm:h-24 sm:w-24 md:h-28 md:w-28"
            />
            <div class="text-center sm:text-left">
              <MyTextConstructor
                alignment="left"
                bodyVariant="body"
                spacing="none"
                class="text-brand-text-soft md:!text-xl lg:!text-2xl"
              >
                Run by a local music teacher in partnership with
                <span class="font-semibold text-brand-text">
                  Trinity Registered Exam Centre 120
                </span>
              </MyTextConstructor>
              <MyTextConstructor
                alignment="left"
                bodyVariant="muted"
                spacing="none"
                class="mt-1 text-brand-text-soft md:!text-lg lg:!text-xl"
              >
                Supporting teachers, parents and students across the UK
              </MyTextConstructor>
            </div>
          </div>

          <img
            :src="trinityCentreLogo"
            alt="Trinity Registered Exam Centre 120"
            class="h-24 w-auto sm:h-28 md:h-32 lg:h-36 xl:h-40"
          />
        </div>
      </div>
    </div>

    <!-- CENTRE CODE BANNER -->
    <div class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary py-5 text-center">
      <p class="px-4 text-xl font-bold tracking-wide text-white sm:text-xl md:text-2xl">
        Book with Centre Code 120 and unlock free support, incentives and recognition
      </p>
    </div>

    <!-- WHY -->
    <section id="why" class="border-b border-brand-border bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="max-w-3xl">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>
              Why use this page
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            spacing="tight"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>
              Something for everyone
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            bodyVariant="body"
            spacing="none"
            class="mt-4 max-w-2xl text-brand-text-soft md:!text-xl lg:!text-2xl"
          >
            Whether you teach music, you are a parent navigating exams for the first time,
            or a student preparing for your next grade — centre code {{ referralCode }} connects
            you to guidance, recognition and real support.
          </MyTextConstructor>
        </div>

        <div class="mt-10">
          <MyRunnerConstructor
            :theArray="whyCards"
            variant="icon"
            :columns="3"
            spacing="normal"
            maxWidth="full"
            :enableHover="true"
            @cardClick="handleRunnerClick"
          />
        </div>
      </div>
    </section>

    <!-- INCENTIVES -->
    <section
      id="incentives"
      class="relative border-t border-brand-border"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_stars_bg.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-black/50" />

      <!-- Shooting stars animation -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <!-- Top stars -->
        <div class="shooting-star shooting-star-1" />
        <div class="shooting-star shooting-star-2" />
        <div class="shooting-star shooting-star-5" />
        <div class="shooting-star shooting-star-8" />
        <div class="shooting-star shooting-star-9" />
        <div class="shooting-star shooting-star-10" />
        <!-- Bottom stars -->
        <div class="shooting-star shooting-star-3" />
        <div class="shooting-star shooting-star-4" />
        <div class="shooting-star shooting-star-6" />
        <div class="shooting-star shooting-star-7" />
        <div class="shooting-star shooting-star-11" />
        <div class="shooting-star shooting-star-12" />
      </div>

      <div class="relative mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <!-- Section header -->
        <div class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight" textColor="text-brand-accent">
            <template #myTitle>
              Incentives
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>
              Hard work deserves more than a certificate
            </template>
          </MyTextConstructor>

          <p class="mx-auto mt-5 max-w-3xl text-center text-lg text-white/80 sm:text-lg md:text-xl lg:text-2xl">
            Real benefits for everyone who books through centre code {{ referralCode }} — whether you enter online or through our face-to-face centres in Liverpool and Wirral.
          </p>
        </div>

        <!-- Incentive cards — 2x2 grid -->
        <div class="mt-10 grid gap-5 sm:grid-cols-2">
          <div
            v-for="item in incentives"
            :key="item.title"
            class="overflow-hidden rounded-2xl bg-white/10 shadow-2xl ring-1 ring-white/10 backdrop-blur-sm transition-all duration-200 hover:-translate-y-1 hover:bg-white/15 hover:shadow-[0_25px_60px_-12px_rgba(0,0,0,0.5)]"
          >
            <div class="p-6">
              <component :is="item.icon" class="mb-3 h-8 w-8 text-brand-accent" />
              <h3 class="text-xl font-bold text-white sm:text-xl">{{ item.title }}</h3>
              <p class="mt-2 text-base leading-snug text-white/80 sm:text-base md:text-lg" v-html="item.detail">
              </p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- VENUE PHOTOS -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>
              Our Exam Venues
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>
              Where the exams happen
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            bodyVariant="body"
            alignment="center"
            spacing="none"
            class="mx-auto mt-4 max-w-3xl text-brand-text-soft md:!text-xl lg:!text-2xl"
          >
            Face-to-face exams take place at our centres in Liverpool and Wirral — both equipped with quality instruments in professional, comfortable settings.
          </MyTextConstructor>

          <!-- Digital exams callout -->
          <div class="mx-auto mt-6 max-w-2xl rounded-2xl bg-brand-primary px-6 py-5 text-center shadow-lg">
            <p class="text-lg font-semibold text-brand-text-inverse sm:text-lg md:text-xl">
              Digital exams can be taken from anywhere in the UK — whether you're up in Scotland, down in South Wales, over in Northern Ireland or on the east coast.
            </p>
          </div>
        </div>

        <!-- Photo grid -->
        <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Steinway Grand — Wirral -->
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.steinwayGrand"
                alt="Steinway & Sons grand piano at our Wirral exam centre"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Wirral Exam Centre</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                Our Wirral venue features a Steinway &amp; Sons grand piano — offering candidates the experience of performing on a world-class instrument.
              </p>
            </div>
          </div>

          <!-- Kawai Upright — Liverpool -->
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.kawaiRoom"
                alt="Kawai upright piano and teaching space at our Liverpool exam centre"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Liverpool Exam Centre</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                A brand new Kawai upright piano in a purpose-set teaching and exam room — a warm, professional space designed to help candidates feel at ease.
              </p>
            </div>
          </div>
        </div>

        <!-- Students heading -->
        <div class="mt-14 text-center">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>
              Students in action
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            bodyVariant="body"
            alignment="center"
            spacing="none"
            class="mx-auto mt-4 max-w-3xl text-brand-text-soft md:!text-xl lg:!text-2xl"
          >
            In lessons, at home, and in school — preparing for their exams and loving every minute.
          </MyTextConstructor>
        </div>

        <!-- Student photos — guitar and piano side by side -->
        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.guitarStudent"
                alt="Guitar student in a school music room during a lesson"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Guitar lesson — school music room</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                School music rooms, teaching studios, at home — wherever you learn, we're here to support you.
              </p>
            </div>
          </div>
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.pianoStudent"
                alt="Piano student practising at a keyboard"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Piano lesson — teaching studio</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                Building confidence one lesson at a time — that's how exam success starts.
              </p>
            </div>
          </div>
        </div>

        <!-- More student photos -->
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.sajeevanPiano"
                alt="Sajeevan at the piano during a lesson"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Piano lesson — teaching studio</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                Every lesson is a step closer to exam day — and every step deserves to be celebrated.
              </p>
            </div>
          </div>
          <div class="group overflow-hidden rounded-2xl bg-brand-surface shadow-xl ring-1 ring-brand-border">
            <div class="aspect-[4/3] overflow-hidden">
              <img
                :src="venuePhotos.keerthanaaPiano"
                alt="Keerthanaa practising piano at home"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div class="p-5">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                <template #myTitle>Piano practice — at home</template>
              </MyTextConstructor>
              <p class="mt-2 text-base leading-snug text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
                The best practice happens where you feel most comfortable — and it all counts towards exam day.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AWARDS & CERTIFICATES — clean dark background -->
    <section class="bg-black">
      <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <!-- Student Hall of Fame — banner (links to Thank You page) -->
        <a href="/thank-you" class="group block overflow-hidden rounded-2xl bg-black p-4 shadow-2xl transition-transform duration-300 hover:scale-[1.005]">
          <img
            src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Highest+score+In5.png"
            alt="Student Hall of Fame — celebrating every exam result through centre 120"
            class="block w-full"
          />
        </a>

        <!-- Certificates (link to Thank You page) -->
        <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
          <a href="/thank-you" class="group block overflow-hidden rounded-2xl bg-brand-surface p-3 shadow-2xl ring-1 ring-brand-border transition-transform duration-300 hover:scale-[1.01]">
            <img
              src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/certThankTeacher_10.png"
              alt="Teacher Certificate of Appreciation"
              class="block w-full rounded-2xl"
            />
          </a>
          <a href="/thank-you" class="group block overflow-hidden rounded-2xl bg-brand-surface p-3 shadow-2xl ring-1 ring-brand-border transition-transform duration-300 hover:scale-[1.01]">
            <img
              src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/certAceiveStu_Dis.png"
              alt="Student Certificate of Achievement — Highest Distinction"
              class="block w-full rounded-2xl"
            />
          </a>
        </div>

        <!-- CTA to Thank You page -->
        <div class="mt-8 text-center">
          <a href="/thank-you">
            <MyButtonConstructor variant="light" size="large">
              Visit the Thank You Page
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- THANK YOU BANNER + BOOKING CTA — clean light background -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <!-- Thank You banner — links to /thank-you -->
        <a href="/thank-you" class="group block overflow-hidden rounded-2xl shadow-2xl transition-transform duration-300 hover:scale-[1.005]">
          <img
            src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/thank_you_banner.jpg"
            alt="Thank You — Every student. Every exam. Recognised."
            class="block w-full"
          />
        </a>

        <div class="mt-10 flex justify-center">
          <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
            Continue to Official Booking
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- READY TO GET STARTED + FAQ — side by side on desktop -->
    <section class="bg-black">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-12">

          <!-- Ready to get started -->
          <div>
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              spacing="tight"
              textColor="text-white"
              class="md:!text-2xl lg:!text-2xl"
            >
              <template #myTitle>
                Ready to get started?
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              subTitleVariant="muted"
              spacing="none"
              textColor="text-white/70"
              class="mt-3 md:!text-base lg:!text-base"
            >
              <template #mySubTitle>
                Choose your next step — whether you are ready to book or want to learn more first.
              </template>
            </MyTextConstructor>

            <div class="mt-8">
              <MyRunnerListTextInfo
                :theArray="usefulLinks"
                maxWidth="full"
                @cardClick="handleRunnerClick"
              />
            </div>
          </div>

          <!-- FAQ -->
          <div id="faq">
            <MyTextConstructor variant="eyebrow" spacing="tight" textColor="text-white/60">
              <template #myTitle>
                FAQ
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              spacing="tight"
              textColor="text-white"
              class="mt-3 md:!text-2xl lg:!text-2xl"
            >
              <template #myTitle>
                Common questions
              </template>
            </MyTextConstructor>

            <div class="mt-8">
              <MyAccordionConstructor
                :items="landingFaqs.map((faq, index) => ({
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
              <div class="mt-4 text-center">
                <a href="/faq">
                  <MyButtonConstructor variant="light" size="small">
                    View all questions
                  </MyButtonConstructor>
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- PIANO KEYS DIVIDER — atmospheric -->
    <section class="relative bg-black">
      <div class="relative overflow-hidden">
        <img
          :src="venuePhotos.kawaiFullKeyboard"
          alt="Piano keys"
          class="h-40 w-full object-cover opacity-30 sm:h-48 md:h-56"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-black via-transparent to-black" />
      </div>
    </section>

    <!-- EMAIL CAPTURE — bottom -->
    <section class="bg-black">
      <div class="mx-auto max-w-lg px-4 pb-14 sm:px-6">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center sm:p-8">
          <h3 class="text-xl font-bold text-white sm:text-xl">
            Don't miss out
          </h3>
          <p class="mt-1 text-base text-white/60 sm:text-base">
            Get exam tips, booking reminders and exclusive offers straight to your inbox.
          </p>
          <div class="mt-4">
            <EmailCapture source="bottom" variant="dark" />
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>