<!-- resources/js/pages/auth/ResetPassword.vue -->
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { update } from '@/routes/password'

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
})

const props = defineProps<{
    token: string
    email: string
}>()

const inputEmail = ref(props.email)
</script>

<template>
    <Head title="Reset password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6">
            <div>
                <MyInputConstructor
                    type="email"
                    name="email"
                    label="Email"
                    size="small"
                    v-model="inputEmail"
                    readonly
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
                    autofocus
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
                Reset password
            </MyButtonConstructor>
        </div>
    </Form>
</template>
