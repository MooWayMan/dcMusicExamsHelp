<script setup lang="ts">
import { useSidebar } from '@/components/ui/sidebar/utils'

const { state } = useSidebar()

const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'
const brandIcon = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/FAVICONmusicexamshelp_logo2+(512+x+512+px)_2.png'
</script>

<template>
    <div class="flex w-full items-center">
        <!-- Favicon when sidebar collapsed -->
        <img
            v-if="state === 'collapsed'"
            :src="brandIcon"
            alt="musicExams.help"
            class="h-10 w-10 rounded-xl"
        />
        <!-- Full wordmark when sidebar expanded -->
        <!-- Width-first sizing: image fills sidebar width, height follows aspect ratio. -->
        <!-- Fixed-height sizing was forcing a wider aspect than the 256px sidebar can give, -->
        <!-- causing the image to squish horizontally. -->
        <img
            v-else
            :src="brandWordmark"
            alt="musicExams.help"
            class="h-auto w-full max-w-full object-contain"
        />
    </div>
</template>
