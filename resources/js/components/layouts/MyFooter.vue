<!-- resources/js/components/layouts/MyFooter.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MySocials from '@/components/layouts/MySocials.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { mainNavigation, type NavigationLink } from '@/data/navigation'

interface Props {
  companyName?: string
  companyNumber?: string
  establishedYear?: number
  showSocials?: boolean
  showNavigation?: boolean
  variant?: 'default' | 'minimal' | 'solid' | 'gradient'
}

const props = withDefaults(defineProps<Props>(), {
  companyName: 'musicExams.help',
  companyNumber: '',
  establishedYear: 2026,
  showSocials: true,
  showNavigation: true,
  variant: 'default',
})

const currentYear = computed(() => new Date().getFullYear())

const copyrightText = computed(() => {
  const yearRange =
    props.establishedYear === currentYear.value
      ? String(currentYear.value)
      : `${props.establishedYear} - ${currentYear.value}`

  return props.companyNumber
    ? `© ${yearRange} ${props.companyName}. Company No. ${props.companyNumber}. All rights reserved.`
    : `© ${yearRange} ${props.companyName}. All rights reserved.`
})

const footerClasses = computed(() => {
  const variants: Record<NonNullable<Props['variant']>, string> = {
    default: 'border-t border-brand-border bg-brand-surface',
    minimal: 'bg-transparent',
    solid: 'bg-brand-primary text-white',
    gradient: 'bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary text-white',
  }

  return `py-12 ${variants[props.variant]}`
})

const isDark = computed(() => props.variant === 'solid' || props.variant === 'gradient')

const sectionTitleColor = computed(() =>
  isDark.value ? 'text-white' : 'text-brand-primary'
)

const bodyTextColor = computed(() =>
  isDark.value ? 'text-white/80' : 'text-brand-text-soft'
)

const socialIconColor = computed(() =>
  isDark.value ? 'text-white' : 'text-brand-primary'
)

const socialHoverColor = computed(() =>
  isDark.value ? 'hover:text-white/80' : 'hover:text-brand-accent'
)

/** Build href (Ziggy route first; then raw url; fallback '#') */
const hrefFor = (link: NavigationLink): string => {
  const name = link.routeName ?? link.route

  if (name) {
    try {
      return route(name, link.params ?? {}) as unknown as string
    } catch (e) {
      console.warn('Footer: invalid route', name, e)
    }
  }

  return link.url ?? '#'
}

/** Is it external? */
const isExternal = (link: NavigationLink): boolean => {
  if (link.external) return true

  const href = hrefFor(link)
  if (href.startsWith('/') || href.startsWith('./') || href.startsWith('../')) return false

  try {
    const u = new URL(href, window.location.origin)
    return u.origin !== window.location.origin
  } catch {
    return false
  }
}

/** Click handler for internal SPA nav */
const handleInternalClick = (link: NavigationLink) => {
  const href = hrefFor(link)
  if (href && href !== '#') router.get(href)
}
</script>

<template>
  <footer :class="footerClasses" aria-labelledby="footerHeading">
    <h2 id="footerHeading" class="sr-only">Footer</h2>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid gap-10 md:grid-cols-[1.4fr_1fr] md:items-start">
        <!-- Brand / copyright column -->
        <div>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            :textColor="sectionTitleColor"
            spacing="tight"
          >
            <template #myTitle>
              {{ props.companyName }}
            </template>

            <template #mySubTitle>
              Independent help for booking music exams clearly and confidently.
            </template>
          </MyTextConstructor>

          <div class="mt-6 max-w-xl">
            <MyTextConstructor
              variant="muted"
              :textColor="bodyTextColor"
              spacing="none"
            >
              <template #myTitle>
                {{ copyrightText }}
              </template>
            </MyTextConstructor>
          </div>

          <div v-if="props.showSocials" class="mt-6">
            <MySocials
              size="medium"
              :iconColor="socialIconColor"
              :hoverColor="socialHoverColor"
            />
          </div>
        </div>

        <!-- Navigation -->
        <div v-if="props.showNavigation">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            :textColor="sectionTitleColor"
            spacing="none"
          >
            <template #myTitle>
              Explore
            </template>
          </MyTextConstructor>

          <div class="mt-4 flex flex-wrap gap-2">
            <template v-for="link in mainNavigation" :key="link.name">
              <!-- External -->
              <a
                v-if="isExternal(link)"
                :href="hrefFor(link)"
                target="_blank"
                rel="noopener noreferrer"
                :class="[
                  'inline-block rounded-full border px-3 py-1.5 text-xs font-medium transition-colors sm:text-sm',
                  isDark
                    ? 'border-white/30 text-white hover:bg-white/10'
                    : 'border-brand-border text-brand-text hover:bg-brand-surface-soft'
                ]"
              >
                {{ link.name }}
              </a>

              <!-- Internal -->
              <button
                v-else-if="hrefFor(link) !== '#'"
                type="button"
                @click="handleInternalClick(link)"
                :class="[
                  'inline-block rounded-full border px-3 py-1.5 text-xs font-medium transition-colors sm:text-sm',
                  isDark
                    ? 'border-white/30 text-white hover:bg-white/10'
                    : 'border-brand-border text-brand-text hover:bg-brand-surface-soft'
                ]"
              >
                {{ link.name }}
              </button>

              <!-- Disabled/fallback -->
              <span
                v-else
                :class="[
                  'inline-block rounded-full border px-3 py-1.5 text-xs font-medium opacity-50 sm:text-sm',
                  isDark ? 'border-white/30 text-white' : 'border-brand-border text-brand-text'
                ]"
              >
                {{ link.name }}
              </span>
            </template>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>