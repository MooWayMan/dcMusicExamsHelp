<!-- resources/js/components/layouts/Navbar.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/20/solid'

import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MySocials from '@/components/layouts/MySocials.vue'
import { mainNavigation, type NavigationLink } from '@/data/navigation'

interface Props {
  fixed?: boolean
  brandName?: string
  homeRouteName?: string
  showSocials?: boolean
  logoSrc?: string
  logoAlt?: string
}

const props = withDefaults(defineProps<Props>(), {
  fixed: true,
  brandName: 'musicexams.help',
  homeRouteName: 'home',
  showSocials: true,
  logoSrc: '',
  logoAlt: 'musicexams.help',
})

const isOpen = ref(false)
const navigation: NavigationLink[] = mainNavigation

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const goHome = () => {
  try {
    router.get(route(props.homeRouteName))
  } catch {
    router.get('/')
  }
}

const hrefFor = (item: NavigationLink): string => {
  const routeName = item.routeName ?? item.route

  if (routeName) {
    try {
      return route(routeName, item.params ?? {}) as unknown as string
    } catch (e) {
      console.warn('Navbar: invalid route', routeName, e)
    }
  }

  return item.url ?? '#'
}

const isExternal = (item: NavigationLink): boolean => {
  if (item.external) return true

  const href = hrefFor(item)

  if (href.startsWith('/') || href.startsWith('./') || href.startsWith('../')) {
    return false
  }

  try {
    const u = new URL(href, window.location.origin)
    return u.origin !== window.location.origin
  } catch {
    return false
  }
}

const handleNavClick = (item: NavigationLink) => {
  const href = hrefFor(item)
  if (href && href !== '#') {
    router.get(href)
    isOpen.value = false
  }
}

const navWrapperClasses = computed(() => [
  'z-40 w-full border-b border-brand-border bg-brand-surface/95 backdrop-blur shadow-sm',
  props.fixed ? 'fixed top-0' : 'relative',
])

const desktopNavVisible = computed(() => navigation.length > 0)
</script>

<template>
  <nav :class="navWrapperClasses">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-18 items-center justify-between gap-4 py-3">
        <!-- Left: Brand -->
        <button
          type="button"
          @click="goHome"
          class="flex shrink-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-accent focus:ring-offset-2"
        >
          <div
            class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-brand-border bg-brand-primary text-white shadow-sm"
          >
            <img
              v-if="props.logoSrc"
              :src="props.logoSrc"
              :alt="props.logoAlt"
              class="h-full w-full object-cover"
            />
            <span
              v-else
              class="font-display text-lg font-semibold tracking-tight"
            >
              M
            </span>
          </div>

          <div class="hidden sm:block text-left">
            <MyTextConstructor
              variant="subheading"
              fontFamily="display"
              textColor="text-brand-primary"
              spacing="none"
            >
              <template #myTitle>{{ props.brandName }}</template>
            </MyTextConstructor>

            <div class="text-xs uppercase tracking-[0.2em] text-brand-text-soft">
              Trinity booking help
            </div>
          </div>
        </button>

        <!-- Desktop Nav -->
        <div
          v-if="desktopNavVisible"
          class="hidden items-center gap-2 md:flex"
        >
          <template
            v-for="item in navigation"
            :key="item.name"
          >
            <a
              v-if="isExternal(item)"
              :href="hrefFor(item)"
              target="_blank"
              rel="noopener noreferrer"
            >
              <MyButtonConstructor
                variant="ghost"
                size="small"
                rounded="full"
              >
                {{ item.name }}
              </MyButtonConstructor>
            </a>

            <button
              v-else
              type="button"
              @click="handleNavClick(item)"
            >
              <MyButtonConstructor
                variant="ghost"
                size="small"
                rounded="full"
              >
                {{ item.name }}
              </MyButtonConstructor>
            </button>
          </template>
        </div>

        <!-- Right: Socials + Mobile toggle -->
        <div class="flex items-center gap-3">
          <div
            v-if="props.showSocials"
            class="hidden lg:flex"
          >
            <MySocials
              size="small"
              iconColor="text-brand-primary"
              hoverColor="hover:text-brand-accent"
            />
          </div>

          <button
            type="button"
            @click="toggleMenu"
            class="inline-flex items-center justify-center rounded-xl border border-brand-border bg-brand-surface p-2 text-brand-primary transition-colors hover:bg-brand-bg focus:outline-none focus:ring-2 focus:ring-brand-accent focus:ring-offset-2 md:hidden"
            :aria-expanded="isOpen"
            aria-controls="mobile-menu"
          >
            <span class="sr-only">Open main menu</span>
            <Bars3Icon
              v-if="!isOpen"
              class="h-6 w-6"
            />
            <XMarkIcon
              v-else
              class="h-6 w-6"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div
      v-if="isOpen"
      id="mobile-menu"
      class="border-t border-brand-border bg-brand-surface md:hidden"
    >
      <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3">
          <template
            v-for="item in navigation"
            :key="item.name"
          >
            <a
              v-if="isExternal(item)"
              :href="hrefFor(item)"
              target="_blank"
              rel="noopener noreferrer"
            >
              <MyButtonConstructor
                variant="outline"
                size="medium"
                rounded="xl"
                fullWidth
              >
                {{ item.name }}
              </MyButtonConstructor>
            </a>

            <button
              v-else
              type="button"
              @click="handleNavClick(item)"
            >
              <MyButtonConstructor
                variant="outline"
                size="medium"
                rounded="xl"
                fullWidth
              >
                {{ item.name }}
              </MyButtonConstructor>
            </button>
          </template>

          <div
            v-if="props.showSocials"
            class="pt-2"
          >
            <MySocials
              size="small"
              iconColor="text-brand-primary"
              hoverColor="hover:text-brand-accent"
            />
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>