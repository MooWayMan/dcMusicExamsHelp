<!-- resources/js/components/reusables/MyTableConstructor.vue -->
<script setup lang="ts">
import { computed, reactive } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

type Column = {
  key: string
  title: string
  width?: string
  sortable?: boolean
  sortFn?: (a: unknown, b: unknown, dir: 'asc' | 'desc') => number
  align?: 'left' | 'center' | 'right'
}

interface Props {
  data: any[]
  columns: Array<Column>
  rowKey?: string
  title?: string
  subtitle?: string
  headerColor?: string
  headerTextColor?: string
  size?: 'small' | 'medium' | 'large'
  striped?: boolean
  bordered?: boolean
  hoverable?: boolean
  responsive?: boolean
  /**
   * Under `sm:`, turn each row into a stacked label/value card instead of
   * leaving it to scroll sideways. Ported from MusicRegisterOnline,
   * 14 Aug 2026 — horizontal scroll is the FALLBACK, not the norm.
   *
   * ⚠️ OFF for grids: anything whose columns are a DIMENSION rather than a
   * set of fields (a day x time matrix) wants :stackOnMobile="false", or one
   * week becomes a list of every cell in it.
   */
  stackOnMobile?: boolean
  /**
   * How many columns a table needs before stacking is worth it. Default 5.
   *
   * 14 Aug 2026 — Paul photographed the public Exam fees page on his phone:
   * two columns, Grade and Fee, already fitting perfectly. Stacking it would
   * have turned nine tidy rows into eighteen lines, each carrying a label the
   * reader can already see in the header. Stacking is a fix for tables that
   * DON'T fit; applied to one that does, it is just damage.
   *
   * Counted per table at render time rather than set per caller, so a narrow
   * table nobody has thought about still does the right thing.
   *
   * ⚠️ FIVE, not four, and the fourth column is why. Paul photographed the
   * public UCAS points table on a phone: four columns whose values are "8",
   * "10", "12" — it fits anywhere, and stacking would have turned three rows
   * into twelve lines. Column count is a PROXY for width and this is where
   * the proxy fails. Every table confirmed broken on a phone has been 5+
   * (contacts 5, tidyup 7, transactions 7, the exam admin lists 5-9); every
   * one confirmed fine has been 4 or fewer. If a wide-ish 4-column table ever
   * does overflow, it scrolls — which is exactly what it did before.
   */
  stackFromColumns?: number
  clickableRows?: boolean
  clickableCells?: boolean
  fullWidth?: boolean
  bare?: boolean
  sortable?: boolean
  defaultSortKey?: string | null
  defaultSortDir?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  headerColor: 'bg-brand-primary',
  headerTextColor: 'text-brand-text-inverse',
  size: 'medium',
  striped: true,
  bordered: true,
  hoverable: true,
  responsive: true,
  stackOnMobile: true,
  stackFromColumns: 5,
  clickableRows: false,
  clickableCells: false,
  fullWidth: true,
  bare: false,
  sortable: true,
  defaultSortKey: null,
  defaultSortDir: 'asc',
})

const emit = defineEmits<{
  rowClick: [row: any, index: number]
  cellClick: [value: unknown, row: any, column: Column, index: number]
  sort: [{ key: string | null; dir: 'asc' | 'desc' }]
}>()

const state = reactive<{
  sortKey: string | null
  sortDir: 'asc' | 'desc'
}>({
  sortKey: props.defaultSortKey ?? null,
  sortDir: props.defaultSortDir,
})

function handleRowClick(row: Record<string, unknown>, index: number) {
  if (props.clickableRows) emit('rowClick', row, index)
}

function handleCellClick(
  value: unknown,
  row: Record<string, unknown>,
  column: Column,
  index: number
) {
  if (props.clickableCells) emit('cellClick', value, row, column, index)
}

function onCellClick(event: Event, row: Record<string, unknown>, column: Column, index: number) {
  event.stopPropagation()
  handleCellClick(row[column.key], row, column, index)
}

const sizeClasses = {
  small: 'text-xs sm:text-sm',
  medium: 'text-sm sm:text-base',
  large: 'text-base sm:text-lg',
}

const cellPadding = {
  small: 'px-2 py-1 sm:px-3 sm:py-2',
  medium: 'px-3 py-2 sm:px-4 sm:py-3',
  large: 'px-4 py-3 sm:px-6 sm:py-4',
}

const wrapperClasses = computed(() =>
  'my-4 rounded-xl border border-brand-border bg-brand-surface px-2 py-4 sm:my-8 sm:px-4 sm:py-8 md:my-10 md:px-6 md:py-10'
)

// `min-w-full` (NOT `w-full`): table is AT LEAST 100% of its scroll-wrapper, so
// it fills the card on desktop, but can grow past 100% when cell content forces
// it (e.g. `whitespace-nowrap` headers + pills on iPhone). That overflow is
// what the wrapper's `overflow-x-auto` needs to scroll. Paired with `min-w-0`
// on AppContent (AppSidebarLayout) which lets the flex item shrink to viewport.
const tableClasses = computed(() => ['min-w-full', sizeClasses[props.size]].join(' '))

