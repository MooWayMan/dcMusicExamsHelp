// tests/js/ownChoice.test.ts
//
// The own-choice data, loaded the way the page loads it. Run by
// tests/Feature/OwnChoiceSongsTest.php inside the gate.

import { test } from 'node:test'
import assert from 'node:assert/strict'
import {
  GRADE_OVERVIEW,
  OWN_CHOICE_GRADES,
  OWN_CHOICE_INSTRUMENTS,
  OWN_CHOICE_PAPERWORK,
  OWN_CHOICE_RULES,
  gradeRows,
} from '@/data/ownChoiceSongs'
import { ROCK_POP_ACOUSTIC_RULE } from '@/lib/guitarExams'

test('all five Rock & Pop instruments are there, each with every grade', () => {
  assert.deepEqual(OWN_CHOICE_INSTRUMENTS.map((i) => i.key), ['guitar', 'bass', 'drums', 'keyboards', 'vocals'])
  for (const instrument of OWN_CHOICE_INSTRUMENTS) {
    assert.equal(instrument.grades.length, OWN_CHOICE_GRADES.length, instrument.name)
    instrument.grades.forEach((rows, index) => {
      assert.ok(rows.length > 0, `${instrument.name} ${OWN_CHOICE_GRADES[index]} has no rows`)
      for (const row of rows) {
        assert.ok(row.label.trim() && row.value.trim(), `${instrument.name} ${OWN_CHOICE_GRADES[index]} has a blank row`)
      }
      const labels = rows.map((r) => r.label)
      assert.equal(new Set(labels).size, labels.length, `${instrument.name} ${OWN_CHOICE_GRADES[index]} repeats a row`)
    })
  }
})

test('length and tempo come once from the overview, never from an instrument', () => {
  assert.deepEqual(GRADE_OVERVIEW.map((g) => g.grade), [...OWN_CHOICE_GRADES])
  assert.equal(OWN_CHOICE_GRADES[0], 'Initial')
  assert.equal(OWN_CHOICE_GRADES[8], 'Grade 8')
  for (const instrument of OWN_CHOICE_INSTRUMENTS) {
    for (const rows of instrument.grades) {
      assert.ok(!rows.some((r) => r.label === 'Length' || r.label === 'Tempo' || r.label === 'Duration'), instrument.name)
    }
  }
  const rows = gradeRows(OWN_CHOICE_INSTRUMENTS[0], 8)
  assert.equal(rows[0].label, 'Length')
  assert.equal(rows[0].value, GRADE_OVERVIEW[8].length)
  assert.equal(rows[1].label, 'Tempo')
})

test('every instrument starts from the same time signatures', () => {
  for (const instrument of OWN_CHOICE_INSTRUMENTS) {
    const initial = instrument.grades[0].find((r) => r.label === 'Time signatures')
    assert.equal(initial?.value, '4/4, 2/2, 3/4', instrument.name)
  }
})

test('the guitar acoustic rule is the shared one, not a copy', () => {
  assert.ok(OWN_CHOICE_INSTRUMENTS[0].notes.includes(ROCK_POP_ACOUSTIC_RULE))
})

test('rules and paperwork are filled in', () => {
  assert.ok(OWN_CHOICE_RULES.length >= 5)
  for (const row of OWN_CHOICE_PAPERWORK) {
    assert.ok(row.topic && row.faceToFace && row.digital, row.topic)
  }
})
