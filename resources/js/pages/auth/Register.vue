<!-- resources/js/pages/auth/Register.vue -->
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { login } from '@/routes'
import { store } from '@/routes/register'

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
})
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div>
                <MyInputConstructor
                    type="text"
                    name="name"
                    label="Name"
                    placeholder="Full name"
                    size="small"
                    required
                    autofocus
                    autocomplete="name"
                    :error="errors.name"
                />
            </div>

            <div>
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
            </div>

            <div>
                <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
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
                    placeholder="Confirm password"
                    class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-lg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent sm:text-xl"
                />
                <p v-if="errors.password_confirmation" class="mt-1 text-sm text-brand-danger">{{ errors.password_confirmation }}</p>
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

        <div class="text-center text-base text-brand-text-soft sm:text-lg">
            Already have an account?
            <TextLink :href="login()" class="font-semibold text-brand-accent hover:underline">Log in</TextLink>
        </div>
    </Form>
</template>
