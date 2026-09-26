<!-- resources/js/components/reusables/MySearchPickConstructor.vue -->
<script lang="ts">
export interface PickItem<T extends string | number = string | number> {
  value: T
  label: string
  hint?: string | null
}
</script>

<script setup lang="ts" generic="T extends string | number">
import { Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

defineOptions({ inheritAttrs: false })

interface Props {
  modelValue?: T | null
  items: PickItem<T>[]
  placeholder?: string
  emptyText?: string
  tone?: 'surface' | 'glass'
  maxShown?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  placeholder: 'Type to search…',
  emptyText: 'Nothing matches',
  tone: 'surface',
  maxShown: 50,
})

const emit = defineEmits<{
  'update:modelValue': [value: T | null]
  pick: [item: PickItem<T>]
}>()

const term = ref('')
const open = ref(false)

function display(item: PickItem<T>): string {
  return item.hint ? `${item.hint} — ${item.label}` : item.label
}

const selected = computed(() => props.items.find((i) => i.value === props.modelValue) ?? null)

// Follow the chosen value: show its text when set, and clear the box when the
// parent clears a value that was picked here (not while someone is typing).
watch(selected, (item, previous) => {
  if (item) {
term.value = display(item)
} else if (previous && term.value === display(previous)) {
term.value = ''
}
}, { immediate: true })

const filtered = computed(() => {
  const t = term.value.trim().toLowerCase()

  if (!t || (selected.value && term.value === display(selected.value))) {
return props.items
}

  return props.items.filter((i) => display(i).toLowerCase().includes(t))
})

function onInput() {
  if (props.modelValue !== null) {
emit('update:modelValue', null)
}

  open.value = true
}
function choose(item: PickItem<T>) {
  term.value = display(item)
  open.value = false
  emit('update:modelValue', item.value)
  emit('pick', item)
}
function closeSoon() {
 setTimeout(() => {
 open.value = false 
}, 150) 
}

const glass = computed(() => props.tone === 'glass')
</script>

<template>
  <div class="w-full">
    <div class="relative">
      <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-brand-accent" />
      <input
        v-model="term"
        type="text"
        autocomplete="off"
        :placeholder="placeholder"
        class="w-full rounded-xl border py-2.5 pr-3 pl-9 text-base focus:border-brand-accent focus:outline-none"
        :class="glass ? 'border-white/20 bg-white/10 text-white placeholder:text-white/50' : 'border-brand-border bg-brand-surface text-brand-text placeholder:text-brand-text-soft'"
        @focus="open = true"
        @input="onInput"
        @blur="closeSoon"
      />
    </div>
    <ul
      v-if="open && filtered.length"
      class="mt-1 max-h-80 w-full overflow-y-auto rounded-xl border"
      :class="glass ? 'border-white/20 bg-white/5' : 'border-brand-border bg-brand-surface'"
    >
      <li v-for="item in filtered.slice(0, maxShown)" :key="item.value">
        <button
          type="button"
          class="block w-full px-3 py-2.5 text-left text-sm transition"
          :class="[
            glass ? 'text-white hover:bg-white/10' : 'text-brand-text hover:bg-brand-surface-soft',
            item.value === modelValue ? (glass ? 'bg-white/10' : 'bg-brand-surface-soft') : '',
          ]"
          @mousedown.prevent="choose(item)"
        >
          <span v-if="item.hint" :class="glass ? 'text-white/60' : 'text-brand-text-soft'">{{ item.hint }} — </span>{{ item.label }}
        </button>
      </li>
    </ul>
    <p
      v-else-if="open && term.trim() && !filtered.length"
      class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"
      :class="glass ? 'border-white/20 bg-white/5 text-white/70' : 'border-brand-border bg-brand-surface text-brand-text-soft'"
    >
      {{ emptyText }}
    </p>
  </div>
</template>
