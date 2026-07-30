// resources/js/data/searchIndex.ts

export interface SearchEntry {
  id: string
  title: string
  snippet: string
  url: string
  section: string
  keywords: string[]
}

export interface ScoredEntry extends SearchEntry {
  score: number
}

export const searchEntries: SearchEntry[] = [
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
    id: 'fees-overview',
    title: 'Fees & Dates',
    snippet: 'Trinity exam fees and booking dates. All fees include a free digital certificate; a printed paper certificate is £5 (UK delivery).',
    url: '/exam-fees',
    section: 'Fees',
    keywords: ['fees', 'cost', 'price', 'how much', 'dates', 'booking dates', 'when', 'exam dates', 'deadlines', 'closing date', 'theory'],
  },
  {
    id: 'faq-15',
    title: 'Can I order a paper certificate, and how much?',
    snippet: 'Every exam includes a free digital certificate. A printed paper certificate is £5 (UK delivery) and can only be ordered by the parent — using the email they gave on the entry — from Trinity at mycertificates.trinitycollege.com.',
    url: '/faq#faq-15',
    section: 'FAQ',
    keywords: ['paper certificate', 'printed certificate', 'physical certificate', 'certificate cost', 'order certificate', 'order a certificate', 'lost certificate', 'damaged certificate', 'replacement certificate', 'reprint certificate', 'get a certificate', 'parent order certificate', 'teacher order certificate', '£5'],
  },
  {
    id: 'topic-results-timing',
    title: 'How long until I get my results?',
    snippet: 'Digital exam results normally arrive within 14 days by email. Face-to-face results through centre 120 are checked and posted within days of the exam.',
    url: '/for-parents#faq-5',
    section: 'FAQ',
    keywords: ['results', 'when are results', 'how long for results', 'result turnaround', 'when do i get results', 'how long until results', 'get my results', 'exam report', 'when results'],
  },
  {
    id: 'topic-late-entry',
    title: 'Are there late entry surcharges?',
    snippet: 'Trinity applies surcharges for late entries. Booking well ahead of the closing date avoids them.',
    url: '/exam-fees#faq-4',
    section: 'Fees',
    keywords: ['late entry', 'surcharge', 'late fee', 'deadline', 'closing date', 'late booking', 'missed deadline'],
  },
  {
    id: 'topic-enter-exam',
    title: 'How do I enter (book) an exam?',
    snippet: 'Click Book Your Exam to open a short menu, choose your exam type, and you are taken to the correct Trinity booking system — check the referral code box says 120.',
    url: '/faq#faq-1',
    section: 'FAQ',
    keywords: ['enter', 'entry', 'entering', 'enter an exam', 'enter a candidate', 'enter a student', 'make an entry', 'book', 'booking', 'how to book', 'sign up for an exam', 'sign up', 'put in for an exam', 'register for an exam', 'apply for an exam', 'how do i enter'],
  },
  {
    id: 'faq-1',
    title: 'Do I book through this website?',
    snippet: 'No — Book Your Exam opens a short menu that takes you to the correct Trinity booking system. Always check the referral code box says 120.',
    url: '/faq#faq-1',
    section: 'FAQ',
    keywords: ['book', 'how to book', 'booking', 'enter', 'entry', 'enter an exam', 'referral code', '120', 'where to book', 'sign up for an exam'],
  },
  {
    id: 'faq-2',
    title: 'Who is this for?',
    snippet: 'Anyone involved in music exams — teachers wanting smoother booking and recognition, parents wanting clear guidance, students preparing for their next grade.',
    url: '/faq#faq-2',
    section: 'FAQ',
    keywords: ['who is this for', 'teachers', 'parents', 'students'],
  },
  {
    id: 'faq-3',
    title: 'What is centre 120?',
    snippet: 'Our registered Trinity centre code, covering our digital centre and face-to-face centres in Liverpool and Wirral, connecting your entry to musicExams.help.',
    url: '/faq#faq-3',
    section: 'FAQ',
    keywords: ['centre 120', 'what is 120', 'centre code', 'liverpool', 'wirral'],
  },
  {
    id: 'faq-4',
    title: 'Does it cost anything extra?',
    snippet: 'No. Exam fees are the same regardless of centre. Centre 120 simply gives you the extra benefits and support of musicExams.help.',
    url: '/faq#faq-4',
    section: 'FAQ',
    keywords: ['extra cost', 'does it cost', 'free', 'fees', 'price'],
  },
  {
    id: 'faq-5',
    title: 'Can I use this if I already have a teacher?',
    snippet: 'Absolutely — this site supports your existing teacher, not replaces them. It just makes booking and admin easier for everyone.',
    url: '/faq#faq-5',
    section: 'FAQ',
    keywords: ['existing teacher', 'already have a teacher', 'my teacher'],
  },
  {
    id: 'faq-6',
    title: 'What are digital exams?',
    snippet: 'Record your performance anywhere and submit it online through Trinity — no travel to a venue. Anyone can submit the recording.',
    url: '/faq#faq-6',
    section: 'FAQ',
    keywords: ['digital exams', 'recorded exam', 'online', 'submit recording', 'video exam'],
  },
  {
    id: 'faq-7',
    title: 'What instruments can I take exams on?',
    snippet: 'A wide range — Classical & Jazz (piano, brass, woodwind, strings, singing, guitar, percussion) and Rock & Pop (guitar, bass, drums, keyboards, vocals).',
    url: '/faq#faq-7',
    section: 'FAQ',
    keywords: ['instruments', 'what instruments', 'piano', 'guitar', 'brass', 'drums', 'singing', 'rock and pop'],
  },
  {
    id: 'faq-8',
    title: 'How do I prepare for my exam?',
    snippet: 'Start with the syllabus for your instrument and grade — pieces, technical work and supporting tests. Your teacher will guide you.',
    url: '/faq#faq-8',
    section: 'FAQ',
    keywords: ['prepare', 'preparation', 'how to prepare', 'practice', 'get ready'],
  },
  {
    id: 'faq-9',
    title: 'Do I have to do scales and arpeggios?',
    snippet: 'For most Classical & Jazz instruments you can choose scales & arpeggios OR exercises — not both. Always check your syllabus.',
    url: '/faq#faq-9',
    section: 'FAQ',
    keywords: ['scales', 'arpeggios', 'exercises', 'technical work', 'from memory'],
  },
  {
    id: 'faq-10',
    title: 'What results can I achieve?',
    snippet: 'Marked out of 100 — Pass 60–74, Merit 75–86, Distinction 87–100. Every candidate gets at least a Bravo Certificate and Recognition listing.',
    url: '/faq#faq-10',
    section: 'FAQ',
    keywords: ['results', 'marks', 'pass', 'merit', 'distinction', 'grades', 'score'],
  },
  {
    id: 'faq-11',
    title: 'Do I need to provide sheet music for the examiner?',
    snippet: 'Trinity-published pieces are on the examiner’s laptop, but bring the original book for copyright. For non-Trinity pieces, provide a copy.',
    url: '/faq#faq-11',
    section: 'FAQ',
    keywords: ['sheet music', 'examiner', 'copy', 'original book', 'copyright', 'photocopy'],
  },
  {
    id: 'faq-12',
    title: 'How do teachers benefit from using centre 120?',
    snippet: 'Recognition badges (10+, 20+, 30+ candidates), Certificates of Appreciation and entry into the quarterly £50 teacher prize draw.',
    url: '/faq#faq-12',
    section: 'FAQ',
    keywords: ['teacher benefits', 'badges', 'appreciation', 'prize draw', 'teachers'],
  },
  {
    id: 'faq-13',
    title: 'What is the Hall of Fame?',
    snippet: 'Merit earns a Take a Bow certificate and Distinction a Standing Ovation, plus a Hall of Fame place. Top quarterly scores earn a gift token.',
    url: '/faq#faq-13',
    section: 'FAQ',
    keywords: ['hall of fame', 'take a bow', 'standing ovation', 'showstopper', 'centre stage', 'gift token'],
  },
  {
    id: 'faq-14',
    title: 'Can I book face-to-face exams through centre 120?',
    snippet: 'Yes — face-to-face sessions in Liverpool and Wirral, plus digital practical and theory exams taken anywhere. Centre 120 covers all three.',
    url: '/faq#faq-14',
    section: 'FAQ',
    keywords: ['face to face', 'in person', 'liverpool', 'wirral', 'venue', 'practical exam'],
  },
]

