<!-- resources/js/components/reusables/MyPhotoSection.vue -->
<script setup lang="ts">
// The full-width section on a darkened blue photo used across the public
// pages: photo, black/50 overlay, centred column, optional white title and
// subtitle. Pass the photo's file name only; they all live in the
// musicexamshelp folder on S3. Content goes in the default slot.
import { computed } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

defineOptions({ inheritAttrs: false })

interface Props {
  image: string
  title?: string
  subtitle?: string
  width?: '3xl' | '4xl' | '5xl'
  rule?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  width: '4xl',
  rule: false,
  id: undefined,
})

const { animClass } = usePageAnimation()

const PHOTO_BASE = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/'

const widthClasses: Record<NonNullable<Props['width']>, string> = {
  '3xl': 'max-w-3xl',
  '4xl': 'max-w-4xl',
  '5xl': 'max-w-5xl',
}

const background = computed(() => ({ backgroundImage: `url('${PHOTO_BASE}${props.image}')` }))
</script>

<template>
  <section :id="props.id" class="relative bg-cover bg-center bg-no-repeat" :style="background">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative mx-auto px-4 py-12 sm:px-6 lg:py-16" :class="widthClasses[props.width]">
      <div v-if="props.title" :class="animClass('fade-up', 1)">
        <div v-if="props.rule" class="mx-auto mb-4 h-1 w-16 rounded-full bg-white/30"></div>
        <MyTextConstructor
          variant="subheading"
          fontFamily="display"
          alignment="center"
          spacing="tight"
          textColor="text-white"
          class="md:!text-2xl lg:!text-3xl"
        >
          <template #myTitle>{{ props.title }}</template>
        </MyTextConstructor>
        <div v-if="props.subtitle" class="mx-auto mt-3 max-w-2xl text-base md:text-lg lg:text-xl">
          <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-white/80">
            {{ props.subtitle }}
          </MyTextConstructor>
        </div>
      </div>
      <slot />
    </div>
  </section>
</template>
