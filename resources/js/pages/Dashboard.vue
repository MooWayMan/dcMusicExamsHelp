<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { LayoutDashboard } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { dashboard } from '@/routes'

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => user.value?.role === 'admin')

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

    <div class="flex h-full flex-1 flex-col items-center justify-center gap-6 p-8">
        <MyTextConstructor variant="heading" alignment="center">
            <template #myTitle>Welcome, {{ user?.name }}</template>
        </MyTextConstructor>

        <Link v-if="isAdmin" href="/admin">
            <MyButtonConstructor variant="primary" size="large" :icon="LayoutDashboard">
                Go to Admin Dashboard
            </MyButtonConstructor>
        </Link>

        <p v-else class="text-lg text-brand-text-soft">
            Your dashboard is being set up. Check back soon.
        </p>
    </div>
</template>
