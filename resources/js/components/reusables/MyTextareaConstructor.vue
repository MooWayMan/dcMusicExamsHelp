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
  size?: 'small' | 'medium' | 'large'
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
  size: 'medium',
})

const sizeClass = computed(() => {
  switch (props.size) {
    case 'small':
      return 'px-3 py-2 text-lg sm:text-xl'
    case 'large':
      return 'px-5 py-3.5 text-2xl sm:text-3xl md:text-4xl'
    default:
      return 'px-4 py-3 text-xl sm:text-2xl md:text-3xl'
  }
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
    <MyTextConstructor
      v-if="label"
      variant="button-lg"
      alignment="left"
      textColor="text-brand-text"
      spacing="none"
    >
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
      class="w-full rounded-xl border bg-brand-surface text-brand-text transition focus:outline-none focus:ring-2"
      :class="[sizeClass, stateClass]"
    />

    <!-- ERROR -->
    <div v-if="error" class="mt-2">
      <MyTextConstructor
        subTitleVariant="muted"
        alignment="left"
        textColor="text-brand-danger"
        spacing="none"
      >
        <template #mySubTitle>{{ error }}</template>
      </MyTextConstructor>
    </div>

    <!-- SUCCESS -->
    <div v-if="success" class="mt-2">
      <MyTextConstructor
        subTitleVariant="muted"
        alignment="left"
        textColor="text-brand-success"
        spacing="none"
      >
        <template #mySubTitle>{{ success }}</template>
      </MyTextConstructor>
    </div>
  </div>
</template>