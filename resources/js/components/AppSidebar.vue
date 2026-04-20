<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    School,
    ClipboardList,
    AlertCircle,
    GraduationCap,
    BarChart3,
    CheckSquare,
    Map,
    Clock,
    Award,
    Construction,
    Gift,
    FileSpreadsheet,
    Contact as ContactIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin',
        icon: BarChart3,
    },
    {
        title: 'Teachers',
        href: '/admin/teachers',
        icon: Users,
        children: [
            { title: 'All Teachers', href: '/admin/teachers' },
            { title: 'Add Teacher', href: '/admin/teachers/create' },
        ],
    },
    {
        title: 'Schools',
        href: '/admin/schools',
        icon: School,
        children: [
            { title: 'All Schools', href: '/admin/schools' },
            { title: 'Add School', href: '/admin/schools/create' },
        ],
    },
    {
        title: 'Orders',
        href: '/admin/orders',
        icon: ClipboardList,
    },
    {
        title: 'Pending Results',
        href: '/admin/pending-results',
        icon: AlertCircle,
    },
    {
        title: 'Exam Entries',
        href: '/admin/exam-entries',
        icon: FileSpreadsheet,
    },
    {
        title: 'Students',
        href: '/admin/students',
        icon: GraduationCap,
    },
    {
        title: 'Contacts',
        href: '/admin/contacts',
        icon: ContactIcon,
    },
    {
        title: 'Tasks',
        href: '/admin/tasks',
        icon: CheckSquare,
        children: [
            { title: 'All Tasks', href: '/admin/tasks' },
            { title: 'Add Task', href: '/admin/tasks/create' },
        ],
    },
    {
        title: 'Certificates',
        href: '/admin/certificates',
        icon: Award,
    },
    {
        title: 'Quarter End',
        href: '/admin/quarter-end',
        icon: Gift,
    },
    {
        title: 'Roadmap',
        href: '/admin/roadmap',
        icon: Map,
    },
    {
        title: 'Page Maintenance',
        href: '/admin/page-maintenance',
        icon: Construction,
    },
    {
        title: 'Session Hours',
        href: '/admin/session-logs',
        icon: Clock,
    },
];

// TODO: consider adding a direct "Profile" link here so settings/profile
// isn't hidden inside the user avatar dropdown at the very bottom.
// Low priority — single-admin app, Paul is the only user.
const footerNavItems: NavItem[] = [];
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
            <NavMain :items="mainNavItems" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
