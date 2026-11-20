<!-- resources/js/components/examfees/TabCrossRef.vue -->
<script setup lang="ts">
// The "Looking for exam dates?" / "Looking for exam fees?" panel at the foot
// of each tab on /exam-fees, with a button that jumps to the other tab.
// Fades in after the tab switch so it doesn't ping-pong.
import type { Component } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

const props = defineProps<{
  title: string
  buttonLabel: string
  icon: Component
  visible: boolean
}>()

const emit = defineEmits<{ go: [] }>()
</script>

<template>
  <section class="bg-brand-surface-soft">
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
      <div
        class="text-center transition-all duration-700 ease-out"
        :class="props.visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'"
      >
        <div class="mx-auto mb-3 h-1 w-16 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent"></div>
        <div class="text-lg font-semibold sm:text-xl">
          <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-brand-text">{{ props.title }}</MyTextConstructor>
        </div>
        <div class="mx-auto mt-2 max-w-xl text-sm sm:text-base">
          <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-brand-text-soft">
            <slot />
          </MyTextConstructor>
        </div>
        <button
          type="button"
          @click="emit('go')"
          :disabled="!props.visible"
          class="mt-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:opacity-90 sm:text-base"
        >
          <component :is="props.icon" class="h-4 w-4" />
          {{ props.buttonLabel }}
        </button>
      </div>
    </div>
  </section>
</template>
