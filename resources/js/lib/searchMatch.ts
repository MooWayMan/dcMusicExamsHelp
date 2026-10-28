// resources/js/lib/searchMatch.ts

// How site search decides whether a word matches. No imports, so the search
// behaviour test can run it directly with node (tests/js/search.test.ts).
//
// A search word matches a WORD in the text that starts with it, never the
// middle of one: "ill" finds "illness" but not "will" or "skills", "pay" finds
// "payment". A trailing plural "s" is dropped on both sides, so "exams" finds
// "exam".

const STOP_WORDS = new Set([
  'the', 'a', 'an', 'to', 'of', 'for', 'and', 'or', 'is', 'are', 'do', 'does',
  'i', 'my', 'me', 'can', 'you', 'your', 'what', 'how', 'in', 'on', 'it', 'this',
  'that', 'with', 'at', 'be', 'if', 'get', 'have', 'has',
])

function singular(word: string): string {
  return word.length > 3 && word.endsWith('s') && !word.endsWith('ss') ? word.slice(0, -1) : word
}

export function words(text: string): string[] {
  return (text.toLowerCase().match(/[a-z0-9£]+/g) ?? []).map(singular)
}

// The words of a query worth searching for: stop words dropped, unless the
// query is nothing but stop words.
export function queryTerms(query: string): string[] {
  const all = words(query)
  const meaningful = all.filter((word) => !STOP_WORDS.has(word) && word.length > 1)
  return meaningful.length ? meaningful : all
}

export function hasTerm(textWords: string[], term: string): boolean {
  return textWords.some((word) => word.startsWith(term))
}

// True when the whole query appears in the text as a run of words.
export function hasPhrase(textWords: string[], query: string): boolean {
  const phrase = words(query)
  if (phrase.length < 2) return false
  return ` ${textWords.join(' ')} `.includes(` ${phrase.join(' ')} `)
}
