// resources/js/data/searchIndex.ts

import { GIFT_TOKEN_REDEEM_RULE } from '@/lib/prizeRules'
import { GUITAR_EXAMS_ANCHOR, GUITAR_EXAMS_SUMMARY } from '@/lib/guitarExams'
import { FAQ_PAGES } from '@/data/faqs'
import { faqLink, plainText } from '@/lib/faqs'
import { hasPhrase, hasTerm, queryTerms, words } from '@/lib/searchMatch'

export interface SearchEntry {
  id: string
  title: string
  snippet: string
  url: string
  section: string
  keywords: string[]
  // The full text, searched but not shown. FAQ answers carry it.
  body?: string
}

export interface ScoredEntry extends SearchEntry {
  score: number
}

// Pages and topics. FAQ answers are NOT listed here: they are read from
// data/faqs (see faqEntries below), so never hand-write a '#faq-N' link.
const topicEntries: SearchEntry[] = [
  {
    id: 'page-home',
    title: 'Home',
    snippet: 'Book Trinity College London music exams through centre 120 — digital and face-to-face grades, free certificates, recognition and incentives.',
    url: '/',
    section: 'Pages',
    keywords: ['home', 'book exam', 'trinity', 'centre 120', 'music exams'],
  },
  {
    id: 'page-for-teachers',
    title: 'For Teachers',
    snippet: 'How teachers benefit from centre 120: easier booking, recognition badges, Certificates of Appreciation and the quarterly teacher prize draw.',
    url: '/for-teachers',
    section: 'Pages',
    keywords: ['teachers', 'teacher benefits', 'badges', 'prize draw', 'recognition', 'appreciation'],
  },
  {
    id: 'page-teacher-awards',
    title: 'Teacher Awards',
    snippet: 'The tiered teacher badge system (10+, 20+, 30+ candidates), Certificates of Appreciation and the £50 teacher prize draw.',
    url: '/for-teachers/awards',
    section: 'Pages',
    keywords: ['teacher awards', 'badges', 'tiers', 'appreciation', 'prize draw', 'gift token'],
  },
  {
    id: 'page-for-parents',
    title: 'For Parents',
    snippet: 'Clear guidance for parents on how Trinity music exams work, results, certificates and how centre 120 supports your child and their teacher.',
    url: '/for-parents',
    section: 'Pages',
    keywords: ['parents', 'results', 'how exams work', 'guidance', 'children'],
  },
  {
    id: 'page-for-students',
    title: 'For Students',
    snippet: 'For students preparing for their next grade — how to get ready, what to expect on the day and the recognition you earn.',
    url: '/for-students',
    section: 'Pages',
    keywords: ['students', 'preparing', 'next grade', 'practice', 'recognition'],
  },
  {
    id: 'page-books',
    title: 'Books',
    snippet: 'Official Trinity exam books — ebooks at 10% off for instant download, and paperbacks via Amazon, all linked in one place.',
    url: '/books',
    section: 'Pages',
    keywords: ['books', 'ebooks', 'paperbacks', 'exam book', 'sheet music', 'faber', 'discount'],
  },
  {
    id: 'page-piece-finder',
    title: 'Piece Finder',
    snippet: 'Search the syllabus repertoire to find set pieces for your instrument and grade.',
    url: '/syllabus',
    section: 'Pages',
    keywords: ['piece finder', 'repertoire', 'pieces', 'syllabus search', 'set pieces'],
  },
  {
    id: 'page-top-ten',
    title: 'Top Ten Pieces',
    snippet: 'The most popular exam pieces, voted for by teachers.',
    url: '/top-ten',
    section: 'Pages',
    keywords: ['top ten', 'popular pieces', 'vote', 'favourites'],
  },
  {
    id: 'page-recognition',
    title: 'Recognition & Hall of Fame',
    snippet: 'Every candidate through centre 120 is recognised — Bravo, Take a Bow and Standing Ovation certificates, the Hall of Fame and quarterly prize draws.',
    url: '/recognition',
    section: 'Pages',
    keywords: ['recognition', 'hall of fame', 'bravo certificate', 'take a bow', 'standing ovation', 'prize draw'],
  },
  {
    id: 'page-incentives',
    title: 'Incentives',
    snippet: 'The rewards, prize draws and recognition on offer to students and teachers who use centre 120.',
    url: '/incentives',
    section: 'Pages',
    keywords: ['incentives', 'rewards', 'prize draw', 'gift token', 'recognition'],
  },
  {
    id: 'page-about',
    title: 'About',
    snippet: 'musicExams.help is built by Paul, a working music teacher in Liverpool and Wirral, to make Trinity exam booking and admin easier for everyone.',
    url: '/about',
    section: 'Pages',
    keywords: ['about', 'paul', 'who', 'story', 'music teacher'],
  },
  {
    id: 'page-contact',
    title: 'Contact Us',
    snippet: 'Get in touch with any question about booking Trinity music exams through centre 120.',
    url: '/contact',
    section: 'Pages',
    keywords: ['contact', 'get in touch', 'email', 'help', 'question', 'cancel', 'cancellation', 'refund', 'reschedule', 'change date', 'withdraw', 'complaint', 'correction', 'problem', 'mistake'],
  },
  {
    id: 'page-switch-120',
    title: 'Switch to Centre 120',
    snippet: 'How to move your Trinity exam entries to centre 120 and connect with musicExams.help.',
    url: '/switch-to-centre-120',
    section: 'Pages',
    keywords: ['switch', 'change centre', 'centre 120', 'move entries'],
  },
  {
    id: 'page-trinity-exam-info',
    title: 'Trinity Exam Information',
    snippet: 'Key information about Trinity College London exams and how centre 120 works.',
    url: '/trinity-exam-information',
    section: 'Pages',
    keywords: ['trinity', 'exam information', 'centre 120', 'about exams'],
  },
  {
    id: 'guide-overview',
    title: 'Exam Guide',
    snippet: 'Everything you need to know about Trinity music exams — grades, what to expect, digital exams, UCAS points and syllabuses.',
    url: '/exam-guide',
    section: 'Exam Guide',
    keywords: ['exam guide', 'guide', 'how exams work', 'overview'],
  },
  {
    id: 'guide-grades',
    title: 'Grades Explained',
    snippet: 'Trinity graded exams are marked out of 100: Pass 60–74, Merit 75–86, Distinction 87–100. How the grades and marks work.',
    url: '/exam-guide/grades-explained',
    section: 'Exam Guide',
    keywords: ['grades', 'marks', 'pass', 'merit', 'distinction', 'scoring', 'out of 100'],
  },
  {
    id: 'guide-expect',
    title: 'What to Expect',
    snippet: 'What happens on exam day and afterwards — the written report, how results are validated by Trinity, and how to order your certificate.',
    url: '/exam-guide/what-to-expect',
    section: 'Exam Guide',
    keywords: ['what to expect', 'exam day', 'results', 'report', 'certificate', 'after the exam'],
  },
  {
    id: 'guide-digital',
    title: 'Digital Exams',
    snippet: 'Record your performance anywhere and submit it online through Trinity. Results within 14 days; a free digital certificate, paper £5 extra.',
    url: '/exam-guide/digital-exams',
    section: 'Exam Guide',
    keywords: ['digital exams', 'recorded', 'video', 'online', 'submit', 'results', 'certificate'],
  },
  {
    id: 'guide-ucas',
    title: 'UCAS Points',
    snippet: 'How Trinity Grades 6–8 can earn UCAS tariff points towards university applications.',
    url: '/exam-guide/ucas-points',
    section: 'Exam Guide',
    keywords: ['ucas', 'points', 'university', 'tariff', 'grade 6', 'grade 7', 'grade 8'],
  },
  {
    id: 'guide-syllabuses',
    title: 'Syllabuses',
    snippet: 'The Trinity syllabuses for each instrument and grade — pieces, technical work and supporting tests, for Classical & Jazz and Rock & Pop.',
    url: '/exam-guide/syllabuses',
    section: 'Exam Guide',
    keywords: ['syllabus', 'syllabuses', 'scales', 'arpeggios', 'pieces', 'technical work', 'instruments'],
  },
  {
    id: 'topic-guitar-exams',
    title: 'Which guitar exam? Rock & Pop, Acoustic or Classical',
    snippet: GUITAR_EXAMS_SUMMARY,
    url: GUITAR_EXAMS_ANCHOR,
    section: 'Exam Guide',
    keywords: ['guitar', 'guitar exam', 'which guitar exam', 'acoustic guitar', 'acoustic', 'electric guitar', 'electric', 'electro-acoustic', 'classical guitar', 'rock and pop guitar', 'rock & pop guitar', 'steel string', 'nylon string', 'plectrum', 'fingerstyle'],
  },
  {
    id: 'fees-overview',
    title: 'Fees & Dates',
    snippet: 'Trinity exam fees and booking dates. All fees include a free digital certificate; a printed paper certificate is £5 (UK delivery).',
    url: '/exam-fees',
    section: 'Fees',
    keywords: ['fees', 'cost', 'price', 'how much', 'dates', 'booking dates', 'when', 'exam dates', 'deadlines', 'closing date', 'theory'],
  },
];

