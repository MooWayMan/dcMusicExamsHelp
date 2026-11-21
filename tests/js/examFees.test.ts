// tests/js/examFees.test.ts
//
// The fee schedules, loaded the way the page loads them. Run by
// tests/Feature/ExamFeesTest.php inside the gate.

import { test } from 'node:test'
import assert from 'node:assert/strict'
import {
  FEE_SCHEDULES,
  currentSchedule,
  schedulesOnShow,
  scheduleForYear,
  formatFee,
  gradeRows,
  diplomaRows,
  lowestFee,
  feesChangeAnswer,
  posterOnShow,
} from '@/data/examFees'

const day = (iso: string) => {
  const [y, m, d] = iso.split('-').map(Number)
  return new Date(y, m - 1, d, 12)
}

test('every year has every grade, and no fee is missing', () => {
  for (const s of FEE_SCHEDULES) {
    assert.equal(s.faceToFace.length, 9, `${s.year} face-to-face`)
    assert.equal(s.digital.length, 9, `${s.year} digital`)
    assert.equal(s.theory.length, 8, `${s.year} theory`)
    assert.equal(s.faceToFace[0].grade, 'Initial')
    assert.equal(s.theory[0].grade, 'Grade 1')
    const all = [...s.faceToFace, ...s.digital, ...s.theory, ...s.diplomasFaceToFace, ...s.diplomasDigital]
    for (const row of all) assert.ok(Number.isFinite(row.fee) && row.fee > 0, `${s.year} has a blank fee`)
  }
})

test('the years run back to back, one calendar year each', () => {
  FEE_SCHEDULES.forEach((s, i) => {
    assert.equal(s.from, `${s.year}-01-01`)
    assert.equal(s.to, `${s.year}-12-31`)
    if (i > 0) assert.equal(s.year, FEE_SCHEDULES[i - 1].year + 1)
  })
})

test('2027 matches Trinity’s published PDF at the ends of each table', () => {
  const s = FEE_SCHEDULES.find((x) => x.year === 2027)
  assert.ok(s)
  assert.equal(s.faceToFace[0].fee, 57)
  assert.equal(s.faceToFace[8].fee, 142)
  assert.equal(s.digital[0].fee, 51)
  assert.equal(s.digital[8].fee, 125)
  assert.equal(s.theory[0].fee, 43)
  assert.equal(s.theory[7].fee, 77)
  assert.equal(s.diplomasDigital[2].fee, 594)
  assert.equal(s.more?.groupMemberFee, 16)
  assert.equal(s.more?.certificates[2].choral, 349)
  assert.equal(s.more?.musicalDevelopment[5].fee, 43)
})

test('the page opens on this year and switches itself on 1 January', () => {
  assert.equal(currentSchedule(day('2026-11-20')).year, 2026)
  assert.equal(currentSchedule(day('2026-12-31')).year, 2026)
  assert.equal(currentSchedule(day('2027-01-01')).year, 2027)
})

test('both years show until the old one ends, then only the new one', () => {
  assert.deepEqual(schedulesOnShow(day('2026-11-20')).map((s) => s.year), [2026, 2027])
  assert.deepEqual(schedulesOnShow(day('2027-01-01')).map((s) => s.year), [2027])
  assert.deepEqual(schedulesOnShow(day('2029-06-01')).map((s) => s.year), [2027])
})

test('asking for a year that is not on show falls back to this year', () => {
  assert.equal(scheduleForYear(2027, day('2026-11-20')).year, 2027)
  assert.equal(scheduleForYear(2026, day('2027-02-01')).year, 2027)
  assert.equal(scheduleForYear(1999, day('2026-11-20')).year, 2026)
})

test('fees print in pounds and pence, rows keep their keys unique', () => {
  assert.equal(formatFee(57), '£57.00')
  assert.deepEqual(gradeRows([{ grade: 'Initial', fee: 57 }]), [{ grade: 'Initial', fee: '£57.00' }])
  assert.equal(lowestFee(FEE_SCHEDULES[1].digital), 51)
  for (const s of FEE_SCHEDULES) {
    const keys = diplomaRows(s.diplomasFaceToFace).map((r) => r.key)
    assert.equal(new Set(keys).size, keys.length, `${s.year} diploma keys repeat`)
  }
})

test('the FAQ names the current dates, and the new year once published', () => {
  const now = feesChangeAnswer(day('2026-11-20'))
  assert.match(now, /1 January 2026 to 31 December 2026/)
  assert.match(now, /2027 fees, which apply from 1 January 2027/)
  const later = feesChangeAnswer(day('2027-03-01'))
  assert.match(later, /1 January 2027 to 31 December 2027/)
  assert.doesNotMatch(later, /published the/)
})

test('the 2027 wall poster shows from now and through 2027; a year without one shows none', () => {
  assert.equal(posterOnShow(day('2026-11-21'))?.year, 2027)
  assert.equal(posterOnShow(day('2027-06-01'))?.year, 2027)
  const p = FEE_SCHEDULES[1].poster
  assert.match(p?.pdf ?? '', /^https:\/\/moowaymusicbucket\.s3\.eu-west-2\.amazonaws\.com\/musicexamshelp\/wall-poster-2027-fees\.pdf$/)
  assert.match(p?.preview ?? '', /wall-poster-2027-fees\.png$/)
  assert.equal(posterOnShow(day('2026-11-21'), [FEE_SCHEDULES[0]]), undefined)
})
