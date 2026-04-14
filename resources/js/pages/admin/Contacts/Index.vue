<!-- resources/js/pages/admin/Contacts/Index.vue -->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import { Search } from 'lucide-vue-next'

interface Contact {
    id: number
    name: string
    email: string | null
    phone: string | null
    role: string
}

interface PaginatedData {
    data: Contact[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    contacts: PaginatedData
    filters: { search: string | null; role: string | null }
}>()

const search = ref(props.filters.search ?? '')
let timeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        router.get('/admin/contacts', {
            search: value || undefined,
            role: props.filters.role || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

function filterRole(role: string | null) {
    router.get('/admin/contacts', {
        search: search.value || undefined,
        role: role || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader
            title="Contacts"
            subtitle="All people in the system"
            eyebrow="Admin"
            size="compact"
        />

        <!-- Search -->
        <div class="mt-6 flex items-center gap-4">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search contacts..."
                    class="w-full rounded-lg border border-brand-border bg-brand-surface py-3 pl-10 pr-4 text-lg"
                />
            </div>

            <p class="text-base text-brand-text-soft">
                {{ contacts.total }} contacts
            </p>
        </div>

        <!-- Role filter -->
        <div class="mt-3 flex gap-2">
            <button @click="filterRole(null)" class="btn">All</button>
            <button @click="filterRole('teacher')" class="btn">Teachers</button>
            <button @click="filterRole('parent')" class="btn">Parents</button>
            <button @click="filterRole('admin')" class="btn">Admins</button>
        </div>

        <!-- Table -->
        <div class="mt-4 rounded-xl border border-brand-border bg-brand-surface">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-brand-border">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="contact in contacts.data" :key="contact.id"
                        class="hover:bg-brand-surface-soft cursor-pointer"
                        @click="router.visit(`/admin/contacts/${contact.id}`)">
                        <td class="px-4 py-3">{{ contact.name }}</td>
                        <td class="px-4 py-3">{{ contact.email }}</td>
                        <td class="px-4 py-3">{{ contact.role }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>