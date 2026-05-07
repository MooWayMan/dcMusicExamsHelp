<!-- resources/js/pages/admin/QuarterEnd/Index.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Award, CheckCircle2, Circle, Download, Package,
  Trophy, Users, Clock, Star, ChevronDown, ChevronUp, Copy,
  Gift, Sparkles, Loader2, ExternalLink
} from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'

interface Student {
  name: string
  instrument: string
  grade: string
  score: number
  result: string
  certificate: string
  method: string
}

interface Teacher {
  teacher_name: string
  applicant_email: string | null
  applicant_name: string | null
  is_parent_booking: boolean
  booking_role: 'parent' | 'self' | null
  total_entries: number
  with_results: number
  pending: number
  distinctions: number
  merits: number
  passes: number
  badge_tier: string | null
  total_all_time: number
  students: Student[]
}

interface AwardWinner {
  name: string
  full_name: string
  score: number
  instrument: string
  grade: string
  // Teacher routing — drives the "Copy Top Scorer Email" button. Email
  // can be null if neither the order applicant nor any linked contact
  // has a stored email (rare, but the UI handles it gracefully).
  teacher_name: string | null
  teacher_email: string | null
  is_parent_booking: boolean
  booking_role: 'parent' | 'self' | null
}

interface GroupAwards {
  distinction: AwardWinner[]
  merit: AwardWinner[]
}

interface Summary {
  total_entries: number
  with_results: number
  pending: number
  total_fees: string
  teacher_count: number
  has_pending: boolean
  // Legacy single-winner fields — overall top across both groups.
  // Kept for the summary stat card; per-group winners live in top_scorers.
  showstopper: { name: string; full_name: string; score: number; instrument: string; winners?: { name: string; full_name: string; instrument: string; grade: string }[] } | null
  centre_stage?: { name: string; full_name: string; score: number; instrument: string; winners?: { name: string; full_name: string; instrument: string; grade: string }[] } | null
  centre_stage: { name: string; full_name: string; score: number; instrument: string } | null
  // Awards split by grade group (matches the public Awards banner):
  //   • Initial–5  (Initial, Grades 1–5)
  //   • 6–8        (Grades 6–8)
  // Each leaf is an array of tied winners (empty when no winner in that band).
  top_scorers: {
    initial_5: GroupAwards
    '6_8': GroupAwards
  }
  // True when Paul has overridden the pending-gate via ?finalise=1.
  // Drives a "Preview only — N pending" warning banner in Step 3.
  finalised_with_pending: boolean
  // Snapshot of an existing publication for this quarter, if any. Once
  // published, the Publish button switches to "Already published" and
  // the per-winner email buttons remain available for re-sending.
  publication: {
    published_at: string
    finalised_with_pending: boolean
    pending_count: number
  } | null
}

interface EligibleTeacher {
  name: string
  entries: number
  is_registered: boolean
  eligible: boolean
  reason: string
}

interface PrizeDrawData {
  student_tickets: Array<{ name: string; instrument: string; grade: string; teacher: string }>
  teacher_tickets: Array<{ name: string; entries: number; is_registered: boolean }>
  eligible_teachers: EligibleTeacher[]
  student_ticket_count: number
  teacher_ticket_count: number
}

interface ExistingDraw {
  winner_name: string
  winner_instrument?: string
  winner_grade?: string
  winner_teacher?: string
  winner_entries?: number
  total_tickets: number
  created_at: string
}

const props = defineProps<{
  quarter: number
  year: number
  quarterLabel: string
  teachers: Teacher[]
  emailsSent: string[]
  summary: Summary
  prizeDraw: PrizeDrawData
  existingDraws: {
    student: ExistingDraw | null
    teacher: ExistingDraw | null
  }
}>()

const page = usePage()
// Prefer flash (just-generated) but fall back to persistedBatchResult (from
// disk scan in controller) so download links stay visible across navigation
// and after Inertia's flash is consumed.
const batchResult = computed(() =>
  (page.props as any).flash?.batch_result
    ?? (page.props as any).persistedBatchResult
    ?? null
)

// Per-group award winners — pulled from the new top_scorers payload, with
// safe fallbacks so the page still renders if the controller returns the
// legacy summary shape (e.g. an old cached response).
const initial5 = computed<GroupAwards>(() => props.summary.top_scorers?.initial_5 ?? { distinction: [], merit: [] })
const grades68 = computed<GroupAwards>(() => props.summary.top_scorers?.['6_8'] ?? { distinction: [], merit: [] })
const hasAnyAward = computed(() =>
  initial5.value.distinction.length > 0
  || initial5.value.merit.length > 0
  || grades68.value.distinction.length > 0
  || grades68.value.merit.length > 0
)

// Production data stores grades as either "Grade 1" or bare "1" or "Initial"
// — normalise to a single human-readable form. "Initial" never gets a
// "Grade" prefix per Trinity convention.
const formatGrade = (g: unknown): string => {
  if (g === null || g === undefined || g === '') return ''
  const trimmed = String(g).trim()
  const normalised = trimmed.replace(/^grade\s+/i, '')
  if (normalised === 'Initial') return 'Initial'
  return `Grade ${normalised}`
}

// "Preview leaders so far" — reloads with ?finalise=1 (param name kept for
// backend compatibility, but it's a preview, not a commitment). Backend
// recalculates awards from whatever scores ARE in. Nothing is published,
// no certificates are generated, no emails are sent. Refreshing the page
// without the param re-hides the awards.
function finaliseNow() {
  router.get('/admin/quarter-end', {
    quarter: props.quarter,
    year: props.year,
    finalise: 1,
    step: 3, // bring user back to Step 3 (Prize Draws & Top Scorers)
  }, { preserveState: true, preserveScroll: true })
}

const publishing = ref(false)
const generatingCerts = ref(false)
// After "Generate top-scorer certs" runs, we hold the manifest in memory so
// the per-winner Download Cert links work without a page reload. Keyed by
// candidate full_name → standalone_path so we can match against the winner
// rows in the four award panels.
const topScorerCertPaths = ref<Record<string, string>>({})

// "Generate top-scorer certs" — produces ONLY the 4 (+ ties) Showstopper /
// Centre Stage PDFs in storage/app/certificates/{year}-Q{q}/top-scorers/.
// Doesn't touch the per-student certs or ZIPs, so it's much faster than
// re-running the full batch.
async function generateTopScorerCerts() {
  generatingCerts.value = true
  try {
    const res = await fetch('/admin/certificates/top-scorers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getXsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ quarter: props.quarter, year: props.year }),
    })

    if (! res.ok) {
      const data = await res.json().catch(() => ({}))
      throw new Error(data.error ?? `HTTP ${res.status}`)
    }
    const data = await res.json()

    // Index by full name so the Vue lookup-by-winner works.
    const map: Record<string, string> = {}
    for (const c of data.certs ?? []) {
      map[c.name] = c.download_url
    }
    topScorerCertPaths.value = map
    alert(`Generated ${data.count} top-scorer certificate${data.count === 1 ? '' : 's'}. Use the "Download Cert" button next to each winner to grab the PDF.`)
  } catch (e: any) {
    alert(`Cert generation failed: ${e.message ?? 'unknown error'}`)
    console.error(e)
  } finally {
    generatingCerts.value = false
  }
}

// "Publish top-scorer awards" — POSTs to the snapshot endpoint. The public
// Recognition page reads from the snapshot, so this is the moment the four
// winners go live. Idempotent: re-clicking refreshes the snapshot.
async function publishTopScorers() {
  const alreadyPublished = !!props.summary.publication
  const confirmMessage = alreadyPublished
    ? `This quarter's top scorers were already published on ${new Date(props.summary.publication!.published_at).toLocaleDateString('en-GB')}.\n\nRe-publishing will overwrite the snapshot with the current leaders. Continue?`
    : `Publish top-scorer awards for ${props.quarterLabel} now?\n\n• The four winners will appear on the public Recognition page immediately.\n• ${props.summary.pending} result${props.summary.pending === 1 ? '' : 's'} still pending — those won't change the published list. If a late score later beats a leader, top up the gift token manually.\n\nContinue?`

  if (! window.confirm(confirmMessage)) return

  publishing.value = true
  try {
    const res = await fetch('/admin/quarter-end/publish-top-scorers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getXsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ quarter: props.quarter, year: props.year }),
    })

    if (! res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    alert(`Published! ${data.winner_count} winner${data.winner_count === 1 ? '' : 's'} are now live on the public Recognition page.`)
    // Reload so the page reflects the new publication state. step=3 keeps
    // us on the Prize Draws / Top Scorers tab.
    router.get('/admin/quarter-end', {
      quarter: props.quarter,
      year: props.year,
      finalise: 1,
      step: 3,
    }, { preserveState: true, preserveScroll: true })
  } catch (e) {
    alert('Publish failed. Check the network tab and try again, or message me with the error.')
    console.error(e)
  } finally {
    publishing.value = false
  }
}

// ── Top-scorer award helpers ──────────────────────────────────────────────
//
// Gift token split. Paul's rule (matches public Recognition page wording):
//   1 winner  → £20
//   2 winners → £10 each
//   3+        → £5 each (minimum £5)
function tokenSplit(n: number): number {
  if (n <= 0) return 0
  if (n === 1) return 20
  if (n === 2) return 10
  return 5
}

