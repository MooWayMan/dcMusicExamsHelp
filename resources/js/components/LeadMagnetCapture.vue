<!-- resources/js/components/LeadMagnetCapture.vue -->
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { CheckCircle2, FileDown } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

// Lead magnet capture form: collect name + email, optional marketing
// consent (GDPR — silence is NOT consent), then POST to
// /lead-magnet/subscribe which emails the Trinity Exam Checklist PDF.
//
// Lives inline on Welcome.vue between the hero and the grid sections.
// Brand-token styled so it slots into either light or dark backgrounds
// without leaking colours.
interface Props {
  variant?: 'light' | 'dark'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'light',
})

// localStorage key — once a visitor has successfully grabbed the PDF in
// this browser, we remember it so they don't see the form again on
// subsequent visits. Stops repeat-submissions out of confusion. Per-browser,
// per-incognito-session — fresh device = fresh form.
const STORAGE_KEY = 'leadMagnetClaimed:trinity-exam-checklist'

const name = ref('')
const email = ref('')
const marketingConsent = ref(false)
// Honeypot — a real user never fills this; bots routinely do. Submissions
// where this is non-empty are silently dropped server-side. See
// docs/dev-rules.md "Public forms" rule.
const websiteUrl = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isDone = ref(false)
const claimedPreviously = ref(false)
const claimedFirstName = ref('')

onMounted(() => {
  if (typeof window === 'undefined') return
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (raw) {
      const parsed = JSON.parse(raw) as { name?: string; at?: string }
      claimedPreviously.value = true
      claimedFirstName.value = (parsed.name ?? '').split(' ')[0] || ''
      isDone.value = true
    }
  } catch {
    // Bad/empty localStorage — treat as fresh visitor.
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  successMessage.value = ''

  if (!name.value.trim() || !email.value.trim()) {
    errorMessage.value = 'Please enter your name and email.'
    return
  }

  isSubmitting.value = true

  try {
    const xsrf = decodeURIComponent(
      document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''
    )

    const response = await fetch('/lead-magnet/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrf,
      },
      body: JSON.stringify({
        name: name.value.trim(),
        email: email.value.trim(),
        marketing_consent: marketingConsent.value,
        website_url: websiteUrl.value,
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        errorMessage.value = Object.values(data.errors).flat().join(' ')
      } else {
        errorMessage.value = 'Something went wrong. Please try again.'
      }
      return
    }

    successMessage.value = data.message ?? 'Check your inbox — the checklist is on its way.'
    isDone.value = true

    // Remember in this browser so they don't see the form on every visit.
    try {
      window.localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ name: name.value.trim(), at: new Date().toISOString() }),
      )
      claimedFirstName.value = name.value.trim().split(' ')[0]
    } catch {
      // localStorage might be disabled — non-fatal, the form still
      // worked, they just won't get the persisted thank-you on refresh.
    }
  } catch {
    errorMessage.value = 'Something went wrong. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    class="rounded-2xl border-4 border-brand-accent bg-brand-surface p-6 shadow-xl sm:p-8"
  >
    <!-- Success state — fresh submit -->
    <div v-if="isDone && !claimedPreviously" class="flex flex-col items-center gap-3 text-center">
      <CheckCircle2 class="h-10 w-10 text-brand-success" />
      <h3 class="text-xl font-bold text-brand-text sm:text-2xl">
        {{ successMessage }}
      </h3>
      <p class="text-base text-brand-text-soft">
        It usually arrives within a minute. Check your spam folder if it doesn&rsquo;t show up.
      </p>
    </div>

    <!-- Returning-visitor state — already grabbed the PDF in this browser -->
    <div v-else-if="isDone && claimedPreviously" class="flex flex-col items-center gap-3 text-center">
      <CheckCircle2 class="h-8 w-8 text-brand-success" />
      <h3 class="text-lg font-bold text-brand-text sm:text-xl">
        <template v-if="claimedFirstName">You&rsquo;ve got the Trinity Exam Checklist, {{ claimedFirstName }}.</template>
        <template v-else>You&rsquo;ve got the Trinity Exam Checklist.</template>
      </h3>
      <p class="text-sm text-brand-text-soft">
        Need it again? <button type="button" @click="claimedPreviously = false; isDone = false" class="font-medium text-brand-accent underline hover:opacity-80">Send a fresh copy</button>
      </p>
    </div>

    <!-- Form -->
    <div v-else class="grid gap-4 sm:grid-cols-2 sm:items-center">
      <!-- Left: pitch -->
      <div>
        <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-brand-accent/10 px-3 py-1 text-sm font-semibold text-brand-accent">
          <FileDown class="h-4 w-4" />
          Free download
        </div>
        <h3 class="text-2xl font-bold text-brand-text sm:text-3xl">
          Trinity Exam Checklist
        </h3>
        <p class="mt-2 text-base text-brand-text-soft sm:text-lg">
          A simple, printable checklist for preparing, recording, and sitting your Trinity music exam — sent straight to your inbox.
        </p>
      </div>

      <!-- Right: form -->
      <form @submit.prevent="handleSubmit" class="space-y-3">
        <!-- Honeypot: hidden from real users, irresistible to bots. -->
        <input
          v-model="websiteUrl"
          type="text"
          name="website_url"
          tabindex="-1"
          autocomplete="off"
          aria-hidden="true"
          class="absolute -left-[10000px] h-0 w-0 opacity-0"
        />

        <input
          v-model="name"
          type="text"
          placeholder="Your name"
          required
          class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
        />

        <input
          v-model="email"
          type="email"
          placeholder="Your email"
          required
          class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
        />

        <label class="flex cursor-pointer items-start gap-2 text-sm text-brand-text-soft">
          <input
            v-model="marketingConsent"
            type="checkbox"
            class="mt-1 h-4 w-4 shrink-0 cursor-pointer rounded border-brand-border accent-brand-accent"
          />
          <span>
            <span class="font-semibold text-brand-text">Yes, send me Trinity exam tips, results-day reminders and centre 120 community updates.</span>
            <span class="mt-0.5 block">Usually once or twice a month. Never spam. Easy unsubscribe.</span>
          </span>
        </label>

        <MyButtonConstructor
          type="submit"
          variant="primary"
          size="medium"
          class="w-full"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Sending...' : 'Send me the checklist' }}
        </MyButtonConstructor>

        <p v-if="errorMessage" class="text-center text-sm text-brand-danger">
          {{ errorMessage }}
        </p>

        <p class="text-center text-xs text-brand-text-soft">
          We email you the PDF once. Tick the box above for occasional Trinity exam guidance.
          <a href="/privacy" class="underline hover:text-brand-accent">Privacy Policy</a>
        </p>
      </form>
    </div>
  </div>
</template>
