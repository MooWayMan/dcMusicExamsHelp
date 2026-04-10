<!-- resources/js/pages/TermsOfUse.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'

const { animClass } = usePageAnimation()

const breadcrumbPages = [
  { name: 'Terms of Use', href: '/terms', current: true },
]

const pageMeta = {
  title: 'Terms of Use — musicExams.help',
  description:
    'Terms of use for the musicExams.help website. Information accuracy disclaimer, acceptable use, and intellectual property.',
}

const lastUpdated = '10 April 2026'
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
              <template #myTitle>Legal</template>
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
              <template #myTitle>Terms of Use</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Last updated: {{ lastUpdated }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CONTENT -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 2)" class="prose-policy space-y-8">

          <!-- ABOUT THIS SITE -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">About this website</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help is operated by Paul Sheridan, a sole trader based in Wirral, England.
              We are a registered exam centre (centre 120) for Trinity College London music examinations.
              This website provides information, guidance and support for teachers, parents and students
              preparing for Trinity graded music exams.
            </p>
          </div>

          <!-- INFORMATION ACCURACY -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Information accuracy</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We work hard to keep everything on this site accurate and up to date. However, exam fees,
              dates, syllabus details, regulations and policies can change at any time. The information
              on this site is provided in good faith as a helpful guide, but it may not always reflect
              the very latest changes from Trinity College London.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Before making any decisions about exam entries, bookings or purchases, we recommend
              confirming the latest details on the official
              <a href="https://www.trinitycollege.com" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">Trinity College London website</a>.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help accepts no liability for any loss, inconvenience or costs arising from
              reliance on information provided on this website. This does not affect your statutory rights.
            </p>
          </div>

          <!-- NOT TRINITY -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Relationship with Trinity College London</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help is an independent exam centre operating under a Local Area Representative
              agreement with Trinity College London. We are not part of Trinity College London, and
              this website is not operated or endorsed by Trinity College London. Any views, guidance
              or opinions expressed on this site are our own.
            </p>
          </div>

          <!-- EXTERNAL LINKS -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">External links</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              This site contains links to external websites, including Trinity College London's booking
              systems and Faber Music. These links are provided for your convenience. We have no control
              over the content of external sites and accept no responsibility for them. Following an
              external link does not imply any endorsement of that site by musicExams.help.
            </p>
          </div>

          <!-- INTELLECTUAL PROPERTY -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Intellectual property</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              The content on this website — including text, images, design and logos — is owned by
              musicExams.help unless otherwise stated. You may not reproduce, distribute or use any
              content from this site for commercial purposes without our written permission.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Trinity College London and the Trinity logo are trademarks of Trinity College London.
              All Trinity-related materials referenced on this site remain the property of Trinity
              College London.
            </p>
          </div>

          <!-- ACCEPTABLE USE -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Acceptable use</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              You may use this website for personal, non-commercial purposes related to music
              education and exam preparation. You agree not to misuse this site or use it in any way
              that could damage, disable or impair the service, or interfere with any other user's
              experience.
            </p>
          </div>

          <!-- CHANGES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Changes to these terms</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We may update these terms from time to time. Any changes will be posted on this page
              with an updated date. By continuing to use the site after changes are posted, you accept
              the revised terms.
            </p>
          </div>

          <!-- CONTACT -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Contact</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you have any questions about these terms, please contact us at
              <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
            </p>
          </div>

        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
