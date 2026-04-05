<!-- resources/js/pages/ThankYou.vue — TEMP BOILERPLATE FOR TESTING -->
<script setup lang="ts">
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
</script>

<template>
  <Head title="Thank You — musicExams.help" description="Thank you page" />

  <div class="min-h-screen bg-brand-bg text-brand-text">
    <Navbar />

    <section class="pt-32 pb-20">
      <div class="mx-auto max-w-3xl px-4 text-center">
        <h1 class="text-3xl font-bold text-brand-primary">Thank You</h1>
        <p class="mt-4 text-lg text-brand-text-soft">
          This is a test page. If you can see this, the route is working.
        </p>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>
</template>
