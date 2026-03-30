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
  data: Array<Record<string, unknown>>
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
  clickableRows?: boolean
  clickableCells?: boolean
  sortable?: boolean
  defaultSortKey?: string | null
  defaultSortDir?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  headerColor: 'bg-blue-900',
  headerTextColor: 'text-white',
  size: 'medium',
  striped: true,
  bordered: true,
  hoverable: true,
  responsive: true,
  clickableRows: false,
  clickableCells: false,
  sortable: true,
  defaultSortKey: null,
  defaultSortDir: 'asc',
})

const emit = defineEmits<{
  rowClick: [row: Record<string, unknown>, index: number]
  cellClick: [value: unknown, row: Record<string, unknown>, column: Column, index: number]
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
  'my-4 rounded-xl border border-black/10 bg-white px-3 py-6 sm:my-8 sm:px-4 sm:py-8 md:my-10 md:px-6 md:py-10'
)

const tableBoxClasses = computed(() =>
  ['inline-block overflow-hidden rounded-lg', props.bordered ? 'border-4 border-blue-900' : 'border-0'].join(' ')
)

const tableClasses = computed(() => ['w-full min-w-[720px]', sizeClasses[props.size]].join(' '))

const headerBaseClasses = computed(() =>
  [props.headerColor, props.headerTextColor, 'whitespace-nowrap font-bold', cellPadding[props.size]].join(' ')
)

const headerClickableClasses = 'cursor-pointer select-none hover:opacity-90'

const cellClasses = computed(() =>
  [
    cellPadding[props.size],
    'whitespace-nowrap border-b border-r border-gray-200 text-gray-900 last:border-r-0',
    props.clickableCells ? 'cursor-pointer transition-colors duration-150 hover:bg-blue-100 hover:text-blue-900' : '',
  ]
    .filter(Boolean)
    .join(' ')
)

const rowClasses = computed(() =>
  [
    'bg-white last:border-b-0',
    props.hoverable && props.clickableRows ? 'cursor-pointer transition-colors duration-200 hover:bg-blue-50' : '',
  ]
    .filter(Boolean)
    .join(' ')
)

const stripedRowClasses = computed(() =>
  [
    'bg-gray-50',
    props.hoverable && props.clickableRows ? 'cursor-pointer transition-colors duration-200 hover:bg-blue-50' : '',
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

function normalize(val: unknown) {
  if (val === null || val === undefined) return { type: 'null', v: null }
  if (typeof val === 'number') return { type: 'number', v: val }
  if (typeof val === 'boolean') return { type: 'number', v: val ? 1 : 0 }
  if (val instanceof Date) return { type: 'number', v: val.getTime() }

  const asNum = Number(val)
  if (!Number.isNaN(asNum) && String(val).trim() !== '') {
    return { type: 'number', v: asNum }
  }

  return { type: 'string', v: String(val).toLowerCase() }
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
  <div :class="wrapperClasses">
    <div v-if="props.title || props.subtitle" class="mb-4 text-center">
      <MyTextConstructor
        v-if="props.title"
        variant="heading"
        alignment="center"
        spacing="tight"
        textColor="text-gray-800"
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
        textColor="text-gray-500"
        class="mt-2"
      >
        <template #mySubTitle>
          {{ props.subtitle }}
        </template>
      </MyTextConstructor>
    </div>

    <div :class="props.responsive ? 'w-full overflow-x-auto' : ''">
      <div class="mx-auto w-max">
        <div :class="tableBoxClasses">
          <table :class="tableClasses">
            <thead>
              <tr>
                <th
                  v-for="column in columns"
                  :key="column.key"
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
                      textColor="text-gray-900"
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
          class="rounded-lg bg-white py-8 text-center text-gray-500"
        >
          <MyTextConstructor
            variant="subheading"
            alignment="center"
            spacing="tight"
            textColor="text-gray-500"
          >
            <template #myTitle>
              No data available
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            subTitleVariant="muted"
            alignment="center"
            spacing="none"
            textColor="text-gray-500"
            class="mt-2"
          >
            <template #mySubTitle>
              Add some data to see it displayed here
            </template>
          </MyTextConstructor>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}
</style>