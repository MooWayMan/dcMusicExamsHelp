// resources/js/data/examFees.ts
//
// Trinity's UK fees, one schedule per calendar year, written down once. The
// /exam-fees page draws every table and every "from £..." line from here, and
// the fees FAQ reads its dates from here. A new year is a new entry in
// FEE_SCHEDULES; the page switches to it on its own on 1 January, and a past
// year drops off the switch the day after it ends.
//
// Sources: Trinity's "Fee information for Trinity Music exams, UK & Ireland"
// PDF for each year (2027: 1 January to 31 December 2027). GBP only.

import { TRINITY_GRADES } from '@/lib/grades'

export interface GradeFee {
  grade: string
  fee: number
}

export interface DiplomaFee {
  diploma: string
  unit?: string
  fee: number
}

export interface CertificateFee {
  level: string
  solo: number
  pairs: number
  choral: number
}

export interface MoreFees {
  certificates: CertificateFee[]
  groupMemberFee: number
  musicalDevelopment: GradeFee[]
}

export interface FeeSchedule {
  year: number
  from: string
  to: string
  faceToFace: GradeFee[]
  digital: GradeFee[]
  theory: GradeFee[]
  diplomasFaceToFace: DiplomaFee[]
  diplomasDigital: DiplomaFee[]
  diplomasDigitalTag?: string
  more?: MoreFees
}

const GRADES = TRINITY_GRADES
const THEORY_GRADES = GRADES.slice(1)

function grades(names: string[], fees: number[]): GradeFee[] {
  return names.map((grade, i) => ({ grade, fee: fees[i] }))
}

export const FEE_SCHEDULES: FeeSchedule[] = [
  {
    year: 2026,
    from: '2026-01-01',
    to: '2026-12-31',
    faceToFace: grades(GRADES, [55, 61, 68, 76, 86, 99, 109, 122, 138]),
    digital: grades(GRADES, [49, 55, 61, 68, 78, 88, 98, 109, 120]),
    theory: grades(THEORY_GRADES, [42, 44, 48, 52, 57, 62, 69, 74]),
    diplomasFaceToFace: [
      { diploma: 'ATCL Recital', fee: 333 },
      { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: 197 },
      { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: 215 },
      { diploma: 'AMusTCL (Theory)', fee: 149 },
      { diploma: 'LTCL Recital', fee: 468 },
      { diploma: 'LTCL Music Teaching', unit: 'Unit 1', fee: 277 },
      { diploma: 'LTCL Music Teaching', unit: 'Unit 2', fee: 542 },
      { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: 277 },
      { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: 507 },
      { diploma: 'LMusTCL (Theory)', fee: 200 },
      { diploma: 'FTCL Recital', fee: 660 },
    ],
    diplomasDigital: [
      { diploma: 'ATCL Recital', fee: 292 },
      { diploma: 'LTCL Recital', fee: 379 },
      { diploma: 'FTCL Recital', fee: 576 },
    ],
    diplomasDigitalTag: 'new for 2026',
  },
  {
    year: 2027,
    from: '2027-01-01',
    to: '2027-12-31',
    faceToFace: grades(GRADES, [57, 63, 71, 79, 89, 102, 113, 126, 142]),
    digital: grades(GRADES, [51, 57, 63, 70, 80, 91, 101, 112, 125]),
    theory: grades(THEORY_GRADES, [43, 45, 49, 53, 59, 64, 71, 77]),
    diplomasFaceToFace: [
      { diploma: 'ATCL Recital', fee: 343 },
      { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: 203 },
      { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: 222 },
      { diploma: 'AMusTCL (Theory)', fee: 154 },
      { diploma: 'LTCL Recital', fee: 482 },
      { diploma: 'LTCL Music Teaching', unit: 'Unit 1', fee: 285 },
      { diploma: 'LTCL Music Teaching', unit: 'Unit 2', fee: 558 },
      { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: 285 },
      { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: 522 },
      { diploma: 'LMusTCL (Theory)', fee: 206 },
      { diploma: 'FTCL Recital', fee: 680 },
    ],
    diplomasDigital: [
      { diploma: 'ATCL Recital', fee: 301 },
      { diploma: 'LTCL Recital', fee: 391 },
      { diploma: 'FTCL Recital', fee: 594 },
    ],
    more: {
      certificates: [
        { level: 'Foundation', solo: 86, pairs: 129, choral: 239 },
        { level: 'Intermediate', solo: 111, pairs: 165, choral: 293 },
        { level: 'Advanced', solo: 148, pairs: 220, choral: 349 },
      ],
      groupMemberFee: 16,
      musicalDevelopment: grades(
        ['Entry 1', 'Entry 2', 'Entry 3', 'Level 1', 'Level 2', 'Level 3'],
        [19, 24, 30, 34, 35, 43],
      ),
    },
  },
]

export function formatFee(pounds: number): string {
  return `£${pounds.toFixed(2)}`
}

export function lowestFee(rows: { fee: number }[]): number {
  return Math.min(...rows.map((r) => r.fee))
}

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

export function formatDay(iso: string): string {
  const [y, m, d] = iso.split('-').map(Number)
  return `${d} ${MONTHS[m - 1]} ${y}`
}

function isoDay(today: Date): string {
  const m = String(today.getMonth() + 1).padStart(2, '0')
  const d = String(today.getDate()).padStart(2, '0')
  return `${today.getFullYear()}-${m}-${d}`
}

export function schedulesOnShow(today: Date, schedules: FeeSchedule[] = FEE_SCHEDULES): FeeSchedule[] {
  const day = isoDay(today)
  const showing = schedules.filter((s) => s.to >= day)
  return showing.length > 0 ? showing : schedules.slice(-1)
}

export function currentSchedule(today: Date, schedules: FeeSchedule[] = FEE_SCHEDULES): FeeSchedule {
  const day = isoDay(today)
  const started = schedules.filter((s) => s.from <= day)
  return started.length > 0 ? started[started.length - 1] : schedules[0]
}

export function scheduleForYear(year: number, today: Date, schedules: FeeSchedule[] = FEE_SCHEDULES): FeeSchedule {
  return schedulesOnShow(today, schedules).find((s) => s.year === year) ?? currentSchedule(today, schedules)
}

export function gradeRows(rows: GradeFee[]): { grade: string; fee: string }[] {
  return rows.map((r) => ({ grade: r.grade, fee: formatFee(r.fee) }))
}

export function diplomaRows(rows: DiplomaFee[]): { key: string; diploma: string; unit: string; fee: string }[] {
  return rows.map((r) => ({
    key: `${r.diploma} ${r.unit ?? ''}`.trim(),
    diploma: r.diploma,
    unit: r.unit ?? '—',
    fee: formatFee(r.fee),
  }))
}

export function certificateRows(more: MoreFees): { level: string; solo: string; pairs: string; choral: string }[] {
  return more.certificates.map((c) => ({
    level: c.level,
    solo: formatFee(c.solo),
    pairs: formatFee(c.pairs),
    choral: formatFee(c.choral),
  }))
}

export function feesChangeAnswer(today: Date): string {
  const current = currentSchedule(today)
  const next = schedulesOnShow(today).find((s) => s.from > current.to)
  const now = `Trinity sets fees on a calendar year basis — the current fees run from ${formatDay(current.from)} to ${formatDay(current.to)}.`
  return next
    ? `${now} Trinity has published the ${next.year} fees, which apply from ${formatDay(next.from)}. Use the year switch at the top of the fee tables to see them.`
    : `${now} Any changes are announced by Trinity ahead of the new year.`
}
