// resources/js/data/faqs/switchToCentre120.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /switch-to-centre-120. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/switch-to-centre-120',
  label: 'Switch to Centre 120 FAQ',
  items: [
    {
      question: 'Does using centre 120 cost anything extra?',
      answer: 'No. Trinity charges your candidate exactly the same fee whether or not centre 120 is on the booking. Your student pays Trinity direct. We receive a small reseller commission from Trinity (not from you, not from the candidate) which funds the recognition programme — prize draws, certificates, gift tokens.',
    },
    {
      question: 'How exactly do I add centre 120 at booking?',
      answer: 'Easiest: click the <strong>Book a Trinity Exam</strong> button on this page — for digital bookings we automatically pre-fill <strong>120</strong> in the referral code field for you. Just verify it\'s still in the box before paying, as a page refresh can occasionally clear it. Or, if you book through Trinity directly, type <strong>120</strong> in the "referral code" field at checkout. Face-to-face bookings at our Liverpool or Wirral venues are linked to centre 120 automatically — no code needed.',
    },
    {
      question: 'I currently book with another centre. Can I switch?',
      answer: 'Yes — simply enter centre 120 on your next Trinity booking. There is no contract on either side, no formal switch needed. Each booking is independent. Your existing students with prior bookings under another centre stay where they are; new bookings flow through 120.',
    },
    {
      question: "What if my student doesn't pass?",
      answer: 'Every centre 120 candidate gets a Bravo Certificate regardless of result and is listed on the Recognition page. We never publish failed scores — we only publish that they entered and sat the exam. The recognition is for the effort, not just the outcome.',
    },
  ],
}

export default page
