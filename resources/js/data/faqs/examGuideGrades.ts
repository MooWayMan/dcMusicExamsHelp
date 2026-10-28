// resources/js/data/faqs/examGuideGrades.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide/grades-explained. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide/grades-explained',
  label: 'Grades Explained FAQ',
  items: [
    {
      question: 'Do I have to start at Grade 1?',
      answer:
        'No. You can enter at any grade. If your teacher thinks you\'re ready for Grade 3, you can go straight to Grade 3. There\'s no requirement to pass earlier grades first.',
    },
    {
      question: 'Can I skip grades?',
      answer:
        'Yes. It\'s very common to skip grades, especially in the early stages. Many students go from Grade 1 to Grade 3, or Grade 5 to Grade 7. Your teacher will guide you on when you\'re ready for the next step.',
    },
    {
      question: 'What happens if I don\'t pass?',
      answer:
        'You\'ll receive detailed feedback from the examiner explaining what went well and what to work on. You can retake the same grade if you want to, or you can move on to the next grade and use the feedback to improve — there\'s no requirement to pass one grade before entering the next.',
    },
    {
      question: 'Are Rock & Pop grades the same level as Classical & Jazz?',
      answer:
        'Yes. Both are Ofqual-regulated at the same level. A Grade 5 in Rock & Pop Guitar is the same qualification level as a Grade 5 in Classical Guitar. The skills assessed are different, but the standard is equivalent.',
    },
    {
      question: 'How long should I prepare between grades?',
      answer:
        'It varies hugely depending on the student\'s age, the instrument, and how much they practise. Younger students naturally take longer to develop the skills needed for higher grades. As a rough guide: Grades 1–3 might be one grade per year, Grades 4–5 might take 1–2 years each, and Grades 6–8 often take 2+ years each. Regular practice makes the biggest difference — but there\'s no rush, go at your own pace.',
    },
    {
      question: 'Do I need Grade 5 Theory to take higher grades?',
      answer:
        'No — Trinity does not require a theory pass before taking any grade. You can go straight to Grade 8 on your instrument without any theory exams. (This is different from some other exam boards.)',
    },
  ],
}

export default page
