// resources/js/data/faqs/contact.ts

import type { FaqPage } from '@/lib/faqs'

// The FAQ on /contact. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/contact',
  label: 'Contact FAQ',
  items: [
    {
      question: 'How quickly will I get a reply?',
      answer: 'We aim to respond to all enquiries within 24 hours during term time. During school holidays it may take a little longer, but we\'ll always get back to you.',
    },
    {
      question: 'Can I call instead?',
      answer: 'We don\'t currently offer phone support, but email is the quickest way to reach us. If your question is urgent, mention it in the subject line and we\'ll prioritise it.',
    },
    {
      question: 'I need help booking an exam — can you walk me through it?',
      answer: 'Absolutely. Drop us a message with the instrument, grade and whether you want face-to-face, digital or theory, and we\'ll guide you through the process step by step.',
    },
  ],
}

export default page
