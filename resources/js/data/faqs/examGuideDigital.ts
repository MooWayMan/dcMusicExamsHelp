// resources/js/data/faqs/examGuideDigital.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-guide/digital-exams. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-guide/digital-exams',
  label: 'Digital Exams FAQ',
  items: [
    {
      question: 'Can I re-record my performance?',
      answer:
        'Yes — as many times as you like before you upload. Once you\'ve submitted your recording through the portal, that\'s your final submission and it cannot be changed. So make sure you\'re happy with it before you hit upload. This is one of the biggest advantages of digital exams — take your time and get it right.',
    },
    {
      question: 'Who can submit the recording?',
      answer:
        'Anyone — the candidate, a teacher, or a parent. There\'s no restriction on who uploads the video. The important thing is that it\'s submitted before the deadline.',
    },
    {
      question: 'Do I need a professional camera or microphone?',
      answer:
        'No. A modern smartphone records perfectly good quality video and audio. Just make sure the examiner can see your hands and hear you clearly. You don\'t need professional equipment.',
    },
    {
      question: 'Is a digital exam easier than face-to-face?',
      answer:
        'The qualification and marking criteria are identical. Digital exams remove the pressure of a single live performance (you can re-record), but the standard required is the same. Many candidates find digital less stressful, others prefer the energy of performing live.',
    },
    {
      question: 'Can I do Rock & Pop digitally?',
      answer:
        'Yes. Digital exams are available for both Classical & Jazz and Rock & Pop, across all grades. The same digital flexibility applies to both exam types.',
    },
    {
      question: 'What\'s the deadline for uploading?',
      answer:
        'You have 28 days from the date of booking to submit your recording. Check your booking confirmation for the exact deadline. For UCAS purposes, recordings must be uploaded at least three weeks before the second Friday in June.',
    },
    {
      question: 'How long until I get my result?',
      answer:
        'Results for digital graded exams are normally provided within 14 days of submission. Diploma exams may take up to 28 days. Your digital certificate is sent within 48 hours of receiving your result. If you\'d like a printed paper certificate, that\'s an extra £5.',
    },
    {
      question: 'Can I do a diploma digitally?',
      answer:
        'Yes. Trinity now offers digital versions of the ATCL, LTCL and FTCL Recital diplomas for both Classical & Jazz and Rock & Pop. Digital diplomas are marked to the same standard and carry the same qualification. Teaching and theory diplomas are face-to-face only.',
    },
    {
      question: 'Can I use a recorded accompaniment?',
      answer:
        'Yes — recorded accompaniments are permitted at all grades for digital exams. This is a big advantage over face-to-face, where a live accompanist is required from Grade 4 onwards. You can use any suitable recorded accompaniment.',
    },
  ],
}

export default page