type AwardKey = 'initial_5_distinction' | 'initial_5_merit' | '6_8_distinction' | '6_8_merit'

interface AwardMeta {
  certificate: 'Showstopper' | 'Centre Stage'
  groupLabel: 'Initial–5' | 'Grades 6–8'
  bandLabel: 'Highest Distinction' | 'Highest Merit'
}

const AWARD_META: Record<AwardKey, AwardMeta> = {
  initial_5_distinction: { certificate: 'Showstopper',  groupLabel: 'Initial–5',   bandLabel: 'Highest Distinction' },
  initial_5_merit:       { certificate: 'Centre Stage', groupLabel: 'Initial–5',   bandLabel: 'Highest Merit'       },
  '6_8_distinction':     { certificate: 'Showstopper',  groupLabel: 'Grades 6–8',  bandLabel: 'Highest Distinction' },
  '6_8_merit':           { certificate: 'Centre Stage', groupLabel: 'Grades 6–8',  bandLabel: 'Highest Merit'       },
}

// Greeting name resolver. Two cases Paul wants distinct:
//   • "Mrs Fakerson" → "Mrs Fakerson"  (keep title + surname)
//   • "Sarah Mitchell" → "Sarah"        (drop surname, just first name)
// Detects titles case-insensitively and tolerates trailing dots
// ("Mr." / "Dr.") which appear on some imported teacher names.
function recipientGreetingName(teacherName: string | null | undefined): string {
  if (! teacherName) return 'there'
  const parts = teacherName.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return 'there'

  const titles = ['mr', 'mrs', 'ms', 'miss', 'mx', 'dr', 'rev', 'sir', 'dame', 'prof']
  const firstNorm = parts[0].toLowerCase().replace(/\.$/, '')

  if (titles.includes(firstNorm)) {
    // Title-prefixed name → "Mrs Fakerson" (title + surname). If there's
    // only the title and nothing else, fall through to using it on its
    // own (rare edge case — better than greeting "there").
    if (parts.length < 2) return parts[0]
    return `${parts[0]} ${parts[parts.length - 1]}`
  }

  // No title — first name only.
  return parts[0]
}

function copyTopScorerEmail(winner: AwardWinner, awardKey: AwardKey, tieCount: number) {
  const meta = AWARD_META[awardKey]
  const split = tokenSplit(tieCount)
  const recipientFirstName = recipientGreetingName(winner.teacher_name)
  // Use the candidate's real first name in the email body. The GDPR
  // initial-only rule applies to public pages and emails to people outside
  // the family — not to a private email *to* the parent or teacher about
  // their own child / student. Falls back to the short name only if for
  // some reason full_name isn't on the payload.
  const fullName = (winner.full_name ?? winner.name).trim()
  const winnerName = fullName.split(/\s+/)[0] || winner.name

  // Tie sentence — three cases:
  //   • Sole winner       → full £20.
  //   • 2-way tie         → £20 split equally (£10 each).
  //   • 3+ way tie        → each winner gets £5 (the minimum-£5 rule kicks
  //                         in, so it's NOT a clean split of £20 — Paul
  //                         pays £15 for a 3-way, £20 for a 4-way, more
  //                         than £20 for 5+).
  let tieSentence: string
  if (tieCount === 1) {
    tieSentence = `As the sole top scorer in this category, ${winnerName} receives the full £${split} gift token.`
  } else if (tieCount === 2) {
    tieSentence = `It was a 2-way tie at the top score, so the £20 gift token is split equally — ${winnerName}'s share is £${split}.`
  } else {
    tieSentence = `It was a ${tieCount}-way tie at the top score, so each winner receives a £${split} gift token — ${winnerName}'s share is £${split}.`
  }

  // Two voices — teacher (third-person about the candidate) vs parent
  // (second-person about your child / the candidate). Self-applicants get
  // a 'you-won-it' tone.
  let body: string
  if (winner.booking_role === 'self') {
    body = `Hi ${recipientFirstName},

Wonderful news — you've been awarded the **${meta.bandLabel} (${meta.groupLabel})** for ${props.quarterLabel}!

You scored ${winner.score} marks in ${winner.instrument} Grade ${winner.grade} — a brilliant achievement.

${tieSentence.replace(`${winnerName}'s share`, 'your share').replace(`${winnerName} receives`, 'you receive')}

Your personalised ${meta.certificate} Certificate is attached to this email — print it, display it on a tablet for photos, or share it on social media.

Here's the Amazon gift card code:

[PASTE GIFT CARD CODE HERE]

You can add this to any Amazon account — it's not tied to a name or email.

You'll also appear on the Recognition page at https://musicexams.help/recognition.

Huge congratulations — and thank you for choosing centre 120.

Best wishes,
Paul Sheridan`
  } else if (winner.is_parent_booking) {
    body = `Hi ${recipientFirstName},

Wonderful news — ${winnerName} has been awarded the **${meta.bandLabel} (${meta.groupLabel})** for ${props.quarterLabel}!

They scored ${winner.score} marks in ${winner.instrument} Grade ${winner.grade} — a brilliant achievement.

${tieSentence}

${winnerName}'s personalised ${meta.certificate} Certificate is attached to this email — print it, display it on a tablet for photos, or share it on social media.

Here's the Amazon gift card code:

[PASTE GIFT CARD CODE HERE]

You can add this to any Amazon account — it's not tied to a name or email.

${winnerName} will also appear on the Recognition page at https://musicexams.help/recognition.

Huge congratulations to ${winnerName} — and thank you for choosing centre 120.

Best wishes,
Paul Sheridan`
  } else {
    body = `Hi ${recipientFirstName},

Wonderful news — one of your students, ${winnerName}, has been awarded the **${meta.bandLabel} (${meta.groupLabel})** for ${props.quarterLabel}!

They scored ${winner.score} marks in ${winner.instrument} Grade ${winner.grade} — a brilliant achievement.

${tieSentence}

${winnerName}'s personalised ${meta.certificate} Certificate is attached to this email — please pass it on to them along with the gift card code below.

Here's the Amazon gift card code for you to pass on to ${winnerName}'s parent/guardian:

[PASTE GIFT CARD CODE HERE]

It can be added to any Amazon account — it's not tied to a name or email.

${winnerName} will also appear on the Recognition page at https://musicexams.help/recognition.

Congratulations to them — and well done to you for entering them through centre 120!

Quick tip — if you'd like to see all your students' results, certificates and awards in one place (and get notified the moment new scores come in), you can create a free teacher account at https://musicexams.help/register.

Best wishes,
Paul

---

P.S. Here's a suggested message you can copy and paste when you forward this on to ${winnerName}'s parent/guardian — feel free to tweak or skip:

"Hi [Parent Name], wonderful news — musicExams.help (centre 120) have just awarded ${winnerName} the ${meta.bandLabel} (${meta.groupLabel}) for ${props.quarterLabel} for their brilliant ${winner.score}-mark performance in ${winner.instrument} Grade ${winner.grade}. Their personalised ${meta.certificate} Certificate is attached, along with an Amazon gift card to celebrate. They'll also appear on the Recognition page at https://musicexams.help/recognition (first name and surname initial only — let me know if you'd like the full name shown). Huge congratulations to ${winnerName}! — [Your Name]"`
  }

  navigator.clipboard.writeText(body)
  const recipientNote = winner.teacher_email
    ? `Now click "Open in Gmail" to compose to ${winner.teacher_email}, attach ${winner.full_name}'s ${meta.certificate} Certificate PDF, and send.`
    : 'No email is on file for this recipient — you\'ll need to look it up. The template is on your clipboard.'
  alert(`Top-scorer email copied to clipboard.\n\n${recipientNote}`)
}

function openGmailComposeForWinner(winner: AwardWinner, awardKey: AwardKey) {
  const meta = AWARD_META[awardKey]
  const subject = encodeURIComponent(`Top Scorer Award — ${meta.certificate} Certificate — ${winner.name}`)
  const to = encodeURIComponent(winner.teacher_email ?? '')
  window.open(`https://mail.google.com/mail/?view=cm&to=${to}&su=${subject}`, '_blank')
}

// Track which teachers have been "done" — initialise from database
const completedTeachers = ref<Record<string, boolean>>(
  Object.fromEntries((props.emailsSent || []).map(name => [name, true]))
)
const expandedTeacher = ref<string | null>(null)

function toggleTeacher(name: string) {
  expandedTeacher.value = expandedTeacher.value === name ? null : name
}

function getXsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function markDone(name: string) {
  const newState = !completedTeachers.value[name]
  completedTeachers.value[name] = newState

  // Persist to database
  try {
    await fetch('/admin/quarter-end/mark-sent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getXsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        teacher_name: name,
        quarter: props.quarter,
        year: props.year,
        sent: newState,
      }),
    })
  } catch (e) {
    // Revert on failure
    completedTeachers.value[name] = !newState
  }
}

const completedCount = computed(() => Object.values(completedTeachers.value).filter(Boolean).length)

// Student certs still to send = scored entries belonging to teachers we
// haven't yet ticked "Done!" for. Reduces as Paul works through the list.
const remainingCertsToSend = computed(() =>
  (props.teachers ?? [])
    .filter(t => !completedTeachers.value[t.teacher_name])
    .reduce((sum, t) => sum + (t.with_results ?? 0), 0)
)

