<script setup lang="ts">
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue'
import { authConfig } from '@/composables/useAuthConfig'
</script>

<template>
    <AuthSimpleLayout :title="authConfig.title" :description="authConfig.description">
        <slot />
    </AuthSimpleLayout>
</template>
