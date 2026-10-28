// resources/js/data/faqs/examGuideSyllabuses.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide/syllabuses. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide/syllabuses',
  label: 'Syllabuses FAQ',
  items: [
    {
      question: 'What is a syllabus?',
      answer:
        'The syllabus is Trinity\'s official document listing everything you need to know about an exam — the pieces you can choose, the scales and technical work required, the supporting tests and how the exam is marked. It\'s your roadmap for preparing.',
    },
    {
      question: 'Do I need to buy the syllabus?',
      answer:
        'No — the syllabus information is free to view on Trinity\'s website. The links on this page take you straight to the right place for your instrument. You may need to buy the sheet music for your chosen pieces, but the syllabus itself is free.',
    },
    {
      question: 'How often does the syllabus change?',
      answer:
        'Both Classical & Jazz and Rock & Pop syllabuses have no fixed end date — new pieces are added regularly and the list keeps growing. Occasionally a piece may be removed due to licensing or permissions, so always check the current syllabus before choosing your pieces.',
    },
    {
      question: 'Can I play pieces from an older syllabus?',
      answer:
        'Yes — for both Classical & Jazz and Rock & Pop, the repertoire lists are cumulative. Pieces from previous lists are usually still valid, and new pieces are added over time. Always check the Trinity website for the latest information.',
    },
  ],
}

export default page
