// tests/js/paymentDocumentLines.test.ts
//
// Hours worked and the minimum wage on a payment-document line. Run by
// tests/Feature/Admin/PaymentDocumentTest.php inside the gate.

import { test } from 'node:test'
import assert from 'node:assert/strict'
import { TIME_OPTIONS, hoursBetween } from '@/lib/workTimes'
import { minimumWageOn } from '@/lib/minimumWage'

test('the hours between two times', () => {
    assert.equal(hoursBetween('08:30', '15:00'), '6.50')
    assert.equal(hoursBetween('08:30', '17:00'), '8.50')
    assert.equal(hoursBetween('09:15', '09:30'), '0.25')
})

test('no hours until both times are there and the end is after the start', () => {
    assert.equal(hoursBetween('08:30', ''), null)
    assert.equal(hoursBetween(null, '17:00'), null)
    assert.equal(hoursBetween('17:00', '08:30'), null)
    assert.equal(hoursBetween('08:30', '08:30'), null)
})

test('quarter-hour choices from 6am to 10pm, labelled the way people say them', () => {
    assert.deepEqual(TIME_OPTIONS[0], { value: '06:00', label: '6:00am' })
    assert.deepEqual(TIME_OPTIONS[TIME_OPTIONS.length - 1], { value: '22:00', label: '10:00pm' })
    assert.ok(TIME_OPTIONS.some((o) => o.value === '08:30' && o.label === '8:30am'))
    assert.ok(TIME_OPTIONS.some((o) => o.value === '12:00' && o.label === '12:00pm'))
    assert.ok(TIME_OPTIONS.some((o) => o.value === '17:00' && o.label === '5:00pm'))
})

test('the minimum wage follows the date of the work', () => {
    const rates = [
        { from: '2027-04-01', rate: '13.21' },
        { from: '2026-04-01', rate: '12.71' },
    ]
    assert.equal(minimumWageOn(rates, '2026-03-31'), null)
    assert.equal(minimumWageOn(rates, '2026-04-01'), '12.71')
    assert.equal(minimumWageOn(rates, '2026-12-03'), '12.71')
    assert.equal(minimumWageOn(rates, '2027-04-01'), '13.21')
    assert.equal(minimumWageOn([], '2026-12-03'), null)
})
