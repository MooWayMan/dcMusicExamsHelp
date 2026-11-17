<!-- resources/js/components/paymentdocuments/WhatForPicker.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import MySelectConstructor from '@/components/reusables/MySelectConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

// What a payment document, or one of its lines, is for: a dropdown of the
// usual types (Exam steward, Venue rent…) and every type already used, plus
// "New type…", which opens a box to type one. Used for the document's
// "What for" and for each line, so both behave the same.

const NEW_TYPE = '__new_type__'

const props = withDefaults(
    defineProps<{
        modelValue: string
        types: string[]
        label: string
        error?: string
    }>(),
    { error: '' },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const typingNew = ref(props.modelValue !== '' && !props.types.includes(props.modelValue))

watch(
    () => props.modelValue,
    (value) => {
        if (props.types.includes(value)) typingNew.value = false
        else if (value !== '') typingNew.value = true
    },
)

const options = computed(() => [...props.types.map((t) => ({ value: t, label: t })), { value: NEW_TYPE, label: 'New type…' }])

const choice = computed({
    get: () => (typingNew.value ? NEW_TYPE : props.modelValue),
    set: (value: string) => {
        typingNew.value = value === NEW_TYPE
        emit('update:modelValue', value === NEW_TYPE ? '' : value)
    },
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <MySelectConstructor v-model="choice" :options="options" size="small" :label="label" placeholder="Choose…" />
        <MyInputConstructor
            v-if="typingNew"
            :model-value="modelValue"
            size="compact"
            placeholder="Type the new type, e.g. Piano tuning"
            :aria-label="`New ${label.toLowerCase()}`"
            :error="error"
            @update:model-value="(v) => emit('update:modelValue', String(v))"
        />
        <MyTextConstructor v-else-if="error" textColor="text-brand-danger" spacing="none">
            <template #myPara>{{ error }}</template>
        </MyTextConstructor>
    </div>
</template>
