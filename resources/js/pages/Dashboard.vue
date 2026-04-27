<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { LayoutDashboard, ClipboardList, Users, GraduationCap, CheckSquare, Award, AlertCircle, Home, LogOut } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { dashboard, logout } from '@/routes'

// Non-admin users (e.g. anyone who signed up before we disabled registration)
// land here and previously had no visible way out — sidebar hamburger on
// mobile is too easy to miss. These two buttons give them an obvious exit.
const handleLogout = () => {
    router.flushAll()
}

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => user.value?.role === 'admin')

const logo = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'

const quickLinks = [
    { title: 'Admin Dashboard', subtitle: 'Stats, orders & contacts', href: '/admin', icon: LayoutDashboard },
    { title: 'Orders', subtitle: 'View all exam orders', href: '/admin/orders', icon: ClipboardList },
    { title: 'Pending Results', subtitle: 'Weekly results checklist', href: '/admin/pending-results', icon: AlertCircle },
    { title: 'Teachers', subtitle: 'Manage teacher records', href: '/admin/teachers', icon: Users },
    { title: 'Tasks', subtitle: 'Your to-do list', href: '/admin/tasks', icon: CheckSquare },
    { title: 'Certificates', subtitle: 'Generate certificates', href: '/admin/certificates', icon: Award },
]

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col items-center p-6 sm:p-8">
        <!-- Logo and welcome -->
        <div class="mt-4 flex flex-col items-center gap-5 sm:mt-8">
            <img
                :src="logo"
                alt="musicExams.help"
                class="h-16 w-auto sm:h-20"
            />
            <MyTextConstructor variant="heading" alignment="center" spacing="none">
                <template #myTitle>Welcome back, {{ user?.name?.split(' ')[0] }}</template>
            </MyTextConstructor>
            <p class="text-base text-brand-text-soft sm:text-lg">
                Centre 120 — Trinity College London
            </p>
        </div>

        <!-- Quick links grid (admin only) -->
        <div v-if="isAdmin" class="mt-10 w-full max-w-3xl">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="link in quickLinks"
                    :key="link.href"
                    :href="link.href"
                    class="group flex items-center gap-4 rounded-xl border border-brand-border bg-brand-surface p-5 transition-all hover:border-brand-accent hover:shadow-md"
                >
                    <div class="rounded-lg bg-brand-accent/10 p-3 transition-colors group-hover:bg-brand-accent/20">
                        <component :is="link.icon" class="h-6 w-6 text-brand-accent" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-brand-text">{{ link.title }}</p>
                        <p class="text-sm text-brand-text-soft">{{ link.subtitle }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Non-admin message + visible exit -->
        <div v-else class="mt-8 flex w-full max-w-md flex-col items-center gap-6">
            <p class="text-center text-lg text-brand-text-soft">
                Thanks for signing up — your dashboard is being set up. Check back soon.
            </p>

            <div class="flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
                <Link
                    href="/"
                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-brand-border bg-brand-surface px-6 py-3 text-base font-semibold text-brand-primary shadow-sm transition-colors hover:bg-brand-bg"
                    data-test="dashboard-home-link"
                >
                    <Home class="h-5 w-5" />
                    Back to home
                </Link>

                <Link
                    :href="logout()"
                    @click="handleLogout"
                    as="button"
                    class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-transparent bg-brand-primary px-6 py-3 text-base font-semibold text-brand-text-inverse shadow-sm transition-colors hover:bg-brand-primary-dark"
                    data-test="dashboard-logout-button"
                >
                    <LogOut class="h-5 w-5" />
                    Log out
                </Link>
            </div>
        </div>
    </div>
</template>
