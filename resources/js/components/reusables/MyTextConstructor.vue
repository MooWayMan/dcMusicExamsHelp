<!-- resources/js/components/reusables/MyTextConstructor.vue -->
<script setup lang="ts">
import { computed, useSlots } from 'vue'

interface Props {
  variant?:
    | 'display'
    | 'heading'
    | 'subheading'
    | 'body'
    | 'muted'
    | 'eyebrow'
    | 'button'
    | 'button-sm'
    | 'button-lg'
  alignment?: 'left' | 'center' | 'right'
  textColor?: string
  bgColor?: string
  showUnderline?: boolean
  underlineColor?: string
  spacing?: 'none' | 'tight' | 'normal' | 'relaxed'
  fontFamily?: 'default' | 'display'
  subTitleVariant?: 'body' | 'muted' | 'subheading'
  bodyVariant?: 'body' | 'muted'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'body',
  alignment: 'left',
  textColor: '',
  bgColor: '',
  showUnderline: false,
  underlineColor: 'bg-brand-accent',
  spacing: 'normal',
  fontFamily: 'default',
  subTitleVariant: 'muted',
  bodyVariant: 'body',
})

const slots = useSlots()

const alignmentClasses = computed(
  () =>
    ({
      left: 'text-left',
      center: 'text-center',
      right: 'text-right',
    })[props.alignment]
)

const spacingClasses = computed(
  () =>
    ({
      none: 'space-y-0',
      tight: 'space-y-1',
      normal: 'space-y-2',
      relaxed: 'space-y-3',
    })[props.spacing]
)

const fontFamilyClass = computed(() =>
  props.fontFamily === 'display' ? 'font-display' : 'font-sans'
)

const variantClasses: Record<NonNullable<Props['variant']>, string> = {
  display: 'text-4xl font-bold tracking-tight text-brand-primary md:text-5xl',
  heading: 'text-2xl font-bold tracking-tight text-brand-primary md:text-3xl',
  subheading: 'text-lg font-semibold text-brand-primary md:text-xl',
  body: 'text-base leading-7 text-brand-text',
  muted: 'text-sm leading-6 text-brand-text-soft',
  eyebrow: 'text-xs font-semibold uppercase tracking-[0.2em] text-brand-accent',
  button: 'text-sm font-semibold leading-none',
  'button-sm': 'text-xs font-semibold leading-none',
  'button-lg': 'text-base font-semibold leading-none',
}

const resolvedTextColor = computed(() =>
  props.textColor && props.textColor !== 'inherit' ? props.textColor : ''
)

const titleClasses = computed(() => [
  fontFamilyClass.value,
  variantClasses[props.variant],
  resolvedTextColor.value,
  props.textColor === 'inherit' ? 'text-inherit' : '',
])

const subTitleClasses = computed(() => {
  const map = {
    body: 'text-base leading-7 text-brand-text-soft',
    muted: 'text-sm leading-6 text-brand-text-soft',
    subheading: 'text-lg font-medium text-brand-text-soft',
  } as const

  return [fontFamilyClass.value, map[props.subTitleVariant]]
})

const bodyClasses = computed(() => {
  const map = {
    body: 'text-base leading-7 text-brand-text',
    muted: 'text-sm leading-6 text-brand-text-soft',
  } as const

  return [fontFamilyClass.value, map[props.bodyVariant]]
})

const wrapperClasses = computed(() => [
  alignmentClasses.value,
  spacingClasses.value,
  props.bgColor,
])

const underlineClasses = computed(() => [
  'h-1 w-16 rounded-full',
  props.underlineColor,
  props.alignment === 'left' ? 'mx-0' : 'mx-auto',
  props.alignment === 'right' ? 'ml-auto mr-0' : '',
])

const hasEyebrowSlot = computed(() => Boolean(slots.myEyebrow))
const hasTitleSlot = computed(() => Boolean(slots.myTitle))
const hasSubTitleSlot = computed(() => Boolean(slots.mySubTitle))
const hasDefaultSlot = computed(() => Boolean(slots.default))
</script>

<template>
  <div :class="wrapperClasses">
    <div
      v-if="hasEyebrowSlot"
      class="font-sans text-xs font-semibold uppercase tracking-[0.2em] text-brand-accent"
    >
      <slot name="myEyebrow" />
    </div>

    <div v-if="hasTitleSlot" :class="titleClasses">
      <slot name="myTitle" />
    </div>

    <div v-if="showUnderline" :class="underlineClasses" />

    <div v-if="hasSubTitleSlot" :class="subTitleClasses">
      <slot name="mySubTitle" />
    </div>

    <div v-if="hasDefaultSlot" :class="bodyClasses">
      <slot />
    </div>
  </div>
</template>