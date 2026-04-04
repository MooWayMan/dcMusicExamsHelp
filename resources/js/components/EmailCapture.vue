<!-- resources/js/components/EmailCapture.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useSubscription } from '@/composables/useSubscription'

interface Props {
  variant?: 'light' | 'dark'
  source?: string
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'light',
  source: 'website',
  compact: false,
})

const { isSubscribed, subscriberName, markSubscribed } = useSubscription()

const name = ref('')
const email = ref('')
const role = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

const isDark = computed(() => props.variant === 'dark')

async function handleSubmit() {
  errorMessage.value = ''

  if (!name.value.trim() || !email.value.trim()) {
    errorMessage.value = 'Please enter your name and email.'
    return
  }

  isSubmitting.value = true

  try {
    // Get CSRF token from the meta tag or cookie
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      || (usePage().props as any).csrf_token
      || ''

    const response = await fetch('/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(
          document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''
        ),
      },
      body: JSON.stringify({
        name: name.value.trim(),
        email: email.value.trim(),
        role: role.value || null,
        source: props.source,
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

    markSubscribed(name.value.trim())
  } catch {
    errorMessage.value = 'Something went wrong. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div>
    <!-- Subscribed state — replaces the form everywhere -->
    <div v-if="isSubscribed" class="rounded-xl border border-brand-accent/30 bg-brand-accent/10 px-5 py-4 text-center">
      <p class="text-sm font-semibold sm:text-base" :class="isDark ? 'text-white' : 'text-brand-accent'">
        You're on the list{{ subscriberName ? `, ${subscriberName}` : '' }}! We'll be in touch soon.
      </p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-3">
      <div :class="compact ? 'flex flex-col gap-2 sm:flex-row' : 'space-y-3'">
        <input
          v-model="name"
          type="text"
          placeholder="Your name"
          required
          :class="[
            'w-full rounded-lg border px-4 py-3 text-sm transition-colors focus:outline-none focus:ring-2 sm:text-base',
            isDark
              ? 'border-white/20 bg-white/10 text-white placeholder-white/50 focus:border-brand-accent focus:ring-brand-accent/30'
              : 'border-brand-border bg-brand-surface text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:ring-brand-accent/30'
          ]"
        />

        <input
          v-model="email"
          type="email"
          placeholder="Your email"
          required
          :class="[
            'w-full rounded-lg border px-4 py-3 text-sm transition-colors focus:outline-none focus:ring-2 sm:text-base',
            isDark
              ? 'border-white/20 bg-white/10 text-white placeholder-white/50 focus:border-brand-accent focus:ring-brand-accent/30'
              : 'border-brand-border bg-brand-surface text-brand-text placeholder-brand-text-soft focus:border-brand-accent focus:ring-brand-accent/30'
          ]"
        />

        <button
          v-if="compact"
          type="submit"
          :disabled="isSubmitting"
          class="shrink-0 cursor-pointer rounded-lg bg-brand-accent px-6 py-3 text-sm font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark disabled:opacity-50 sm:text-base"
        >
          {{ isSubmitting ? 'Sending...' : 'Subscribe' }}
        </button>
      </div>

      <!-- Role selector (not shown in compact mode) -->
      <div v-if="!compact" class="flex flex-wrap gap-2">
        <span :class="isDark ? 'text-sm text-white/60' : 'text-sm text-brand-text-soft'">I am a:</span>
        <label
          v-for="r in [{ value: 'teacher', label: 'Teacher' }, { value: 'parent', label: 'Parent' }, { value: 'student', label: 'Student' }]"
          :key="r.value"
          :class="[
            'inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors sm:text-sm',
            role === r.value
              ? 'border-brand-accent bg-brand-accent text-brand-text-inverse'
              : isDark
                ? 'border-white/20 text-white/70 hover:bg-white/10'
                : 'border-brand-border text-brand-text-soft hover:bg-brand-surface-soft'
          ]"
        >
          <input
            type="radio"
            name="role"
            :value="r.value"
            v-model="role"
            class="sr-only"
          />
          {{ r.label }}
        </label>
      </div>

      <button
        v-if="!compact"
        type="submit"
        :disabled="isSubmitting"
        class="w-full cursor-pointer rounded-lg bg-brand-accent px-6 py-3 text-sm font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark disabled:opacity-50 sm:text-base"
      >
        {{ isSubmitting ? 'Sending...' : 'Stay in the loop' }}
      </button>

      <p v-if="errorMessage" class="text-center text-xs text-red-400 sm:text-sm">
        {{ errorMessage }}
      </p>

      <p :class="['text-center text-xs', isDark ? 'text-white/40' : 'text-brand-text-soft/60']">
        No spam, just useful updates. Unsubscribe any time.
        <a href="/privacy" :class="isDark ? 'text-white/50 hover:text-white/70' : 'text-brand-text-soft hover:text-brand-accent'" class="underline">Privacy Policy</a>
      </p>
    </form>
  </div>
</template>
