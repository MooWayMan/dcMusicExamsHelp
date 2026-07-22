<!-- resources/js/pages/auth/Register.vue -->
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import TextLink from '@/components/TextLink.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { store as login } from '@/routes/login'

// Wayfinder may not yet expose `@/routes/register` on first dev/build after
// turning registration back on (it's regenerated when Vite runs against the
// freshly-routed app), so we POST to the literal `/register` URL — Fortify
// owns this route and the path is part of its public contract.

defineOptions({
    layout: {
        title: 'Create your account',
        description: 'Sign up to see your candidates and download their results',
    },
})

const props = defineProps<{
    roles: string[]
}>()

const roleOptions: { value: string; label: string; description: string }[] = [
    {
        value: 'teacher',
        label: 'Teacher',
        description: "I'm a music teacher entering candidates through centre 120 — or planning to.",
    },
    {
        value: 'parent',
        label: 'Parent / Guardian',
        description: "I'm the parent or guardian of an under-18 candidate.",
    },
    {
        value: 'self',
        label: 'Adult candidate (18+)',
        description: "I'm taking the exam myself and I'm 18 or over.",
    },
    {
        value: 'school_admin',
        label: 'School Admin',
        description: "I'm school office staff arranging exams for our pupils.",
    },
]

// Filter to only show roles the backend will accept (admin is excluded
// server-side; this is belt-and-braces for the UI).
const visibleRoles = roleOptions.filter((opt) => props.roles.includes(opt.value) && opt.value !== 'admin')
</script>

<template>
    <Head title="Create your account" />

    <Form
        action="/register"
        method="post"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <MyInputConstructor
                type="text"
                name="name"
                label="Your name"
                placeholder="Jane Smith"
                size="small"
                required
                autofocus
                autocomplete="name"
                :error="errors.name"
            />

            <MyInputConstructor
                type="email"
                name="email"
                label="Email address"
                placeholder="email@example.com"
                size="small"
                required
                autocomplete="email"
                :error="errors.email"
            />

            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">
                    I&rsquo;m signing up as
                </label>
                <div class="flex flex-col gap-2">
                    <label
                        v-for="opt in visibleRoles"
                        :key="opt.value"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border border-brand-border bg-brand-surface p-3 transition-colors hover:border-brand-accent has-[:checked]:border-brand-accent has-[:checked]:bg-brand-accent/5"
                    >
                        <input
                            type="radio"
                            name="role"
                            :value="opt.value"
                            class="mt-1 h-4 w-4 shrink-0 accent-brand-accent"
                            required
                        />
                        <span class="flex flex-col">
                            <span class="text-base font-semibold text-brand-text">{{ opt.label }}</span>
                            <span class="text-sm text-brand-text-soft">{{ opt.description }}</span>
                        </span>
                    </label>
                </div>
                <p v-if="errors.role" class="mt-1 text-sm text-brand-danger">{{ errors.role }}</p>
            </div>

            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Choose a password"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent sm:text-xl"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-brand-danger">{{ errors.password }}</p>
            </div>

            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">Confirm password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Type it again"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent sm:text-xl"
                />
                <p v-if="errors.password_confirmation" class="mt-1 text-sm text-brand-danger">{{ errors.password_confirmation }}</p>
            </div>

            <div>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-brand-border bg-brand-surface p-3 transition-colors hover:border-brand-accent has-[:checked]:border-brand-accent has-[:checked]:bg-brand-accent/5"
                >
                    <input
                        type="checkbox"
                        name="marketing_consent"
                        value="1"
                        class="mt-1 h-4 w-4 shrink-0 accent-brand-accent"
                    />
                    <span class="flex flex-col">
                        <span class="text-base font-semibold text-brand-text">Keep me in the loop</span>
                        <span class="text-sm text-brand-text-soft">
                            We&rsquo;re building lots of useful new apps and tools for music teachers &mdash; tick to be
                            the first to hear about them. Occasional emails only, and you can change this any time in
                            your account settings.
                        </span>
                    </span>
                </label>
                <p class="mt-1 text-sm text-brand-text-soft">
                    We&rsquo;ll never share your email address &mdash; see our
                    <a href="/privacy" class="text-brand-accent hover:underline">privacy policy</a>.
                </p>
                <p class="mt-2 text-sm text-brand-text-soft">
                    Creating an account also means we&rsquo;ll email you about your exams &mdash; results,
                    reminders and account notices. Those aren&rsquo;t marketing, so you&rsquo;ll get them
                    whether or not you tick the box above.
                </p>
            </div>

            <MyButtonConstructor
                type="submit"
                variant="primary"
                size="large"
                fullWidth
                :disabled="processing"
            >
                <Spinner v-if="processing" class="mr-2" />
                Create account
            </MyButtonConstructor>
        </div>

        <div class="flex flex-col items-center gap-2 text-center">
            <p class="text-base text-brand-text-soft sm:text-lg">
                Already have an account?
                <TextLink :href="login().url" class="text-brand-accent hover:underline">Log in</TextLink>
            </p>
            <a href="/" class="text-base text-brand-text-soft hover:text-brand-accent hover:underline sm:text-lg">
                ← Return to home page
            </a>
        </div>
    </Form>
</template>
