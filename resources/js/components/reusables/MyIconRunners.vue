<!-- resources/js/components/reusables/MyIconRunners.vue -->
<script setup lang="ts">
import { computed, type Component } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface CardData {
  id?: string | number
  title: string
  subTitle: string
  descript: string
  icon: Component
  url?: string
  isExternal?: boolean
}

interface Props {
  theArray: CardData[]
  columns?: 1 | 2 | 3
  spacing?: 'tight' | 'normal' | 'loose'
  enableHover?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  columns: 3,
  spacing: 'normal',
  enableHover: true,
})

const emit = defineEmits<{
  cardClick: [card: CardData & { isExternal: boolean }]
}>()

const isExternalLink = (url: string): boolean => {
  try {
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

const getIsExternal = (card: CardData): boolean => {
  if (card.isExternal !== undefined) return card.isExternal
  if (card.url) return isExternalLink(card.url)
  return false
}

const handleCardClick = (card: CardData) => {
  emit('cardClick', { ...card, isExternal: getIsExternal(card) })
}

const spacingClasses = computed(() => {
  switch (props.spacing) {
    case 'tight':
      return 'gap-2 sm:gap-3 lg:gap-4'
    case 'loose':
      return 'gap-6 sm:gap-8 lg:gap-12'
    default:
      return 'gap-4 sm:gap-6 lg:gap-8'
  }
})

const gridClasses = computed(() => {
  const base = `grid w-full py-4 ${spacingClasses.value}`
  switch (props.columns) {
    case 1:
      return `${base} grid-cols-1`
    case 2:
      return `${base} grid-cols-1 sm:grid-cols-2`
    default:
      return `${base} grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`
  }
})
</script>

<template>
  <div :class="gridClasses">
    <button
      v-for="(card, index) in theArray"
      :key="card.id || index"
      type="button"
      class="mx-auto flex min-h-[280px] w-full max-w-sm flex-col items-center rounded-2xl border-[6px] border-blue-800 bg-white p-4 text-center shadow-lg transition-all duration-300"
      :class="props.enableHover ? 'hover:-translate-y-1 hover:scale-[1.02] hover:border-blue-600 hover:bg-blue-50 hover:shadow-xl' : ''"
      @click="handleCardClick(card)"
    >
      <div class="mb-4 flex justify-center">
        <component
          :is="card.icon"
          class="h-8 w-8 text-blue-500 transition-transform duration-300 sm:h-10 sm:w-10 lg:h-12 lg:w-12"
          :class="props.enableHover ? 'group-hover:scale-110' : ''"
          aria-hidden="true"
        />
      </div>

      <div class="flex flex-1 flex-col">
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
          subTitleVariant="muted"
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
          bodyVariant="muted"
          alignment="center"
          spacing="none"
          textColor="text-gray-600"
          class="mt-3"
        >
          {{ card.descript }}
        </MyTextConstructor>
      </div>
    </button>
  </div>
</template>