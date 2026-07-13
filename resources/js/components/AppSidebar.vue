<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    UserCog,
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
    MessageSquareText,
    Mail as MailIcon,
    Upload,
    Receipt,
    Tags,
    ClipboardCheck,
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

// The admin nav is grouped by intent so related screens sit together,
// rather than one long flat list.
//   1. Overview  — single landing screen for the admin
//   2. People    — Contacts / Schools / Students
//   3. Exams     — Orders → Exam Entries → Pending Results → Certificates → Quarter End
//   4. Tools     — Tasks / Roadmap / Page Maintenance / Session Hours

const adminOverviewNavItems: NavItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin',
        icon: BarChart3,
    },
];

const adminPeopleNavItems: NavItem[] = [
    {
        title: 'Contacts',
        href: '/admin/contacts',
        icon: ContactIcon,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: UserCog,
    },
    {
        title: 'Subscribers',
        href: '/admin/subscribers',
        icon: MailIcon,
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
        title: 'Students',
        href: '/admin/students',
        icon: GraduationCap,
    },
];

const adminExamsNavItems: NavItem[] = [
    {
        title: 'Import',
        href: '/admin/imports',
        icon: Upload,
    },
    {
        title: 'Orders',
        href: '/admin/orders',
        icon: ClipboardList,
    },
    {
        title: 'Reconciliation',
        href: '/admin/reconciliation',
        icon: Receipt,
    },
    {
        title: 'Exam Entries',
        href: '/admin/exam-entries',
        icon: FileSpreadsheet,
    },
    {
        title: 'Pending Results',
        href: '/admin/pending-results',
        icon: AlertCircle,
    },
    {
        title: 'Quarter Comparison',
        href: '/admin/quarter-comparison',
        icon: BarChart3,
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
];

const adminToolsNavItems: NavItem[] = [
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
        title: 'Roadmap',
        href: '/admin/roadmap',
        icon: Map,
    },
    {
        title: 'Quick Replies',
        href: '/admin/quick-replies',
        icon: MessageSquareText,
    },
    {
        title: 'Address Labels',
        href: '/admin/labels',
        icon: Tags,
    },
    {
        title: 'Results Scan',
        href: '/admin/results-scan',
        icon: ClipboardCheck,
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
            <template v-if="isAdmin">
                <NavMain :items="adminOverviewNavItems" label="Admin" />
                <NavMain :items="adminPeopleNavItems" label="People" />
                <NavMain :items="adminExamsNavItems" label="Exams" />
                <NavMain :items="adminToolsNavItems" label="Tools" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
