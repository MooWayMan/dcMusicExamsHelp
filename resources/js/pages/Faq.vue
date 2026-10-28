<!-- resources/js/pages/Faq.vue -->
<script setup lang="ts">
import { useAccordionHashOpen } from '@/composables/useAccordionHashOpen'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import BookingModal from '@/components/BookingModal.vue'
import SiteSearchBox from '@/components/SiteSearchBox.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import faqPage from '@/data/faqs/faq'
import { faqAccordionItems } from '@/lib/faqs'

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

const faqs = faqPage.items

useAccordionHashOpen()


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
          <SiteSearchBox placeholder="Search" />
        </div>
        </div>
      </div>
    </section>

    <!-- FAQ ACCORDION -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 2)">
          <MyAccordionConstructor
            :items="faqAccordionItems(faqPage)"
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
