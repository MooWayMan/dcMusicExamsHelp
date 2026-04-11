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
    url: 'https://www.facebook.com/musicexams.help',
    srLabel: 'Facebook',
    icon: `<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>`,
  },
  {
    name: 'Instagram',
    url: 'https://www.instagram.com/musicexams.help',
    srLabel: 'Instagram',
    icon: `<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>`,
  },
  {
    name: 'TikTok',
    url: 'https://www.tiktok.com/@musicexamshelp',
    srLabel: 'TikTok',
    icon: `<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>`,
  },
  {
    name: 'LinkedIn',
    url: 'https://www.linkedin.com/company/musicexams-help',
    srLabel: 'LinkedIn',
    icon: `<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>`,
  },
  {
    name: 'YouTube',
    url: 'https://www.youtube.com/@TrinityExamsLiverpool_UK',
    srLabel: 'YouTube',
    icon: `<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814z"/><path fill="white" d="M9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>`,
  },
  {
    name: 'X (Twitter)',
    url: 'https://x.com/musicExamsHelp',
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