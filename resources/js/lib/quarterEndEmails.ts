// resources/js/lib/quarterEndEmails.ts

// The results emails Quarter End copies to the clipboard: one for a teacher
// (or music school), one for a parent who booked directly, one for a candidate
// who booked their own exam. Plain text, pasted into Gmail by Paul.
//
// `late` adds an apology and marks the subject, for a quarter that goes out
// after it should have (Q2 2026 went out in late September).

export interface EmailStudent {
    name: string
    instrument: string
    grade: string
    score: number
    result: string
    certificate: string
}

export interface EmailRecipient {
    teacher_name: string
    applicant_name: string | null
    is_school: boolean
    is_parent_booking: boolean
    booking_role: string | null
    total_entries: number
    pending: number
    badge_tier: string | null
    students: EmailStudent[]
}

export interface EmailAwardWinner {
    name: string
    instrument: string
    grade: string
    score: number
}

export interface EmailTopScorers {
    initial_5?: { distinction: EmailAwardWinner[]; merit: EmailAwardWinner[] }
    '6_8'?: { distinction: EmailAwardWinner[]; merit: EmailAwardWinner[] }
}

export interface EmailStudentDrawWinner {
    name: string
    instrument: string
    grade: string
    teacher: string
}

export interface ResultsEmailOptions {
    quarterLabel: string
    late: boolean
    topScorers?: EmailTopScorers
    studentWinner: EmailStudentDrawWinner | null
    teacherDrawRun: boolean
}

/**
 * "Mrs Fakerson" for a titled name, otherwise the first name, "there" when
 * there is no name at all.
 */
export function recipientGreetingName(name: string | null | undefined): string {
    if (!name) return 'there'
    const parts = name.trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return 'there'

    const titles = ['mr', 'mrs', 'ms', 'miss', 'mx', 'dr', 'rev', 'sir', 'dame', 'prof']
    if (titles.includes(parts[0].toLowerCase().replace(/\.$/, ''))) {
        return parts.length < 2 ? parts[0] : `${parts[0]} ${parts[parts.length - 1]}`
    }

    return parts[0]
}

/**
 * A music school row is named after the school ("Pulse Music School"), so
 * greet the person who booked instead of saying "Hi Pulse".
 */
function greeting(recipient: EmailRecipient): string {
    const name = recipient.is_school && recipient.applicant_name ? recipient.applicant_name : recipient.teacher_name
    return recipientGreetingName(name)
}

/** "Anna M": first name and surname initial, for anyone outside the family. */
function shortName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/)
    return parts.length > 1 ? `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}` : fullName
}

function andList(items: string[]): string {
    if (items.length <= 1) return items[0] ?? ''
    return `${items.slice(0, -1).join(', ')} and ${items[items.length - 1]}`
}

export type ResultsEmailKind = 'teacher' | 'parent' | 'self'

export function resultsEmailKind(recipient: EmailRecipient): ResultsEmailKind {
    if (recipient.booking_role === 'self') return 'self'
    if (recipient.is_parent_booking) return 'parent'
    return 'teacher'
}

export function resultsEmailSubject(recipient: EmailRecipient, options: ResultsEmailOptions): string {
    const kind = resultsEmailKind(recipient)
    const late = options.late ? " (sorry it's late!)" : ''

    if (kind === 'self') {
        return `Your musicExams.help Certificate — Trinity ${options.quarterLabel}${late}`
    }
    if (kind === 'parent') {
        const plural = recipient.students.length > 1 ? 's' : ''
        const names = recipient.students.map(s => s.name.split(' ')[0]).join(' & ')
        return `musicExams.help Certificate${plural} — ${names}${late}`
    }
    return `${options.quarterLabel} Exam Results — Your Students Did Brilliantly!${late}`
}

export function resultsEmailBody(recipient: EmailRecipient, options: ResultsEmailOptions): string {
    const kind = resultsEmailKind(recipient)
    if (kind === 'self') return selfBody(recipient, options)
    if (kind === 'parent') return parentBody(recipient, options)
    return teacherBody(recipient, options)
}

