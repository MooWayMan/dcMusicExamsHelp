// resources/js/data/faqs/examGuideSpecialNeeds.ts

import type { FaqPage } from '@/lib/faqs'
import { SEN_EMAIL, SEN_EVIDENCE, SEN_LINKS, SPECIAL_NEEDS_PATH } from '@/data/specialNeeds'

const a = (url: string, text: string) => `<a href="${url}" target="_blank" rel="noopener noreferrer">${text}</a>`

// The FAQ on /exam-guide/special-needs. The page draws its accordion from this
// list and site search reads it too, so an answer is written here once. The
// email, links and evidence rule come from data/specialNeeds.ts.
const page: FaqPage = {
  path: SPECIAL_NEEDS_PATH,
  label: 'Special Needs FAQ',
  items: [
    {
      question: 'Who sends the special needs form, the teacher or the parent?',
      answer:
        'Whoever books the exam, whether that is a parent, the teacher or the school. It is the same form whoever sends it. What matters is that it reaches Trinity in time.',
      keywords: ['special needs', 'sen', 'send', 'who', 'teacher', 'parent', 'form', 'access arrangements'],
    },
    {
      question: 'Is the special needs form the same for digital and face-to-face exams?',
      answer: `No, Trinity has three. A face-to-face exam uses the ${a(SEN_LINKS.faceToFaceForm, 'face-to-face form')}, which also covers theory on paper. A digital grade or diploma uses the ${a(SEN_LINKS.digitalForm, 'digital grades and diplomas form')}. A digital theory exam uses the ${a(SEN_LINKS.theoryDigitalForm, 'digital theory form')}.`,
      keywords: ['same form', 'which form', 'digital form', 'face to face form', 'different form', 'theory form', 'download'],
    },
    {
      question: 'Where do I send the special needs form?',
      answer: `For a face-to-face exam, email it to <strong>${SEN_EMAIL}</strong> or give it to your Trinity representative. For a digital exam booked on MyTrinity, upload it to the booking when MyTrinity asks; otherwise email it to the same address. It never goes to the examiner and never goes in with the exam video.`,
      keywords: ['where', 'email', 'address', 'send form', 'sen form', 'special needs form', 'music-csn'],
    },
    {
      question: 'What is the deadline for a special needs form?',
      answer:
        'Face-to-face and paper theory: at least 28 days before the exam, and at least six weeks if you need Braille or other modified materials. Digital grades and diplomas: when you book, and before the video is submitted. Digital theory: at least 5 days before the exam. Late requests may not be possible.',
      keywords: ['deadline', 'when', '28 days', 'six weeks', 'late', 'how long before'],
    },
    {
      question: 'My child had adjustments last time. Do I need to apply again?',
      answer:
        'Yes. Trinity needs a new special needs form for every exam, even when the same adjustment was approved before.',
      keywords: ['again', 'every exam', 'last time', 'previous', 'renew', 'new form'],
    },
    {
      question: 'Can I just tell the examiner about my child’s needs on the day?',
      answer:
        'No. Examiners cannot act on anything first raised on the day, and a form handed over in the exam room is not accepted. It has to be approved by Trinity beforehand.',
      keywords: ['examiner', 'on the day', 'tell', 'verbally', 'hand in'],
    },
    {
      question: 'Do I upload the special needs form with the digital exam video?',
      answer:
        'No. Upload it to the MyTrinity booking, or email it, when you book. Never put it in with the video. Where evidence is needed, wait for the approval email before recording.',
      keywords: ['upload', 'video', 'digital', 'submission', 'mytrinity', 'record'],
    },
    {
      question: 'What evidence do I need for special needs adjustments?',
      answer: `Only some adjustments need it, such as extra time, the memory test, the aural awareness test and prompts for technical work. ${SEN_EVIDENCE}`,
      keywords: ['evidence', 'proof', 'diagnosis', 'letter', 'doctor', 'senco', 'report', 'ehcp'],
    },
    {
      question: 'How much extra time can a candidate have?',
      answer:
        'In a face-to-face practical exam it depends on the grade; the table on this page gives the minutes. In a digital exam it is extra time between items, usually no more than 5 minutes in total, and the video must still be one continuous recording.',
      keywords: ['extra time', 'more time', 'minutes', 'time'],
    },
    {
      question: 'Will an adjustment change my child’s marks?',
      answer:
        'No. An adjustment helps the candidate show what they can do, but the marking standard is the same for everyone and marks are never raised because of a condition.',
      keywords: ['marks', 'marking', 'fair', 'lower standard', 'easier'],
    },
    {
      question: 'What is the difference between a special needs form and a pre-assessment adjustment?',
      answer:
        'The special needs form is for a disability or learning need, goes to Trinity beforehand and never goes to the examiner. A pre-assessment adjustment is a change to the syllabus itself, such as playing a different instrument or edition; it uses a different form, and if approved the letter is handed to the examiner or uploaded with the video.',
      keywords: ['pre-assessment', 'different instrument', 'difference', 'confused', 'which form', 'adjustment'],
    },
    {
      question: 'Is there a special needs form for dyslexia?',
      answer:
        'It is the same special needs form. For dyslexia the most common requests are extra time, modified materials (enlarged print or coloured paper) and a typed copy of the examiner’s report afterwards. Coloured overlays and tinted glasses can be used without applying.',
      keywords: ['dyslexia', 'dyslexic', 'coloured paper', 'overlay', 'large print', 'typed report'],
    },
    {
      question: 'What about wheelchair access or other changes to the exam venue?',
      answer:
        'Tell the exam centre as soon as possible. Changes to the room or building are arranged by the centre, not through Trinity’s special needs form.',
      keywords: ['wheelchair', 'access', 'venue', 'building', 'stairs', 'room', 'mobility'],
    },
    {
      question: 'Does it cost anything to ask for adjustments?',
      answer:
        'Trinity’s special needs pages don’t mention any fee for a special needs request.',
      keywords: ['cost', 'fee', 'free', 'charge', 'price'],
    },
    {
      question: 'Where can I read Trinity’s own special needs guidance?',
      answer: `On Trinity’s <a href="${SEN_LINKS.overview}" target="_blank" rel="noopener noreferrer">special needs and access arrangements page</a>, which has every form, and in its <a href="${SEN_LINKS.policy}" target="_blank" rel="noopener noreferrer">reasonable adjustment policy</a>.`,
      keywords: ['trinity', 'guidance', 'policy', 'official', 'reasonable adjustments'],
    },
  ],
}

export default page
