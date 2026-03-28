<!-- resources/js/components/reusables/PageHeader.vue -->
<script setup lang="ts">
import { computed, useSlots } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  title: string
  subtitle?: string
  eyebrow?: string
  icon?: string
  showIcon?: boolean
  centerAlign?: boolean
  surface?: 'default' | 'solid' | 'minimal'
  size?: 'default' | 'compact' | 'hero'
  contained?: boolean
  showUnderline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  subtitle: '',
  eyebrow: '',
  icon: '',
  showIcon: false,
  centerAlign: false,
  surface: 'default',
  size: 'default',
  contained: true,
  showUnderline: false,
})

const slots = useSlots()

const alignment = computed(() => (props.centerAlign ? 'center' : 'left'))

const wrapperClasses = computed(() => {
  const base = 'w-full overflow-hidden'
  const variants: Record<NonNullable<Props['surface']>, string> = {
    default: 'border-b border-brand-border bg-brand-bg',
    solid: 'bg-brand-primary',
    minimal: 'bg-transparent',
  }

  return [base, variants[props.surface]]
})

const containerClasses = computed(() => {
  const width = props.contained ? 'mx-auto max-w-7xl' : 'w-full'
  const sizeMap: Record<NonNullable<Props['size']>, string> = {
    compact: 'px-4 py-6 sm:px-6 lg:px-8 lg:py-8',
    default: 'px-4 py-8 sm:px-6 lg:px-8 lg:py-10',
    hero: 'px-4 py-12 sm:px-6 lg:px-8 lg:py-16',
  }

  return [width, sizeMap[props.size]]
})

const contentWidthClasses = computed(() => {
  if (props.size === 'hero') return 'max-w-4xl'
  return 'max-w-3xl'
})

const titleVariant = computed(() => {
  if (props.size === 'compact') return 'heading'
  return 'display'
})

const titleColor = computed(() =>
  props.surface === 'solid' ? 'text-white' : 'text-brand-primary'
)

const subtitleColor = computed(() =>
  props.surface === 'solid' ? 'text-white/80' : 'text-brand-text-soft'
)

const eyebrowColor = computed(() =>
  props.surface === 'solid' ? 'text-white/80' : 'text-brand-accent'
)

const iconWrapperClasses = computed(() =>
  props.surface === 'solid'
    ? 'bg-white/10 text-white ring-1 ring-white/20'
    : 'bg-brand-surface text-brand-primary ring-1 ring-brand-border shadow-sm'
)

// const titleSpacing = computed(() => (props.subtitle ? 'tight' : 'none'))
const hasActions = computed(() => Boolean(slots.actions))
</script>

<template>
  <div :class="wrapperClasses">
    <div :class="containerClasses">
      <div :class="centerAlign ? 'text-center' : 'text-left'">
        <div
          v-if="showIcon && icon"
          class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl"
          :class="iconWrapperClasses"
        >
          <svg
            class="h-7 w-7"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path :d="icon" />
          </svg>
        </div>

        <div :class="[contentWidthClasses, centerAlign ? 'mx-auto' : '']">
          <MyTextConstructor
            :variant="titleVariant"
            :alignment="alignment"
            :text-color="titleColor"
            :show-underline="showUnderline"
            spacing="none"
            font-family="display"
          >
            <template #myEyebrow v-if="eyebrow">
              <span :class="eyebrowColor">{{ eyebrow }}</span>
            </template>

            <template #myTitle>
              {{ title }}
            </template>
          </MyTextConstructor>

          <div
            v-if="subtitle"
            class="mt-3 text-base leading-7 md:text-lg"
            :class="subtitleColor"
          >
            {{ subtitle }}
          </div>
        </div>

        <div v-if="hasActions" class="mt-6">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </div>
</template>