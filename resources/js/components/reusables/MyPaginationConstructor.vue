<!-- resources/js/components/reusables/MyPaginationConstructor.vue -->
<!--
  MyPaginationConstructor — the paging controls for any long list.

  Pairs with the usePagination composable: that owns the slicing, this owns
  the controls. Extracted 7 Aug 2026 (Paul: "page with long lists need
  pagination") from the one hand-rolled copy in lessons/Index.vue, so every
  list screen pages the same way instead of each growing its own.

  Renders nothing when there is only one page — controls that can't do
  anything are noise.

  Navy borders and 44px tap targets to match the rest of the app.

  Ported from MusicRegisterOnline, 21 Sep 2026. Only change: the active
  page's text uses the brand-text-inverse token instead of text-white.
-->
<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        page: number;
        pageCount: number;
        showingFrom: number;
        showingTo: number;
        total: number;
        /** What the rows are, for the count line: "showing 1–25 of 63 changes". */
        noun?: string;
        nounPlural?: string;
    }>(),
    { noun: 'item', nounPlural: '' },
);

const emit = defineEmits<{ (e: 'update:page', value: number): void }>();

const label = computed<string>(() =>
    props.total === 1 ? props.noun : props.nounPlural || `${props.noun}s`,
);

/**
 * Page numbers to offer: first, last, and a window around the current page,
 * with gaps marked by null. Keeps a 40-page list to one row of buttons.
 */
const pages = computed<(number | null)[]>(() => {
    const count = props.pageCount;
    const current = props.page;

    if (count <= 7) {
        return Array.from({ length: count }, (_, i) => i + 1);
    }

    const out: (number | null)[] = [1];
    const from = Math.max(2, current - 1);
    const to = Math.min(count - 1, current + 1);

    if (from > 2) out.push(null);
    for (let i = from; i <= to; i++) out.push(i);
    if (to < count - 1) out.push(null);
    out.push(count);

    return out;
});

function go(n: number): void {
    emit('update:page', Math.min(Math.max(1, n), props.pageCount));
}

const BTN =
    'inline-flex h-11 min-w-11 items-center justify-center rounded-full border-2 px-3 text-sm font-semibold transition-colors';
const IDLE =
    'border-brand-primary bg-brand-surface text-brand-text hover:bg-brand-surface-soft dark:border-brand-accent';
const ACTIVE = 'border-brand-cta bg-brand-cta text-brand-text-inverse';
const OFF = 'cursor-not-allowed border-brand-border bg-brand-surface-soft text-brand-text-soft';
</script>

<template>
    <div
        v-if="pageCount > 1"
        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-brand-border pt-4"
    >
        <p class="text-xs text-brand-text-soft [font-variant-numeric:tabular-nums]">
            Showing {{ showingFrom }}–{{ showingTo }} of {{ total }} {{ label }}
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            <button
                type="button"
                :class="[BTN, page <= 1 ? OFF : IDLE]"
                :disabled="page <= 1"
                aria-label="Previous page"
                @click="go(page - 1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>

            <template v-for="(p, i) in pages" :key="`p-${i}`">
                <span
                    v-if="p === null"
                    class="px-1 text-sm text-brand-text-soft"
                    aria-hidden="true"
                    >…</span
                >
                <button
                    v-else
                    type="button"
                    :class="[BTN, '[font-variant-numeric:tabular-nums]', p === page ? ACTIVE : IDLE]"
                    :aria-current="p === page ? 'page' : undefined"
                    :aria-label="`Page ${p}`"
                    @click="go(p)"
                >
                    {{ p }}
                </button>
            </template>

            <button
                type="button"
                :class="[BTN, page >= pageCount ? OFF : IDLE]"
                :disabled="page >= pageCount"
                aria-label="Next page"
                @click="go(page + 1)"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
