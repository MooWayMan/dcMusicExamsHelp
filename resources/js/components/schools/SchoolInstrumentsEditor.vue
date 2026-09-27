<!-- resources/js/components/schools/SchoolInstrumentsEditor.vue -->
<!--
  What a school teaches, ticked on the school's Edit page. v-model is the
  list of instrument ids; App\Services\SchoolLinks saves it.
-->
<script setup lang="ts">
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import MyCheckboxConstructor from '@/components/reusables/MyCheckboxConstructor.vue'
import type { SchoolOption } from '@/types/schools'

defineProps<{ options: SchoolOption[] }>()
const ids = defineModel<number[]>({ required: true })

function set(id: number, on: boolean) {
    ids.value = on ? [...ids.value, id] : ids.value.filter((i) => i !== id)
}
</script>

<template>
    <MyCardConstructor title="Instruments taught">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            <MyCheckboxConstructor
                v-for="i in options"
                :key="i.id"
                :model-value="ids.includes(i.id)"
                :label="i.name"
                @update:model-value="(on) => set(i.id, on)"
            />
        </div>
    </MyCardConstructor>
</template>
