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
  fontFamily?: 'default' | 'display' | 'asap-condensed'
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

const alignmentClasses = computed(() => {
  return {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }[props.alignment]
})

const spacingClasses = computed(() => {
  return {
    none: 'space-y-0',
    tight: 'space-y-1',
    normal: 'space-y-2',
    relaxed: 'space-y-4',
  }[props.spacing]
})

const fontFamilyClass = computed(() => {
  return {
    default: 'font-sans',
    display: 'font-display',
    'asap-condensed': 'font-asap-condensed',
  }[props.fontFamily]
})

const variantClasses: Record<NonNullable<Props['variant']>, string> = {
  display: 'text-5xl font-bold tracking-tight text-brand-primary md:text-6xl lg:text-7xl',
  heading: 'text-4xl font-bold tracking-tight text-brand-primary md:text-5xl',
  subheading: 'text-2xl font-semibold text-brand-primary md:text-3xl',
  body: 'text-xl leading-9 text-brand-text',
  muted: 'text-lg leading-8 text-brand-text-soft',
  eyebrow: 'text-sm font-semibold uppercase tracking-[0.08em] text-blue-700',
  button: 'text-base font-semibold leading-none',
  'button-sm': 'text-sm font-semibold leading-none',
  'button-lg': 'text-lg font-semibold leading-none',
}

const resolvedTextColor = computed(() => {
  return props.textColor && props.textColor !== 'inherit' ? props.textColor : ''
})

const titleClasses = computed(() => {
  return [
    fontFamilyClass.value,
    variantClasses[props.variant],
    props.textColor === 'inherit' ? '!text-inherit' : '',
    resolvedTextColor.value,
  ]
})

const subTitleClasses = computed(() => {
  const map = {
    body: 'text-xl leading-9 text-brand-text-soft',
    muted: 'text-lg leading-8 text-brand-text-soft',
    subheading: 'text-2xl font-medium text-brand-text-soft',
  } as const

  return [
    fontFamilyClass.value,
    map[props.subTitleVariant],
    props.textColor === 'inherit' ? '!text-inherit' : '',
    resolvedTextColor.value,
  ]
})

const bodyClasses = computed(() => {
  const map = {
    body: 'text-xl leading-9 text-brand-text',
    muted: 'text-lg leading-8 text-brand-text-soft',
  } as const

  return [
    fontFamilyClass.value,
    map[props.bodyVariant],
    props.textColor === 'inherit' ? '!text-inherit' : '',
    resolvedTextColor.value,
  ]
})

const wrapperClasses = computed(() => {
  return [
    alignmentClasses.value,
    spacingClasses.value,
    props.bgColor,
  ]
})

const underlineClasses = computed(() => {
  return [
    'h-1 w-16 rounded-full',
    props.underlineColor,
    props.alignment === 'left' ? 'mx-0' : 'mx-auto',
    props.alignment === 'right' ? 'ml-auto mr-0' : '',
  ]
})

const hasEyebrowSlot = computed(() => Boolean(slots.myEyebrow))
const hasTitleSlot = computed(() => Boolean(slots.myTitle))
const hasSubTitleSlot = computed(() => Boolean(slots.mySubTitle))
const hasDefaultSlot = computed(() => Boolean(slots.default))
</script>

<template>
  <div :class="wrapperClasses">
    <div
      v-if="hasEyebrowSlot"
      :class="[fontFamilyClass, variantClasses.eyebrow]"
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