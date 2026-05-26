<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <!--
          `min-w-0` releases SidebarInset (a flex item in SidebarProvider's row
          flex) to shrink below its min-content, so wide tables inside scroll
          rather than expanding the whole inset. `overflow-x-clip` (not `-hidden`)
          provides a safety belt without breaking iOS Safari touch scroll on
          nested `overflow-x-auto` containers.
        -->
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
