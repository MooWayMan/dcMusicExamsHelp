<!-- resources/js/components/dashboard/DashboardLinkCard.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import type { Component } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

// A whole-card link on the teacher dashboard (Top Ten, Piece tracker):
// icon, title, one line of what it is for, and a chevron.
defineProps<{
  href: string
  icon: Component
  title: string
  text: string
}>()
</script>

<template>
  <Link
    :href="href"
    class="group flex items-center gap-4 rounded-xl border border-brand-accent/30 bg-brand-accent/5 p-5 transition-all hover:border-brand-accent hover:shadow-md"
  >
    <div class="rounded-lg bg-brand-accent/10 p-3 transition-colors group-hover:bg-brand-accent/20">
      <component :is="icon" class="h-6 w-6 text-brand-accent" />
    </div>
    <div class="flex-1 text-sm">
      <MyTextConstructor variant="button" spacing="none" textColor="text-brand-text">
        <template #myTitle>{{ title }}</template>
      </MyTextConstructor>
      <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">
        {{ text }}
      </MyTextConstructor>
    </div>
    <ChevronRight class="h-5 w-5 shrink-0 text-brand-accent" />
  </Link>
</template>
