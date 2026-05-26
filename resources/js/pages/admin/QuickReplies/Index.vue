<!-- resources/js/pages/admin/QuickReplies/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Copy, Check, MessageSquareText, List } from 'lucide-vue-next'
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

const props = defineProps<{
  templates: Template[]
}>()

const { animClass } = usePageAnimation()

// Tracks which template id is currently flashing "Copied!"
const recentlyCopiedId = ref<string | null>(null)

// Group templates by audience, preserving the order each audience first appears
// in the controller array. That keeps Parent / Teacher / Snippet / Canned answer
// / DG Exam in the order Paul has set, rather than alphabetising.
const groupedTemplates = computed(() => {
  const groups: Record<string, Template[]> = {}
  const order: string[] = []
  for (const tpl of props.templates) {
    if (!groups[tpl.audience]) {
      groups[tpl.audience] = []
      order.push(tpl.audience)
    }
    groups[tpl.audience].push(tpl)
  }
  return order.map((audience) => ({ audience, items: groups[audience] }))
})

function audienceAnchorId(audience: string): string {
  return 'group-' + audience.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

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
    case 'DG Exam':
      return 'bg-brand-teal/10 text-brand-teal'
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

  <div class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
    <!-- Jump-to TOC -->
    <aside
      :class="animClass('fade-up', 1)"
      class="mb-6 rounded-xl border border-brand-border bg-brand-surface-soft p-5"
    >
      <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-brand-text">
        <List class="h-4 w-4" />
        Jump to
      </h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="group in groupedTemplates" :key="group.audience">
          <a
            :href="'#' + audienceAnchorId(group.audience)"
            class="text-xs font-semibold uppercase tracking-wide text-brand-text-soft hover:text-brand-accent"
          >
            {{ group.audience }} ({{ group.items.length }})
          </a>
          <ul class="mt-1 space-y-1">
            <li v-for="t in group.items" :key="t.id">
              <a
                :href="'#' + t.id"
                class="text-sm text-brand-text hover:text-brand-accent hover:underline"
              >
                {{ t.title }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </aside>

    <!-- Grouped entries -->
    <div :class="animClass('fade-up', 2)" class="space-y-10">
      <section
        v-for="group in groupedTemplates"
        :key="group.audience"
        :id="audienceAnchorId(group.audience)"
        class="scroll-mt-20"
      >
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-brand-text-soft">
          {{ group.audience }}
        </h2>

        <div class="space-y-4">
          <article
            v-for="template in group.items"
            :id="template.id"
            :key="template.id"
            class="scroll-mt-20 overflow-hidden rounded-xl border-2 border-brand-border bg-brand-surface"
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
                  <h3 class="text-base font-semibold text-brand-text">{{ template.title }}</h3>
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
      </section>
    </div>

    <p :class="animClass('fade-up', 3)" class="mt-6 text-xs text-brand-text-soft">
      Edit the canonical bank in
      <code class="rounded bg-brand-surface-soft px-1 py-0.5 text-[11px]">reference_reply_templates_bank.md</code>
      then mirror changes into <code class="rounded bg-brand-surface-soft px-1 py-0.5 text-[11px]">QuickRepliesController</code>.
    </p>
  </div>
</template>
