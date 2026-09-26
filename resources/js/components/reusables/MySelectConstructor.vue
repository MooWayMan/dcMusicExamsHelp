<!-- resources/js/components/reusables/MySelectConstructor.vue -->
<script lang="ts">
export interface SelectOption<T extends string | number = string | number> {
  value: T
  label: string
}
</script>

<script setup lang="ts" generic="T extends string | number">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

defineOptions({ inheritAttrs: false })

interface Props {
  modelValue?: T | '' | null
  options: SelectOption<T>[]
  placeholder?: string
  label?: string
  ariaLabel?: string
  tone?: 'surface' | 'glass'
  size?: 'small' | 'medium'
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: '',
  label: '',
  ariaLabel: '',
  tone: 'surface',
  size: 'medium',
  disabled: false,
})

const emit = defineEmits<{ 'update:modelValue': [value: T] }>()

const selectId = `select-${Math.random().toString(36).slice(2, 11)}`

// The placeholder option carries '', which reaches the parent as "nothing chosen".
const value = computed({
  get: () => props.modelValue ?? '',
  set: (v: T | '') => emit('update:modelValue', v as T),
})

const sizeClasses = computed(() => (props.size === 'small' ? 'px-3 py-2.5 text-sm' : 'px-3 py-2.5 text-base'))

const toneClasses = computed(() =>
  props.tone === 'glass'
    ? 'border-white/20 bg-white/10 text-white backdrop-blur-sm'
    : 'border-brand-border bg-brand-surface text-brand-text',
)

const optionClass = 'text-brand-text'
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="selectId" class="mb-1 block">
      <MyTextConstructor variant="button-sm" spacing="none" :textColor="tone === 'glass' ? 'text-white/80' : 'text-brand-text-soft'">
        <template #myTitle>{{ label }}</template>
      </MyTextConstructor>
    </label>
    <select
      :id="selectId"
      v-model="value"
      :disabled="disabled"
      :aria-label="ariaLabel || label || undefined"
      class="w-full rounded-xl border focus:border-brand-accent focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
      :class="[sizeClasses, toneClasses]"
    >
      <option v-if="placeholder" :class="optionClass" value="">{{ placeholder }}</option>
      <option v-for="o in options" :key="o.value" :class="optionClass" :value="o.value">{{ o.label }}</option>
    </select>
  </div>
</template>
