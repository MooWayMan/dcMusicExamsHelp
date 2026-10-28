// resources/js/data/faqs/forTeachers.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /for-teachers. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/for-teachers',
  label: 'For Teachers FAQ',
  items: [
    {
      question: 'Do my students pay more through centre 120?',
      answer: 'No. Exam fees are exactly the same as booking directly with Trinity. Centre 120 simply connects your entries to additional benefits and recognition.',
    },
    {
      question: 'Can I still use a different centre code?',
      answer: 'If you already have a different centre code, you can keep using it. But your students will miss out on our certificates (Bravo, Take a Bow, Standing Ovation), the Recognition page, the <strong>Hall of Fame</strong>, prize draws and other incentives that come with centre 120.',
    },
    {
      question: 'How does the teacher badge system work?',
      answer: 'Every entry through centre 120 counts — regardless of result. That includes face-to-face exams, digital practical exams and digital theory exams. Totals refresh every quarter. At 10 entries you earn the <strong>Bronze Award</strong>, at 20 the <strong>Silver Award</strong>, at 30 the <strong>Gold Award</strong>, and at 40+ the <strong>Top Award</strong>. Badges are digital — display them on your website, email signature or social media and call yourself an award-winning music teacher.',
    },
    {
      question: 'Do you offer face-to-face, digital and theory exams?',
      answer: 'Yes, all three. Face-to-face sessions run at venues in Liverpool and Wirral throughout the year. Digital practical exams can be recorded anywhere — at home, school or studio — and submitted online through Trinity. Digital theory exams (Grades 1–8) are taken online through the MusicGurus platform. All exam types are available through our Book Your Exam button.',
    },
  ],
}

export default page
