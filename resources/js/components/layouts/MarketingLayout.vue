<!-- resources/js/layouts/MarketingLayout.vue -->
<script setup lang="ts">
import Navbar from '@/components/layouts/Navbar.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />

    <main class="flex-1">
      <slot />
    </main>

    <MyFooter />
  </div>
</template>