// Step tracking — only auto-advance to step 2 when the batch has JUST run
// (flash data exists). Loading the page on a later visit should start on
// step 1 so download links are visible, even though files exist on disk.
// Preserve the step across Inertia navigations triggered by Preview /
// Publish (which both do `router.get(...)` and would otherwise drop us
// back on Step 1). We read `?step=N` from the URL on mount, and our
// reload calls pass `step: 3` so the user lands back where they were.
const currentStep = ref<number>((() => {
  const params = new URLSearchParams(window.location.search)
  const stepParam = parseInt(params.get('step') ?? '', 10)
  if (stepParam >= 1 && stepParam <= 3) return stepParam
  return (page.props as any).flash?.batch_result ? 2 : 1
})())

// Batch generate
const batchGenerating = ref(false)
function batchGenerate() {
  batchGenerating.value = true
  router.post('/admin/certificates/batch', {
    quarter: props.quarter,
    year: props.year,
  }, {
    preserveScroll: true,
    onFinish: () => {
      batchGenerating.value = false
      currentStep.value = 2
    },
  })
}

// Copy email template to clipboard
function copyEmailTemplate(teacher: Teacher) {
  // Self-applicants (candidate booked their own exam) get a you-centric template.
  if (teacher.booking_role === 'self') {
    copySelfApplicantTemplate(teacher)
    return
  }
  // Parent bookings get a direct-to-parent template (no "students" plural,
  // no teacher prize draw talk, warm intro to the site).
  if (teacher.is_parent_booking) {
    copyParentDirectTemplate(teacher)
    return
  }

  const studentList = teacher.students
    .map(s => `  • ${s.name} — ${s.instrument} Grade ${s.grade} — ${s.score} (${s.result}) — ${s.certificate}`)
    .join('\n')

  const badgeText = teacher.badge_tier
    ? `\n\nI'm also pleased to award you a ${teacher.badge_tier} Certificate of Appreciation for entering ${teacher.total_entries}+ candidates through centre 120 this quarter. Thank you for your continued support!\n`
    : ''

  // Top scorer mentions — four awards per quarter (matches public Awards
  // banner): Highest Distinction & Highest Merit in each of two groups
  // (Initial–5 and 6–8). Ties are listed together (gift token is split).
  const formatWinners = (winners: AwardWinner[]) =>
    winners
      .map(w => `${w.name} — ${w.instrument} Grade ${w.grade} — ${w.score} marks`)
      .join(' & ')

  const buildAwardLine = (label: string, winners: AwardWinner[]) =>
    winners.length ? `${label}: ${formatWinners(winners)}` : ''

  const top = props.summary.top_scorers
  const awardLines = [
    buildAwardLine('Highest Distinction (Initial–5)', top?.initial_5?.distinction ?? []),
    buildAwardLine('Highest Merit (Initial–5)',       top?.initial_5?.merit       ?? []),
    buildAwardLine('Highest Distinction (Grades 6–8)', top?.['6_8']?.distinction  ?? []),
    buildAwardLine('Highest Merit (Grades 6–8)',       top?.['6_8']?.merit        ?? []),
  ].filter(Boolean)

  const topScorerText = awardLines.length
    ? `\n\nQuarterly award winners:\n  • ${awardLines.join('\n  • ')}\nWinners receive a gift token (split equally if tied) and a personalised certificate.\n`
    : ''

  // Student prize draw winner — teacher needs to pass the gift token on
  // Use first name + initial only (GDPR — no full names without consent)
  const winnerShortName = studentWinner.value
    ? studentWinner.value.name.split(' ').length > 1
      ? `${studentWinner.value.name.split(' ')[0]} ${studentWinner.value.name.split(' ').slice(-1)[0][0].toUpperCase()}`
      : studentWinner.value.name
    : ''
  const studentDrawText = studentWinner.value
    ? `\n\nStudent Prize Draw\nThe winner of the £50 gift token this quarter is ${winnerShortName} (${studentWinner.value.instrument} Grade ${studentWinner.value.grade}) — congratulations! Every student entered through centre 120 was in the draw.${studentWinner.value.teacher === teacher.teacher_name ? ' As their teacher, I\'ll be in touch with you separately about getting the prize to them.' : ''}\n`
    : ''

  const firstName = recipientGreetingName(teacher.teacher_name)

  const template = `Hi ${firstName},

Quick heads-up — I've moved to a new email address: musicexams@musicexams.help. Please save this for future correspondence.

Before I get to the good stuff: do you have any students who booked their exam through centre 120 in 2026 but booked directly or through a parent? If so, reply with their names so I can link them to you — it counts towards your Teacher Appreciation badge and extra tickets in the teacher prize draw!

---

Your Students' Results

Your students have done brilliantly! Here are the results:

${studentList}${teacher.pending > 0 ? `\n\nNote: ${teacher.pending} of your students are still awaiting results — I'll be in touch as soon as they come through.\n` : ''}

Everything is in the attached ZIP file — just double-click to open it. Inside you'll find:
  • Personalised certificates for each student
  • A results report (PDF)
  • A spreadsheet (CSV)

Every student receives at least a Bravo Certificate, with Merit earning a Take a Bow Certificate and Distinction earning a Standing Ovation Certificate.${badgeText}${topScorerText}

---

Prize Draws

Every quarter we run two prize draws — one for students, one for teachers. Every student entry through centre 120 earns one ticket.
${studentDrawText}
Teacher draw: taking place in the coming weeks. The prize is a £50 gift token to help buy musical instruments for your school. The more students linked to you, the more tickets you have — that's why the question at the top matters!

The teacher draw result won't be published on the website — no competition between teachers. Winners can see their result privately by logging in, and you're welcome to promote it on your own channels if you win!

Top Scorer awards and gift tokens are announced around 6 weeks after the quarter ends, once all results (including digital) are in. Keep an eye on musicExams.help!

---

Introducing musicExams.help

I've recently launched musicExams.help — a free resource for teachers, parents and students booking Trinity exams through centre 120. If parents ever ask things like "what's the difference between digital and face-to-face?" — point them straight to the site.

Highlights:
  • NEW — your own teacher dashboard: track all your students' bookings, results, certificates and awards in one place (free, sign up at https://musicexams.help/register)
  • Student recognition — Hall of Fame, certificates and quarterly prize draws
  • Teacher awards — Bronze, Silver, Gold and Top Award badges
  • Faber music book discounts for teachers
  • Booking made easy across all 3 Trinity systems

Have a look: https://musicexams.help

We'd love any feedback — even a quick "looks good" helps!

---

Thank you for everything you do for your students — and for choosing to enter them through centre 120. It really is appreciated.

Best wishes,
Paul

P.S. Here's a message you can send to parents with their child's certificate:

"Hi [Parent Name], I've recently partnered with musicExams.help, a platform that supports teachers, parents and students taking Trinity exams. Your child now receives a personalised certificate — please find it attached. They also appear on the Recognition page at https://musicexams.help/recognition (first name and surname initial only). If you'd like their full name displayed, just email musicexams@musicexams.help."`

  navigator.clipboard.writeText(template)
  alert('Email template copied to clipboard! Now click "Open in Gmail" to compose.')
}

/**
 * Direct-to-parent email template — used when the row is a parent booking
 * (Gillian Leslie, Adrian O'Malley, Claire Reed, etc.) rather than a teacher.
 * No teacher-prize-draw talk, no Faber pitch, short and warm.
 */
function copyParentDirectTemplate(teacher: Teacher) {
  const firstName = recipientGreetingName(teacher.teacher_name)
  const count = teacher.students.length

  // Use candidates' first names in prose instead of assuming the applicant
  // is a parent ("your child") — works for guardians, grandparents, aunts,
  // or the candidate themselves.
  const candidateFirstNames = teacher.students.map(s => s.name.split(' ')[0])
  const namesSentence = count === 1
    ? candidateFirstNames[0]
    : count === 2
      ? `${candidateFirstNames[0]} and ${candidateFirstNames[1]}`
      : `${candidateFirstNames.slice(0, -1).join(', ')} and ${candidateFirstNames.slice(-1)[0]}`
  const certWord = count === 1 ? 'certificate is attached below' : 'certificates are attached below'
  const examWord = count === 1 ? 'Trinity exam' : 'Trinity exams'

  const template = `Hi ${firstName},

I'm Paul Sheridan, running Trinity exam centre 120. Thank you for entering ${namesSentence} for their recent ${examWord} through centre 120. Their personalised musicExams.help ${certWord}.

This is our own centre 120 recognition — separate from any certificate Trinity themselves issue (Trinity send a digital certificate directly to candidates who pass).

How our certificates work: every candidate entered through centre 120 earns at least a Bravo certificate as a thank-you for taking part. Candidates who achieve a Merit earn a Take a Bow certificate, and those who achieve a Distinction earn a Standing Ovation certificate.

${namesSentence} will also appear on the Recognition page at https://musicexams.help/recognition — first name and surname initial only, for GDPR. If you'd like the full name shown, just reply and say the word.

I've recently launched musicExams.help — a free resource for anyone booking Trinity exams. It covers the difference between digital and face-to-face, grades explained, UCAS points and more. Have a look when you get a minute: https://musicexams.help

If ${namesSentence} ${count === 1 ? 'has' : 'have'} a music teacher, do let them know about our site too — teachers earn their own appreciation badges for supporting candidates through centre 120.

Every entry through centre 120 also gets one ticket in our quarterly prize draw — the £50 gift token winner is announced on the Recognition page. Good luck in future draws!

Thanks for choosing centre 120.

Best wishes,
Paul Sheridan`

  navigator.clipboard.writeText(template)
  alert('Parent email template copied to clipboard! Now click "Open in Gmail" to compose.')
}

/**
 * Self-applicant template — when the candidate booked their own exam (adult
 * learner, or a teenager going direct). Recipient IS the candidate, so the
 * whole voice is second-person.
 */
function copySelfApplicantTemplate(teacher: Teacher) {
  const firstName = recipientGreetingName(teacher.teacher_name)
  const count = teacher.students.length
  const certWord = count === 1 ? 'certificate is attached below' : 'certificates are attached below'
  const examWord = count === 1 ? 'Trinity exam' : 'Trinity exams'

  const template = `Hi ${firstName},

I'm Paul Sheridan, running Trinity exam centre 120. Thank you for entering your recent ${examWord} through centre 120. Your personalised musicExams.help ${certWord}.

This is our own centre 120 recognition — separate from any certificate Trinity themselves issue (Trinity send a digital certificate directly to candidates who pass).

How our certificates work: every candidate entered through centre 120 earns at least a Bravo certificate as a thank-you for taking part. A Merit earns a Take a Bow certificate, and a Distinction earns a Standing Ovation certificate.

Your name will also appear on the Recognition page at https://musicexams.help/recognition — first name and surname initial only, for GDPR. If you'd like your full name shown, just reply and say the word.

I've recently launched musicExams.help — a free resource for anyone booking Trinity exams. It covers the difference between digital and face-to-face, grades explained, UCAS points and more. Have a look when you get a minute: https://musicexams.help

If you have a music teacher, do let them know about our site too — teachers earn their own appreciation badges for supporting candidates through centre 120.

Every entry through centre 120 also gets one ticket in our quarterly prize draw — the £50 gift token winner is announced on the Recognition page. Good luck in future draws!

Thanks for choosing centre 120.

Best wishes,
Paul Sheridan`

  navigator.clipboard.writeText(template)
  alert('Self-applicant email template copied to clipboard! Now click "Open in Gmail" to compose.')
}

function openGmailCompose(teacher: Teacher) {
  // Subject varies by recipient type. Teacher → existing. Parent → child-focused.
  // Self → your-own-result focused.
  let subjectText: string
  if (teacher.booking_role === 'self') {
    subjectText = `Your musicExams.help Certificate — Trinity ${props.quarterLabel}`
  } else if (teacher.is_parent_booking) {
    subjectText = `musicExams.help Certificate${teacher.students.length > 1 ? 's' : ''} — ${teacher.students.map(s => s.name.split(' ')[0]).join(' & ')}`
  } else {
    subjectText = `${props.quarterLabel} Exam Results — Your Students Did Brilliantly!`
  }
  const subject = encodeURIComponent(subjectText)
  const to = encodeURIComponent(teacher.applicant_email || '')
  window.open(`https://mail.google.com/mail/?view=cm&to=${to}&su=${subject}`, '_blank')
}

