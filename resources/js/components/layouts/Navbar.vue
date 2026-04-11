<!-- resources/js/components/layouts/Navbar.vue -->
<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { Bars3Icon, XMarkIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MySocials from '@/components/layouts/MySocials.vue'
import BookingModal from '@/components/BookingModal.vue'

interface Props {
  fixed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fixed: true,
})

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => user.value?.role === 'admin')

const isOpen = ref(false)

/* Lock body scroll when mobile nav is open */
watch(isOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})
const openDropdown = ref<string | null>(null)
const openMobileDropdown = ref<string | null>(null)
const showBookingModal = ref(false)

const currentPath = computed(() => page.url)

interface NavChild {
  name: string
  href: string
  highlight?: boolean
}

interface NavItem {
  name: string
  href: string
  show: boolean
  children?: NavChild[]
}

const navigation = computed<NavItem[]>(() => {
  const isHome = currentPath.value === '/'
  return [
    { name: 'Home', href: '/', show: !isHome },
    {
      name: 'More',
      href: '#more',
      show: true,
      children: [
        { name: 'For Teachers', href: '/for-teachers' },
        { name: 'Faber Discounts', href: '/for-teachers/faber-discounts' },
        { name: 'Teacher Awards', href: '/for-teachers/awards' },
        { name: 'For Parents', href: '/for-parents' },
        { name: 'For Students', href: '/for-students' },
        { name: '---', href: '#divider-thankyou' },
        { name: '★ Recognition (click here)', href: '/recognition', highlight: true },
        { name: 'Incentives', href: '/incentives' },
        { name: '---', href: '#divider-1' },
        { name: 'Exam Guide', href: '/exam-guide' },
        { name: 'Grades Explained', href: '/exam-guide/grades-explained' },
        { name: 'What to Expect', href: '/exam-guide/what-to-expect' },
        { name: 'Digital Exams', href: '/exam-guide/digital-exams' },
        { name: 'UCAS Points', href: '/exam-guide/ucas-points' },
        { name: 'Syllabuses', href: '/exam-guide/syllabuses' },
        { name: '---', href: '#divider-2' },
        { name: 'Fees & Dates', href: '/exam-fees' },
        { name: 'About', href: '/about' },
        { name: 'FAQ', href: '/faq' },
        { name: 'Contact Us', href: '/contact' },
      ],
    },
  ].filter(item => item.show)
})

const toggleDropdown = (name: string) => {
  openDropdown.value = openDropdown.value === name ? null : name
}

const closeDropdowns = () => {
  openDropdown.value = null
}

const toggleMobileDropdown = (name: string) => {
  openMobileDropdown.value = openMobileDropdown.value === name ? null : name
}

const navigateTo = (href: string) => {
  isOpen.value = false
  openDropdown.value = null
  openMobileDropdown.value = null

  if (href.startsWith('#')) {
    const el = document.querySelector(href)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' })
    } else {
      router.visit('/' + href)
    }
    return
  }

  router.visit(href)
}

