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

const brandWordmark = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'
</script>

<template>
    <!-- Fixed top bar -->
    <div
        class="sticky top-0 z-30 flex h-20 shrink-0 items-center border-b border-sidebar-border/70 bg-brand-surface/95 px-4 backdrop-blur-sm sm:px-6 xl:h-24"
    >
        <!-- Left: sidebar trigger + home -->
        <div class="flex items-center gap-1.5">
            <SidebarTrigger class="-ml-1" />

            <Link
                href="/"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-accent"
            >
                <Home class="h-4 w-4" />
            </Link>

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <span class="text-brand-border">|</span>
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Centre: brand wordmark -->
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
            <Link href="/" class="cursor-pointer flex items-center">
                <img
                    :src="brandWordmark"
                    alt="musicexams.help"
                    class="h-8 w-auto sm:h-14 xl:h-20"
                />
            </Link>
        </div>
    </div>
</template>
