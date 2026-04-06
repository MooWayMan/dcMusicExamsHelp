<!-- resources/js/pages/CookiePolicy.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'

const { animClass } = usePageAnimation()

const breadcrumbPages = [
  { name: 'Cookie Policy', href: '/cookies', current: true },
]

const pageMeta = {
  title: 'Cookie Policy — musicExams.help',
  description:
    'How musicExams.help uses cookies and similar technologies. Details on essential and analytics cookies.',
}

const lastUpdated = '4 April 2026'
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
              <template #myTitle>Cookie Policy</template>
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

          <!-- WHAT ARE COOKIES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">What are cookies?</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Cookies are small text files stored on your device when you visit a website. They are widely used to make
              websites work properly, remember your preferences, and provide usage information to site owners.
            </p>
          </div>

          <!-- HOW WE USE COOKIES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">How we use cookies</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help uses a small number of cookies. We do not use any advertising or marketing cookies.
            </p>
          </div>

          <!-- ESSENTIAL COOKIES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Essential cookies</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              These cookies are necessary for the website to function and cannot be switched off. They are set in response
              to actions you take such as logging in, filling in forms, or setting your cookie preferences.
            </p>
            <div class="mt-3 overflow-x-auto rounded-lg border border-brand-border">
              <table class="w-full text-left text-sm">
                <thead class="bg-brand-surface-soft">
                  <tr>
                    <th class="px-4 py-2 font-semibold text-brand-text">Cookie</th>
                    <th class="px-4 py-2 font-semibold text-brand-text">Purpose</th>
                    <th class="px-4 py-2 font-semibold text-brand-text">Duration</th>
                  </tr>
                </thead>
                <tbody class="bg-brand-surface">
                  <tr class="border-t border-brand-border">
                    <td class="px-4 py-2 font-mono text-xs text-brand-text-soft">laravel_session</td>
                    <td class="px-4 py-2 text-brand-text">Keeps you logged in and maintains your session</td>
                    <td class="px-4 py-2 text-brand-text-soft">2 hours</td>
                  </tr>
                  <tr class="border-t border-brand-border">
                    <td class="px-4 py-2 font-mono text-xs text-brand-text-soft">XSRF-TOKEN</td>
                    <td class="px-4 py-2 text-brand-text">Protects against cross-site request forgery (security)</td>
                    <td class="px-4 py-2 text-brand-text-soft">2 hours</td>
                  </tr>
                  <tr class="border-t border-brand-border">
                    <td class="px-4 py-2 font-mono text-xs text-brand-text-soft">cookie-consent</td>
                    <td class="px-4 py-2 text-brand-text">Remembers your cookie preference (accepted or declined)</td>
                    <td class="px-4 py-2 text-brand-text-soft">1 year</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ANALYTICS COOKIES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Analytics cookies</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              These cookies are only set if you click "Accept" on our cookie banner. They help us understand how visitors
              use our website so we can improve it. All data is anonymised — we cannot identify you personally from analytics data.
            </p>
            <div class="mt-3 overflow-x-auto rounded-lg border border-brand-border">
              <table class="w-full text-left text-sm">
                <thead class="bg-brand-surface-soft">
                  <tr>
                    <th class="px-4 py-2 font-semibold text-brand-text">Cookie</th>
                    <th class="px-4 py-2 font-semibold text-brand-text">Purpose</th>
                    <th class="px-4 py-2 font-semibold text-brand-text">Duration</th>
                  </tr>
                </thead>
                <tbody class="bg-brand-surface">
                  <tr class="border-t border-brand-border">
                    <td class="px-4 py-2 font-mono text-xs text-brand-text-soft">_ga</td>
                    <td class="px-4 py-2 text-brand-text">Google Analytics — distinguishes unique visitors</td>
                    <td class="px-4 py-2 text-brand-text-soft">2 years</td>
                  </tr>
                  <tr class="border-t border-brand-border">
                    <td class="px-4 py-2 font-mono text-xs text-brand-text-soft">_ga_TZJ8ZCZW3W</td>
                    <td class="px-4 py-2 text-brand-text">Google Analytics — maintains session state</td>
                    <td class="px-4 py-2 text-brand-text-soft">2 years</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="mt-3 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Google Analytics data is processed by Google LLC. You can learn more about how Google uses your data at
              <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">policies.google.com/privacy</a>.
              You can also opt out of Google Analytics across all websites by installing the
              <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">Google Analytics opt-out browser add-on</a>.
            </p>
          </div>

          <!-- MANAGING COOKIES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Managing your cookie preferences</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              When you first visit our site, a cookie banner will ask for your consent to analytics cookies.
              You can accept or decline at that point.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              To change your preference later, click the "Cookie Preferences" link in the footer of any page.
              This will re-open the cookie banner so you can update your choice.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              You can also control cookies through your browser settings. Most browsers allow you to block or delete cookies.
              Please note that blocking essential cookies may prevent parts of the website from working correctly.
            </p>
          </div>

          <!-- CONTACT -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Questions</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you have any questions about our use of cookies, please contact us at
              <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              For more information about your data and privacy, please see our
              <a href="/privacy" class="font-medium text-brand-accent hover:underline">Privacy Policy</a>.
            </p>
          </div>

        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
