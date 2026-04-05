<!-- resources/js/pages/Contact.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { router } from '@inertiajs/vue3'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyTextareaConstructor from '@/components/reusables/MyTextareaConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Mail, Clock, MessageCircle } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Contact Us — musicExams.help',
  description:
    'Get in touch with musicExams.help. Questions about Trinity music exams, booking through centre 120, or anything else — we\'re here to help.',
}

const breadcrumbPages = [
  { name: 'Contact', href: '/contact', current: true },
]

/* ── Contact form ── */
const form = ref({
  name: '',
  email: '',
  subject: '',
  message: '',
})
const formSubmitting = ref(false)
const formSuccess = ref(false)
const formError = ref('')

function submitForm() {
  formError.value = ''

  if (!form.value.name.trim() || !form.value.email.trim() || !form.value.message.trim()) {
    formError.value = 'Please fill in your name, email and message.'
    return
  }

  formSubmitting.value = true

  router.post('/contact', form.value, {
    onSuccess: () => {
      formSuccess.value = true
      formSubmitting.value = false
      form.value = { name: '', email: '', subject: '', message: '' }
    },
    onError: (errors: Record<string, string>) => {
      formError.value = Object.values(errors).join(' ')
      formSubmitting.value = false
    },
  })
}

/* ── FAQs ── */
const faqs = [
  {
    id: 1,
    question: 'How quickly will I get a reply?',
    answer: 'We aim to respond to all enquiries within 24 hours during term time. During school holidays it may take a little longer, but we\'ll always get back to you.',
  },
  {
    id: 2,
    question: 'Can I call instead?',
    answer: 'We don\'t currently offer phone support, but email is the quickest way to reach us. If your question is urgent, mention it in the subject line and we\'ll prioritise it.',
  },
  {
    id: 3,
    question: 'I need help booking an exam — can you walk me through it?',
    answer: 'Absolutely. Drop us a message with the instrument, grade and whether you want face-to-face or digital, and we\'ll guide you through the process step by step.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-24 pb-6 md:pt-28 lg:pt-28">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Get in Touch</template>
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
              <template #myTitle>How can we help?</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Whether you're a teacher, parent or student — if you have a question about Trinity exams
              or booking through centre 120, we'd love to hear from you.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CONTACT INFO STRIP -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto flex max-w-4xl flex-col items-center justify-center gap-6 px-4 py-6 sm:flex-row sm:gap-10 sm:px-6">
        <div :class="animClass('fade-up', 3)" class="flex items-center gap-3 text-white">
          <Mail class="h-5 w-5 flex-shrink-0" />
          <a href="mailto:musicexams@musicexams.help" class="text-sm font-medium underline decoration-white/40 hover:decoration-white sm:text-base">
            musicexams@musicexams.help
          </a>
        </div>
        <div :class="animClass('fade-up', 3)" class="flex items-center gap-3 text-white/80">
          <Clock class="h-5 w-5 flex-shrink-0" />
          <span class="text-sm sm:text-base">Usually replies within 24 hours</span>
        </div>
      </div>
    </section>

    <!-- CONTACT FORM -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <div class="mx-auto mb-4 h-1 w-16 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent"></div>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Send us a message</template>
          </MyTextConstructor>
        </div>

        <!-- Success message -->
        <div
          v-if="formSuccess"
          :class="animClass('fade-up', 2)"
          class="mt-8 rounded-2xl border border-brand-success/30 bg-brand-success/5 p-8 text-center"
        >
          <MessageCircle class="mx-auto mb-4 h-12 w-12 text-brand-success" />
          <p class="text-lg font-semibold text-brand-text sm:text-xl">Message sent!</p>
          <p class="mt-2 text-base text-brand-text-soft">
            Thanks for getting in touch. We'll get back to you as soon as we can.
          </p>
          <div class="mt-6">
            <MyButtonConstructor variant="primary" size="medium" @click="formSuccess = false">
              Send another message
            </MyButtonConstructor>
          </div>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="submitForm" class="mt-8 space-y-6">
          <div :class="animClass('fade-up', 2)">
            <MyInputConstructor
              v-model="form.name"
              type="text"
              label="Your name"
              placeholder="e.g. Jane Smith"
              size="small"
              variant="outline"
              required
            />
          </div>

          <div :class="animClass('fade-up', 2)">
            <MyInputConstructor
              v-model="form.email"
              type="email"
              label="Email address"
              placeholder="e.g. jane@example.com"
              size="small"
              variant="outline"
              required
            />
          </div>

          <div :class="animClass('fade-up', 3)">
            <MyInputConstructor
              v-model="form.subject"
              type="text"
              label="Subject (optional)"
              placeholder="e.g. Question about Grade 5 booking"
              size="small"
              variant="outline"
            />
          </div>

          <div :class="animClass('fade-up', 3)">
            <MyTextareaConstructor
              v-model="form.message"
              label="Your message"
              placeholder="Tell us how we can help..."
              size="small"
              variant="outline"
              :rows="5"
              required
            />
          </div>

          <!-- Error message -->
          <p v-if="formError" class="text-sm font-medium text-brand-danger">{{ formError }}</p>

          <div :class="animClass('fade-up', 4)">
            <MyButtonConstructor
              variant="primary"
              size="large"
              type="submit"
              :disabled="formSubmitting"
              :fullWidth="true"
            >
              {{ formSubmitting ? 'Sending...' : 'Send Message' }}
            </MyButtonConstructor>
          </div>
        </form>
      </div>
    </section>

    <!-- FAQ -->
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
            <template #myTitle>Before you write</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>
      </div>
    </section>

    <MyFooter />
  </div>
</template>
