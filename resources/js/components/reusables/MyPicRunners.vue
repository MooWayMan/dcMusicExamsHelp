<!-- resources/js/components/reusables/MyPicRunners.vue -->
<script setup lang="ts">
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface CardData {
  id?: string | number
  title?: string
  subTitle?: string
  url?: string
  image?: string
  paragraph?: string
  header?: string
  footer?: string
  headerBgColor?: string
  headerTextColor?: string
  footerBgColor?: string
  footerTextColor?: string
}

interface Props {
  theArray: CardData[]
  columns?: 1 | 2 | 3 | 4
  showHeader?: boolean
  showFooter?: boolean
  enableHover?: boolean
  spacing?: 'tight' | 'normal' | 'loose'
}

const props = withDefaults(defineProps<Props>(), {
  columns: 3,
  showHeader: true,
  showFooter: true,
  enableHover: true,
  spacing: 'normal',
})

const emit = defineEmits<{
  cardClick: [card: CardData & { isExternal: boolean }]
}>()

const isExternalLink = (url: string): boolean => {
  try {
    if (!url) return false
    if (url.startsWith('/') || url.startsWith('./') || url.startsWith('../')) return false
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return new URL(url).hostname !== window.location.hostname
    }
    if (url.startsWith('//')) {
      return new URL(`https:${url}`).hostname !== window.location.hostname
    }
    return false
  } catch {
    return false
  }
}

const handleCardClick = (card: CardData) => {
  emit('cardClick', { ...card, isExternal: isExternalLink(card.url || '') })
}

const gridClassMap: Record<NonNullable<Props['columns']>, string> = {
  1: 'grid-cols-1',
  2: 'grid-cols-1 sm:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}

const gapClassMap: Record<NonNullable<Props['spacing']>, string> = {
  tight: 'gap-3',
  normal: 'gap-4 sm:gap-6',
  loose: 'gap-6 sm:gap-8',
}
</script>

<template>
  <div class="grid justify-center" :class="[gridClassMap[props.columns], gapClassMap[props.spacing]]">
    <div
      v-for="card in theArray"
      :key="card.id"
      class="flex w-full justify-center"
    >
      <button
        type="button"
        class="group flex h-full w-full max-w-md flex-col overflow-hidden rounded-2xl border-2 border-gray-200 bg-white shadow-md transition-all duration-300"
        :class="props.enableHover ? 'hover:-translate-y-1 hover:shadow-xl' : ''"
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
          class="relative aspect-video w-full overflow-hidden bg-gray-200"
        >
          <img
            :src="card.image"
            :alt="card.title || ''"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            loading="lazy"
          />
        </div>

        <div class="flex flex-1 flex-col justify-between p-4 sm:p-5">
          <div class="space-y-3 text-left">
            <MyTextConstructor
              v-if="card.title"
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
    </div>
  </div>
</template>