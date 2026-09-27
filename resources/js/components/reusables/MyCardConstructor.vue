<!-- resources/js/components/reusables/MyCardConstructor.vue -->
<!--
  MyCardConstructor: a section of a page on its own card (surface, border,
  rounding, padding), with an optional title. Built 27 Sep 2026 so a card
  is one constructor rather than the same class string hand-typed on every
  page; tests/Feature/CardConstructorGuardTest.php keeps count of the
  hand-rolled ones left, and that count may only go down.

  padding: none (the content brings its own, e.g. a headed table), small
  (p-4), medium (p-5, the default), large (p-6, p-8 from sm).

  Usage:
  <MyCardConstructor title="Add a pupil">...</MyCardConstructor>
-->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

const props = withDefaults(
  defineProps<{
    title?: string
    padding?: 'none' | 'small' | 'medium' | 'large'
  }>(),
  { title: '', padding: 'medium' },
)

const paddingClass = computed(() => ({
  none: '',
  small: 'p-4',
  medium: 'p-5',
  large: 'p-6 sm:p-8',
}[props.padding]))
</script>

<template>
  <section class="rounded-xl border border-brand-border bg-brand-surface" :class="paddingClass">
    <div v-if="title" class="flex flex-col gap-4">
      <MyTextConstructor variant="button-lg" spacing="none">
        <template #myTitle>{{ title }}</template>
      </MyTextConstructor>
      <slot />
    </div>
    <slot v-else />
  </section>
</template>
