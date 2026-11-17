// resources/js/lib/workTimes.ts
//
// Start and finish times for hourly work (stewards on a payment document),
// and the hours between them. Times are stored as 24-hour "HH:MM".

import type { SelectOption } from '@/components/reusables/MySelectConstructor.vue'

const FIRST = 6 * 60
const LAST = 22 * 60
const STEP = 15

function toMinutes(time: string): number | null {
    const match = /^(\d{1,2}):(\d{2})$/.exec(time)
    return match ? Number(match[1]) * 60 + Number(match[2]) : null
}

function label(minutes: number): string {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    const hour12 = h % 12 === 0 ? 12 : h % 12
    return `${hour12}:${String(m).padStart(2, '0')}${h < 12 ? 'am' : 'pm'}`
}

function value(minutes: number): string {
    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`
}

/** Every quarter-hour from 6am to 10pm, e.g. { value: '08:30', label: '8:30am' }. */
export const TIME_OPTIONS: SelectOption<string>[] = Array.from({ length: (LAST - FIRST) / STEP + 1 }, (_, i) => {
    const minutes = FIRST + i * STEP
    return { value: value(minutes), label: label(minutes) }
})

/** Hours worked as "8.50", or null when either time is missing or the end is not after the start. */
export function hoursBetween(start: string | null | undefined, end: string | null | undefined): string | null {
    const from = start ? toMinutes(start) : null
    const to = end ? toMinutes(end) : null
    if (from === null || to === null || to <= from) return null
    return ((to - from) / 60).toFixed(2)
}
