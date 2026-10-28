// resources/js/data/faqs/forParents.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /for-parents. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/for-parents',
  label: 'For Parents FAQ',
  items: [
    {
      question: 'What is a music exam?',
      answer: 'A graded music exam is a formal assessment of your child\'s playing ability on their instrument. Depending on the grade and instrument, they may perform prepared pieces, technical work, sight-reading, aural tests or a combination — not every exam includes all of these. It\'s a milestone that shows progress and gives them something to work towards.',
    },
    {
      question: 'What grades are there?',
      answer: 'Trinity offers grades from Initial (beginner) through to Grade 8 (advanced), plus diplomas beyond that. Most students work through the grades over several years, guided by their teacher.',
    },
    {
      question: 'How much does it cost?',
      answer: 'Exam fees vary by grade and are set by Trinity College London. Using centre 120 doesn\'t cost anything extra — the fees are the standard Trinity prices. You can see the full fee breakdown on our <a href="/exam-fees?from=for-parents" class="font-semibold text-brand-accent underline hover:opacity-70">Exam Fees page</a>.',
    },
    {
      question: 'What is the difference between digital and face-to-face?',
      answer: 'Face-to-face exams take place at a local venue with an examiner in the room. Digital exams can be recorded anywhere and submitted online. Both are marked to the same standard and carry the same qualification. Digital exams are also cheaper at every grade. Many families prefer digital for the convenience.',
    },
    {
      question: 'How long until we get the result?',
      answer: 'For digital exams, results are normally provided within 14 days of submission. The exam report is sent as an attachment to the email address provided at booking — no login or portal needed. If you gave your own email when the exam was booked, you\'ll receive it directly. For face-to-face exams through centre 120, results are checked and posted to the applicant within days of the exam — much faster than the usual turnaround. In both cases, a digital certificate is issued automatically for any Pass, Merit or Distinction. If you\'d like a printed paper certificate, that\'s an extra £5.',
      keywords: ['how long until i get my results', 'results', 'when are results', 'how long for results', 'result turnaround', 'when do i get results', 'how long until results', 'get my results', 'exam report', 'when results'],
    },
    {
      question: 'Do I need to be present for the exam?',
      answer: 'For face-to-face exams, parents are not allowed in the exam room. The only exception is if a candidate needs help setting up equipment (e.g. an electric keyboard) — a parent can come in to help with setup but must then leave and go to the designated waiting area. A teacher or accompanist may be present in the room if they are performing with the candidate. For digital exams, you may need to help with the recording setup, but the performance itself is your child\'s. Anyone can submit the recording — it doesn\'t have to be the teacher.',
    },
    {
      question: 'What happens after we book?',
      answer: 'The exam can be booked by you (the parent), the teacher or the school — whoever is named as the applicant will receive the correspondence.<br /><br /><strong>Digital exams:</strong> You\'ll get a confirmation email straight away. Login details for the upload portal usually arrive within the hour, but can take up to a week (check spam). You then have four weeks to record and submit.<br /><br /><strong>Classical &amp; Jazz face-to-face:</strong> You\'ll know your exam day and time straight away when you book through the MOB booking system. An appointment slip needs to be filled in and handed to the examiner — printed if possible, but handwritten is fine if you can\'t print it.<br /><br /><strong>Rock &amp; Pop face-to-face:</strong> Your exam date and time will be confirmed closer to the session — usually around three weeks before. An appointment slip needs to be filled in and handed to the examiner — printed if possible, but handwritten is fine if you can\'t print it.',
    },
    {
      question: 'What does centre 120 mean?',
      answer: 'Centre 120 is our registered Trinity exam centre. It covers digital exams, face-to-face exams in Liverpool and Wirral, and digital theory exams. When your child\'s entry is connected to centre 120, they receive at least a <strong>Bravo Certificate</strong>, qualify for the Recognition page, the <strong>Hall of Fame</strong> and other incentives at no extra cost.',
    },
  ],
}

export default page
