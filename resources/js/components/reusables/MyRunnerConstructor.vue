<!-- resources/js/components/reusables/MyRunnerConstructor.vue -->
<script setup lang="ts">
import { computed, type Component } from 'vue'
import { ArrowUpRightIcon } from '@heroicons/vue/20/solid'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface RunnerItem {
  id?: string | number
  title: string
  subTitle?: string
  descript?: string
  paragraph?: string
  url?: string
  icon?: Component
  image?: string
  header?: string
  footer?: string
  showIcon?: boolean
  isExternal?: boolean
  level?: number | string
  type?: number | string
  headerBgColor?: string
  headerTextColor?: string
  footerBgColor?: string
  footerTextColor?: string
}

interface Props {
  theArray: RunnerItem[]
  variant?: 'text' | 'icon' | 'image'
  columns?: 1 | 2 | 3 | 4
  spacing?: 'tight' | 'normal' | 'loose'
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | 'full'
  enableHover?: boolean
  showHeader?: boolean
  showFooter?: boolean
  imageAspect?: 'video' | 'square' | 'auto'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'text',
  columns: 3,
  spacing: 'normal',
  maxWidth: 'full',
  enableHover: true,
  showHeader: true,
  showFooter: true,
  imageAspect: 'video',
})

const emit = defineEmits<{
  cardClick: [card: RunnerItem & { isExternal: boolean }]
}>()

const isExternalLink = (url: string): boolean => {
  try {
    if (!url) return false
    if (url.startsWith('/') || url.startsWith('./') || url.startsWith('../')) return false
    const urlObj = url.startsWith('//') ? new URL(`https:${url}`) : new URL(url)
    return typeof window !== 'undefined' ? urlObj.hostname !== window.location.hostname : true
  } catch {
    return false
  }
}

const getIsExternal = (card: RunnerItem): boolean => {
  if (card.isExternal !== undefined) return card.isExternal
  if (card.url) return isExternalLink(card.url)
  return false
}

const handleCardClick = (card: RunnerItem) => {
  emit('cardClick', { ...card, isExternal: getIsExternal(card) })
}

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

const gridColumns = computed(() => {
  switch (props.columns) {
    case 1:
      return 'grid-cols-1'
    case 2:
      return 'grid-cols-1 sm:grid-cols-2'
    case 3:
      return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
    case 4:
      return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'
    default:
      return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
  }
})

const gapClass = computed(() => {
  switch (props.spacing) {
    case 'tight':
      return 'gap-3'
    case 'loose':
      return 'gap-6 sm:gap-8'
    default:
      return 'gap-4 sm:gap-6'
  }
})

const wrapperClass = computed(() => widthMap[props.maxWidth])

const textCardClass = computed(() => [
  'group cursor-pointer rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm transition-all duration-200',
  props.enableHover ? 'hover:-translate-y-1 hover:shadow-lg' : '',
])

const iconCardClass = computed(() => [
  'group cursor-pointer rounded-2xl border-[4px] border-blue-800 bg-white p-5 shadow-lg transition-all duration-300',
  props.enableHover ? 'hover:-translate-y-1 hover:scale-[1.02] hover:border-blue-600 hover:bg-blue-50 hover:shadow-xl' : '',
])

const imageCardClass = computed(() => [
  'group cursor-pointer overflow-hidden rounded-2xl border border-brand-border bg-white shadow-md transition-all duration-300',
  props.enableHover ? 'hover:-translate-y-1 hover:shadow-xl' : '',
])

const aspectClass = computed(() => {
  switch (props.imageAspect) {
    case 'square':
      return 'aspect-square'
    case 'auto':
      return ''
    default:
      return 'aspect-video'
  }
})

const badges = (card: RunnerItem) => {
  const result: { label: string }[] = []
  if (card.level) result.push({ label: `Level ${card.level}` })
  if (card.type) result.push({ label: `${card.type}` })
  return result
}
</script>

