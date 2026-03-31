<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ChevronRight } from 'lucide-vue-next';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const props = withDefaults(defineProps<{
    items: NavItem[];
    label?: string;
}>(), {
    label: 'Platform',
});

const page = usePage();
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname);

function isActive(href: string): boolean {
    const current = currentPath.value;
    if (href === '/admin' || href === '/dashboard') return current === href;
    return current === href || current.startsWith(href + '/');
}

function hasActiveChild(item: NavItem): boolean {
    if (!item.children) return false;
    return item.children.some(child => isActive(child.href));
}

// Track open/closed state for each collapsible section
const openState = ref<Record<string, boolean>>({});
// Auto-open sections that have active children on load
props.items.forEach(item => {
    if (item.children?.length && (isActive(item.href) || hasActiveChild(item))) {
        openState.value[item.title] = true;
    }
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Items WITH children: collapsible -->
                <Collapsible
                    v-if="item.children?.length"
                    as-child
                    :default-open="isActive(item.href) || hasActiveChild(item)"
                    class="group/collapsible"
                    v-model:open="openState[item.title]"
                >
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isActive(item.href) || hasActiveChild(item)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href" @click="openState[item.title] = true">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <CollapsibleTrigger as-child @click.prevent.stop>
                                    <ChevronRight class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </CollapsibleTrigger>
                            </Link>
                        </SidebarMenuButton>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isActive(child.href)"
                                        size="md"
                                    >
                                        <Link :href="child.href">
                                            <span>{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Items WITHOUT children: simple link -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="isActive(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
