<!-- resources/js/components/reusables/MyAlert.vue -->
<script setup lang="ts">
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface Props {
  title?: string
  subTitle?: string
  buttonText?: string
  variant?: 'info' | 'success' | 'warning' | 'neutral'
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subTitle: '',
  buttonText: '',
  variant: 'neutral',
})

const variantClasses = {
  info: 'bg-brand-surface-soft border-brand-border',
  success: 'bg-brand-success-soft border-brand-success',
  warning: 'bg-brand-surface-soft border-brand-border',
  neutral: 'bg-brand-surface border-brand-border',
}
</script>

<template>
  <div
    class="rounded-2xl border p-6 space-y-4"
    :class="variantClasses[variant]"
  >
    <MyTextConstructor variant="heading">
      <template #myTitle>{{ title }}</template>
    </MyTextConstructor>

    <MyTextConstructor variant="body" textColor="soft">
      {{ subTitle }}
    </MyTextConstructor>

    <MyButtonConstructor v-if="buttonText" variant="primary" size="small">
      {{ buttonText }}
    </MyButtonConstructor>
  </div>
</template>