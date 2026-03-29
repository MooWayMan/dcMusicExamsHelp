<!-- resources/js/components/reusables/MyBanner.vue -->
<script setup lang="ts">
import type { Component } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface Props {
  text: string
  buttonText?: string
  buttonIcon?: Component
  buttonLink?: string
  variant?: 'default' | 'dark' | 'primary'
  rounded?: boolean
  padding?: 'tight' | 'normal' | 'loose'
}

const props = withDefaults(defineProps<Props>(), {
  buttonText: '',
  buttonIcon: undefined,
  buttonLink: '',
  variant: 'dark',
  rounded: false,
  padding: 'normal',
})

const paddingClasses: Record<NonNullable<Props['padding']>, string> = {
  tight: 'p-4',
  normal: 'p-5 sm:p-6',
  loose: 'p-6 sm:p-8',
}

const variantClasses: Record<NonNullable<Props['variant']>, string> = {
  default: 'bg-white border border-brand-border',
  dark: 'bg-brand-primary text-white',
  primary: 'bg-brand-bg border border-brand-border',
}

const textColor: Record<NonNullable<Props['variant']>, string> = {
  default: 'text-brand-primary',
  dark: 'text-white',
  primary: 'text-brand-primary',
}

const buttonVariant: Record<NonNullable<Props['variant']>, 'primary' | 'light' | 'outline'> = {
  default: 'outline',
  dark: 'light',
  primary: 'primary',
}

const handleButtonClick = () => {
  if (props.buttonLink) {
    window.open(props.buttonLink, '_blank', 'noopener,noreferrer')
  }
}
</script>

<template>
  <div
    class="my-4 flex flex-col items-center justify-between gap-4 md:flex-row"
    :class="[variantClasses[variant], paddingClasses[padding], rounded ? 'rounded-xl' : '']"
  >
    <MyTextConstructor
      variant="subheading"
      alignment="center"
      spacing="none"
      :textColor="textColor[variant]"
      class="md:text-left"
    >
      <template #myTitle>
        {{ text }}
      </template>
    </MyTextConstructor>

    <div v-if="buttonText" class="flex justify-center md:justify-end">
      <MyButtonConstructor
        :variant="buttonVariant[variant]"
        size="medium"
        :icon="buttonIcon"
        @clicked="handleButtonClick"
      >
        {{ buttonText }}
      </MyButtonConstructor>
    </div>
  </div>
</template>