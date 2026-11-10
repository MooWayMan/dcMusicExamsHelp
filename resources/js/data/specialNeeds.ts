// resources/js/data/specialNeeds.ts

// Special needs / SEN / access arrangements for Trinity music exams, written
// once. The Special needs page and its FAQ read from here, and the What to
// Expect page points here instead of keeping its own copy. Sources (checked
// 10 Nov 2026): Trinity "Practical exams (face to face)" and "Digital Grade
// and Diploma candidates with special needs" pages, the support article
// "Requesting Reasonable Adjustments & SEN Arrangements" (modified 27 Oct),
// and "Information & Regulations - Music" in ~/Documents/Claude.

export const SPECIAL_NEEDS_PATH = '/exam-guide/special-needs'

export const SEN_EMAIL = 'music-csn@trinitycollege.co.uk'

export const SEN_LINKS = {
  overview: 'https://www.trinitycollege.com/qualifications/music/special-needs',
  faceToFace: 'https://trinitycollege.com/qualifications/music/special-needs/practical',
  // Trinity has THREE forms (checked 10 Nov 2026): face-to-face practical and
  // paper theory share one; digital grades/diplomas and digital theory each
  // have their own.
  faceToFaceForm: 'https://trinitycollege.com/resource/?id=10667',
  digitalForm: 'https://trinitycollege.com/resource/?id=10664',
  theoryDigitalForm: 'https://trinitycollege.com/resource/?id=10665',
  theoryPaper: 'https://trinitycollege.com/qualifications/music/special-needs/theory',
  theoryDigital: 'https://trinitycollege.com/qualifications/music/special-needs/theory-digital',
  policy: 'https://www.trinitycollege.com/resource/?id=5347',
} as const

// The one-line rule other pages can quote.
export const SEN_FACE_TO_FACE_DEADLINE =
  'Face-to-face: a new form for every exam, at least 28 days before the exam date. Nothing can be arranged with the examiner on the day.'

export interface SenStep {
  title: string
  // HTML allowed (the glass card renders it).
  detail: string
}

const link = (url: string, text: string) =>
  `<a href="${url}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">${text}</a>`

export const SEN_ROUTES: SenStep[] = [
  {
    title: 'Face-to-face exams',
    detail: `Fill in Trinity’s ${link(SEN_LINKS.faceToFaceForm, 'face-to-face special needs form')} and email it with any evidence to <strong>${SEN_EMAIL}</strong>, or send it to your Trinity representative. Send it <strong>at least 28 days before the exam</strong>, and at least six weeks before if you need modified materials such as Braille. A <strong>new form is needed for every exam</strong>, even if the same adjustment was approved last time.`,
  },
  {
    title: 'Digital exams',
    detail: `When you book on MyTrinity, answer yes to the question about adjustments, download the ${link(SEN_LINKS.digitalForm, 'digital special needs form')} and upload it to the booking with any evidence. You can’t pay until it’s done. It is a different form from the face-to-face one. If you weren’t asked, email it to <strong>${SEN_EMAIL}</strong> when you book. Never put it in with the exam video. Where evidence is needed, wait for the approval email before you record.`,
  },
  {
    title: 'Theory exams',
    detail: `A <strong>paper</strong> theory exam uses the ${link(SEN_LINKS.faceToFaceForm, 'face-to-face form')}, with the same 28-day rule. A <strong>digital</strong> theory exam has its ${link(SEN_LINKS.theoryDigitalForm, 'own form')}, emailed to <strong>${SEN_EMAIL}</strong> at least 5 days before the exam. Details: ${link(SEN_LINKS.theoryPaper, 'paper theory')}, ${link(SEN_LINKS.theoryDigital, 'digital theory')}.`,
  },
  {
    title: 'The building itself',
    detail: 'For wheelchair access or another change to the exam room or venue, tell the exam centre as soon as possible. That part is arranged by the centre, not by Trinity.',
  },
]

export interface SenAdjustment {
  adjustment: string
  what: string
  evidence: string
}

// Face-to-face practical exams, from Trinity's practical-exams page.
export const SEN_ADJUSTMENTS: SenAdjustment[] = [
  { adjustment: 'Examiner briefed', what: 'The examiner is told about the candidate’s condition before the exam. Marks are never raised because of it.', evidence: 'Not needed' },
  { adjustment: 'Extra time', what: 'Extra time for the exam, set by grade (see the table below).', evidence: 'Needed' },
  { adjustment: 'Modified materials', what: 'Enlarged print, Modified Stave Notation or coloured paper. The candidate brings their own modified copies of pieces, plus the originals. Coloured overlays or tinted glasses need no application.', evidence: 'Not needed' },
  { adjustment: 'Braille tests', what: 'Braille sight reading, playback or improvisation tests, with preparation time before the exam (5 minutes at the lowest grades, up to 20 at Grades 7–8). Needs six weeks’ notice.', evidence: 'Not needed' },
  { adjustment: 'Memory test', what: 'Replaces sight reading for blind or visually impaired candidates who don’t read Braille.', evidence: 'Needed' },
  { adjustment: 'Aural awareness test', what: 'Replaces the usual aural test for candidates with hearing loss.', evidence: 'Needed' },
  { adjustment: 'Prompts for technical work', what: 'A brief glance at written notes for scales and other technical work. Leaning on them too much can lead to a referral.', evidence: 'Needed' },
  { adjustment: 'Typed report form', what: 'After the exam, a typed copy of the examiner’s report, for candidates with dyslexia or who use a screen reader. Send Trinity a scan of the report.', evidence: 'Not needed' },
]

