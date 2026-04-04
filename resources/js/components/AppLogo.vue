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
        <img
            v-else
            :src="brandWordmark"
            alt="musicExams.help"
            class="h-14 w-auto xl:h-20"
        />
    </div>
</template>