<template>
  <div class="mx-auto" :class="wrapperClass">
    <div class="grid" :class="[gridColumns, gapClass]">
      <template v-for="(card, index) in props.theArray" :key="card.id ?? index">
        <!-- TEXT VARIANT -->
        <div
          v-if="props.variant === 'text'"
          role="button"
          tabindex="0"
          :class="textCardClass"
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
              <component
                :is="card.icon"
                v-else-if="card.icon"
                class="h-4 w-4"
                aria-hidden="true"
              />
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </div>

            <div class="flex-1">
              <MyTextConstructor variant="subheading" spacing="tight">
                <template #myTitle>
                  <span class="transition-colors group-hover:text-brand-accent">
                    {{ card.title }}
                  </span>
                </template>
              </MyTextConstructor>

              <MyTextConstructor
                v-if="card.subTitle"
                subTitleVariant="muted"
                spacing="none"
                class="mt-2"
              >
                <template #mySubTitle>
                  {{ card.subTitle }}
                </template>
              </MyTextConstructor>
            </div>
          </div>

          <div v-if="badges(card).length > 0" class="mt-4 flex flex-wrap gap-2">
            <div
              v-for="(badge, badgeIndex) in badges(card)"
              :key="badgeIndex"
              class="rounded-full bg-brand-bg px-3 py-1 text-xs font-medium text-brand-text-soft"
            >
              {{ badge.label }}
            </div>
          </div>
        </div>

        <!-- ICON VARIANT -->
        <button
          v-else-if="props.variant === 'icon'"
          type="button"
          :class="iconCardClass"
          @click="handleCardClick(card)"
        >
          <div class="mb-4 flex justify-center">
            <component
              v-if="card.icon"
              :is="card.icon"
              class="h-10 w-10 text-blue-500 transition-transform duration-300 group-hover:scale-110"
              aria-hidden="true"
            />
          </div>

          <MyTextConstructor
            variant="subheading"
            alignment="center"
            spacing="tight"
            textColor="text-black"
          >
            <template #myTitle>
              {{ card.title }}
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            v-if="card.subTitle"
            subTitleVariant="subheading"
            alignment="center"
            spacing="none"
            textColor="text-blue-700"
            class="mt-3"
          >
            <template #mySubTitle>
              {{ card.subTitle }}
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            v-if="card.descript"
            bodyVariant="muted"
            alignment="center"
            spacing="none"
            textColor="text-gray-600"
            class="mt-3"
          >
            {{ card.descript }}
          </MyTextConstructor>
        </button>

        <!-- IMAGE VARIANT -->
        <button
          v-else
          type="button"
          :class="imageCardClass"
          @click="handleCardClick(card)"
        >
          <div
            v-if="props.showHeader && card.header"
            class="px-4 py-2 text-center"
            :class="card.headerBgColor ?? 'bg-gradient-to-r from-blue-700 via-blue-900 to-blue-700'"
          >
            <MyTextConstructor
              variant="button"
              alignment="center"
              spacing="none"
              :textColor="card.headerTextColor ?? 'text-white'"
            >
              <template #myTitle>
                {{ card.header }}
              </template>
            </MyTextConstructor>
          </div>

          <div
            v-if="card.image"
            class="relative w-full overflow-hidden bg-gray-200"
            :class="aspectClass"
          >
            <img
              :src="card.image"
              :alt="card.title"
              class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              loading="lazy"
            />
          </div>

          <div class="flex flex-1 flex-col justify-between p-4 sm:p-5">
            <div class="space-y-3 text-left">
              <MyTextConstructor
                variant="subheading"
                alignment="left"
                spacing="tight"
                textColor="text-brand-primary"
              >
                <template #myTitle>
                  {{ card.title }}
                </template>
              </MyTextConstructor>

              <MyTextConstructor
                v-if="card.subTitle"
                subTitleVariant="muted"
                alignment="left"
                spacing="none"
                textColor="text-brand-text"
              >
                <template #mySubTitle>
                  {{ card.subTitle }}
                </template>
              </MyTextConstructor>

              <MyTextConstructor
                v-if="card.paragraph"
                bodyVariant="muted"
                alignment="left"
                spacing="none"
                textColor="text-brand-text-soft"
              >
                {{ card.paragraph }}
              </MyTextConstructor>
            </div>
          </div>

          <div
            v-if="props.showFooter && card.footer"
            class="px-4 py-2 text-center"
            :class="card.footerBgColor ?? 'bg-gradient-to-r from-gray-600 to-gray-900'"
          >
            <MyTextConstructor
              variant="button"
              alignment="center"
              spacing="none"
              :textColor="card.footerTextColor ?? 'text-white'"
            >
              <template #myTitle>
                {{ card.footer }}
              </template>
            </MyTextConstructor>
          </div>
        </button>
        
      </template>
    </div>
  </div>
</template>

<style scoped>
.aspect-video {
  aspect-ratio: 16 / 9;
}

.aspect-square {
  aspect-ratio: 1 / 1;
}

@supports not (aspect-ratio: 16 / 9) {
  .aspect-video {
    position: relative;
    height: 0;
    padding-bottom: 56.25%;
  }

  .aspect-video img {
    position: absolute;
    inset: 0;
  }
}

@supports not (aspect-ratio: 1 / 1) {
  .aspect-square {
    position: relative;
    height: 0;
    padding-bottom: 100%;
  }

  .aspect-square img {
    position: absolute;
    inset: 0;
  }
}
</style>