<!-- resources/js/pages/ComingSoonPage.vue -->
<script setup lang="ts">
import Head from '@/components/layouts/Head.vue'
import { Mail } from 'lucide-vue-next'

const logoUrl = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'
const iconUrl = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/FAVICONmusicexamshelp_logo2+(512+x+512+px)_2.png'
</script>

<template>
  <Head
    title="musicExams.help — Coming Soon"
    description="musicExams.help is launching soon. Trinity College London exam support for teachers, parents and students using centre 120."
  />

  <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-[hsl(220,40%,13%)] via-[hsl(220,45%,18%)] to-[hsl(220,40%,13%)] px-6 text-center">

    <!-- Card -->
    <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-white/5 px-8 py-12 backdrop-blur-sm sm:px-12 sm:py-16">

      <!-- Logo -->
      <div class="mb-8 animate-fade-in">
        <img
          :src="iconUrl"
          alt="musicExams.help"
          class="mx-auto h-24 w-24 sm:h-32 sm:w-32"
        />
      </div>

      <!-- Wordmark in white card -->
      <div class="mx-auto mb-6 animate-fade-in-delay-1">
        <div class="mx-auto inline-block rounded-xl bg-white">
          <img
            :src="logoUrl"
            alt="musicExams.help"
            class="h-18 sm:h-22"
          />
        </div>
      </div>

      <!-- Tagline -->
      <div class="mb-8 animate-fade-in-delay-2">
        <p class="text-xl font-semibold text-white sm:text-2xl md:text-3xl">
          Something exciting is on its way
        </p>
        <p class="mx-auto mt-4 max-w-lg text-base leading-relaxed text-white/70 sm:text-lg">
          Trinity College London exam support for teachers, parents and students.
          We're putting the finishing touches on and will be live very soon.
        </p>
      </div>

      <!-- Contact -->
      <div class="animate-fade-in-delay-3">
        <a
          href="mailto:musicexams@musicexams.help"
          class="inline-flex items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:text-base"
        >
          <Mail class="h-4 w-4" />
          musicexams@musicexams.help
        </a>
      </div>

    </div>

    <!-- Footer -->
    <div class="mt-10 animate-fade-in-delay-3">
      <p class="text-xs text-white/40 sm:text-sm">
        &copy; {{ new Date().getFullYear() }} musicExams.help — Centre 120
      </p>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.8s ease-out both;
}
.animate-fade-in-delay-1 {
  animation: fadeIn 0.8s ease-out 0.2s both;
}
.animate-fade-in-delay-2 {
  animation: fadeIn 0.8s ease-out 0.4s both;
}
.animate-fade-in-delay-3 {
  animation: fadeIn 0.8s ease-out 0.6s both;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