/* Close dropdown when clicking outside */
const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('[data-dropdown]')) {
    closeDropdowns()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'
const navIcon = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/FAVICONmusicexamshelp_logo2+(512+x+512+px)_2.png'

const navClasses = computed(() =>
  props.fixed
    ? 'fixed top-0 z-50 w-full border-b border-slate-200 bg-white'
    : 'sticky top-0 z-50 border-b border-slate-200 bg-white'
)
</script>

<template>
  <nav :class="navClasses">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between">
        <Link href="/" class="flex shrink-0 items-center">
          <!-- Small icon on mobile -->
          <img
            :src="navIcon"
            alt="musicExams.help"
            class="h-10 w-10 rounded-xl sm:hidden"
          />
          <!-- Full wordmark on larger screens -->
          <img
            :src="brandWordmark"
            alt="musicExams.help"
            class="hidden h-14 w-auto sm:block xl:h-20"
          />
        </Link>

        <!-- DESKTOP NAV -->
        <div class="hidden items-center gap-6 lg:flex">
          <template v-for="item in navigation" :key="item.name">
            <!-- Item WITH dropdown -->
            <div v-if="item.children" class="relative" data-dropdown>
              <button
                class="flex items-center gap-1 transition hover:opacity-70"
                @click="toggleDropdown(item.name)"
              >
                <MyTextConstructor
                  variant="button"
                  textColor="text-slate-700"
                  spacing="none"
                >
                  <template #myTitle>{{ item.name }}</template>
                </MyTextConstructor>
                <ChevronDownIcon
                  class="h-4 w-4 text-slate-500 transition-transform duration-200"
                  :class="{ 'rotate-180': openDropdown === item.name }"
                />
              </button>

              <!-- Dropdown panel -->
              <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
              >
                <div
                  v-if="openDropdown === item.name"
                  class="absolute left-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-brand-border bg-white shadow-xl ring-1 ring-black/5"
                >
                  <!-- Link to parent page (skip for More since it's not a real page) -->
                  <template v-if="!item.href.startsWith('#')">
                    <button
                      class="block w-full px-4 py-3 text-left text-sm font-semibold text-brand-accent transition hover:bg-brand-surface-soft"
                      @click="navigateTo(item.href)"
                    >
                      {{ item.name }} overview
                    </button>
                    <div class="border-t border-brand-border"></div>
                  </template>
                  <template v-for="child in item.children" :key="child.name">
                    <div v-if="child.name === '---'" class="border-t border-brand-border"></div>
                    <button
                      v-else
                      class="block w-full px-4 py-3 text-left text-sm transition hover:bg-brand-surface-soft hover:text-brand-accent"
                      :class="[
                        child.href.includes('/exam-guide/') || child.href.includes('/for-teachers/') ? 'pl-8 text-xs' : '',
                        child.highlight ? 'font-bold text-brand-accent' : 'text-slate-700'
                      ]"
                      @click="navigateTo(child.href)"
                    >
                      {{ child.name }}
                    </button>
                  </template>
                </div>
              </Transition>
            </div>

            <!-- Item WITHOUT dropdown -->
            <button
              v-else
              class="transition hover:opacity-70"
              @click="navigateTo(item.href)"
            >
              <MyTextConstructor
                variant="button"
                textColor="text-slate-700"
                spacing="none"
              >
                <template #myTitle>{{ item.name }}</template>
              </MyTextConstructor>
            </button>
          </template>

          <!-- Auth links -->
          <Link v-if="isAdmin" href="/admin" class="transition hover:opacity-70">
            <MyTextConstructor variant="button" textColor="text-brand-accent" spacing="none">
              <template #myTitle>Admin</template>
            </MyTextConstructor>
          </Link>
          <Link v-else-if="user" href="/dashboard" class="transition hover:opacity-70">
            <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
              <template #myTitle>Dashboard</template>
            </MyTextConstructor>
          </Link>
          <Link v-else href="/login" class="transition hover:opacity-70">
            <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
              <template #myTitle>Sign In</template>
            </MyTextConstructor>
          </Link>

          <!-- Social icons (desktop only) -->
          <MySocials
            size="small"
            spacing="tight"
            iconColor="text-slate-400"
            hoverColor="hover:text-brand-accent"
          />

          <MyButtonConstructor variant="primary" size="medium" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>

        <!-- MOBILE hamburger -->
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl p-3 text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 lg:hidden"
          @click="isOpen = !isOpen"
        >
          <span class="sr-only">Toggle menu</span>
          <Bars3Icon v-if="!isOpen" class="h-8 w-8" />
          <XMarkIcon v-else class="h-8 w-8" />
        </button>
      </div>
    </div>

    <!-- MOBILE NAV -->
    <div v-if="isOpen" class="max-h-[calc(100vh-5rem)] overflow-y-auto border-t border-slate-200 bg-white lg:hidden">
      <div class="space-y-1 px-4 py-4 sm:px-6">
        <template v-for="item in navigation" :key="item.name">
          <!-- Item WITH children — accordion style -->
          <div v-if="item.children">
            <button
              class="flex w-full items-center justify-between rounded-xl px-3 py-3 hover:bg-slate-50"
              @click="toggleMobileDropdown(item.name)"
            >
              <MyTextConstructor
                variant="button"
                textColor="text-slate-700"
                spacing="none"
              >
                <template #myTitle>{{ item.name }}</template>
              </MyTextConstructor>
              <ChevronDownIcon
                class="h-4 w-4 text-slate-500 transition-transform duration-200"
                :class="{ 'rotate-180': openMobileDropdown === item.name }"
              />
            </button>

            <div v-if="openMobileDropdown === item.name" class="ml-4 space-y-1 border-l-2 border-brand-accent/30 pl-3">
              <button
                v-if="!item.href.startsWith('#')"
                class="block w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-brand-accent transition hover:bg-slate-50"
                @click="navigateTo(item.href)"
              >
                {{ item.name }} overview
              </button>
              <template v-for="child in item.children" :key="child.name">
                <div v-if="child.name === '---'" class="my-1 border-t border-brand-border"></div>
                <button
                  v-else
                  class="block w-full rounded-lg px-3 py-2.5 text-left text-sm transition hover:bg-slate-50 hover:text-brand-accent"
                  :class="[
                    child.href.includes('/exam-guide/') || child.href.includes('/for-teachers/') ? 'pl-6 text-xs' : '',
                    child.highlight ? 'font-bold text-brand-accent' : 'text-slate-600'
                  ]"
                  @click="navigateTo(child.href)"
                >
                  {{ child.name }}
                </button>
              </template>
            </div>
          </div>

          <!-- Item WITHOUT children -->
          <button
            v-else
            class="block w-full rounded-xl px-3 py-3 text-left hover:bg-slate-50"
            @click="navigateTo(item.href)"
          >
            <MyTextConstructor
              variant="button"
              textColor="text-slate-700"
              spacing="none"
            >
              <template #myTitle>{{ item.name }}</template>
            </MyTextConstructor>
          </button>
        </template>

        <!-- Auth links (mobile) -->
        <Link v-if="isAdmin" href="/admin" class="block rounded-xl px-3 py-3 hover:bg-slate-50" @click="isOpen = false">
          <MyTextConstructor variant="button" textColor="text-brand-accent" spacing="none">
            <template #myTitle>Admin</template>
          </MyTextConstructor>
        </Link>
        <Link v-else-if="user" href="/dashboard" class="block rounded-xl px-3 py-3 hover:bg-slate-50" @click="isOpen = false">
          <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
            <template #myTitle>Dashboard</template>
          </MyTextConstructor>
        </Link>
        <Link v-else href="/login" class="block rounded-xl px-3 py-3 hover:bg-slate-50" @click="isOpen = false">
          <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
            <template #myTitle>Sign In</template>
          </MyTextConstructor>
        </Link>

        <div class="mt-3">
          <MyButtonConstructor variant="primary" size="medium" fullWidth @click="showBookingModal = true; isOpen = false">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </div>
  </nav>

  <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
</template>
