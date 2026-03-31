<script setup lang="ts">
const navIcon = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/icon_64x64.png'
const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/logo-wordmark.png'
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
        <img :src="navIcon" alt="musicexams.help" class="size-8" />
    </div>
    <div class="ml-1 flex flex-1 items-center">
        <img :src="brandWordmark" alt="musicexams.help" class="h-7 w-auto" />
    </div>
</template>
