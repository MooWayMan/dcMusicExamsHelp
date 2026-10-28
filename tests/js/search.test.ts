// tests/js/search.test.ts
//
// Site search, run for real: node loads the same searchIndex the browser does.
// Run by tests/Feature/SearchBehaviourTest.php inside the gate.

import { test } from 'node:test'
import assert from 'node:assert/strict'
import { runSearch, searchEntries } from '@/data/searchIndex'
import { FAQ_PAGES } from '@/data/faqs'

const top = (query: string) => runSearch(query)[0]

test('every FAQ answer on the site can be found by its own question', () => {
  for (const page of FAQ_PAGES) {
    page.items.forEach((item, index) => {
      const urls = runSearch(item.question).slice(0, 3).map((result) => result.title)
      assert.ok(urls.includes(item.question), `"${item.question}" (${page.path} #${index + 1}) is not in the top 3`)
    })
  }
})

test('each FAQ link opens the answer it is titled with', () => {
  for (const entry of searchEntries.filter((e) => e.id.startsWith('faq:'))) {
    const [path, hash] = entry.url.split('#')
    const page = FAQ_PAGES.find((p) => p.path === path)
    assert.ok(page, `no FAQ page for ${entry.url}`)
    const n = Number(hash.replace('faq-', ''))
    assert.equal(page.items[n - 1]?.question, entry.title, entry.url)
  }
})

test('a search word matches the start of a word, not the middle', () => {
  assert.equal(top('ill')?.title, 'My child was ill and missed their exam — what happens?')
  for (const result of runSearch('ill')) {
    const text = `${result.title} ${result.keywords.join(' ')} ${result.snippet} ${result.body ?? ''}`.toLowerCase()
    assert.match(text, /\bill/, `"${result.title}" came up for "ill" without a word starting "ill"`)
  }
})

test('plurals find the singular', () => {
  assert.ok(runSearch('certificates').some((r) => r.title === 'Can I order a paper certificate, and how much?'))
})

test('the topics people actually search for come first', () => {
  assert.equal(top('acoustic guitar')?.title, 'Which guitar exam? Rock & Pop, Acoustic or Classical')
  assert.equal(top('missed exam')?.title, 'My child was ill and missed their exam — what happens?')
  assert.equal(top('paper certificate')?.title, 'Can I order a paper certificate, and how much?')
  assert.match(top('how long for results')?.title ?? '', /result/i)
  assert.equal(top('gift token expire')?.url, '/incentives#faq-7')
})

test('nothing for nonsense, nothing for an empty box', () => {
  assert.deepEqual(runSearch(''), [])
  assert.deepEqual(runSearch('zzzqqq'), [])
})
