<!-- resources/js/components/layouts/Navbar.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

const isOpen = ref(false)

const navigation = [
  { name: 'Why use this page', href: '#why' },
  { name: 'Incentives', href: '#incentives' },
  { name: 'FAQ', href: '#faq' },
]

const navIcon =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/icon_64x64.png'

const bookingUrl = 'https://www.trinitycollege.com/'
</script>

<template>
  <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between">
        <Link href="/" class="flex shrink-0 items-center">
          <img
            :src="navIcon"
            alt="musicexams.help"
            class="h-10 w-10 rounded-xl sm:h-11 sm:w-11"
          />
        </Link>

        <div class="hidden items-center gap-8 md:flex">
          <a
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            class="transition hover:opacity-70"
          >
            <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
              <template #myTitle>
                {{ item.name }}
              </template>
            </MyTextConstructor>
          </a>

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
          class="inline-flex items-center justify-center rounded-xl p-2 text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 md:hidden"
          @click="isOpen = !isOpen"
        >
          <span class="sr-only">Toggle menu</span>
          <Bars3Icon v-if="!isOpen" class="h-6 w-6" />
          <XMarkIcon v-else class="h-6 w-6" />
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
          <MyTextConstructor variant="button" textColor="text-slate-700" spacing="none">
            <template #myTitle>
              {{ item.name }}
            </template>
          </MyTextConstructor>
        </a>

        <a
          :href="bookingUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="mt-3 block"
        >
          <MyButtonConstructor variant="primary" size="medium" full-width>
            Book Your Exam
          </MyButtonConstructor>
        </a>
      </div>
    </div>
  </nav>
</template>