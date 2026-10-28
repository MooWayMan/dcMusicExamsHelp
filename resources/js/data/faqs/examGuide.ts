// resources/js/data/faqs/examGuide.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide',
  label: 'Exam Guide FAQ',
  items: [
    {
      question: 'Can I do both Classical & Jazz and Rock & Pop?',
      answer:
        'Yes — they are separate qualifications so a student can take exams in both. For example, a guitarist could do Classical Guitar Grade 3 and Rock & Pop Guitar Grade 2 at the same time. They count as different subjects.',
    },
    {
      question: 'Which one should I choose?',
      answer:
        'It depends on what you enjoy playing. If you love learning from sheet music and playing classical or jazz repertoire, Classical & Jazz is the natural fit. If you prefer playing songs by bands and artists you listen to, Rock & Pop is probably more motivating. Your teacher will know which suits you best.',
    },
    {
      question: 'Are Rock & Pop exams easier than Classical & Jazz?',
      answer:
        'No. They are different, not easier. Rock & Pop exams assess different skills — groove, feel, musicality within a band context. Classical & Jazz exams focus more on technique, sight-reading and aural skills. Both are rigorous and both are regulated by Ofqual.',
    },
    {
      question: 'Should I do a theory exam as well?',
      answer:
        'You don\'t have to — there\'s no requirement to pass a theory exam before taking any Trinity grade. But understanding music theory can genuinely help your playing. Knowing how harmony, structure and rhythm work gives you a deeper understanding of the pieces you\'re performing. Theory exams can be taken online from home and are a great way to build musical knowledge alongside your practical grades.',
    },
    {
      question: 'Do all three exam types earn UCAS points?',
      answer:
        'Yes. Grades 6, 7 and 8 in Classical & Jazz, Rock & Pop and Music Theory all earn UCAS points. A Grade 8 Distinction is worth 30 UCAS points regardless of which exam type.',
    },
    {
      question: 'What if I play an instrument not covered by Rock & Pop?',
      answer:
        'Rock & Pop only covers guitar, bass, drums, keyboards and vocals. If you play a different instrument — such as flute, trumpet, violin or saxophone — you would take the Classical & Jazz exam for that instrument.',
    },
  ],
}

export default page