// Copy prize winner email (to send to the winning student's teacher)
function copyWinnerEmail(teacher: Teacher) {
  const firstName = recipientGreetingName(teacher.teacher_name)
  const winner = studentWinner.value
  if (!winner) return

  const winnerFirst = winner.name.split(' ')[0]
  const winnerInitial = winner.name.split(' ').length > 1
    ? `${winnerFirst} ${winner.name.split(' ').slice(-1)[0][0].toUpperCase()}`
    : winnerFirst

  const template = `Hi ${firstName},

Great news — one of your students, ${winnerInitial}, has won the ${props.quarterLabel} student prize draw! They'll be receiving a £50 Amazon gift token.

Here's the gift card code for you to pass on to their parent/guardian:

[PASTE GIFT CARD CODE HERE]

They can add this to any Amazon account — it's not tied to a name or email.

Their name will appear on the musicExams.help Recognition page as "${winnerInitial}". If they or their parent would like us to display their full name instead, just let me know and I'll update it.

Congratulations to them — and well done to you for entering them through centre 120!

PS — if you don't already, you can track all your students' results and awards in one place at https://musicexams.help/register (free teacher account).`

  navigator.clipboard.writeText(template)
  alert('Winner email copied to clipboard!')
}

// Copy heads-up email (to send from OLD email address)
function copyHeadsUpEmail(teacher: Teacher) {
  const firstName = recipientGreetingName(teacher.teacher_name)

  const template = `Hi ${firstName},

Just a quick note — I've sent you your ${props.quarterLabel} exam results and certificates from my new email address: musicexams@musicexams.help

If you can't see it, please check your junk/spam folder and mark it as safe. This is the address I'll be using going forward for everything related to Trinity exams through centre 120.

Thanks,
Paul`

  navigator.clipboard.writeText(template)
  alert('Heads-up email copied to clipboard!')
}

// Sample email for teachers to forward certificates to parents
function copyParentEmailSample() {
  const template = `Here's a suggested email you can send to parents along with their child's certificate:

---

Dear [Parent Name],

I'm pleased to let you know that [Student Name] has received their ${props.quarterLabel} Trinity College London exam result!

They achieved a score of [Score] — [Result (Pass/Merit/Distinction)] — in [Instrument] Grade [Grade]. Well done to them!

I've attached their personalised certificate from musicExams.help, which recognises their achievement through centre 120.

Their result will also appear on the musicExams.help Recognition page at https://musicexams.help/recognition — have a look when you get a chance!

If you have any questions about future exams or would like to book their next grade, just let me know.

Best wishes,
[Your Name]`

  navigator.clipboard.writeText(template)
  alert('Sample parent email copied to clipboard!')
}

// Quarter selector
function changeQuarter(q: number, y: number) {
  router.get('/admin/quarter-end', { quarter: q, year: y }, { preserveState: false })
}

// --- PRIZE DRAW ---
const drawingStudent = ref(false)
const drawingTeacher = ref(false)
const studentTestWinner = ref<{ name: string; instrument: string; grade: string; teacher: string } | null>(null)
const teacherTestWinner = ref<{ name: string; entries: number; is_registered: boolean } | null>(null)
const studentRealDone = ref(!!props.existingDraws.student)
const teacherRealDone = ref(!!props.existingDraws.teacher)
const studentRealWinner = ref(props.existingDraws.student)
const teacherRealWinner = ref(props.existingDraws.teacher)
const testDrawCount = ref({ student: 0, teacher: 0 })

async function runDraw(type: 'student' | 'teacher', mode: 'test' | 'real') {
  if (type === 'student') drawingStudent.value = true
  else drawingTeacher.value = true

  // Real draw confirmation
  if (mode === 'real') {
    const confirmed = confirm(`This is the REAL ${type} prize draw. The result will be permanently recorded and cannot be changed. Continue?`)
    if (!confirmed) {
      if (type === 'student') drawingStudent.value = false
      else drawingTeacher.value = false
      return
    }
  }

  try {
    const res = await fetch('/admin/quarter-end/draw', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getXsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ type, quarter: props.quarter, year: props.year, mode }),
    })

    if (!res.ok) {
      const err = await res.json()
      alert(err.error || `Could not run ${type} draw`)
      return
    }

    const data = await res.json()

    if (type === 'student') {
      if (mode === 'test') {
        studentTestWinner.value = data.winner
        testDrawCount.value.student++
      } else {
        studentRealWinner.value = { winner_name: data.winner.name, winner_instrument: data.winner.instrument, winner_grade: data.winner.grade, winner_teacher: data.winner.teacher, total_tickets: data.total_tickets, created_at: new Date().toISOString() }
        studentRealDone.value = true
      }
    } else {
      if (mode === 'test') {
        teacherTestWinner.value = data.winner
        testDrawCount.value.teacher++
      } else {
        teacherRealWinner.value = { winner_name: data.winner.name, winner_entries: data.winner.entries, total_tickets: data.total_tickets, created_at: new Date().toISOString() }
        teacherRealDone.value = true
      }
    }
  } catch (e) {
    alert(`Could not run ${type} draw`)
  } finally {
    if (type === 'student') drawingStudent.value = false
    else drawingTeacher.value = false
  }
}

// For the email template — use real winner if available, otherwise null
const studentWinner = computed(() => {
  if (studentRealWinner.value) return {
    name: studentRealWinner.value.winner_name,
    instrument: studentRealWinner.value.winner_instrument ?? '',
    grade: studentRealWinner.value.winner_grade ?? '',
    teacher: studentRealWinner.value.winner_teacher ?? '',
  }
  return null
})
const teacherWinner = computed(() => {
  if (teacherRealWinner.value) return {
    name: teacherRealWinner.value.winner_name,
    entries: teacherRealWinner.value.winner_entries ?? 0,
    is_registered: false,
  }
  return null
})
</script>

