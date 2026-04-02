<!-- resources/js/layouts/MarketingLayout.vue -->
<script setup lang="ts">
import Navbar from '@/components/layouts/Navbar.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-brand-bg text-brand-text">
    <Navbar />

    <main id="main-content" class="flex-1 pt-10 md:pt-20">
      <slot />
    </main>

    <MyFooter />
  </div>
</template>