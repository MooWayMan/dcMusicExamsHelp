// resources/js/data/faqs/welcome.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/',
  label: 'Home Page FAQ',
  items: [
    {
      question: 'Do I book through this website?',
      answer:
        'No — when you click Book Your Exam, you will see a short menu asking which type of exam you want. Each option takes you to the correct Trinity booking system. For digital exams, our link pre-fills centre 120 automatically, but if you refresh or go back it can disappear — always check the referral code box says 120 before you book. For face-to-face exams, your entry is automatically connected to centre 120.',
    },
    {
      question: 'Who is this for?',
      answer:
        'Anyone involved in music exams. Teachers looking for a smoother booking process and recognition. Parents wanting clear guidance on how exams work. Students preparing for their next grade.',
    },
    {
      question: 'What is centre 120?',
      answer:
        'Centre 120 is our registered Trinity College London exam centre. It covers digital exams, face-to-face exams in Liverpool and Wirral, and digital theory exams. When your entry is connected to centre 120, you unlock access to incentives, recognition and ongoing support.',
    },
    {
      question: 'Does it cost anything extra?',
      answer:
        'No. The exam fees are the same regardless of which centre you use. Centre 120 simply gives you access to the extra benefits and support offered through musicExams.help.',
    },
  ],
}

export default page
