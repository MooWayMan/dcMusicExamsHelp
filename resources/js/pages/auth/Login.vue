<!-- resources/js/pages/auth/Login.vue -->
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Spinner } from '@/components/ui/spinner'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
})

defineProps<{
    status?: string
    canResetPassword: boolean
    canRegister: boolean
}>()
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 rounded-lg bg-brand-success-soft p-3 text-center text-base font-medium text-brand-success"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div>
                <MyInputConstructor
                    type="email"
                    name="email"
                    label="Email address"
                    placeholder="email@example.com"
                    size="small"
                    required
                    autofocus
                    autocomplete="email"
                    :error="errors.email"
                />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="text-lg font-semibold text-brand-text sm:text-xl">Password</label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-base text-brand-accent hover:underline"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent sm:text-xl"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-brand-danger">{{ errors.password }}</p>
            </div>

            <div class="flex items-center gap-3">
                <Checkbox id="remember" name="remember" />
                <label for="remember" class="text-base text-brand-text sm:text-lg">Remember me</label>
            </div>

            <MyButtonConstructor
                type="submit"
                variant="primary"
                size="large"
                fullWidth
                :disabled="processing"
            >
                <Spinner v-if="processing" class="mr-2" />
                Log in
            </MyButtonConstructor>
        </div>

        <div
            v-if="canRegister"
            class="text-center text-base text-brand-text-soft sm:text-lg"
        >
            Don't have an account?
            <TextLink :href="register()" class="font-semibold text-brand-accent hover:underline">Sign up</TextLink>
        </div>
    </Form>
</template>
