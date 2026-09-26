<!-- resources/js/components/reusables/MyCheckboxConstructor.vue -->
<script setup lang="ts">
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

defineOptions({ inheritAttrs: false })

// A tick box with its label. The label is optional so a table column
// headed by the question can hold just the box; give it an ariaLabel then.
withDefaults(defineProps<{
  label?: string
  ariaLabel?: string
  disabled?: boolean
}>(), {
  label: '',
  ariaLabel: '',
  disabled: false,
})

const checked = defineModel<boolean>({ required: true })
</script>

<template>
  <label class="inline-flex cursor-pointer items-center gap-2 text-sm" :class="disabled ? 'cursor-not-allowed opacity-60' : ''">
    <input
      v-model="checked"
      type="checkbox"
      :disabled="disabled"
      :aria-label="ariaLabel || label || undefined"
      class="h-5 w-5 rounded border-brand-border accent-brand-accent"
    >
    <MyTextConstructor v-if="label" bodyVariant="inherit" spacing="none">{{ label }}</MyTextConstructor>
  </label>
</template>
