<!-- resources/js/components/BookingModal.vue -->
<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import { Monitor, Users, Music, X } from 'lucide-vue-next'

interface Props {
  show: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'close'): void
}>()

const bookingOptions = [
  {
    id: 'digital',
    icon: Monitor,
    title: 'Digital Exam',
    subtitle: 'Classical & Jazz or Rock & Pop',
    detail: 'Record anywhere on your own schedule — at home, school or studio. Submit online within 28 days of booking.',
    url: 'https://booking.trinitycollege.com/?larCode=120',
    iconBg: 'bg-brand-accent/10',
    iconColor: 'text-brand-accent',
    borderColor: 'border-brand-accent',
    note: 'Centre code 120 is applied automatically',
  },
  {
    id: 'f2f-classical',
    icon: Music,
    title: 'Face-to-Face — Classical & Jazz',
    subtitle: 'Three sessions per year: March / July / December',
    detail: 'Live with a Trinity examiner at our Liverpool or Wirral venue. Choose your day — exam time is emailed shortly after.',
    url: 'https://musicbooking.trinitycollege.co.uk/OEWeb/loadExamDtl.do',
    iconBg: 'bg-brand-success-soft',
    iconColor: 'text-brand-success',
    borderColor: 'border-brand-success',
    note: 'Opens the Trinity Music Online Booking system',
  },
  {
    id: 'f2f-rockpop',
    icon: Users,
    title: 'Face-to-Face — Rock & Pop',
    subtitle: 'Three sessions per year: March / July / December',
    detail: 'Live with a Trinity examiner at our Liverpool or Wirral venue. Day is stated at booking — exam time emailed close to exam date.',
    url: 'https://my-trinity.trinitycollege.com/music/grades-and-diplomas/',
    iconBg: 'bg-brand-teal/10',
    iconColor: 'text-brand-teal',
    borderColor: 'border-brand-teal',
    note: 'Opens the MyTrinity booking system',
  },
]

const handleOptionClick = (url: string) => {
  window.open(url, '_blank', 'noopener,noreferrer')
  emit('close')
}

const handleBackdropClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    emit('close')
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
        @click="handleBackdropClick"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div
            v-if="show"
            class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-brand-surface shadow-2xl ring-1 ring-brand-border"
          >
            <!-- Header -->
            <div class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-6 py-5">
              <div class="flex items-center justify-between">
                <div>
                  <MyTextConstructor
                    variant="button-lg"
                    spacing="none"
                    textColor="text-brand-text-inverse"
                  >
                    <template #myTitle>Book Your Exam</template>
                  </MyTextConstructor>
                  <p class="mt-1 text-sm text-white/80 sm:text-base">
                    Choose how you'd like to take your exam
                  </p>
                </div>
                <button
                  @click="emit('close')"
                  class="flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/20 hover:text-white"
                  aria-label="Close booking modal"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- Options -->
            <div class="max-h-[70vh] overflow-y-auto p-5 sm:p-6">
              <div class="space-y-4">
                <button
                  v-for="option in bookingOptions"
                  :key="option.id"
                  @click="handleOptionClick(option.url)"
                  class="group flex w-full items-start gap-4 rounded-xl border-2 border-brand-border bg-brand-bg p-4 text-left transition-all duration-200 hover:border-brand-accent hover:shadow-lg sm:p-5"
                >
                  <div
                    :class="[option.iconBg, 'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12']"
                  >
                    <component
                      :is="option.icon"
                      :class="[option.iconColor, 'h-5 w-5 sm:h-6 sm:w-6']"
                    />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-lg font-semibold text-brand-text group-hover:text-brand-accent sm:text-lg">
                      {{ option.title }}
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-brand-accent sm:text-sm">
                      {{ option.subtitle }}
                    </p>
                    <p class="mt-1.5 text-sm leading-snug text-brand-text-soft sm:text-base">
                      {{ option.detail }}
                    </p>
                    <p class="mt-2 text-xs text-brand-text-soft/70 sm:text-sm">
                      {{ option.note }}
                    </p>
                  </div>
                  <div class="mt-1 shrink-0 text-brand-text-soft transition group-hover:text-brand-accent">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                  </div>
                </button>
              </div>

              <p class="mt-5 text-center text-sm text-brand-text-soft">
                All three options lead to official Trinity College London booking systems.
                There is no extra charge for booking through centre 120.
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
