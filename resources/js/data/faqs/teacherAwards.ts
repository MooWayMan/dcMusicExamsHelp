// resources/js/data/faqs/teacherAwards.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /for-teachers/awards. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/for-teachers/awards',
  label: 'Teacher Awards FAQ',
  items: [
    {
      question: 'What counts as an entry?',
      answer: 'Every student entered through centre 120 counts towards your total — whether digital, face-to-face or theory, and regardless of whether they pass, get a merit, distinction or any other result. The entry itself is what matters. If a parent books the exam directly, just let us know which teacher the student belongs to so we can link it to the right person.',
    },
    {
      question: 'Does it matter who books the exam?',
      answer: 'No. Parents, teachers and schools can all book exams. As long as we know which teacher or school the student is linked to, it counts. If a parent books directly, either the parent or the teacher can get in touch to let us know — it only takes a moment.',
    },
    {
      question: 'Can a school earn an award, or just individual teachers?',
      answer: 'Either teachers or schools can earn awards. By default, entries are linked to the teacher. If you\'d prefer the recognition to go to your school instead, just let us know — it\'s your choice. The key thing is that every entry counts, regardless of who actually books the exam.',
    },
    {
      question: 'How do I display my badge?',
      answer: 'You will receive a digital badge image file that you can add to your website, social media profiles, email signature or printed materials. It is yours to use however you like.',
    },
    {
      question: 'How are my entries tracked?',
      answer: 'We track entries through Trinity\'s centre 120 booking records. For digital exams, our link pre-fills centre 120 automatically when you use the Book Your Exam button — but if you refresh the page or use the back button the code can disappear. Always check the referral code box says 120 before you book. For face-to-face exams in Liverpool or Wirral, your entry is automatically connected to centre 120 — no code needed.',
    },
    {
      question: 'Do entries reset or carry over?',
      answer: 'Entries reset each quarter. This means you can earn a new award every quarter — so a teacher or school could receive the Top Award in Q1, Q2, Q3 and Q4 of the same year. The more quarters you hit a tier, the more awards you collect.',
    },
  ],
}

export default page
