<!-- resources/js/components/reusables/MyTextareaConstructor.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  modelValue?: string
  label?: string
  placeholder?: string
  error?: string
  success?: string
  disabled?: boolean
  readonly?: boolean
  rows?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  label: '',
  placeholder: '',
  error: '',
  success: '',
  disabled: false,
  readonly: false,
  rows: 4,
})

const emit = defineEmits(['update:modelValue'])

const stateClass = computed(() => {
  if (props.error) return 'border-brand-danger focus:ring-brand-danger'
  if (props.success) return 'border-brand-success focus:ring-brand-success'
  return 'border-brand-border focus:ring-brand-accent'
})
</script>

<template>
  <div class="space-y-2">
    <!-- LABEL -->
    <MyTextConstructor v-if="label" variant="label">
      <template #myTitle>{{ label }}</template>
    </MyTextConstructor>

    <!-- TEXTAREA -->
    <textarea
      :value="modelValue"
      @input="emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :rows="rows"
      :disabled="disabled"
      :readonly="readonly"
      class="w-full rounded-xl border bg-brand-surface px-4 py-3 text-base text-brand-text transition focus:outline-none focus:ring-2"
      :class="stateClass"
    />

    <!-- ERROR -->
    <MyTextConstructor v-if="error" variant="caption" textColor="danger">
      <template #myTitle>{{ error }}</template>
    </MyTextConstructor>

    <!-- SUCCESS -->
    <MyTextConstructor v-if="success" variant="caption" textColor="success">
      <template #myTitle>{{ success }}</template>
    </MyTextConstructor>
  </div>
</template>