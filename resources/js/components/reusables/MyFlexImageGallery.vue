<!-- resources/js/components/reusables/MyFlexImageGallery.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface ImageData {
  id: number
  src: string
  alt: string
  title?: string
}

interface Props {
  images: ImageData[]
  columns?: 1 | 2 | 3 | 4
  spacing?: 'gaps' | 'no-gaps'
  imageRounded?: boolean
  imageHeight?: string
  slideshow?: boolean
  slideshowInterval?: number
  slideshowTransition?: 'fade' | 'slide' | 'zoom' | 'flip'
  transitionSpeed?: number
  displayCount?: number
  preserveAspectRatio?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  columns: 3,
  spacing: 'gaps',
  imageRounded: true,
  imageHeight: 'h-48',
  slideshow: false,
  slideshowInterval: 3000,
  slideshowTransition: 'fade',
  transitionSpeed: 800,
  displayCount: 4,
  preserveAspectRatio: false,
})

const currentSlideIndex = ref(0)
const imageTransitionStates = ref<boolean[]>([])
const individualImageIndices = ref<number[]>([])
const isProcessingTransitions = ref(false)
let slideInterval: ReturnType<typeof setInterval> | null = null

const initializeTransitionStates = () => {
  const maxDisplay = Math.min(props.displayCount, 4)
  imageTransitionStates.value = Array(maxDisplay).fill(false)
  individualImageIndices.value = Array.from(
    { length: maxDisplay },
    (_, i) => i % Math.max(props.images.length, 1)
  )
}

const displayedImages = computed(() => {
  if (!props.images || props.images.length === 0) return []
  if (!props.slideshow) return props.images

  const maxDisplay = Math.min(props.displayCount, 4)
  if (props.images.length <= maxDisplay) return props.images
  return individualImageIndices.value.map((i) => props.images[i % props.images.length])
})

const effectiveColumns = computed(() =>
  props.slideshow
    ? (Math.min(displayedImages.value.length, props.displayCount) as 1 | 2 | 3 | 4)
    : props.columns
)

const gridClasses = computed(() => 'flex flex-wrap justify-center transition-all')

const itemClasses = computed(() => {
  const baseClasses = {
    1: 'w-full max-w-md mx-auto',
    2: 'w-full sm:w-1/2',
    3: 'w-1/3',
    4: 'w-1/2 lg:w-1/4',
  }[effectiveColumns.value] || 'w-1/3'

  const spacingCls = props.spacing === 'gaps' ? 'p-2' : 'p-0'
  return `${baseClasses} ${spacingCls}`
})

const imageClasses = (index: number) => {
  let classes = 'transition-all'

  if (props.imageRounded && props.spacing === 'gaps') classes += ' rounded-lg'

  classes +=
    effectiveColumns.value === 1 || props.preserveAspectRatio
      ? ' w-full h-auto object-contain'
      : ' absolute inset-0 w-full h-full object-cover'

  if (props.slideshow && imageTransitionStates.value[index]) {
    const effectClasses: Record<string, string> = {
      fade: ' opacity-0',
      slide: ' translate-x-2',
      zoom: ' scale-95',
      flip: ' rotate-y-180',
    }
    classes += effectClasses[props.slideshowTransition] || ''
  }

  return classes
}

const transitionSingleImage = (index: number) =>
  new Promise<void>((resolve) => {
    imageTransitionStates.value[index] = true

    setTimeout(() => {
      individualImageIndices.value[index] =
        (individualImageIndices.value[index] + 1) % props.images.length

      setTimeout(() => {
        imageTransitionStates.value[index] = false
        resolve()
      }, props.transitionSpeed / 2)
    }, props.transitionSpeed / 2)
  })

const nextSlide = async () => {
  if (!props.slideshow || props.images.length <= props.displayCount || isProcessingTransitions.value) {
    return
  }

  isProcessingTransitions.value = true
  const maxDisplay = Math.min(props.displayCount, 4)
  const pause = 300

  for (let i = 0; i < maxDisplay; i += 1) {
    await transitionSingleImage(i)
    if (i < maxDisplay - 1) {
      await new Promise((resolve) => setTimeout(resolve, pause))
    }
  }

  currentSlideIndex.value = (currentSlideIndex.value + 1) % props.images.length
  isProcessingTransitions.value = false
}

const startSlideshow = () => {
  if (!props.slideshow || props.images.length <= props.displayCount) return
  stopSlideshow()
  slideInterval = setInterval(nextSlide, props.slideshowInterval)
}

const stopSlideshow = () => {
  if (slideInterval) {
    clearInterval(slideInterval)
    slideInterval = null
  }
}

watch(
  [() => props.images, () => props.displayCount],
  () => {
    initializeTransitionStates()
    if (props.slideshow) startSlideshow()
  },
  { deep: true, immediate: true }
)

onMounted(() => {
  initializeTransitionStates()
  if (props.slideshow) startSlideshow()
})

onUnmounted(stopSlideshow)
</script>

<template>
  <div class="w-full">
    <div class="w-full bg-brand-surface">
      <div :class="gridClasses" @mouseenter="stopSlideshow" @mouseleave="startSlideshow">
        <div
          v-for="(image, idx) in displayedImages"
          :key="props.slideshow ? `${idx}-${individualImageIndices[idx]}` : `${image.id}-${idx}`"
          :class="itemClasses"
        >
          <div v-if="image?.src" class="relative w-full">
            <div
              :class="
                effectiveColumns === 1 || props.preserveAspectRatio
                  ? 'relative w-full'
                  : `relative w-full overflow-hidden ${props.imageHeight}`
              "
            >
              <img
                :src="image.src"
                :alt="image.alt"
                :class="imageClasses(idx)"
                :style="{ transitionDuration: `${props.transitionSpeed}ms` }"
              />
            </div>

            <div
              v-if="image.title"
              class="absolute bottom-0 left-0 right-0 bg-brand-primary/80 p-3"
              :class="{ 'rounded-b-lg': props.imageRounded && props.spacing === 'gaps' }"
            >
              <MyTextConstructor
                variant="button"
                alignment="center"
                textColor="text-brand-text-inverse"
                spacing="none"
              >
                <template #myTitle>
                  {{ image.title }}
                </template>
              </MyTextConstructor>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>