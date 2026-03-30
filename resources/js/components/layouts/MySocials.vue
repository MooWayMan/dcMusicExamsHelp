<!-- resources/js/components/layouts/MySocials.vue -->
<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: 'small' | 'medium' | 'large'
  layout?: 'horizontal' | 'vertical'
  spacing?: 'tight' | 'normal' | 'loose'
  hoverEffect?: boolean
  showLabels?: boolean
  iconColor?: string
  hoverColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'medium',
  layout: 'horizontal',
  spacing: 'normal',
  hoverEffect: true,
  showLabels: false,
  iconColor: 'text-brand-primary',
  hoverColor: 'hover:text-brand-accent',
})

interface SocialLink {
  name: string
  url: string
  icon: string
  srLabel: string
}

const socialLinks: SocialLink[] = [
  {
    name: 'Facebook',
    url: 'https://www.facebook.com/moowaymusicman',
    srLabel: 'Facebook',
    icon: `<path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />`,
  },
  {
    name: 'YouTube',
    url: 'https://www.youtube.com/channel/UCw8iCxIGgdaximWrb9iUBKw',
    srLabel: 'YouTube',
    icon: `<path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>`,
  },
  {
    name: 'X (Twitter)',
    url: 'https://twitter.com/MooWayMan',
    srLabel: 'X',
    icon: `<path d="M14.095479,10.316482L22.286354,1h-1.940718l-7.115352,8.087682L7.551414,1H1l8.589488,12.231093L1,23h1.940717l7.509372-8.542861L16.448587,23H23L14.095479,10.316482z M11.436522,13.338465l-0.871624-1.218704l-6.924311-9.68815h2.981339l5.58978,7.82155l0.867949,1.218704l7.26506,10.166271h-2.981339L11.436522,13.338465z"/>`,
  },
]

const iconSizes: Record<NonNullable<Props['size']>, string> = {
  small: 'h-5 w-5',
  medium: 'h-6 w-6',
  large: 'h-8 w-8',
}

const containerClasses = computed(() => {
  const layoutMap: Record<
    NonNullable<Props['layout']>,
    Record<NonNullable<Props['spacing']>, string>
  > = {
    horizontal: {
      tight: 'flex flex-row items-center gap-2',
      normal: 'flex flex-row items-center gap-4',
      loose: 'flex flex-row items-center gap-6',
    },
    vertical: {
      tight: 'flex flex-col items-start gap-2',
      normal: 'flex flex-col items-start gap-3',
      loose: 'flex flex-col items-start gap-5',
    },
  }

  return layoutMap[props.layout][props.spacing]
})

const linkClasses = computed(() => [
  'group relative inline-flex items-center transition-all duration-200',
  props.iconColor,
  props.hoverEffect ? props.hoverColor : '',
  props.showLabels ? 'gap-2' : '',
])

const iconSize = computed(() => iconSizes[props.size])
</script>

<template>
  <div :class="containerClasses">
    <a
      v-for="social in socialLinks"
      :key="social.name"
      :href="social.url"
      target="_blank"
      rel="noopener noreferrer"
      :class="linkClasses"
      :aria-label="`Visit our ${social.name} page`"
    >
      <span class="sr-only">{{ social.srLabel }}</span>

      <svg
        :class="[
          iconSize,
          'transition-transform duration-200',
          hoverEffect ? 'group-hover:-translate-y-0.5' : '',
        ]"
        fill="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <g v-html="social.icon"></g>
      </svg>

      <span
        v-if="!showLabels"
        class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-md bg-brand-text px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100"
      >
        {{ social.name }}
      </span>

      <span
        v-if="showLabels"
        class="text-sm font-medium"
      >
        {{ social.name }}
      </span>
    </a>
  </div>
</template>

<style scoped>
a:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 4px;
  border-radius: 0.5rem;
}
</style>