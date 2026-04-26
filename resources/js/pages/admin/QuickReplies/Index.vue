<!-- resources/js/pages/admin/QuickReplies/Index.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { Copy, Check, MessageSquareText } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface Template {
  id: string
  audience: string
  title: string
  when: string
  subject: string | null
  body: string
}

defineProps<{
  templates: Template[]
}>()

const { animClass } = usePageAnimation()

// Tracks which template id is currently flashing "Copied!"
const recentlyCopiedId = ref<string | null>(null)

async function copyToClipboard(template: Template) {
  // Compose copy payload — subject + body when subject exists, body alone otherwise
  const payload = template.subject
    ? `Subject: ${template.subject}\n\n${template.body}`
    : template.body

  try {
    await navigator.clipboard.writeText(payload)
    recentlyCopiedId.value = template.id
    setTimeout(() => {
      if (recentlyCopiedId.value === template.id) {
        recentlyCopiedId.value = null
      }
    }, 1800)
  } catch (err) {
    // Older Safari / iOS sometimes refuses writeText — fall back to a textarea select.
    const textarea = document.createElement('textarea')
    textarea.value = payload
    textarea.setAttribute('readonly', '')
    textarea.style.position = 'absolute'
    textarea.style.left = '-9999px'
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      recentlyCopiedId.value = template.id
      setTimeout(() => {
        if (recentlyCopiedId.value === template.id) {
          recentlyCopiedId.value = null
        }
      }, 1800)
    } finally {
      document.body.removeChild(textarea)
    }
  }
}

function audiencePillClass(audience: string): string {
  switch (audience) {
    case 'Parent':
      return 'bg-brand-accent/10 text-brand-accent'
    case 'Teacher / School Admin':
      return 'bg-brand-success/10 text-brand-success'
    case 'Snippet':
      return 'bg-brand-text-soft/10 text-brand-text-soft'
    default:
      return 'bg-brand-surface-soft text-brand-text-soft'
  }
}
</script>

<template>
  <PageHeader
    title="Quick Replies"
    subtitle="Phone-friendly template bank for inbound enquiries — tap, copy, paste"
    eyebrow="Inbox tools"
    :showIcon="true"
  >
    <template #actions>
      <div class="flex items-center gap-2 text-sm text-brand-text-soft">
        <MessageSquareText class="h-4 w-4" />
        Source: reply template bank
      </div>
    </template>
  </PageHeader>

  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div :class="animClass('fade-up', 1)" class="space-y-4">
      <article
        v-for="template in templates"
        :key="template.id"
        class="overflow-hidden rounded-xl border-2 border-brand-border bg-brand-surface"
      >
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-brand-border/50 px-5 py-4 sm:px-6">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span
                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="audiencePillClass(template.audience)"
              >
                {{ template.audience }}
              </span>
              <h2 class="text-base font-semibold text-brand-text">{{ template.title }}</h2>
            </div>
            <p class="mt-2 text-sm text-brand-text-soft">{{ template.when }}</p>
          </div>

          <button
            type="button"
            @click="copyToClipboard(template)"
            class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition-all"
            :class="recentlyCopiedId === template.id
              ? 'bg-brand-success/15 text-brand-success'
              : 'bg-brand-surface-soft text-brand-text-soft hover:bg-brand-accent/10 hover:text-brand-accent'"
          >
            <component
              :is="recentlyCopiedId === template.id ? Check : Copy"
              class="h-4 w-4"
            />
            {{ recentlyCopiedId === template.id ? 'Copied' : 'Copy' }}
          </button>
        </header>

        <div class="space-y-3 px-5 py-4 sm:px-6">
          <p v-if="template.subject" class="text-sm text-brand-text-soft">
            <span class="font-semibold text-brand-text">Subject:</span>
            {{ template.subject }}
          </p>

          <pre class="whitespace-pre-wrap break-words font-sans text-sm leading-relaxed text-brand-text">{{ template.body }}</pre>
        </div>
      </article>
    </div>

    <p :class="animClass('fade-up', 2)" class="mt-6 text-xs text-brand-text-soft">
      Edit the canonical bank in
      <code class="rounded bg-brand-surface-soft px-1 py-0.5 text-[11px]">reference_reply_templates_bank.md</code>
      then mirror changes into <code class="rounded bg-brand-surface-soft px-1 py-0.5 text-[11px]">QuickRepliesController</code>.
    </p>
  </div>
</template>
