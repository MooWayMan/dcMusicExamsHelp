<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { SidebarProvider } from '@/components/ui/sidebar';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <!--
        The banner is rendered as a fragment sibling above the rest of the
        shell. It must NOT live inside SidebarProvider — that wrapper uses
        a horizontal flex row, so any extra child becomes a third column
        that squashes the sidebar and main content into thin strips and
        breaks the sidebar's clickable nav. Multi-root templates are
        supported in Vue 3, so this is the cleanest fix.
    -->
    <ImpersonationBanner />
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
