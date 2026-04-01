<!-- resources/js/layouts/auth/AuthSimpleLayout.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { home } from '@/routes'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

const heroLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'

defineProps<{
    title?: string
    description?: string
}>()
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-brand-bg px-4 py-10 sm:px-6 md:px-10">
        <div class="w-full max-w-lg">
            <div class="flex flex-col gap-8">
                <!-- Logo + Title -->
                <div class="flex flex-col items-center gap-6">
                    <Link :href="home()" class="flex flex-col items-center">
                        <img
                            :src="heroLogo"
                            alt="musicexams.help"
                            class="h-20 w-auto sm:h-24"
                        />
                    </Link>

                    <div class="w-full text-center">
                        <MyTextConstructor
                            variant="subheading"
                            alignment="center"
                            fontFamily="display"
                            spacing="tight"
                        >
                            <template #myTitle>
                                {{ title }}
                            </template>
                        </MyTextConstructor>

                        <MyTextConstructor
                            v-if="description"
                            bodyVariant="muted"
                            alignment="center"
                            spacing="none"
                            class="mt-2"
                        >
                            {{ description }}
                        </MyTextConstructor>
                    </div>
                </div>

                <!-- Form content -->
                <div class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