<template>
  <div>
    <PageHeader
      :title="`Quarter End — ${quarterLabel}`"
      subtitle="Step-by-step guide to sending certificates, badges and emails to teachers"
      eyebrow="Admin"
      size="compact"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <select
            :value="quarter"
            class="rounded-lg border border-brand-border bg-brand-surface px-2 py-1 text-sm"
            @change="changeQuarter(Number(($event.target as HTMLSelectElement).value), year)"
          >
            <option :value="1">Q1</option>
            <option :value="2">Q2</option>
            <option :value="3">Q3</option>
            <option :value="4">Q4</option>
          </select>
          <select
            :value="year"
            class="rounded-lg border border-brand-border bg-brand-surface px-2 py-1 text-sm"
            @change="changeQuarter(quarter, Number(($event.target as HTMLSelectElement).value))"
          >
            <option :value="2026">2026</option>
            <option :value="2027">2027</option>
          </select>
        </div>
      </template>
    </PageHeader>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

      <!-- SUMMARY CARDS -->
      <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Users class="mx-auto mb-2 h-6 w-6 text-brand-accent" />
          <p class="text-2xl font-bold text-brand-text">{{ summary.teacher_count }}</p>
          <p class="text-xs text-brand-text-soft">Teachers</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Award class="mx-auto mb-2 h-6 w-6 text-brand-accent" />
          <p class="text-2xl font-bold text-brand-text">{{ remainingCertsToSend }}</p>
          <p class="text-xs text-brand-text-soft">Certificates left to send</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Clock class="mx-auto mb-2 h-6 w-6 text-amber-500" />
          <p class="text-2xl font-bold text-brand-text">{{ summary.pending }}</p>
          <p class="text-xs text-brand-text-soft">Still awaiting results</p>
        </div>
        <div class="rounded-xl border border-brand-border bg-brand-surface p-4 text-center">
          <Trophy class="mx-auto mb-2 h-6 w-6 text-yellow-500" />
          <p class="text-2xl font-bold text-brand-text" v-if="summary.has_pending">—</p>
          <p class="text-2xl font-bold text-brand-text" v-else-if="summary.showstopper">{{ summary.showstopper.score }}</p>
          <p class="text-2xl font-bold text-brand-text" v-else>—</p>
          <p class="text-xs text-brand-text-soft" v-if="summary.has_pending">Results pending</p>
          <p
            v-else-if="summary.showstopper"
            class="text-xs text-brand-text-soft"
          >
            Showstopper:
            <!-- Single winner — keep the existing one-line layout. -->
            <span v-if="(summary.showstopper.winners?.length ?? 1) <= 1">
              {{ summary.showstopper.name }}
            </span>
            <!-- Tied at the top score — list every name, comma-separated. -->
            <span v-else class="block">
              {{ summary.showstopper.winners!.map(w => w.name).join(' &amp; ') }}
              <span class="block text-[11px] text-brand-text-soft/80">
                ({{ summary.showstopper.winners!.length }}-way tie)
              </span>
            </span>
          </p>
          <p class="text-xs text-brand-text-soft" v-else>Top Scorers</p>
        </div>
      </div>

      <!-- PROGRESS BAR -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-brand-text">Progress: {{ completedCount }} / {{ teachers.length }} teachers done</span>
          <span class="text-sm text-brand-text-soft">{{ Math.round((completedCount / Math.max(teachers.length, 1)) * 100) }}%</span>
        </div>
        <div class="h-3 rounded-full bg-brand-surface-soft overflow-hidden">
          <div
            class="h-full rounded-full bg-brand-accent transition-all duration-500"
            :style="{ width: `${(completedCount / Math.max(teachers.length, 1)) * 100}%` }"
          />
        </div>
      </div>

      <!-- STEP TABS -->
      <div class="mb-6 flex gap-2">
        <button
          v-for="(stepLabel, idx) in ['1. Generate Certificates', '2. Email Teachers', '3. Prize Draws']"
          :key="idx"
          class="rounded-lg px-4 py-2 text-sm font-semibold transition"
          :class="currentStep === idx + 1
            ? 'bg-brand-accent text-white'
            : 'bg-brand-surface-soft text-brand-text-soft hover:bg-brand-surface hover:text-brand-text'"
          @click="currentStep = idx + 1"
        >
          {{ stepLabel }}
        </button>
      </div>

      <!-- STEPS -->
      <div class="mb-8 space-y-4">

        <!-- STEP 1: Generate certificates -->
        <div v-show="currentStep === 1" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold bg-brand-accent text-white">
              <CheckCircle2 v-if="batchResult" class="h-5 w-5" />
              <span v-else>1</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Generate All Certificates</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            This creates a ZIP file per teacher containing all their students' certificates, plus a master ZIP with everything.
          </p>
          <MyButtonConstructor
            v-if="!batchResult"
            size="medium"
            variant="primary"
            :icon="batchGenerating ? Clock : Package"
            :disabled="batchGenerating"
            @click="batchGenerate"
          >
            {{ batchGenerating ? 'Generating... please wait' : `Generate All ${quarterLabel} Certificates` }}
          </MyButtonConstructor>

          <!-- Results -->
          <div v-if="batchResult" class="mt-3 rounded-lg border border-brand-success bg-brand-success-soft p-4">
            <p class="text-sm font-bold text-brand-text mb-3">{{ batchResult.total }} certificates generated</p>
            <div class="flex flex-wrap gap-2">
              <a
                v-if="batchResult.master_zip"
                :href="`/admin/certificates/download/${batchResult.master_zip}`"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-bold text-white hover:opacity-90 transition"
              >
                <Download class="h-4 w-4" /> Download All (Master ZIP)
              </a>
              <MyButtonConstructor size="small" variant="outline" @click="currentStep = 2">
                Next: Email Teachers →
              </MyButtonConstructor>
            </div>
          </div>
        </div>

        <!-- STEP 2: Email each teacher -->
        <div v-show="currentStep === 2" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
              :class="completedCount === teachers.length ? 'bg-brand-success text-white' : currentStep >= 2 ? 'bg-brand-accent text-white' : 'bg-brand-border text-brand-text-soft'">
              <CheckCircle2 v-if="completedCount === teachers.length" class="h-5 w-5" />
              <span v-else>2</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Email Each Teacher</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            Go through each teacher below. Download their ZIP, copy the email template, paste into Gmail, attach the ZIP, and send. Tick them off as you go.
          </p>

          <div v-if="currentStep >= 2" class="space-y-3">
            <!-- Advance to Step 3 -->
            <div class="flex justify-end">
              <MyButtonConstructor size="small" variant="outline" @click="currentStep = 3">
                Next: Prize Draws →
              </MyButtonConstructor>
            </div>
            <div
              v-for="teacher in teachers"
              :key="teacher.teacher_name"
              class="rounded-lg border transition"
              :class="completedTeachers[teacher.teacher_name]
                ? 'border-brand-accent bg-brand-accent/10'
                : 'border-brand-border bg-white'"
            >
              <!-- Teacher header -->
              <div
                class="flex items-center justify-between px-4 py-3 cursor-pointer"
                @click="toggleTeacher(teacher.teacher_name)"
              >
                <div class="flex items-center gap-3">
                  <button
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 transition"
                    :class="completedTeachers[teacher.teacher_name]
                      ? 'border-brand-accent bg-brand-accent text-white'
                      : 'border-brand-border hover:border-brand-accent'"
                    @click.stop="markDone(teacher.teacher_name)"
                  >
                    <CheckCircle2 v-if="completedTeachers[teacher.teacher_name]" class="h-4 w-4" />
                  </button>
                  <div>
                    <span class="font-bold text-brand-text">{{ teacher.teacher_name }}</span>
                    <span v-if="teacher.booking_role === 'self'" class="ml-2 inline-block rounded-full bg-purple-500/10 px-2 py-0.5 text-xs font-semibold text-purple-700">
                      Self booking
                    </span>
                    <span v-else-if="teacher.is_parent_booking" class="ml-2 inline-block rounded-full bg-brand-success/10 px-2 py-0.5 text-xs font-semibold text-brand-success">
                      Parent booking
                    </span>
                    <span v-if="teacher.badge_tier" class="ml-2 inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                      {{ teacher.badge_tier }} Badge
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-3 text-sm text-brand-text-soft">
                  <span>{{ teacher.with_results }} cert{{ teacher.with_results !== 1 ? 's' : '' }}</span>
                  <span v-if="teacher.pending > 0" class="text-amber-500">{{ teacher.pending }} pending</span>
                  <ChevronUp v-if="expandedTeacher === teacher.teacher_name" class="h-4 w-4" />
                  <ChevronDown v-else class="h-4 w-4" />
                </div>
              </div>

              <!-- Expanded detail -->
              <div v-if="expandedTeacher === teacher.teacher_name" class="border-t border-brand-border px-4 py-4 space-y-4">
                <!-- Contact -->
                <div v-if="teacher.applicant_email" class="text-sm">
                  <span class="text-brand-text-soft">Email:</span>
                  <a :href="`mailto:${teacher.applicant_email}`" class="ml-1 font-medium text-brand-accent hover:underline">{{ teacher.applicant_email }}</a>
                </div>

                <!-- Student results table -->
                <div class="overflow-x-auto rounded-lg border border-brand-border">
                  <table class="w-full text-sm">
                    <thead class="bg-brand-surface-soft">
                      <tr>
                        <th class="px-3 py-2 text-left font-semibold text-brand-text">Student</th>
                        <th class="px-3 py-2 text-left font-semibold text-brand-text">Instrument</th>
                        <th class="px-3 py-2 text-center font-semibold text-brand-text">Grade</th>
                        <th class="px-3 py-2 text-center font-semibold text-brand-text">Score</th>
                        <th class="px-3 py-2 text-left font-semibold text-brand-text">Result</th>
                        <th class="px-3 py-2 text-left font-semibold text-brand-text">Certificate</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(student, i) in teacher.students" :key="i" class="border-t border-brand-border">
                        <td class="px-3 py-2"><span class="font-medium text-brand-text">{{ student.name }}</span></td>
                        <td class="px-3 py-2"><span class="text-sm text-brand-text-soft">{{ student.instrument }}</span></td>
                        <td class="px-3 py-2 text-center"><span class="text-sm text-brand-text-soft">{{ student.grade }}</span></td>
                        <td class="px-3 py-2 text-center text-sm font-bold" :class="{
                          'text-yellow-600': student.score >= 87,
                          'text-brand-accent': student.score >= 75 && student.score < 87,
                          'text-brand-text': student.score < 75,
                        }">{{ student.score }}</td>
                        <td class="px-3 py-2">
                          <span class="rounded-full px-2 py-0.5 text-sm font-medium"
                            :class="{
                              'bg-brand-success-soft text-brand-success': student.result === 'Distinction',
                              'bg-brand-accent/10 text-brand-accent': student.result === 'Merit',
                              'bg-brand-surface-soft text-brand-text-soft': student.result === 'Pass',
                              'bg-brand-danger-soft text-brand-danger': student.result === 'Below Pass',
                            }">
                            {{ student.result }}
                          </span>
                        </td>
                        <td class="px-3 py-2">
                          <span class="inline-block rounded-full bg-brand-accent/10 px-2 py-0.5 text-xs font-semibold text-brand-accent">
                            {{ student.certificate }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Summary -->
                <div class="flex flex-wrap gap-4 text-sm">
                  <span v-if="teacher.distinctions" class="font-semibold text-yellow-600">{{ teacher.distinctions }} Distinction{{ teacher.distinctions > 1 ? 's' : '' }}</span>
                  <span v-if="teacher.merits" class="font-semibold text-brand-accent">{{ teacher.merits }} Merit{{ teacher.merits > 1 ? 's' : '' }}</span>
                  <span v-if="teacher.passes" class="font-semibold text-brand-text">{{ teacher.passes }} Pass{{ teacher.passes > 1 ? 'es' : '' }}</span>
                  <span v-if="teacher.badge_tier" class="font-semibold text-brand-success">🏆 {{ teacher.badge_tier }} Badge ({{ teacher.total_entries }} entries this quarter)</span>
                </div>

                <!-- Orphaned bucket — no real recipient exists yet. Show
                     actionable guidance instead of fake email buttons. -->
                <div v-if="!teacher.applicant_email" class="rounded-lg border border-dashed border-amber-400 bg-amber-50 p-3 text-sm text-amber-900">
                  <p class="font-semibold mb-1">No contact linked yet</p>
                  <p>These candidates were booked without a named parent or teacher. For each, look up the correspondence email on Trinity's candidate page, then run <code class="rounded bg-amber-100 px-1 py-0.5 font-mono">contacts:add "Parent Name" parent email@example.com</code> on the server, and update the candidate's teacher_name on the Exam Entry to match.</p>
                </div>

                <!-- Actions — hidden when no email -->
                <div v-if="teacher.applicant_email" class="flex flex-wrap gap-2">
                  <a
                    v-if="batchResult?.download_links?.[teacher.teacher_name]"
                    :href="`/admin/certificates/download/${batchResult.download_links[teacher.teacher_name]}`"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                  >
                    <Download class="h-4 w-4" /> Download ZIP
                  </a>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-primary px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="copyEmailTemplate(teacher)"
                  >
                    <Copy class="h-4 w-4" /> Copy Email Template
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="openGmailCompose(teacher)"
                  >
                    <ExternalLink class="h-4 w-4" /> Open in Gmail
                  </button>
                  <button
                    v-if="studentWinner && studentWinner.teacher === teacher.teacher_name"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition"
                    @click="copyWinnerEmail(teacher)"
                  >
                    <Gift class="h-4 w-4" /> Copy Winner Email
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-surface border border-brand-border px-3 py-2 text-sm font-semibold text-brand-text hover:bg-brand-surface-soft transition"
                    @click="copyHeadsUpEmail(teacher)"
                  >
                    <Copy class="h-4 w-4" /> Copy Heads-Up (Old Email)
                  </button>
                  <button
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="completedTeachers[teacher.teacher_name]
                      ? 'bg-brand-accent text-white'
                      : 'bg-brand-accent/10 text-brand-accent border border-brand-accent hover:bg-brand-accent hover:text-white'"
                    @click="markDone(teacher.teacher_name)"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                    {{ completedTeachers[teacher.teacher_name] ? 'Done!' : 'Mark as Sent' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: Prize Draws -->
        <div v-show="currentStep === 3" class="rounded-xl border-2 border-brand-accent bg-brand-surface p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
              :class="(studentWinner && teacherWinner) ? 'bg-brand-success text-white' : currentStep >= 3 ? 'bg-brand-accent text-white' : 'bg-brand-border text-brand-text-soft'">
              <CheckCircle2 v-if="studentWinner && teacherWinner" class="h-5 w-5" />
              <span v-else>3</span>
            </div>
            <h3 class="text-lg font-bold text-brand-text">Prize Draws & Top Scorer Awards</h3>
          </div>
          <p class="text-sm text-brand-text-soft mb-4">
            Run the quarterly prize draws. Every student entry = one ticket. Eligible teachers get one ticket per entry they submitted.
          </p>

          <div class="space-y-6">

            <!-- Top Scorers — the public Recognition page only shows
                 awards once (a) the prize draw has been run AND (b) no
                 pending results, so this Step-3 panel is admin-only.
                 The "Preview leaders so far" button just bypasses the
                 pending gate on this page — it doesn't publish, generate
                 certs, or send emails. Pure peek. -->
            <div v-if="summary.has_pending && !summary.finalised_with_pending" class="rounded-lg border border-brand-border bg-brand-surface-soft p-4">
              <div class="flex items-start gap-3">
                <Clock class="h-5 w-5 shrink-0 text-brand-text-soft mt-0.5" />
                <div class="flex-1">
                  <p class="font-semibold text-brand-text-soft">Top scorer awards will appear once all results are in ({{ summary.pending }} still pending)</p>
                  <p class="mt-1 text-sm text-brand-text-soft">Want a sneak peek? You can preview who's leading right now. Nothing gets published, no certificates are generated, no emails are sent — it's just for your eyes.</p>
                  <button
                    class="mt-3 inline-flex items-center gap-2 rounded-lg border border-brand-accent bg-brand-accent/10 px-3 py-2 text-sm font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition"
                    @click="finaliseNow"
                  >
                    <Sparkles class="h-4 w-4" /> Preview leaders so far
                  </button>
                </div>
              </div>
            </div>

            <!-- Preview banner shown when awards are revealed despite
                 pending results. Makes it visually distinct so it's
                 obviously a peek, not the final published list. -->
            <div v-if="summary.finalised_with_pending" class="rounded-lg border-2 border-amber-400 bg-amber-50 p-4">
              <div class="flex items-start gap-3">
                <Sparkles class="h-5 w-5 shrink-0 text-amber-600 mt-0.5" />
                <div>
                  <p class="font-bold text-amber-900">Preview only — {{ summary.pending }} result{{ summary.pending === 1 ? '' : 's' }} still pending</p>
                  <p class="mt-1 text-sm text-amber-800">These are the current leaders based on the scores in so far. <strong>Nothing is published</strong> — the public Recognition page won't show top-scorer awards until the quarter is fully finalised (all results in + prize draw run). Click around, copy emails to test, or just see who's winning. Refresh without the preview link to re-hide.</p>
                </div>
              </div>
            </div>

            <!-- Empty-state when the awards block would otherwise be hidden.
                 Happens for quarters with no scored Distinctions or Merits
                 yet (e.g. early in a quarter). Explains WHY the cert-gen
                 and Publish buttons aren't here. -->
            <div v-if="(summary.finalised_with_pending || !summary.has_pending) && !hasAnyAward" class="rounded-lg border border-brand-border bg-brand-surface-soft p-4">
              <div class="flex items-start gap-3">
                <Trophy class="h-5 w-5 shrink-0 text-brand-text-soft mt-0.5" />
                <div>
                  <p class="font-semibold text-brand-text">No top-scorer awards in this quarter yet</p>
                  <p class="mt-1 text-sm text-brand-text-soft">No candidates have been awarded a Merit or Distinction in {{ quarterLabel }} so far. The four winners (Initial–5 + 6–8 × Distinction + Merit) will appear here once results come in. The <strong>Generate top-scorer certs</strong> and <strong>Publish</strong> buttons will follow.</p>
                </div>
              </div>
            </div>

            <div v-if="hasAnyAward" class="space-y-4">
              <!-- "Generate top-scorer certs" — fast standalone, just the
                   4 PDFs (plus any ties), without re-running the full
                   batch. After it runs, each winner row gets a download
                   link below. -->
              <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-brand-border bg-brand-surface-soft p-3">
                <div class="flex-1">
                  <p class="text-sm font-semibold text-brand-text">Top-scorer certificates</p>
                  <p class="text-xs text-brand-text-soft">Generate just the 4 (+ ties) Showstopper / Centre Stage PDFs — much faster than re-running the full batch.</p>
                </div>
                <button
                  class="inline-flex items-center gap-2 rounded-lg bg-brand-accent px-3 py-2 text-sm font-semibold text-white hover:opacity-90 transition disabled:opacity-50"
                  :disabled="generatingCerts"
                  @click="generateTopScorerCerts"
                >
                  <Sparkles class="h-4 w-4" />
                  {{ generatingCerts ? 'Generating…' : 'Generate top-scorer certs' }}
                </button>
              </div>

              <!-- Initial–5 group -->
              <div>
                <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-brand-text-soft">Initial–Grade 5</h4>
                <div class="space-y-3">
                  <div v-if="initial5.distinction.length" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4">
                    <div class="flex items-center gap-2 mb-2">
                      <Star class="h-5 w-5 text-yellow-600" />
                      <span class="font-bold text-yellow-800">
                        Showstopper — Highest Distinction (Initial–5) — {{ quarterLabel }}
                        <span v-if="initial5.distinction.length > 1" class="ml-1 text-xs font-normal">— {{ initial5.distinction.length }}-way tie · £{{ tokenSplit(initial5.distinction.length) }} each</span>
                      </span>
                    </div>
                    <div v-for="w in initial5.distinction" :key="`i5d-${w.full_name}`" class="mt-2 flex flex-wrap items-center gap-2">
                      <p class="flex-1 text-sm text-yellow-800">
                        {{ w.name }} — {{ w.instrument }} {{ formatGrade(w.grade) }} — {{ w.score }} marks
                        <span class="ml-1 text-xs text-yellow-700">(admin: {{ w.full_name }} · teacher: {{ w.teacher_name ?? '—' }})</span>
                      </p>
                      <a v-if="topScorerCertPaths[w.full_name]" :href="topScorerCertPaths[w.full_name]" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-white px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-700 hover:text-white transition">
                        <Download class="h-3 w-3" /> Download Cert
                      </a>
                      <button class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-white px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-700 hover:text-white transition" @click="copyTopScorerEmail(w, 'initial_5_distinction', initial5.distinction.length)">
                        <Copy class="h-3 w-3" /> Copy Email
                      </button>
                      <button v-if="w.teacher_email" class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-yellow-700 px-2.5 py-1 text-xs font-semibold text-white hover:opacity-90 transition" @click="openGmailComposeForWinner(w, 'initial_5_distinction')">
                        <ExternalLink class="h-3 w-3" /> Open Gmail
                      </button>
                    </div>
                  </div>
                  <div v-if="initial5.merit.length" class="rounded-lg border border-brand-accent/30 bg-brand-accent/5 p-4">
                    <div class="flex items-center gap-2 mb-2">
                      <Trophy class="h-5 w-5 text-brand-accent" />
                      <span class="font-bold text-brand-text">
                        Centre Stage — Highest Merit (Initial–5) — {{ quarterLabel }}
                        <span v-if="initial5.merit.length > 1" class="ml-1 text-xs font-normal">— {{ initial5.merit.length }}-way tie · £{{ tokenSplit(initial5.merit.length) }} each</span>
                      </span>
                    </div>
                    <div v-for="w in initial5.merit" :key="`i5m-${w.full_name}`" class="mt-2 flex flex-wrap items-center gap-2">
                      <p class="flex-1 text-sm text-brand-text">
                        {{ w.name }} — {{ w.instrument }} {{ formatGrade(w.grade) }} — {{ w.score }} marks
                        <span class="ml-1 text-xs text-brand-text-soft">(admin: {{ w.full_name }} · teacher: {{ w.teacher_name ?? '—' }})</span>
                      </p>
                      <a v-if="topScorerCertPaths[w.full_name]" :href="topScorerCertPaths[w.full_name]" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-white px-2.5 py-1 text-xs font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition">
                        <Download class="h-3 w-3" /> Download Cert
                      </a>
                      <button class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-white px-2.5 py-1 text-xs font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition" @click="copyTopScorerEmail(w, 'initial_5_merit', initial5.merit.length)">
                        <Copy class="h-3 w-3" /> Copy Email
                      </button>
                      <button v-if="w.teacher_email" class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-brand-accent px-2.5 py-1 text-xs font-semibold text-white hover:opacity-90 transition" @click="openGmailComposeForWinner(w, 'initial_5_merit')">
                        <ExternalLink class="h-3 w-3" /> Open Gmail
                      </button>
                    </div>
                  </div>
                  <p v-if="!initial5.distinction.length && !initial5.merit.length" class="text-sm italic text-brand-text-soft">
                    No Distinction or Merit results in the Initial–5 group this quarter.
                  </p>
                </div>
              </div>

              <!-- 6-8 group -->
              <div>
                <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-brand-text-soft">Grades 6–8</h4>
                <div class="space-y-3">
                  <div v-if="grades68.distinction.length" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4">
                    <div class="flex items-center gap-2 mb-2">
                      <Star class="h-5 w-5 text-yellow-600" />
                      <span class="font-bold text-yellow-800">
                        Showstopper — Highest Distinction (6–8) — {{ quarterLabel }}
                        <span v-if="grades68.distinction.length > 1" class="ml-1 text-xs font-normal">— {{ grades68.distinction.length }}-way tie · £{{ tokenSplit(grades68.distinction.length) }} each</span>
                      </span>
                    </div>
                    <div v-for="w in grades68.distinction" :key="`g68d-${w.full_name}`" class="mt-2 flex flex-wrap items-center gap-2">
                      <p class="flex-1 text-sm text-yellow-800">
                        {{ w.name }} — {{ w.instrument }} {{ formatGrade(w.grade) }} — {{ w.score }} marks
                        <span class="ml-1 text-xs text-yellow-700">(admin: {{ w.full_name }} · teacher: {{ w.teacher_name ?? '—' }})</span>
                      </p>
                      <a v-if="topScorerCertPaths[w.full_name]" :href="topScorerCertPaths[w.full_name]" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-white px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-700 hover:text-white transition">
                        <Download class="h-3 w-3" /> Download Cert
                      </a>
                      <button class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-white px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-700 hover:text-white transition" @click="copyTopScorerEmail(w, '6_8_distinction', grades68.distinction.length)">
                        <Copy class="h-3 w-3" /> Copy Email
                      </button>
                      <button v-if="w.teacher_email" class="inline-flex items-center gap-1 rounded-md border border-yellow-700 bg-yellow-700 px-2.5 py-1 text-xs font-semibold text-white hover:opacity-90 transition" @click="openGmailComposeForWinner(w, '6_8_distinction')">
                        <ExternalLink class="h-3 w-3" /> Open Gmail
                      </button>
                    </div>
                  </div>
                  <div v-if="grades68.merit.length" class="rounded-lg border border-brand-accent/30 bg-brand-accent/5 p-4">
                    <div class="flex items-center gap-2 mb-2">
                      <Trophy class="h-5 w-5 text-brand-accent" />
                      <span class="font-bold text-brand-text">
                        Centre Stage — Highest Merit (6–8) — {{ quarterLabel }}
                        <span v-if="grades68.merit.length > 1" class="ml-1 text-xs font-normal">— {{ grades68.merit.length }}-way tie · £{{ tokenSplit(grades68.merit.length) }} each</span>
                      </span>
                    </div>
                    <div v-for="w in grades68.merit" :key="`g68m-${w.full_name}`" class="mt-2 flex flex-wrap items-center gap-2">
                      <p class="flex-1 text-sm text-brand-text">
                        {{ w.name }} — {{ w.instrument }} {{ formatGrade(w.grade) }} — {{ w.score }} marks
                        <span class="ml-1 text-xs text-brand-text-soft">(admin: {{ w.full_name }} · teacher: {{ w.teacher_name ?? '—' }})</span>
                      </p>
                      <a v-if="topScorerCertPaths[w.full_name]" :href="topScorerCertPaths[w.full_name]" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-white px-2.5 py-1 text-xs font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition">
                        <Download class="h-3 w-3" /> Download Cert
                      </a>
                      <button class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-white px-2.5 py-1 text-xs font-semibold text-brand-accent hover:bg-brand-accent hover:text-white transition" @click="copyTopScorerEmail(w, '6_8_merit', grades68.merit.length)">
                        <Copy class="h-3 w-3" /> Copy Email
                      </button>
                      <button v-if="w.teacher_email" class="inline-flex items-center gap-1 rounded-md border border-brand-accent bg-brand-accent px-2.5 py-1 text-xs font-semibold text-white hover:opacity-90 transition" @click="openGmailComposeForWinner(w, '6_8_merit')">
                        <ExternalLink class="h-3 w-3" /> Open Gmail
                      </button>
                    </div>
                  </div>
                  <p v-if="!grades68.distinction.length && !grades68.merit.length" class="text-sm italic text-brand-text-soft">
                    No Distinction or Merit results in the 6–8 group this quarter.
                  </p>
                </div>
              </div>

              <!-- PUBLISH — pushes the snapshot live to the public
                   Recognition page. Idempotent: re-pressing refreshes
                   the snapshot. Disabled state once published. -->
              <div class="rounded-lg border-2 border-brand-success/40 bg-brand-success/5 p-4">
                <div class="flex items-start gap-3">
                  <Trophy class="h-5 w-5 shrink-0 text-brand-success mt-0.5" />
                  <div class="flex-1">
                    <p v-if="summary.publication" class="font-bold text-brand-text">
                      Already published on {{ new Date(summary.publication.published_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </p>
                    <p v-else class="font-bold text-brand-text">Ready to publish?</p>

                    <p v-if="summary.publication" class="mt-1 text-sm text-brand-text-soft">
                      The four winners are live on the
                      <a href="/recognition" target="_blank" class="font-semibold text-brand-accent underline">Recognition page</a>.
                      Re-publish to refresh the snapshot if anything has changed (e.g. a delayed score has come in).
                    </p>
                    <p v-else class="mt-1 text-sm text-brand-text-soft">
                      One click puts the four top-scorer awards live on the public
                      <a href="/recognition" target="_blank" class="font-semibold text-brand-accent underline">Recognition page</a>.
                      The snapshot is locked in — late-arriving scores won't shuffle the list. If a delayed result later beats a leader, top up the gift token manually.
                    </p>

                    <button
                      class="mt-3 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold transition disabled:opacity-50"
                      :class="summary.publication
                        ? 'border border-brand-success bg-white text-brand-success hover:bg-brand-success hover:text-white'
                        : 'bg-brand-success text-white hover:opacity-90'"
                      :disabled="publishing || !hasAnyAward"
                      @click="publishTopScorers"
                    >
                      <Sparkles class="h-4 w-4" />
                      {{ publishing
                        ? 'Publishing…'
                        : summary.publication
                          ? 'Re-publish (refresh snapshot)'
                          : 'Publish top-scorer awards' }}
                    </button>
                    <p v-if="!hasAnyAward" class="mt-2 text-xs text-brand-text-soft italic">
                      Nothing to publish yet — no Distinctions or Merits have been recorded in this quarter.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Student Prize Draw -->
            <div class="rounded-lg border border-brand-border p-4">
              <div class="flex items-center gap-2 mb-2">
                <Gift class="h-5 w-5 text-brand-accent" />
                <span class="font-bold text-brand-text">Student Prize Draw</span>
                <span class="ml-auto text-xs text-brand-text-soft">{{ prizeDraw.student_ticket_count }} tickets in the draw</span>
              </div>

              <p class="text-sm text-brand-text-soft mb-3">Every student who entered through centre 120 this quarter has one ticket per entry.</p>

              <!-- Already drawn (real) -->
              <div v-if="studentRealDone && studentRealWinner" class="rounded-lg border-2 border-brand-success bg-brand-success-soft p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Trophy class="h-5 w-5 text-brand-success" />
                  <span class="font-bold text-brand-text">Official Winner (recorded)</span>
                </div>
                <p class="text-lg font-bold text-brand-text">{{ studentRealWinner.winner_name }}</p>
                <p class="text-sm text-brand-text-soft">{{ studentRealWinner.winner_instrument }} {{ formatGrade(studentRealWinner.winner_grade) }} — Teacher: {{ studentRealWinner.winner_teacher }}</p>
                <p class="mt-2 text-xs text-brand-text-soft">Drawn from {{ studentRealWinner.total_tickets }} tickets. This result is permanently recorded.</p>
              </div>

              <!-- Not yet drawn -->
              <div v-else class="space-y-3">
                <!-- Practice draw result -->
                <div v-if="studentTestWinner" class="rounded-lg border border-dashed border-brand-accent bg-brand-accent/5 p-3">
                  <div class="flex items-center gap-2 mb-1">
                    <Sparkles class="h-4 w-4 text-brand-accent" />
                    <span class="text-sm font-semibold text-brand-accent">Practice Draw #{{ testDrawCount.student }}</span>
                  </div>
                  <p class="font-bold text-brand-text">{{ studentTestWinner.name }}</p>
                  <p class="text-xs text-brand-text-soft">{{ studentTestWinner.instrument }} {{ formatGrade(studentTestWinner.grade) }} — Teacher: {{ studentTestWinner.teacher }}</p>
                  <p class="mt-1 text-xs text-brand-text-soft italic">This is just a practice — not recorded.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <MyButtonConstructor
                    size="small"
                    variant="outline"
                    :icon="drawingStudent ? Loader2 : Sparkles"
                    :disabled="drawingStudent"
                    @click="runDraw('student', 'test')"
                  >
                    {{ drawingStudent ? 'Drawing...' : 'Practice Draw' }}
                  </MyButtonConstructor>

                  <MyButtonConstructor
                    size="medium"
                    variant="primary"
                    :icon="drawingStudent ? Loader2 : Trophy"
                    :disabled="drawingStudent"
                    @click="runDraw('student', 'real')"
                  >
                    {{ drawingStudent ? 'Drawing...' : 'Run REAL Student Draw' }}
                  </MyButtonConstructor>
                </div>
              </div>
            </div>

            <!-- Teacher Prize Draw -->
            <div class="rounded-lg border border-brand-border p-4">
              <div class="flex items-center gap-2 mb-2">
                <Award class="h-5 w-5 text-brand-accent" />
                <span class="font-bold text-brand-text">Teacher Prize Draw</span>
                <span class="ml-auto text-xs text-brand-text-soft">{{ prizeDraw.teacher_ticket_count }} tickets in the draw</span>
              </div>

              <!-- Eligibility table -->
              <div class="mb-3 overflow-x-auto rounded-lg border border-brand-border">
                <table class="w-full text-sm">
                  <thead class="bg-brand-surface-soft">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Applicant/Teacher</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Entries</th>
                      <th class="px-3 py-2 text-center font-semibold text-brand-text">Eligible?</th>
                      <th class="px-3 py-2 text-left font-semibold text-brand-text">Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in prizeDraw.eligible_teachers" :key="t.name" class="border-t border-brand-border">
                      <td class="px-3 py-2">
                        <span class="font-medium text-brand-text">{{ t.name }}</span>
                        <span v-if="t.is_registered" class="ml-1 inline-block rounded-full bg-brand-accent/10 px-1.5 py-0.5 text-xs text-brand-accent">registered</span>
                      </td>
                      <td class="px-3 py-2 text-center"><span class="text-sm font-medium text-brand-text">{{ t.entries }}</span></td>
                      <td class="px-3 py-2 text-center">
                        <CheckCircle2 v-if="t.eligible" class="inline h-4 w-4 text-brand-success" />
                        <span v-else class="text-brand-text-soft">—</span>
                      </td>
                      <td class="px-3 py-2"><span class="text-sm text-brand-text-soft">{{ t.reason }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Already drawn (real) -->
              <div v-if="teacherRealDone && teacherRealWinner" class="rounded-lg border-2 border-brand-success bg-brand-success-soft p-4">
                <div class="flex items-center gap-2 mb-1">
                  <Trophy class="h-5 w-5 text-brand-success" />
                  <span class="font-bold text-brand-text">Official Winner (recorded)</span>
                </div>
                <p class="text-lg font-bold text-brand-text">{{ teacherRealWinner.winner_name }}</p>
                <p class="text-sm text-brand-text-soft">{{ teacherRealWinner.winner_entries }} entries this quarter — from {{ teacherRealWinner.total_tickets }} tickets</p>
                <p class="mt-2 text-xs text-brand-text-soft">This result is permanently recorded.</p>
              </div>

              <!-- Not yet drawn -->
              <div v-else class="space-y-3">
                <!-- Practice draw result -->
                <div v-if="teacherTestWinner" class="rounded-lg border border-dashed border-brand-accent bg-brand-accent/5 p-3">
                  <div class="flex items-center gap-2 mb-1">
                    <Sparkles class="h-4 w-4 text-brand-accent" />
                    <span class="text-sm font-semibold text-brand-accent">Practice Draw #{{ testDrawCount.teacher }}</span>
                  </div>
                  <p class="font-bold text-brand-text">{{ teacherTestWinner.name }}</p>
                  <p class="text-xs text-brand-text-soft">{{ teacherTestWinner.entries }} entries this quarter</p>
                  <p class="mt-1 text-xs text-brand-text-soft italic">This is just a practice — not recorded.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <MyButtonConstructor
                    size="small"
                    variant="outline"
                    :icon="drawingTeacher ? Loader2 : Sparkles"
                    :disabled="drawingTeacher"
                    @click="runDraw('teacher', 'test')"
                  >
                    {{ drawingTeacher ? 'Drawing...' : 'Practice Draw' }}
                  </MyButtonConstructor>

                  <MyButtonConstructor
                    size="medium"
                    variant="primary"
                    :icon="drawingTeacher ? Loader2 : Trophy"
                    :disabled="drawingTeacher"
                    @click="runDraw('teacher', 'real')"
                  >
                    {{ drawingTeacher ? 'Drawing...' : 'Run REAL Teacher Draw' }}
                  </MyButtonConstructor>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</template>
