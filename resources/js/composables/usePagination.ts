// resources/js/composables/usePagination.ts
//
// Client-side pagination for a list the page already holds in full.
//
// WHY CLIENT-SIDE. Every list screen in this app ships its rows in the Inertia
// payload — a teacher's roster, a term's history, a year's transactions are
// hundreds of rows, not thousands. Slicing them here keeps paging instant and
// keeps filtering/searching honest: a search must span the WHOLE list, not
// just the page you happen to be on. (The Lessons hub learned that the hard
// way on 7 Aug 2026 — the table was sorting 25 of 31 rows and calling it
// sorted, so the same pupil appeared twice on one screen.)
//
// Extracted 7 Aug 2026 from lessons/Index.vue, which had the only copy. Paul:
// "page with long lists need pagination" — plural, so it needed to stop being
// one page's private helper.
//
// Pair it with MyPaginationConstructor for the controls.
//
// Ported unchanged from MusicRegisterOnline, 21 Sep 2026 (Site stats' Day by
// day table). Keep the two copies identical; fix both or neither.

import { computed, isRef, ref, watch, type ComputedRef, type Ref } from 'vue';

export interface UsePaginationOptions {
    /** Rows per page. 25 matches the Lessons hub. */
    perPage?: number;
    /**
     * Scroll back to the top on a page change. On by default: paging while
     * halfway down a list otherwise leaves you mid-page with no idea the
     * content changed.
     */
    scrollToTop?: boolean;
}

export interface UsePaginationReturn<T> {
    /** Current page, 1-based. */
    page: Ref<number>;
    /** Total pages, never below 1 so the controls always have something to say. */
    pageCount: ComputedRef<number>;
    /** Just this page's slice — what the table should actually render. */
    paged: ComputedRef<T[]>;
    /** 1-based index of the first row on this page; 0 when the list is empty. */
    showingFrom: ComputedRef<number>;
    /** 1-based index of the last row on this page. */
    showingTo: ComputedRef<number>;
    /** Total rows across every page. */
    total: ComputedRef<number>;
    /** Clamped page setter. */
    goToPage: (n: number) => void;
}

export function usePagination<T>(
    source: Ref<T[]> | ComputedRef<T[]> | (() => T[]),
    options: UsePaginationOptions = {},
): UsePaginationReturn<T> {
    const perPage = Math.max(1, options.perPage ?? 25);
    const scrollToTop = options.scrollToTop ?? true;

    const rows = computed<T[]>(() =>
        typeof source === 'function' && !isRef(source) ? source() : (source as Ref<T[]>).value,
    );

    const page = ref<number>(1);

    const total = computed<number>(() => rows.value.length);
    const pageCount = computed<number>(() => Math.max(1, Math.ceil(total.value / perPage)));

    // Filtering can shrink the list under your feet — land on page 4 of a
    // 2-page result and you get a blank screen that looks like "no results".
    watch(pageCount, (count) => {
        if (page.value > count) {
            page.value = count;
        }
    });

    const paged = computed<T[]>(() => {
        const start = (page.value - 1) * perPage;

        return rows.value.slice(start, start + perPage);
    });

    const showingFrom = computed<number>(() =>
        total.value === 0 ? 0 : (page.value - 1) * perPage + 1,
    );
    const showingTo = computed<number>(() => Math.min(page.value * perPage, total.value));

    function goToPage(n: number): void {
        page.value = Math.min(Math.max(1, n), pageCount.value);

        if (scrollToTop && typeof window !== 'undefined') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    return { page, pageCount, paged, showingFrom, showingTo, total, goToPage };
}