// Extra time in face-to-face practical exams.
export const SEN_EXTRA_TIME: { grades: string; whole: string; unseen: string }[] = [
  { grades: 'Initial – Grade 3', whole: '3 minutes', unseen: 'Up to 1.5 minutes' },
  { grades: 'Grades 4 – 5', whole: '4 minutes', unseen: 'Up to 2 minutes' },
  { grades: 'Grades 6 – 8', whole: '5 minutes', unseen: 'Up to 2.5 minutes' },
]

// Digital grades and diplomas, from Trinity's digital special-needs page.
export const SEN_DIGITAL_ADJUSTMENTS: SenAdjustment[] = [
  { adjustment: 'Examiner briefed', what: 'The examiner is told about the candidate’s condition. Standards stay the same for everyone.', evidence: 'Not needed' },
  { adjustment: 'Extended recording time', what: 'Extra time between items, usually no more than 5 minutes in total. The video must still be one continuous recording.', evidence: 'Not needed' },
  { adjustment: 'Prompts for technical work', what: 'A glance at the music for technical work before playing it.', evidence: 'Needed' },
  { adjustment: 'Modified materials', what: 'Your own modified copies, such as large print.', evidence: 'Not needed' },
]

// The table that answers "which form, where does it go, when?" at a glance.
// Teachers mix these up (Paul, 10 Nov 2026), so it leads the page.
export const SEN_WHICH_FORM: { exam: string; form: string; sendTo: string; when: string }[] = [
  {
    exam: 'Face-to-face practical',
    form: 'Face-to-face form',
    sendTo: `Email ${SEN_EMAIL}, or give it to your Trinity representative`,
    when: 'At least 28 days before the exam. Six weeks for Braille or modified materials.',
  },
  {
    exam: 'Digital practical (grades and diplomas)',
    form: 'Digital grades and diplomas form (a different form)',
    sendTo: `Upload it to the MyTrinity booking when asked. Otherwise email ${SEN_EMAIL}. Never with the video.`,
    when: 'When you book, and before the video is submitted',
  },
  {
    exam: 'Theory on paper',
    form: 'The face-to-face form (the same one)',
    sendTo: `Email ${SEN_EMAIL}, or give it to your Trinity representative`,
    when: 'At least 28 days before the exam',
  },
  {
    exam: 'Theory, digital',
    form: 'Digital theory form (its own form)',
    sendTo: `Email ${SEN_EMAIL}`,
    when: 'At least 5 days before the exam',
  },
]

// The three PDFs, as download buttons under the table.
export const SEN_FORMS: { label: string; url: string }[] = [
  { label: 'Face-to-face & paper theory form', url: SEN_LINKS.faceToFaceForm },
  { label: 'Digital grades & diplomas form', url: SEN_LINKS.digitalForm },
  { label: 'Digital theory form', url: SEN_LINKS.theoryDigitalForm },
]

// Three different requests that teachers confuse with the special needs form.
export const SEN_NOT_TO_CONFUSE: SenStep[] = [
  {
    title: 'Special needs form',
    detail: `For a disability, condition or specific learning need, such as extra time, large print or a briefed examiner. Goes to Trinity <strong>before</strong> the exam. It is <strong>never</strong> handed to the examiner and <strong>never</strong> uploaded with the video.`,
  },
  {
    title: 'Pre-assessment adjustment',
    detail: `For a change to the syllabus itself, such as a different instrument or edition (a B♭ clarinet instead of an A, say). Uses a ${link('https://trinitycollege.com/resource?id=11089', 'different form')} and goes to your Trinity representative. If approved, the letter <strong>is</strong> handed to the examiner on the day or uploaded with the video.`,
  },
  {
    title: 'Special consideration',
    detail: `For something that went wrong close to or on the day, such as illness or a bereavement. Applied for <strong>after</strong> the exam under Trinity’s ${link('https://trinitycollege.com/resource?id=5748', 'special consideration policy')}. A child who was too ill to sit the exam needs a re-entry permit instead (see the FAQ).`,
  },
]

export const SEN_EVIDENCE =
  'Where evidence is needed it must come from a qualified medical or educational professional. Letters from family, friends or anyone with a personal connection are not accepted. Trinity can turn a request down if there isn’t enough information, and keeps evidence on file for three years.'
