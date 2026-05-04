<!-- resources/js/components/UserSidebar.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LayoutDashboard, User as UserIcon, LogOut } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, logout } from '@/routes';
import type { NavItem } from '@/types';

// Mirrors AppSidebar's structure but trimmed to the simple needs of a
// non-admin user — just Dashboard + Profile, with a logout entry pinned
// at the bottom of the main nav for quick access (the avatar dropdown
// already has it but Paul wanted it visible up here too).
const userNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: UserIcon,
    },
];

// Same handler shape as Dashboard.vue: flush Inertia history before the
// logout link is followed so any cached pages can't be brought back via
// the browser's back button after the session is killed.
function handleLogout() {
    router.flushAll();
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <div class="px-2 py-3">
                <Link :href="dashboard()">
                    <AppLogo />
                </Link>
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="userNavItems" label="Account" />

            <!-- Logout uses the Inertia Link :href="logout()" pattern from
                 Dashboard.vue. Sits as a SidebarMenu item so the icon + label
                 match the rest of the sidebar visually. -->
            <div class="px-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child size="sm" tooltip="Log out">
                            <Link :href="logout()" as="button" @click="handleLogout">
                                <LogOut />
                                <span>Log out</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
