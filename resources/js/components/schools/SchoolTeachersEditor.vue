<!-- resources/js/components/schools/SchoolTeachersEditor.vue -->
<!--
  Who works at a school, edited on the school's Edit page. v-model is the
  whole list; App\Services\SchoolLinks saves it. A teacher who has left is
  kept and ticked "No longer works here". Not who has had exams at the
  school's address: that is Trinity's venue, not the school's staff.
-->
<script setup lang="ts">
import { X } from 'lucide-vue-next'
import { computed } from 'vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import MyCheckboxConstructor from '@/components/reusables/MyCheckboxConstructor.vue'
import MySearchPickConstructor from '@/components/reusables/MySearchPickConstructor.vue'
import type { PickItem } from '@/components/reusables/MySearchPickConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import type { SchoolOption, SchoolTeacher } from '@/types/schools'

const props = defineProps<{ options: SchoolOption[] }>()
const teachers = defineModel<SchoolTeacher[]>({ required: true })

const items = computed<PickItem<number>[]>(() =>
    props.options
        .filter((o) => !teachers.value.some((t) => t.id === o.id))
        .map((o) => ({ value: o.id, label: o.name })),
)

function add(item: PickItem<number>) {
    teachers.value = [...teachers.value, { id: item.value, name: item.label, former: false }]
}
function remove(id: number) {
    teachers.value = teachers.value.filter((t) => t.id !== id)
}
function setFormer(id: number, former: boolean) {
    teachers.value = teachers.value.map((t) => (t.id === id ? { ...t, former } : t))
}
</script>

<template>
    <MyCardConstructor title="Teachers who work here">
        <div class="flex flex-col gap-3">
            <div class="text-sm">
                <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">
                    Staff at this school. A teacher can also enter pupils on their own; those entries stay theirs.
                </MyTextConstructor>
            </div>
            <div v-for="t in teachers" :key="t.id" class="flex flex-wrap items-center gap-3">
                <div class="min-w-40 flex-1 text-base">
                    <MyTextConstructor bodyVariant="inherit" :textColor="t.former ? 'text-brand-text-soft' : 'text-brand-text'" spacing="none">{{ t.name }}</MyTextConstructor>
                </div>
                <MyCheckboxConstructor :model-value="t.former" label="No longer works here" @update:model-value="(v) => setFormer(t.id, v)" />
                <MyButtonConstructor size="small" variant="outline" :icon="X" :aria-label="`Remove ${t.name}`" @click="remove(t.id)" />
            </div>
            <div v-if="!teachers.length" class="text-sm">
                <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">No teachers recorded yet.</MyTextConstructor>
            </div>
            <div class="w-full max-w-md">
                <MySearchPickConstructor :model-value="null" :items="items" placeholder="Add a teacher…" @pick="add" />
            </div>
        </div>
    </MyCardConstructor>
</template>
