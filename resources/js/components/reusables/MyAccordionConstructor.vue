<!-- resources/js/components/reusables/MyAccordionConstructor.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface AccordionItem {
  id: string | number
  question: string
  answer?: string
}

interface Props {
  items?: AccordionItem[]
  allowMultiple?: boolean
  size?: 'small' | 'medium' | 'large'
  headerBgColor?: string
  headerTextColor?: string
  headerHoverBgColor?: string
  borderColor?: string
  contentBgColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  items: () => [] as AccordionItem[],
  allowMultiple: false,
  size: 'medium',
  headerBgColor: 'bg-brand-surface',
  headerTextColor: 'text-brand-primary',
  headerHoverBgColor: 'hover:bg-brand-bg',
  borderColor: 'border-brand-border',
  contentBgColor: 'bg-brand-surface',
})

const openItems = ref<Set<string | number>>(new Set())

const toggleItem = (itemId: string | number) => {
  if (props.allowMultiple) {
    if (openItems.value.has(itemId)) {
      openItems.value.delete(itemId)
    } else {
      openItems.value.add(itemId)
    }
    openItems.value = new Set(openItems.value)
  } else {
    if (openItems.value.has(itemId)) {
      openItems.value.clear()
    } else {
      openItems.value = new Set([itemId])
    }
  }
}

const isOpen = (itemId: string | number) => openItems.value.has(itemId)

const sizeKey = computed<NonNullable<Props['size']>>(() => props.size ?? 'medium')

const headerPadding = computed(() => {
  const map = {
    small: 'px-4 py-3',
    medium: 'px-5 py-4',
    large: 'px-6 py-5',
  } as const
  return map[sizeKey.value]
})

const chevronSize = computed(() => {
  const map = {
    small: 'h-5 w-5',
    medium: 'h-6 w-6',
    large: 'h-7 w-7',
  } as const
  return map[sizeKey.value]
})
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-4">
    <div
      v-for="item in props.items"
      :key="item.id"
      class="overflow-hidden rounded-2xl border shadow-sm"
      :class="props.borderColor"
    >
      <button
        type="button"
        :id="`accordion-btn-${item.id}`"
        :aria-expanded="isOpen(item.id)"
        :aria-controls="`accordion-panel-${item.id}`"
        :class="[
          'flex w-full items-center justify-between transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-accent',
          props.headerBgColor,
          props.headerTextColor,
          props.headerHoverBgColor,
          headerPadding
        ]"
        @click="toggleItem(item.id)"
      >
        <MyTextConstructor
          variant="subheading"
          alignment="left"
          textColor="inherit"
          spacing="none"
          class="flex-1"
        >
          <template #myTitle>
            {{ item.question }}
          </template>
        </MyTextConstructor>

        <svg
          :class="[
            'ml-4 shrink-0 transform transition-transform duration-200',
            chevronSize,
            isOpen(item.id) ? 'rotate-90' : ''
          ]"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <div
        :id="`accordion-panel-${item.id}`"
        role="region"
        :aria-labelledby="`accordion-btn-${item.id}`"
        class="overflow-hidden transition-all duration-300 ease-in-out"
        :class="isOpen(item.id) ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0'"
      >
        <div class="px-5 pb-5 pt-1 sm:px-6 sm:pb-6" :class="props.contentBgColor">
          <slot :name="`content-${item.id}`">
            <MyTextConstructor
              v-if="item.answer"
              bodyVariant="body"
              alignment="left"
              spacing="none"
            >
              {{ item.answer }}
            </MyTextConstructor>
          </slot>
        </div>
      </div>
    </div>

    <div
      v-if="props.items.length === 0"
      class="rounded-2xl bg-brand-surface py-12 text-center shadow-sm"
    >
      <MyTextConstructor variant="subheading" alignment="center" spacing="tight">
        <template #myTitle>No FAQ items yet</template>
      </MyTextConstructor>

      <MyTextConstructor
        variant="muted"
        alignment="center"
        spacing="none"
        class="mt-2"
      >
        <template #mySubTitle>Add some items to see your FAQ</template>
      </MyTextConstructor>
    </div>
  </div>
</template>