function teacherBody(recipient: EmailRecipient, options: ResultsEmailOptions): string {
    const studentList = recipient.students
        .map(s => `  • ${s.name} — ${s.instrument} Grade ${s.grade} — ${s.score} (${s.result}) — ${s.certificate}`)
        .join('\n')

    const pendingText = recipient.pending > 0
        ? `\n\nNote: ${recipient.pending} of your students are still awaiting results — I'll be in touch as soon as they come through.\n`
        : ''

    const badgeText = recipient.badge_tier
        ? `\n\nI'm also pleased to award you a ${recipient.badge_tier} Certificate of Appreciation for entering ${recipient.total_entries} candidates through centre 120 this quarter. Thank you for your continued support!\n`
        : ''

    const winnersText = (winners: EmailAwardWinner[]) =>
        winners.map(w => `${w.name} — ${w.instrument} Grade ${w.grade} — ${w.score} marks`).join(' & ')
    const top = options.topScorers
    const awardLines = ([
        ['Highest Distinction (Initial–5)', top?.initial_5?.distinction ?? []],
        ['Highest Merit (Initial–5)', top?.initial_5?.merit ?? []],
        ['Highest Distinction (Grades 6–8)', top?.['6_8']?.distinction ?? []],
        ['Highest Merit (Grades 6–8)', top?.['6_8']?.merit ?? []],
    ] as const)
        .filter(([, winners]) => winners.length > 0)
        .map(([label, winners]) => `${label}: ${winnersText([...winners])}`)

    const topScorerText = awardLines.length
        ? `\n\nQuarterly award winners:\n  • ${awardLines.join('\n  • ')}\nWinners receive a gift token (split equally if tied) and a personalised certificate.\n`
        : ''

    const winner = options.studentWinner
    const studentDrawText = winner
        ? `\n\nStudent Prize Draw\nThe winner of the £50 gift token this quarter is ${shortName(winner.name)} (${winner.instrument} Grade ${winner.grade}) — congratulations! Every student entered through centre 120 was in the draw.${winner.teacher === recipient.teacher_name ? " As their teacher, I'll be in touch with you separately about getting the prize to them." : ''}\n`
        : ''

    const teacherDrawText = options.teacherDrawRun
        ? "Teacher draw: this quarter's draw has been run. Winners are notified privately rather than announced publicly — log in to your dashboard at https://musicexams.help/dashboard to check if you won. The more students you enter through centre 120 next quarter, the more tickets you'll have."
        : "Teacher draw: taking place in the coming weeks. The prize is a £50 gift token to help buy musical instruments for your school. The more students linked to you, the more tickets you have — that's why the question at the top matters!\n\nThe teacher draw result won't be published on the website — no competition between teachers. Winners can see their result privately by logging in, and you're welcome to promote it on your own channels if you win!"

    const topScorerTiming = awardLines.length
        ? ''
        : '\n\nTop Scorer awards and gift tokens are announced around 6 weeks after the quarter ends, once all results (including digital) are in. Keep an eye on musicExams.help!'

    const nudge = options.teacherDrawRun
        ? "Quick favour while I have you: do you have any students who booked their exam through centre 120 in 2026 but booked directly or through a parent? If so, reply with their names and I'll link them to you — it'll feed into your badge tally going forward and add extra tickets to the next teacher prize draw."
        : 'Before I get to the good stuff: do you have any students who booked their exam through centre 120 in 2026 but booked directly or through a parent? If so, reply with their names so I can link them to you — it counts towards your Teacher Appreciation badge and extra tickets in the teacher prize draw!'

    const lateText = options.late
        ? `I'm sorry this is late. Your students' results and certificates for the ${options.quarterLabel} should have reached you weeks ago, and that's down to me. Everything is below and in the attached ZIP.\n\n`
        : ''

    return `Hi ${greeting(recipient)},

${lateText}${nudge}

---

Your Students' Results

Your students have done brilliantly! Here are the results:

${studentList}${pendingText}

Your students' personalised certificates are in the attached ZIP file — just double-click to open it.

Every student receives at least a Bravo Certificate, with Merit earning a Take a Bow Certificate and Distinction earning a Standing Ovation Certificate.

You can see all of your candidates' exam details from January 2026 onwards by logging in at https://musicexams.help/dashboard — including any still waiting on a result. Pick any date range you like and download it as a spreadsheet or a PDF whenever you need it.${badgeText}${topScorerText}

---

Prize Draws

Every quarter we run two prize draws — one for students, one for teachers. Every student entry through centre 120 earns one ticket.
${studentDrawText}
${teacherDrawText}${topScorerTiming}

---

What's on musicExams.help

musicExams.help is a free resource for teachers, parents and students booking Trinity exams through centre 120. If parents ever ask things like "what's the difference between digital and face-to-face?" — point them straight to the site.

  • Your own teacher dashboard: all your students' bookings, results, certificates and awards in one place (free, sign up at https://musicexams.help/register)
  • Student recognition — Hall of Fame, certificates and quarterly prize draws
  • Teacher awards — Bronze, Silver, Gold and Top Award badges
  • Faber music book discounts for teachers
  • Booking made easy across all 3 Trinity systems

Have a look: https://musicexams.help — any feedback, even a quick "looks good", helps!

---

Thank you for everything you do for your students — and for choosing to enter them through centre 120. It really is appreciated.

Best wishes,
Paul

P.S. Here's a message you can send to parents with their child's certificate:

"Hi [Parent Name], here is [Child]'s personalised certificate from musicExams.help, which supports teachers, parents and students taking Trinity exams — please find it attached.${options.late ? " Sorry it's a little late!" : ''} They also appear on the Recognition page at https://musicexams.help/recognition (first name and surname initial only). So we can credit the right person, please reply with your child's music teacher's name — and if their lessons are through a music school, let us know which one. If you'd like their full name displayed, just email musicexams@musicexams.help."`
}