const headerBaseClasses = computed(() =>
  [props.headerColor, props.headerTextColor, 'whitespace-nowrap font-bold', cellPadding[props.size]].join(' ')
)

const headerClickableClasses = 'cursor-pointer select-none hover:opacity-90'

const cellClasses = computed(() =>
  [
    cellPadding[props.size],
    'border-b border-r border-brand-border text-brand-text last:border-r-0',
    props.clickableCells ? 'cursor-pointer transition-colors duration-150 hover:bg-brand-surface-soft hover:text-brand-primary' : '',
  ]
    .filter(Boolean)
    .join(' ')
)

const rowClasses = computed(() =>
  [
    'bg-brand-surface last:border-b-0',
    props.hoverable && props.clickableRows ? 'cursor-pointer transition-colors duration-200 hover:bg-brand-surface-soft' : '',
  ]
    .filter(Boolean)
    .join(' ')
)

const stripedRowClasses = computed(() =>
  [
    'bg-brand-bg',
    props.hoverable && props.clickableRows ? 'cursor-pointer transition-colors duration-200 hover:bg-brand-surface-soft' : '',
  ]
    .filter(Boolean)
    .join(' ')
)

function toggleSortFor(column: Column) {
  if (!props.sortable || column.sortable === false || !column.key) return

  if (state.sortKey === column.key) {
    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc'
  } else {
    state.sortKey = column.key
    state.sortDir = 'asc'
  }

  emit('sort', { key: state.sortKey, dir: state.sortDir })
}

/*
 * Does THIS table stack? Wide enough to need it, and not opted out.
 */
const stacks = computed(
  () => props.stackOnMobile && props.columns.length >= props.stackFromColumns
)

/*
 * Stacking hides <thead>, and the sort buttons live in the header cells — so
 * without this a phone silently loses every sort the desktop has. Same state,
 * same emit, just a control that survives the stack.
 */
const sortableColumns = computed(() =>
  props.sortable ? props.columns.filter((c) => c.sortable !== false && !!c.key) : []
)

const showMobileSort = computed(() => stacks.value && sortableColumns.value.length > 0)

function setMobileSortKey(event: Event) {
  const key = (event.target as HTMLSelectElement).value

  state.sortKey = key === '' ? null : key

  emit('sort', { key: state.sortKey, dir: state.sortDir })
}

function toggleMobileSortDir() {
  if (!state.sortKey) {
    return
  }

  state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc'

  emit('sort', { key: state.sortKey, dir: state.sortDir })
}

function normalize(val: unknown) {
  if (val === null || val === undefined) return { type: 'null', v: 0 }
  if (typeof val === 'number') return { type: 'number', v: val }
  if (typeof val === 'boolean') return { type: 'number', v: val ? 1 : 0 }
  if (val instanceof Date) return { type: 'number', v: val.getTime() }

  const str = String(val).trim()

  // Dates in our standard "13 Jun 2026" display format (PHP `d M Y`) must
  // sort chronologically, not alphabetically — otherwise "01 Jul 2026"
  // sorts before "13 Jun 2026". Match that exact shape and sort by
  // timestamp so every date column across the admin orders correctly.
  if (/^\d{1,2} [A-Za-z]{3} \d{4}$/.test(str)) {
    const t = Date.parse(str)
    if (!Number.isNaN(t)) return { type: 'number', v: t }
  }

  const asNum = Number(val)
  if (!Number.isNaN(asNum) && str !== '') {
    return { type: 'number', v: asNum }
  }

  return { type: 'string', v: str.toLowerCase() }
}

const sortedData = computed(() => {
  const arr = props.data ?? []
  const key = state.sortKey
  const dir = state.sortDir

  if (!props.sortable || !key) return arr

  const col = props.columns.find((c) => c.key === key)
  const mul = dir === 'asc' ? 1 : -1
  const copy = arr.map((row, i) => ({ row, i }))

  copy.sort((a, b) => {
    const av = a.row[key]
    const bv = b.row[key]

    const an = av === null || av === undefined
    const bn = bv === null || bv === undefined

    if (an && !bn) return 1
    if (!an && bn) return -1
    if (an && bn) return a.i - b.i

    if (col?.sortFn) {
      const res = col.sortFn(av, bv, dir)
      if (res !== 0) return res
      return a.i - b.i
    }

    const na = normalize(av)
    const nb = normalize(bv)

    if (na.type === nb.type) {
      if (na.v < nb.v) return -1 * mul
      if (na.v > nb.v) return 1 * mul
      return a.i - b.i
    }

    if (na.type === 'number' && nb.type === 'string') return -1 * mul
    if (na.type === 'string' && nb.type === 'number') return 1 * mul

    return a.i - b.i
  })

  return copy.map((x) => x.row)
})

function sortIndicator(column: Column) {
  if (!props.sortable || column.sortable === false) return ''
  if (state.sortKey !== column.key) return '↕︎'
  return state.sortDir === 'asc' ? '↑' : '↓'
}
</script>