const STOP_WORDS = new Set([
  'the', 'a', 'an', 'to', 'of', 'for', 'and', 'or', 'is', 'are', 'do', 'does',
  'i', 'my', 'me', 'can', 'you', 'your', 'what', 'how', 'in', 'on', 'it', 'this',
  'that', 'with', 'at', 'be', 'if', 'get', 'have', 'has',
])

function tokenize(value: string): string[] {
  return (value.toLowerCase().match(/[a-z0-9£]+/g) ?? [])
}

export function runSearch(query: string): ScoredEntry[] {
  const trimmed = query.trim().toLowerCase()
  if (!trimmed) return []

  const allTerms = tokenize(trimmed)
  const terms = allTerms.filter((t) => !STOP_WORDS.has(t) && t.length > 1)
  const effectiveTerms = terms.length ? terms : allTerms
  if (!effectiveTerms.length) return []

  const results: ScoredEntry[] = []

  for (const entry of searchEntries) {
    const title = entry.title.toLowerCase()
    const keywords = entry.keywords.join(' ').toLowerCase()
    const body = entry.snippet.toLowerCase()

    let score = 0
    let matched = 0

    for (const term of effectiveTerms) {
      let hit = false
      if (title.includes(term)) {
        score += 10
        hit = true
      }
      if (keywords.includes(term)) {
        score += 6
        hit = true
      }
      if (body.includes(term)) {
        score += 2
        hit = true
      }
      if (hit) matched += 1
    }

    if (title.includes(trimmed) || keywords.includes(trimmed)) {
      score += 15
    }

    if (matched === 0) continue
    if (matched < Math.ceil(effectiveTerms.length / 2)) continue

    results.push({ ...entry, score })
  }

  results.sort((a, b) => b.score - a.score)
  return results.slice(0, 12)
}