function parentBody(recipient: EmailRecipient, options: ResultsEmailOptions): string {
    const count = recipient.students.length
    const names = andList(recipient.students.map(s => s.name.split(' ')[0]))
    const certWord = count === 1 ? 'certificate is attached below' : 'certificates are attached below'
    const examWord = count === 1 ? 'Trinity exam' : 'Trinity exams'
    const lateText = options.late
        ? `\n\nI'm sorry this is late — it should have reached you weeks ago, and that's down to me.`
        : ''

    return `Hi ${greeting(recipient)},

I'm Paul Sheridan, running Trinity exam centre 120. Thank you for entering ${names} for their recent ${examWord} through centre 120. Their personalised musicExams.help ${certWord}.${lateText}

This is our own centre 120 recognition — separate from any certificate Trinity themselves issue (Trinity send a digital certificate directly to candidates who pass).

How our certificates work: every candidate entered through centre 120 earns at least a Bravo certificate as a thank-you for taking part. Candidates who achieve a Merit earn a Take a Bow certificate, and those who achieve a Distinction earn a Standing Ovation certificate.

${names} will also appear on the Recognition page at https://musicexams.help/recognition — first name and surname initial only, for GDPR. If you'd like the full name shown, just reply and say the word.

musicExams.help is a free resource for anyone booking Trinity exams. It covers the difference between digital and face-to-face, grades explained, UCAS points and more. Have a look when you get a minute: https://musicexams.help

If ${names} ${count === 1 ? 'has' : 'have'} a music teacher, do let them know about our site too — teachers earn their own appreciation badges for supporting candidates through centre 120.

Every entry through centre 120 also gets one ticket in our quarterly prize draw — the £50 gift token winner is announced on the Recognition page. Good luck in future draws!

Thanks for choosing centre 120.

Best wishes,
Paul Sheridan`
}

function selfBody(recipient: EmailRecipient, options: ResultsEmailOptions): string {
    const count = recipient.students.length
    const certWord = count === 1 ? 'certificate is attached below' : 'certificates are attached below'
    const examWord = count === 1 ? 'Trinity exam' : 'Trinity exams'
    const lateText = options.late
        ? `\n\nI'm sorry this is late — it should have reached you weeks ago, and that's down to me.`
        : ''

    return `Hi ${greeting(recipient)},

I'm Paul Sheridan, running Trinity exam centre 120. Thank you for entering your recent ${examWord} through centre 120. Your personalised musicExams.help ${certWord}.${lateText}

This is our own centre 120 recognition — separate from any certificate Trinity themselves issue (Trinity send a digital certificate directly to candidates who pass).

How our certificates work: every candidate entered through centre 120 earns at least a Bravo certificate as a thank-you for taking part. A Merit earns a Take a Bow certificate, and a Distinction earns a Standing Ovation certificate.

Your name will also appear on the Recognition page at https://musicexams.help/recognition — first name and surname initial only, for GDPR. If you'd like your full name shown, just reply and say the word.

musicExams.help is a free resource for anyone booking Trinity exams. It covers the difference between digital and face-to-face, grades explained, UCAS points and more. Have a look when you get a minute: https://musicexams.help

If you have a music teacher, do let them know about our site too — teachers earn their own appreciation badges for supporting candidates through centre 120.

Every entry through centre 120 also gets one ticket in our quarterly prize draw — the £50 gift token winner is announced on the Recognition page. Good luck in future draws!

Thanks for choosing centre 120.

Best wishes,
Paul Sheridan`
}