// The opening of an answer, for the results list: whole sentences up to about
// 220 characters, or the first 220 cut at a word.
function opening(text: string): string {
  if (text.length <= 220) return text
  const sentences = text.match(/[^.!?]+[.!?]+(\s|$)/g) ?? []
  let out = ''
  for (const sentence of sentences) {
    if ((out + sentence).length > 220) break
    out += sentence
  }
  if (out.trim()) return out.trim()
  return text.slice(0, 220).replace(/\s+\S*$/, '') + '…'
}

// Every FAQ answer on the site, one entry each. When two pages ask the same
// question, the first page in FAQ_PAGES (the main FAQ) wins.
const faqEntries: SearchEntry[] = (() => {
  const seen = new Set<string>()
  const entries: SearchEntry[] = []
  for (const page of FAQ_PAGES) {
    page.items.forEach((item, index) => {
      const key = words(item.question).join(' ')
      if (seen.has(key)) return
      seen.add(key)
      const body = plainText(item.answer)
      entries.push({
        id: `faq:${page.path}#${index + 1}`,
        title: item.question,
        snippet: item.snippet ?? opening(body),
        url: faqLink(page, index),
        section: page.label,
        keywords: item.keywords ?? [],
        body,
      })
    })
  }
  return entries
})()

export const searchEntries: SearchEntry[] = [...topicEntries, ...faqEntries]

export function runSearch(query: string): ScoredEntry[] {
  const trimmed = query.trim()
  if (!trimmed) return []

  const terms = queryTerms(trimmed)
  if (!terms.length) return []

  const results: ScoredEntry[] = []

  for (const entry of searchEntries) {
    const title = words(entry.title)
    const keywords = words(entry.keywords.join(' '))
    const text = words(`${entry.snippet} ${entry.body ?? ''}`)

    let score = 0
    let matched = 0

    for (const term of terms) {
      let hit = false
      if (hasTerm(title, term)) {
        score += 10
        hit = true
      }
      if (hasTerm(keywords, term)) {
        score += 6
        hit = true
      }
      if (hasTerm(text, term)) {
        score += 2
        hit = true
      }
      if (hit) matched += 1
    }

    if (hasPhrase(title, trimmed) || entry.keywords.some((keyword) => hasPhrase(words(keyword), trimmed))) {
      score += 15
    }

    if (matched === 0) continue
    if (matched < Math.ceil(terms.length / 2)) continue

    results.push({ ...entry, score })
  }

  results.sort((a, b) => b.score - a.score)
  return results.slice(0, 12)
}
