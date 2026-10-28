// resources/js/lib/faqs.ts

// Every page's FAQ list lives in resources/js/data/faqs/, one file per page.
// The page draws its accordion from that file and site search reads the same
// file, so an answer is written once and search can never point at the wrong
// one: an answer's link is its position, worked out here, never typed by hand.

export interface FaqItem {
  question: string
  // HTML allowed (links, <strong>); stripped for search.
  answer: string
  // Extra words people search with that the answer itself doesn't use.
  keywords?: string[]
  // A shorter line for the search results, when the answer's opening is not it.
  snippet?: string
}

export interface FaqPage {
  // The page's address, e.g. '/exam-fees'.
  path: string
  // Shown above the result in site search, e.g. 'Exam Fees FAQ'.
  label: string
  items: FaqItem[]
}

// What MyAccordionConstructor needs. The id is the position, which is also
// what #faq-N in a link opens (useAccordionHashOpen).
export function faqAccordionItems(page: FaqPage) {
  return page.items.map((item, index) => ({ id: index + 1, question: item.question, answer: item.answer }))
}

export function faqLink(page: FaqPage, index: number): string {
  return `${page.path}#faq-${index + 1}`
}

const ENTITIES: Record<string, string> = {
  '&amp;': '&', '&nbsp;': ' ', '&lt;': '<', '&gt;': '>', '&quot;': '"',
  '&#39;': "'", '&rsquo;': '’', '&lsquo;': '‘', '&ldquo;': '“', '&rdquo;': '”',
  '&ndash;': '–', '&mdash;': '—', '&pound;': '£',
}

export function plainText(html: string): string {
  return html
    .replace(/<[^>]*>/g, '')
    .replace(/&[a-z#0-9]+;/gi, (entity) => ENTITIES[entity] ?? entity)
    .replace(/\s+/g, ' ')
    .trim()
}
