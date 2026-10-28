// resources/js/data/faqs/examGuideExpect.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide/what-to-expect. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide/what-to-expect',
  label: 'What to Expect FAQ',
  items: [
    {
      question: 'How long does the exam last?',
      answer:
        'It depends on the grade. Initial and Grade 1 exams are around 12 minutes. By Grade 8, you\'re looking at around 25–30 minutes. Your appointment time includes a few minutes for settling in.',
    },
    {
      question: 'Can a parent or teacher come into the room?',
      answer:
        'Parents are not allowed in the exam room during a face-to-face exam. The only exception is if a candidate needs help setting up equipment (e.g. an electric keyboard) — a parent can come in to help with setup but must then leave and go to the designated waiting area. A teacher or accompanist may be present if they are performing with the candidate. For digital exams, you may need to help with the recording setup, but the performance itself is the candidate\'s.',
    },
    {
      question: 'What happens if I make a mistake?',
      answer:
        'Keep playing! Examiners are assessing your overall musicianship — tone, expression, rhythm, and how you handle the piece as a whole. A small slip won\'t ruin your mark. Stopping and starting again is more noticeable than carrying on.',
    },
    {
      question: 'Do I need to memorise my pieces?',
      answer:
        'No — you don\'t have to, but you can if you want to. Either way, your original sheet music must be present. For face-to-face exams, the examiner has all Trinity-published pieces on their laptop — but you still need to bring the original book to prove it was purchased (copyright rules). If any of your pieces are not published by Trinity, you\'ll need to provide a copy for the examiner to follow — a photocopy or a tablet in aeroplane mode is fine. For digital exams, copies of any non-Trinity pieces must be uploaded with your recording.',
    },
    {
      question: 'What if I\'m ill on exam day?',
      answer:
        'Let us know as soon as possible — contact musicExams.help straight away so we can notify Trinity on your behalf. If you can provide a doctor\'s note, nurse\'s note or school nurse\'s note confirming the illness, Trinity will offer a 50% re-entry permit — meaning the candidate can retake the exam at half the usual fee. Don\'t try to push through a serious illness — it\'s better to rearrange and perform at your best.',
    },
  ],
}

export default page
