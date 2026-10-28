// resources/js/data/faqs/examFees.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /exam-fees. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/exam-fees',
  label: 'Exam Fees FAQ',
  items: [
    {
      question: 'Are there any extra costs beyond the exam fee?',
      answer: 'All fees include a digital certificate that can be downloaded or shared online. If you want a printed paper certificate, that costs an additional £5.00 for UK delivery — order one directly from Trinity at <a href="https://mycertificates.trinitycollege.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">mycertificates.trinitycollege.com</a> using the “Paper certificates” option. There are no other hidden charges.',
    },
    {
      question: 'Why are digital exams cheaper than face-to-face?',
      answer: 'Digital exams don\'t require venue hire, an examiner travelling to a location, or scheduled session dates — so Trinity passes the saving on. You get exactly the same Ofqual-regulated certificate and UCAS points either way.',
    },
    {
      question: 'Do I pay more by booking through centre 120?',
      answer: 'No. The exam fees are set by Trinity College London and are the same regardless of which centre you book through. Centre 120 simply gives you access to additional benefits, recognition and support at no extra cost.',
    },
    {
      question: 'Are there late entry surcharges?',
      answer: 'Yes — Trinity applies surcharges for late entries. Full details are in <a href="https://www.trinitycollege.com/qualifications/music/resources/general-resources/our-music-exams/information-regulations-from-2017" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">Trinity\'s policies and regulations</a>. Booking well ahead of the closing date avoids this.',
      keywords: ['are there late entry surcharges', 'late entry', 'surcharge', 'late fee', 'deadline', 'closing date', 'late booking', 'missed deadline'],
    },
    {
      question: 'Do Trinity require a theory exam before higher grades?',
      answer: 'No. Unlike some other exam boards, Trinity has no theory requirement at any grade. You can go straight to Grade 8 without ever sitting a theory exam. Theory exams are available separately if you want them.',
    },
    {
      question: 'When do exam fees change?',
      answer: 'Trinity sets fees on a calendar year basis — the current fees run from 1 January to 31 December 2026. Any changes are announced by Trinity ahead of the new year.',
    },
  ],
}

export default page
