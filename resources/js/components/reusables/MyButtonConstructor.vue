<!-- resources/js/components/reusables/MyButtonConstructor.vue -->
<script setup lang="ts">
import { computed, type Component } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  size?: 'small' | 'medium' | 'large'
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'success' | 'danger'
  icon?: Component
  iconPosition?: 'left' | 'right'
  fullWidth?: boolean
  disabled?: boolean
  disableFocusRing?: boolean
  rounded?: 'md' | 'lg' | 'xl' | 'full'
  type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'medium',
  variant: 'primary',
  icon: undefined,
  iconPosition: 'left',
  fullWidth: false,
  disabled: false,
  disableFocusRing: false,
  rounded: 'xl',
  type: 'button',
})

const emit = defineEmits<{
  (e: 'click', evt: MouseEvent): void
  (e: 'clicked'): void
}>()

const sizeClasses: Record<NonNullable<Props['size']>, string> = {
  small: 'min-h-9 px-3 py-2',
  medium: 'min-h-10 px-4 py-2.5',
  large: 'min-h-12 px-6 py-3',
}

const iconSizeClasses: Record<NonNullable<Props['size']>, string> = {
  small: 'h-4 w-4',
  medium: 'h-5 w-5',
  large: 'h-5 w-5',
}

const roundedClasses: Record<NonNullable<Props['rounded']>, string> = {
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl',
  full: 'rounded-full',
}

const variantClasses: Record<NonNullable<Props['variant']>, string> = {
  primary:
    'bg-brand-cta text-white hover:bg-brand-cta-dark border border-transparent shadow-sm',
  secondary:
    'bg-brand-primary text-white hover:bg-brand-primary-dark border border-transparent shadow-sm',
  outline:
    'bg-brand-surface text-brand-primary hover:bg-brand-bg border border-brand-border',
  ghost:
    'bg-transparent text-brand-primary hover:bg-brand-bg border border-transparent',
  success:
    'bg-brand-accent text-white hover:bg-brand-accent-dark border border-transparent shadow-sm',
  danger:
    'bg-red-600 text-white hover:bg-red-700 border border-transparent shadow-sm',
}

const buttonClasses = computed(() => [
  'inline-flex items-center justify-center gap-2',
  'font-medium transition-all duration-200',
  'disabled:cursor-not-allowed disabled:opacity-60',
  'active:scale-[0.99]',
  sizeClasses[props.size],
  roundedClasses[props.rounded],
  variantClasses[props.variant],
  props.fullWidth ? 'w-full' : '',
  !props.disableFocusRing
    ? 'focus:outline-none focus:ring-2 focus:ring-brand-accent focus:ring-offset-2'
    : '',
])

const textVariant = computed(() => {
  if (props.size === 'small') return 'button-sm'
  if (props.size === 'large') return 'button-lg'
  return 'button'
})

const handleClick = (e: MouseEvent) => {
  if (props.disabled) return
  emit('click', e)
  emit('clicked')
}
</script>

<template>
  <button
    :type="props.type"
    v-bind="$attrs"
    :disabled="props.disabled"
    :class="buttonClasses"
    @click="handleClick"
  >
    <span
      class="flex items-center justify-center gap-2"
      :class="{ 'flex-row-reverse': props.iconPosition === 'right' }"
    >
      <component
        :is="props.icon"
        v-if="props.icon"
        aria-hidden="true"
        :class="iconSizeClasses[props.size]"
      />

      <MyTextConstructor
        :variant="textVariant"
        textColor="inherit"
        alignment="center"
        spacing="none"
      >
        <template #myTitle>
          <slot />
        </template>
      </MyTextConstructor>
    </span>
  </button>
</template>