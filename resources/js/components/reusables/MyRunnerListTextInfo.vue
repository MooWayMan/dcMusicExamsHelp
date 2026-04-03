<!-- resources/js/components/reusables/MyRunnerListTextInfo.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import { ArrowUpRightIcon } from '@heroicons/vue/20/solid'

interface CardData {
  id?: string | number
  title: string
  subTitle?: string
  url?: string
  showIcon?: boolean
  image?: string
  category?: number | string
  level?: number | string
  type?: number | string
  season?: string
  group?: number | string
}

interface Props {
  theArray: CardData[]
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: '3xl',
})

const emit = defineEmits<{
  cardClick: [card: CardData & { isExternal: boolean }]
}>()

const widthMap: Record<NonNullable<Props['maxWidth']>, string> = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  '3xl': 'max-w-3xl',
  '4xl': 'max-w-4xl',
  full: 'max-w-full',
}

const maxWidthClass = computed(() => widthMap[props.maxWidth])

const isExternalLink = (url: string) => {
  try {
    if (!url) return false
    if (url.startsWith('/') || url.startsWith('./') || url.startsWith('../')) {
      return false
    }
    const urlObj = url.startsWith('//') ? new URL(`https:${url}`) : new URL(url)
    return urlObj.hostname !== window.location.hostname
  } catch {
    return false
  }
}

const getIsExternal = (card: CardData) => (card.url ? isExternalLink(card.url) : false)

const handleCardClick = (card: CardData) => {
  emit('cardClick', { ...card, isExternal: getIsExternal(card) })
}

const badges = (card: CardData) => {
  const result: { label: string }[] = []

  if (card.level) result.push({ label: `Level ${card.level}` })
  if (card.type) result.push({ label: `${card.type}` })

  return result
}
</script>

<template>
  <div class="mx-auto" :class="maxWidthClass">
    <ul role="list" class="space-y-3">
      <li
        v-for="(card, index) in props.theArray"
        :key="card.id ?? index"
      >
        <div
          role="button"
          tabindex="0"
          class="group cursor-pointer rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
          @click="handleCardClick(card)"
          @keydown.enter="handleCardClick(card)"
          @keydown.space.prevent="handleCardClick(card)"
        >
          <div class="flex items-start gap-4">
            <div
              v-if="card.showIcon !== false"
              class="mt-1 flex h-8 w-8 items-center justify-center rounded-lg bg-brand-bg text-brand-primary transition-colors group-hover:bg-brand-accent group-hover:text-white"
            >
              <ArrowUpRightIcon v-if="getIsExternal(card)" class="h-4 w-4" />
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </div>

            <div class="flex-1">
              <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-accent">
                <template #myTitle>
                  <span class="transition-colors group-hover:text-brand-primary">
                    {{ card.title }}
                  </span>
                </template>
              </MyTextConstructor>

              <MyTextConstructor
                v-if="card.subTitle"
                bodyVariant="muted"
                spacing="none"
                class="mt-2 md:!text-base lg:!text-base"
              >
                {{ card.subTitle }}
              </MyTextConstructor>
            </div>
          </div>

          <div
            v-if="badges(card).length > 0"
            class="mt-4 flex flex-wrap items-center gap-2"
          >
            <div
              v-for="(badge, i) in badges(card)"
              :key="i"
              class="rounded-full bg-brand-bg px-3 py-1 text-xs font-medium text-brand-text-soft"
            >
              {{ badge.label }}
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>