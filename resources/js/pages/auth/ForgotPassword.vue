<!-- resources/js/pages/auth/ForgotPassword.vue -->
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import TextLink from '@/components/TextLink.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { login } from '@/routes'
import { email } from '@/routes/password'

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
})

defineProps<{
    status?: string
}>()
</script>

<template>
    <Head title="Forgot password" />

    <div
        v-if="status"
        class="mb-4 rounded-lg bg-brand-success-soft p-3 text-center text-base font-medium text-brand-success"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div>
                <MyInputConstructor
                    type="email"
                    name="email"
                    label="Email address"
                    placeholder="email@example.com"
                    size="small"
                    autofocus
                    autocomplete="off"
                    :error="errors.email"
                />
            </div>

            <div class="mt-6">
                <MyButtonConstructor
                    type="submit"
                    variant="primary"
                    size="large"
                    fullWidth
                    :disabled="processing"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    Email password reset link
                </MyButtonConstructor>
            </div>
        </Form>

        <div class="text-center text-base text-brand-text-soft sm:text-lg">
            Or, return to
            <TextLink :href="login()" class="font-semibold text-brand-accent hover:underline">log in</TextLink>
        </div>
    </div>
</template>
