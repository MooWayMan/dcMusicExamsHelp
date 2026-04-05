<!-- resources/js/components/layouts/Navbar.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

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

const currentPath = computed(() => page.url)

const navigation = computed(() => {
  const isHome = currentPath.value === '/'
  return [
    { name: 'Home', href: '/', show: !isHome },
    { name: 'Why use this page', href: isHome ? '#why' : '/#why', show: true },
    { name: 'Incentives', href: isHome ? '#incentives' : '/#incentives', show: true },
    { name: 'Exam Guide', href: '/exam-guide', show: true },
    { name: 'FAQ', href: '/faq', show: true },
  ].filter(item => item.show)
})

const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'
const navIcon = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/FAVICONmusicexamshelp_logo2+(512+x+512+px)_2.png'

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

const navClasses = computed(() =>
  props.fixed
    ? 'fixed top-0 z-50 w-full border-b border-slate-200 bg-white/95 backdrop-blur'
    : 'sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur'
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

        <div class="hidden items-center gap-8 md:flex">
          <a
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            class="transition hover:opacity-70"
          >
            <MyTextConstructor
              variant="button"
              textColor="text-slate-700"
              spacing="none"
            >
              <template #myTitle>
                {{ item.name }}
              </template>
            </MyTextConstructor>
          </a>

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

          <a
            :href="bookingUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            <MyButtonConstructor variant="primary" size="medium">
              Book Your Exam
            </MyButtonConstructor>
          </a>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl p-3 text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 md:hidden"
          @click="isOpen = !isOpen"
        >
          <span class="sr-only">Toggle menu</span>
          <Bars3Icon v-if="!isOpen" class="h-8 w-8" />
          <XMarkIcon v-else class="h-8 w-8" />
        </button>
      </div>
    </div>

    <div v-if="isOpen" class="border-t border-slate-200 bg-white md:hidden">
      <div class="space-y-1 px-4 py-4 sm:px-6">
        <a
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          class="block rounded-xl px-3 py-3 hover:bg-slate-50"
          @click="isOpen = false"
        >
          <MyTextConstructor
            variant="button"
            textColor="text-slate-700"
            spacing="none"
          >
            <template #myTitle>
              {{ item.name }}
            </template>
          </MyTextConstructor>
        </a>

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

        <a
          :href="bookingUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="mt-3 block"
        >
          <MyButtonConstructor variant="primary" size="medium" fullWidth>
            Book Your Exam
          </MyButtonConstructor>
        </a>
      </div>
    </div>
  </nav>
</template>