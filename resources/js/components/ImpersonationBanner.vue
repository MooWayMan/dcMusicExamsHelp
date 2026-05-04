<!-- resources/js/components/ImpersonationBanner.vue -->
<!--
    Sitewide banner shown when an admin is currently impersonating another
    user. Hidden by default — only renders when the shared Inertia prop
    `auth.impersonating` is populated by HandleInertiaRequests.

    Lives in AppShell so it appears regardless of layout (admin sidebar,
    user sidebar, marketing). Sticks to the top of the viewport with a
    high z-index so it can't be hidden by sidebars or modals.
-->
<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { UserCog } from 'lucide-vue-next'

interface ImpersonationState {
    admin_name: string | null
    target_name: string | null
}

const page = usePage()

const impersonating = computed<ImpersonationState | null>(() => {
    const auth = page.props.auth as { impersonating?: ImpersonationState | null } | undefined
    return auth?.impersonating ?? null
})

function returnToAdmin(): void {
    router.post('/impersonate/leave', {}, { preserveScroll: false })
}
</script>

<template>
    <div
        v-if="impersonating"
        role="alert"
        class="sticky top-0 z-50 w-full border-b border-brand-burgundy/40 bg-brand-burgundy text-white shadow-md"
    >
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm">
            <div class="flex items-center gap-2">
                <UserCog class="h-4 w-4 shrink-0" />
                <span>
                    Logged in as
                    <strong class="font-semibold">{{ impersonating.target_name }}</strong>
                    <span v-if="impersonating.admin_name" class="text-white/80">
                        (admin: {{ impersonating.admin_name }})
                    </span>
                </span>
            </div>
            <button
                type="button"
                class="rounded-md bg-white px-3 py-1 text-xs font-semibold text-brand-burgundy transition hover:bg-white/90"
                @click="returnToAdmin"
            >
                Return to admin
            </button>
        </div>
    </div>
</template>
