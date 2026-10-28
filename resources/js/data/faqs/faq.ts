// resources/js/data/faqs/faq.ts

import type { FaqPage } from '@/lib/faqs'
import { GUITAR_EXAMS_SUMMARY, ROCK_POP_ACOUSTIC_RULE } from '@/lib/guitarExams'

// The FAQ on /faq. The page draws its accordion from this list and site
// search reads it too, so an answer is written here once.
const page: FaqPage = {
  path: '/faq',
  label: 'FAQ',
  items: [
    {
      question: 'Do I book through this website?',
      answer:
        'No — when you click Book Your Exam, you will see a short menu asking which type of exam you want. Each option takes you to the correct Trinity booking system. For digital exams and theory exams, our link pre-fills centre 120 automatically, but if you refresh or go back it can disappear — always check the referral code box says 120 before you book. For face-to-face exams, your entry is automatically connected to centre 120.',
      keywords: ['how do i enter (book) an exam', 'enter', 'entry', 'entering', 'enter an exam', 'enter a candidate', 'enter a student', 'make an entry', 'book', 'booking', 'how to book', 'sign up for an exam', 'sign up', 'put in for an exam', 'register for an exam', 'apply for an exam', 'how do i enter', 'referral code', '120', 'where to book'],
      snippet: 'No — Book Your Exam opens a short menu that takes you to the correct Trinity booking system. Always check the referral code box says 120.',
    },
    {
      question: 'Who is this for?',
      answer:
        'Anyone involved in music exams. Teachers looking for a smoother booking process and recognition. Parents wanting clear guidance on how exams work. Students preparing for their next grade.',
      keywords: ['who is this for', 'teachers', 'parents', 'students'],
      snippet: 'Anyone involved in music exams — teachers wanting smoother booking and recognition, parents wanting clear guidance, students preparing for their next grade.',
    },
    {
      question: 'What is centre 120?',
      answer:
        'Centre 120 is our registered Trinity College London exam centre code — it covers both our digital centre and our face-to-face centres in Liverpool and Wirral. When you use it at booking, your entry is connected to musicExams.help — which means every candidate receives at least a <strong>Bravo Certificate</strong>, a place on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a> (with the Hall of Fame for Merit and Distinction results), and entry into our quarterly student prize draw. Teachers also get their own prize draw, recognition badges and ongoing support.',
      keywords: ['centre 120', 'what is 120', 'centre code', 'liverpool', 'wirral'],
      snippet: 'Our registered Trinity centre code, covering our digital centre and face-to-face centres in Liverpool and Wirral, connecting your entry to musicExams.help.',
    },
    {
      question: 'Does it cost anything extra?',
      answer:
        'No. The exam fees are the same regardless of which centre you use. Centre 120 simply gives you access to the extra benefits and support offered through musicExams.help.',
      keywords: ['extra cost', 'does it cost', 'free', 'fees', 'price'],
      snippet: 'No. Exam fees are the same regardless of centre. Centre 120 simply gives you the extra benefits and support of musicExams.help.',
    },
    {
      question: 'Can I use this if I already have a teacher?',
      answer:
        'Absolutely. This site supports your existing teacher, not replaces them. Your teacher stays central to everything — we just make the booking and admin side easier for everyone.',
      keywords: ['existing teacher', 'already have a teacher', 'my teacher'],
      snippet: 'Absolutely — this site supports your existing teacher, not replaces them. It just makes booking and admin easier for everyone.',
    },
    {
      question: 'What are digital exams?',
      answer:
        'Digital exams let you record your performance anywhere — at home, at school, in a studio — and submit it online through Trinity. No need to travel to an exam venue. Anyone can submit the recording, not just the teacher. Results come back the same way as face-to-face exams.',
      keywords: ['digital exams', 'recorded exam', 'online', 'submit recording', 'video exam'],
      snippet: 'Record your performance anywhere and submit it online through Trinity — no travel to a venue. Anyone can submit the recording.',
    },
    {
      question: 'What instruments can I take exams on?',
      answer:
        'Trinity offers graded exams across a wide range of instruments. <strong>Classical &amp; Jazz</strong> covers piano, brass, woodwind, strings, singing, guitar, percussion and more. <strong>Rock &amp; Pop</strong> covers guitar, bass, drums, keyboards and vocals. See our <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabuses page</a> for the full list.',
      keywords: ['instruments', 'what instruments', 'piano', 'guitar', 'brass', 'drums', 'singing', 'rock and pop'],
      snippet: 'A wide range — Classical & Jazz (piano, brass, woodwind, strings, singing, guitar, percussion) and Rock & Pop (guitar, bass, drums, keyboards, vocals).',
    },
    {
      question: 'How do I prepare for my exam?',
      answer:
        'Start by checking the <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabus</a> for your instrument and grade — it sets out exactly what you need to prepare, including pieces, technical work and supporting tests. If you have a teacher, they will guide you through what to work on and help you choose your pieces. If you are preparing on your own, the syllabus and Trinity\'s published books and resources have everything you need.',
      keywords: ['prepare', 'preparation', 'how to prepare', 'practice', 'get ready'],
      snippet: 'Start with the syllabus for your instrument and grade — pieces, technical work and supporting tests. Your teacher will guide you.',
    },
    {
      question: 'Do I have to do scales and arpeggios?',
      answer:
        'For most Classical &amp; Jazz instruments and grades you can choose between scales &amp; arpeggios OR exercises — they are not both required. Brass at Grades 1–5, for example, lets you pick one or the other. At higher grades the alternative may be orchestral or brass band extracts instead. Always check the <a href="/exam-guide/syllabuses?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">syllabus</a> for your specific instrument and grade. If you go with scales they must be played from memory — exercises (or extracts) can be read from sheet music.',
      keywords: ['scales', 'arpeggios', 'exercises', 'technical work', 'from memory'],
      snippet: 'For most Classical & Jazz instruments you can choose scales & arpeggios OR exercises — not both. Always check your syllabus.',
    },
    {
      question: 'What results can I achieve?',
      answer:
        'Trinity graded exams are marked out of 100. A Pass is 60–74, Merit is 75–86, and Distinction is 87–100. Every student entered through centre 120 — whether face-to-face, digital or theory — receives at least a <strong>Bravo Certificate</strong> and is listed on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a>. Merit earns a Take a Bow Certificate and Distinction earns a Standing Ovation Certificate — plus a place in the <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Hall of Fame</a>. The highest scorers each quarter earn a Showstopper or Centre Stage Certificate and a gift token.',
      keywords: ['results', 'marks', 'pass', 'merit', 'distinction', 'grades', 'score'],
      snippet: 'Marked out of 100 — Pass 60–74, Merit 75–86, Distinction 87–100. Every candidate gets at least a Bravo Certificate and Recognition listing.',
    },
    {
      question: 'Do I need to provide sheet music for the examiner?',
      answer:
        'The examiner has all Trinity-published pieces on their laptop, so they don\'t need a copy of those — but you must still bring the original book to prove it was purchased (copyright rules). For any pieces not published by Trinity, you\'ll need to provide a copy for the examiner to follow — a photocopy or a tablet in aeroplane mode is fine. Your teacher can help make sure everything is in order.',
      keywords: ['sheet music', 'examiner', 'copy', 'original book', 'copyright', 'photocopy'],
      snippet: 'Trinity-published pieces are on the examiner’s laptop, but bring the original book for copyright. For non-Trinity pieces, provide a copy.',
    },
    {
      question: 'How do teachers benefit from using centre 120?',
      answer:
        'Teachers who enter candidates through centre 120 earn recognition through our tiered badge system (10+, 20+, 30+ candidates), receive Certificates of Appreciation, and get an entry into the quarterly teacher prize draw (£50 gift token) for every exam booked — the more students entered, the more chances to win. They also get the added benefit of having all the guidance and exam information in one place on musicExams.help — saving time explaining procedures to parents and students.',
      keywords: ['teacher benefits', 'badges', 'appreciation', 'prize draw', 'teachers'],
      snippet: 'Recognition badges (10+, 20+, 30+ candidates), Certificates of Appreciation and entry into the quarterly £50 teacher prize draw.',
    },
    {
      question: 'What is the Hall of Fame?',
      answer:
        'Every student entered through centre 120 receives at least a <strong>Bravo Certificate</strong> and is listed on our <a href="/recognition?from=faq" class="font-semibold text-brand-accent underline hover:opacity-70">Recognition page</a> — everyone is recognised, not just the highest scorers. Merit earns a Take a Bow Certificate and Distinction earns a Standing Ovation Certificate — plus a place in the Hall of Fame. Each quarter we award the top scores in two grade groups — Initial–5 and 6–8 — so the highest Distinction in each group earns a Showstopper Certificate, and the highest Merit in each group earns a Centre Stage Certificate, plus a gift token (£20, or divided equally if there is a tie — minimum £5 each). Every entry also goes into a quarterly student prize draw, and teachers have their own separate prize draw too.',
      keywords: ['hall of fame', 'take a bow', 'standing ovation', 'showstopper', 'centre stage', 'gift token'],
      snippet: 'Merit earns a Take a Bow certificate and Distinction a Standing Ovation, plus a Hall of Fame place. Top quarterly scores earn a gift token.',
    },
    {
      question: 'Can I book face-to-face exams through centre 120?',
      answer:
        'Yes. We offer face-to-face exam sessions in Liverpool and Wirral, as well as digital practical exams and digital theory exams that can be taken anywhere and submitted online. Centre 120 covers all three — for digital exams you enter the code when booking, and for face-to-face exams your entry is connected automatically.',
      keywords: ['face to face', 'in person', 'liverpool', 'wirral', 'venue', 'practical exam'],
      snippet: 'Yes — face-to-face sessions in Liverpool and Wirral, plus digital practical and theory exams taken anywhere. Centre 120 covers all three.',
    },
    {
      question: 'Can I order a paper certificate, and how much?',
      answer:
        'Every Trinity exam comes with a free digital certificate that you can download and share online. A printed paper certificate is £5 (UK delivery), ordered directly from Trinity at <a href="https://mycertificates.trinitycollege.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">mycertificates.trinitycollege.com</a> using the “Paper certificates” option. <strong>Only the parent can order it</strong> — it uses the parent’s own email address (the one used on the entry the applicant made), because that is where Trinity sends the certificate. Teachers and centres cannot order one on a candidate’s behalf, so the parent has to do it themselves. The same page can also order a replacement if a certificate is ever lost or damaged.',
      keywords: ['paper certificate', 'printed certificate', 'physical certificate', 'certificate cost', 'order certificate', 'order a certificate', 'lost certificate', 'damaged certificate', 'replacement certificate', 'reprint certificate', 'get a certificate', 'parent order certificate', 'teacher order certificate', '£5'],
      snippet: 'Every exam includes a free digital certificate. A printed paper certificate is £5 (UK delivery) and can only be ordered by the parent — using the email they gave on the entry — from Trinity at mycertificates.trinitycollege.com.',
    },
    {
      question: 'My child was ill and missed their exam — what happens?',
      answer:
        'Let me know as soon as you can. Trinity can issue a <strong>re-entry permit</strong>, which lets your child sit the same exam at the same level again. I will need a medical certificate covering the date of the exam, and the application has to reach Trinity <strong>within 30 days of the exam date</strong>. Send it to me rather than to Trinity — I deal with them on your behalf. The permit is valid for 12 months from the date it is issued, and a re-entry costs 50% of the entry fee current at that time. If a candidate simply wants to postpone rather than having been ill, the original fee cannot be refunded — the permit route applies to illness and other genuine exceptional circumstances.',
      keywords: ['ill', 'illness', 'unwell', 'sick', 'poorly', 'missed exam', 'missed the exam', 'not well', 'cannot attend', 'could not attend', 'did not attend', 'absent', 'absence', 're-entry', 're entry', 'reentry', 'permit', 'voucher', 'medical certificate', 'doctors note', 'doctor note', 'sick note', 'cancel', 'cancelled', 'cancellation', 'postpone', 'reschedule', 'refund', 'emergency', 'exceptional circumstances'],
      snippet: 'Trinity can issue a re-entry permit to sit the same exam again. Tell us as soon as you can — a medical certificate covering the exam date has to reach Trinity within 30 days.',
    },
    {
      question: 'Which guitar exam should my pupil take?',
      answer:
        `Trinity has three guitar exams: Rock &amp; Pop Guitar, Acoustic Guitar and Classical Guitar. ${GUITAR_EXAMS_SUMMARY} For Rock &amp; Pop: ${ROCK_POP_ACOUSTIC_RULE} See <a href="/exam-guide/syllabuses?from=faq#guitar" class="font-semibold text-brand-accent underline hover:opacity-70">which guitar exam</a> for what each one covers and its syllabus.`,
    },
  ],
}

export default page