<template>
  <!-- When bare=true, skip the card wrapper and render the table directly -->
  <component :is="props.bare ? 'div' : 'div'" :class="props.bare ? 'w-full' : wrapperClasses">
    <div v-if="!props.bare && (props.title || props.subtitle)" class="mb-4 text-center">
      <MyTextConstructor
        v-if="props.title"
        variant="heading"
        alignment="center"
        spacing="tight"
        textColor="text-brand-primary"
      >
        <template #myTitle>
          {{ props.title }}
        </template>
      </MyTextConstructor>

      <MyTextConstructor
        v-if="props.subtitle"
        subTitleVariant="muted"
        alignment="center"
        spacing="none"
        textColor="text-brand-text-soft"
        class="mt-2"
      >
        <template #mySubTitle>
          {{ props.subtitle }}
        </template>
      </MyTextConstructor>
    </div>

    <!--
      Single scroll container. Old chain (responsive → fullWidth → tableBox)
      had three nested `w-full` wrappers which widened to fit the table's
      content on iPhone instead of letting it overflow. Canonical Tailwind
      responsive-table pattern: ONE wrapper, one table inside.
    -->
    <!-- Phones only. Hidden from `sm:` up, where the header cells are back
         and they are the better control (you can see all of them at once). -->
    <div
      v-if="showMobileSort && (data?.length ?? 0) > 0"
      class="mb-2 flex items-center gap-2 sm:hidden"
    >
      <span class="shrink-0 text-xs font-semibold text-brand-text-soft">Sort by</span>
      <select
        :value="state.sortKey ?? ''"
        class="min-w-0 flex-1 rounded-md border border-brand-border bg-brand-surface px-2 py-1.5 text-sm"
        aria-label="Sort by"
        @change="setMobileSortKey"
      >
        <option value="">The order it came in</option>
        <option v-for="column in sortableColumns" :key="column.key" :value="column.key">
          {{ column.title }}
        </option>
      </select>
      <button
        type="button"
        class="shrink-0 rounded-md border-2 border-brand-primary bg-brand-surface px-2 py-1.5 text-sm font-semibold text-brand-text disabled:opacity-50"
        :disabled="!state.sortKey"
        @click="toggleMobileSortDir"
      >
        {{ state.sortDir === 'asc' ? '↑ A–Z' : '↓ Z–A' }}
      </button>
    </div>

    <div :class="[
      'max-w-full',
      stacks ? 'stacked-table' : '',
      props.responsive ? 'overflow-x-auto' : '',
      'rounded-lg',
      props.bordered ? 'border-4 border-brand-primary' : '',
    ]">
          <table :class="tableClasses">
            <thead>
              <tr>
                <th
                  v-for="column in columns"
                  :key="column.key"
                  scope="col"
                  :class="[headerBaseClasses, props.sortable && column.sortable !== false ? headerClickableClasses : '']"
                  :style="column.width ? { width: column.width } : {}"
                  :aria-sort="state.sortKey === column.key ? (state.sortDir === 'asc' ? 'ascending' : 'descending') : 'none'"
                  @click="toggleSortFor(column)"
                >
                  <div class="flex items-center gap-2">
                    <MyTextConstructor
                      variant="button"
                      alignment="left"
                      spacing="none"
                      :textColor="props.headerTextColor"
                    >
                      <template #myTitle>
                        {{ column.title }}
                      </template>
                    </MyTextConstructor>

                    <span
                      v-if="props.sortable && column.sortable !== false"
                      class="text-xs opacity-80"
                    >
                      {{ sortIndicator(column) }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(row, index) in sortedData"
                :key="props.rowKey ? String(row[props.rowKey]) : index"
                :class="props.striped && index % 2 === 1 ? stripedRowClasses : rowClasses"
                @click="handleRowClick(row, index)"
              >
                <td
                  v-for="column in columns"
                  :key="column.key"
                  :data-label="column.title || ''"
                  :class="[
                    cellClasses,
                    column.align === 'right'
                      ? 'text-right'
                      : column.align === 'center'
                        ? 'text-center'
                        : 'text-left'
                  ]"
                  @click="props.clickableCells ? onCellClick($event, row, column, index) : undefined"
                >
                  <template v-if="$slots[`cell-${column.key}`]">
                    <slot
                      :name="`cell-${column.key}`"
                      :value="row[column.key]"
                      :row="row"
                      :index="index"
                    />
                  </template>

                  <template v-else>
                    <MyTextConstructor
                      bodyVariant="muted"
                      alignment="left"
                      spacing="none"
                      textColor="text-brand-text"
                    >
                      {{ row[column.key] }}
                    </MyTextConstructor>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="(data?.length ?? 0) === 0"
          class="rounded-lg bg-brand-surface py-8 text-center"
        >
          <MyTextConstructor
            variant="subheading"
            alignment="center"
            spacing="tight"
            textColor="text-brand-text-soft"
          >
            <template #myTitle>
              No data available
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            subTitleVariant="muted"
            alignment="center"
            spacing="none"
            textColor="text-brand-text-soft"
            class="mt-2"
          >
            <template #mySubTitle>
              Add some data to see it displayed here
            </template>
          </MyTextConstructor>
        </div>
  </component>
</template>

<style scoped>
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}
</style>