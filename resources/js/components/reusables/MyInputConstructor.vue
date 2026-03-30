<!-- resources/js/components/reusables/MyInputConstructor.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  size?: 'small' | 'medium' | 'large'
  variant?: 'solid' | 'outline' | 'ghost'
  placeholder?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  autofocus?: boolean
  label?: string
  error?: string
  success?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  type: 'text',
  size: 'medium',
  variant: 'outline',
  placeholder: '',
  disabled: false,
  readonly: false,
  required: false,
  autofocus: false,
  label: '',
  error: '',
  success: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  focus: [event: FocusEvent]
  blur: [event: FocusEvent]
  keyup: [event: KeyboardEvent]
  keydown: [event: KeyboardEvent]
  enter: [event: KeyboardEvent]
}>()

const inputId = `input-${Math.random().toString(36).slice(2, 11)}`

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'small':
      return 'min-h-9 px-3 py-2 text-lg sm:text-xl'
    case 'large':
      return 'min-h-14 px-5 py-3.5 text-2xl sm:text-3xl md:text-4xl'
    default:
      return 'min-h-12 px-4 py-3 text-xl sm:text-2xl md:text-3xl'
  }
})

const inputClasses = computed(() => {
  const base =
    'w-full rounded-xl border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2'

  if (props.error) {
    return [
      base,
      sizeClasses.value,
      'border-brand-danger bg-brand-surface text-brand-text placeholder:text-brand-text-soft focus:border-brand-danger focus:ring-brand-danger',
      props.disabled ? 'cursor-not-allowed opacity-60' : '',
    ].join(' ')
  }

  if (props.success) {
    return [
      base,
      sizeClasses.value,
      'border-brand-success bg-brand-surface text-brand-text placeholder:text-brand-text-soft focus:border-brand-success focus:ring-brand-success',
      props.disabled ? 'cursor-not-allowed opacity-60' : '',
    ].join(' ')
  }

  if (props.disabled) {
    return [
      base,
      sizeClasses.value,
      'cursor-not-allowed border-brand-border bg-brand-surface-soft text-brand-text-soft placeholder:text-brand-text-soft opacity-60',
    ].join(' ')
  }

  switch (props.variant) {
    case 'solid':
      return [
        base,
        sizeClasses.value,
        'border-brand-border bg-brand-bg text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:ring-brand-accent',
      ].join(' ')
    case 'ghost':
      return [
        base,
        sizeClasses.value,
        'border-transparent bg-brand-bg text-brand-text placeholder:text-brand-text-soft hover:bg-brand-surface-soft focus:border-brand-accent focus:ring-brand-accent',
      ].join(' ')
    default:
      return [
        base,
        sizeClasses.value,
        'border-brand-border bg-brand-surface text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:ring-brand-accent',
      ].join(' ')
  }
})

const handleInput = (event: Event) => {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}

const handleFocus = (event: FocusEvent) => emit('focus', event)
const handleBlur = (event: FocusEvent) => emit('blur', event)
const handleKeyup = (event: KeyboardEvent) => {
  emit('keyup', event)
  if (event.key === 'Enter') emit('enter', event)
}
const handleKeydown = (event: KeyboardEvent) => emit('keydown', event)
</script>

<template>
  <div class="w-full">
    <div v-if="label" class="mb-2">
      <MyTextConstructor
        variant="button-lg"
        alignment="left"
        textColor="text-brand-text"
        spacing="none"
      >
        <template #myTitle>
          {{ label }}
          <span v-if="required" class="ml-1 text-brand-danger">*</span>
        </template>
      </MyTextConstructor>
    </div>

    <input
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :autofocus="autofocus"
      :class="inputClasses"
      :aria-invalid="!!error"
      :aria-describedby="error || success ? `${inputId}-message` : undefined"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      @keyup="handleKeyup"
      @keydown="handleKeydown"
    />

    <div v-if="error || success" :id="`${inputId}-message`" class="mt-2">
      <MyTextConstructor
        subTitleVariant="muted"
        alignment="left"
        :textColor="error ? 'text-brand-danger' : 'text-brand-success'"
        spacing="none"
      >
        <template #mySubTitle>
          {{ error || success }}
        </template>
      </MyTextConstructor>
    </div>
  </div>
</template>

<style scoped>
input {
  appearance: none;
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px transparent inset;
  -webkit-text-fill-color: inherit;
  transition: background-color 5000s ease-in-out 0s;
}
</style>