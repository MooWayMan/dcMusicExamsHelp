<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const navIcon = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/icon_64x64.png'
const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/logo-wordmark.png'
</script>

<template>
    <!-- Fixed top bar -->
    <div
        class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between border-b border-sidebar-border/70 bg-brand-surface/95 px-4 backdrop-blur-sm sm:px-6"
    >
        <!-- Left: sidebar trigger + home button + breadcrumbs -->
        <div class="flex items-center gap-1.5">
            <SidebarTrigger class="-ml-1" />

            <Link
                href="/"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-accent"
            >
                <Home class="h-4 w-4" />
                <span class="hidden sm:inline">Home</span>
            </Link>

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <span class="text-brand-border">|</span>
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Right: brand wordmark -->
        <Link href="/" class="cursor-pointer flex items-center">
            <!-- Small icon on mobile only -->
            <img
                :src="navIcon"
                alt="musicexams.help"
                class="h-9 w-9 rounded-lg sm:hidden"
            />
            <!-- Wordmark graphic on sm+ screens -->
            <img
                :src="brandWordmark"
                alt="musicexams.help"
                class="hidden h-8 w-auto sm:block"
            />
        </Link>
    </div>
</template>
