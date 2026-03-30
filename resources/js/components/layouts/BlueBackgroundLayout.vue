<!-- resources/js/components/layouts/BlueBackgroundLayout.vue -->
<script setup lang="ts">
/**
 * Layout wrapper with background + overlay
 * Now uses brand tokens instead of hardcoded blue
 */

interface Props {
  topMargin?: string
  bottomPadding?: string

  /** Background style */
  variant?: 'primary' | 'subtle' | 'minimal'

  /** Overlay height */
  overlayHeight?: string
  overlayHeightLg?: string

  /** Contain content width */
  contained?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  topMargin: 'mt-0',
  bottomPadding: 'pb-12 lg:pb-20',
  variant: 'primary',
  overlayHeight: 'h-5/6',
  overlayHeightLg: 'lg:h-2/3',
  contained: true,
})

/**
 * Variant styles
 */
const variantStyles: Record<NonNullable<Props['variant']>, {
  bg: string
  overlay: string
}> = {
  primary: {
    bg: 'bg-brand-primary',
    overlay: 'bg-brand-bg',
  },
  subtle: {
    bg: 'bg-brand-bg',
    overlay: 'bg-brand-surface',
  },
  minimal: {
    bg: 'bg-transparent',
    overlay: 'bg-transparent',
  },
}
</script>

<template>
  <!-- Base section -->
  <div
    :class="[
      props.topMargin,
      props.bottomPadding,
      variantStyles[props.variant].bg,
    ]"
  >
    <div class="relative z-0">
      <!-- Overlay -->
      <div
        v-if="props.variant !== 'minimal'"
        :class="[
          'absolute inset-0',
          props.overlayHeight,
          props.overlayHeightLg,
          variantStyles[props.variant].overlay,
        ]"
      />

      <!-- Content -->
      <div
        :class="[
          'relative z-10',
          props.contained ? 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' : 'w-full px-2 sm:px-4 lg:px-6'
        ]"
      >
        <slot />
      </div>
    </div>
  </div>
</template>