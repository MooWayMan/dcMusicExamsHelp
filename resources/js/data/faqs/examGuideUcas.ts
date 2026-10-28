// resources/js/data/faqs/examGuideUcas.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide/ucas-points. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide/ucas-points',
  label: 'UCAS Points FAQ',
  items: [
    {
      question: 'Do I need to tell UCAS about my music exam?',
      answer:
        'No. Trinity sends eligible results to UCAS automatically. You just need to make sure your exam happens before the June deadline.',
    },
    {
      question: 'Can I count UCAS points from more than one music exam?',
      answer:
        'Yes, but only from different subjects. For example, you could count Grade 8 Piano and Grade 7 Music Theory — both count because they\'re different subjects. You cannot count Grade 7 Piano and Grade 8 Piano — only the result with the most UCAS points counts. In practice, a higher grade always earns equal or more points than the grade below it, even at a lower result band.',
    },
    {
      question: 'Do Rock & Pop exams earn the same UCAS points as Classical & Jazz?',
      answer:
        'Yes, exactly the same. A Grade 8 Distinction in Rock & Pop Guitar earns 30 UCAS points, the same as a Grade 8 Distinction in Classical Guitar.',
    },
    {
      question: 'What if I miss the June deadline?',
      answer:
        'Your result will still count for the following year\'s UCAS cycle. The qualification doesn\'t expire — it\'s just about when the result reaches UCAS.',
    },
  ],
}

export default page
