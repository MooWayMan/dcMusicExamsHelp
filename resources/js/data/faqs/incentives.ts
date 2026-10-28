// resources/js/data/faqs/incentives.ts

import type { FaqPage } from '@/lib/faqs'
import { GIFT_TOKEN_REDEEM_RULE } from '@/lib/prizeRules'

// The FAQ on /incentives. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/incentives',
  label: 'Incentives FAQ',
  items: [
    {
      question: 'Does it cost anything extra to get these incentives?',
      answer: 'No. Everything on this page is included at no extra cost when you book through centre 120. The exam fees are the standard Trinity prices.',
    },
    {
      question: 'What are the certificates?',
      answer: 'Every student entered through centre 120 receives at least a Bravo Certificate. Score a Merit and you receive a Take a Bow Certificate instead. Score a Distinction and you receive a Standing Ovation Certificate instead. The highest scorers each quarter earn the top awards in two grade groups (Initial–5 and 6–8) — a Showstopper Certificate for the highest Distinction in each group and a Centre Stage Certificate for the highest Merit in each group. These are digital certificates you can print at home, display on a tablet or iPad for photos, or share straight to social media. No colour printer needed — just hold it up on the screen and snap a photo. These are in addition to the official Trinity certificate that every Pass, Merit and Distinction receives.',
    },
    {
      question: 'How does the prize draw work?',
      answer: 'We run two separate quarterly prize draws — one for students and one for teachers. Every exam entry through centre 120 — face-to-face, digital or theory — earns the student an automatic entry. Teachers also get one entry for every exam booked through centre 120 — the more students entered, the more chances to win. Winners are drawn at the end of each quarter and receive a <strong>£50 gift token</strong>. You don\'t need to do anything extra — entries are automatic.',
    },
    {
      question: 'Can I opt out of the Recognition page?',
      answer: 'Yes. If you\'d prefer not to be listed, just let us know and we\'ll remove your entry. By default, only your first name and initial are shown — full names are only displayed with permission.',
    },
    {
      question: 'How do teacher badges work?',
      answer: 'Every exam entry through centre 120 counts towards a teacher\'s award — regardless of the result and regardless of who booked it. There are four tiers: Bronze (10+), Silver (20+), Gold (30+) and Top Award (40+). Totals refresh every quarter.',
    },
    {
      question: 'Do digital exams qualify?',
      answer: 'Yes. Every incentive on this page applies to both face-to-face and digital exams booked through centre 120. Digital exams count exactly the same.',
    },
    {
      question: 'How long do I have to use a gift token?',
      answer: GIFT_TOKEN_REDEEM_RULE,
      keywords: ['how long do i have to use a gift token', 'gift token', 'gift card', 'expire', 'expiry', 'redeem', 'claim prize', 'prize draw', '12 months'],
    },
  ],
}

export default page
