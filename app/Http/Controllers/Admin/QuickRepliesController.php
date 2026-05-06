<?php

// app/Http/Controllers/Admin/QuickRepliesController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class QuickRepliesController extends Controller
{
    /**
     * Quick Replies — phone-friendly template bank for inbound enquiry replies.
     *
     * Source of truth: `reference_reply_templates_bank.md` (memory). When you
     * update the canonical templates, mirror the change here. Static array is
     * deliberate — these change rarely and a DB-backed UI is overkill.
     */
    public function index(): Response
    {
        $templates = [
            [
                'id' => 'parent-enquiry',
                'audience' => 'Parent',
                'title' => 'New parent enquiry',
                'when' => 'Use when a parent emails for the first time, asking about booking, fees, exam day, or anything practical.',
                'subject' => 'Re: your enquiry — musicExams.help',
                'body' => <<<'TEXT'
Hi [Name],

Thanks for reaching out — glad to help. I built musicExams.help to answer questions exactly like yours, so hopefully it becomes your go-to for anything exam-related.

[Direct answer to their specific question, 1–3 sentences. Use "the candidate" and "the teacher" — generic, not gender or instrument specific.]

Do take a look around https://musicexams.help/for-parents when you have a moment — guides on booking, fees and exam day, plus our recognition scheme: certificates for every candidate (Take a Bow for Merit, Standing Ovation for Distinction), top-scorer awards each quarter, and our quarterly prize draw that every candidate is entered into just for sitting an exam.

Have a quick word with the teacher if you're unsure which route is best. If they haven't come across musicExams.help yet, do point them our way.

Good luck with the exam — shout if anything's still unclear.

Thanks,
Paul
TEXT,
            ],
            [
                'id' => 'teacher-enquiry',
                'audience' => 'Teacher / School Admin',
                'title' => 'New teacher / school admin enquiry',
                'when' => 'Use when a teacher or school admin emails for the first time. Mentions centre 120 and the Faber discount partnership.',
                'subject' => 'Re: your enquiry — musicExams.help',
                'body' => <<<'TEXT'
Hi [Name],

Thanks for reaching out — lovely to hear from you. I built musicExams.help to support teachers and their students through the exam process, so hopefully it becomes a useful resource for you.

[Direct answer to their specific question, 1–3 sentences.]

When you're ready to enter candidates, just use centre code **120** on the Trinity booking system — that's us, Liverpool & Wirral.

Do take a look around https://musicexams.help/for-teachers when you have a moment — exam dates, how we recognise your students (Take a Bow and Standing Ovation certificates, plus Centre Stage / Showstopper awards for quarterly top scorers), our quarterly all-entrants prize draw across F2F, digital and theory exams (token relayed through you to the student), Faber book discounts through our partnership, and the wider support we offer teachers through the year.

Let me know if you have any other questions — and good luck with your students' exams.

Thanks,
Paul
TEXT,
            ],
            [
                'id' => 'teacher-text-booking-nudge',
                'audience' => 'Teacher / School Admin',
                'title' => 'Teacher text — quick booking + site nudge',
                'when' => 'Use when a teacher messages by text/WhatsApp asking for the centre code or booking info. Casual format, no subject line. Replace [Name] with their first name.',
                'subject' => null,
                'body' => <<<'TEXT'
Hi [Name], good to hear from you! Hope you're well.

Easiest path: head to https://musicexams.help/for-teachers, tap the menu (top-right) → Book Your Exam → pick the right exam type (Theory, F2F, or Digital). It routes you to the right system with my code already filled in. No manual entry needed.

While you're there you'll see what students get on top of their Trinity cert (recognition awards, quarterly prize draw, Faber book discounts).

Good luck with the entries!
Paul
TEXT,
            ],
            [
                'id' => 'booking-system-explainer',
                'audience' => 'Parent',
                'title' => 'F2F booking — how it works',
                'when' => 'Use when a parent or candidate asks about a specific F2F exam date at one of our venues (Liverpool / Wirral), what time their exam will be, or how booking works. F2F flow does NOT auto-apply centre 120 — candidate picks venue from Trinity\'s full UK list. Days open backwards from Thursday — if Thursday fills, Wednesday opens next, then Tuesday. Slots are allocated in booking order, so if you can see the current count, give them a steer on likely time. Pick the right exam-type wording — Classical & Jazz vs Rock & Pop (R&P is Liverpool only; C&J is Liverpool or Wirral). NOT for digital or theory enquiries — they have no day/time slots and centre 120 IS auto-applied for those.',
                'subject' => 'Re: your enquiry — musicExams.help',
                'body' => <<<'TEXT'
Hi [Name],

Thanks for getting in touch. Yes — we're running exams [at venue / on date the candidate asked about]. [Optional: mention any other nearby dates or venues, e.g. "We've also got a session on Friday 10 July at our Wirral venue, and if the Thursday fills up we'll open an additional day at Liverpool on Wednesday 8 July."]

To book, head to https://musicexams.help, click Book Your Exam, and pick "Face-to-Face — [Classical & Jazz / Rock & Pop]". That opens Trinity's booking system, which shows all their UK venues — pick [Liverpool / Wirral / Liverpool or Wirral] and the day you want. The day runs from 9am to 4:30pm with breaks for lunch and so on. Slots are allocated in the order people book, so earlier bookers get earlier times — within about an hour of booking you'll get an email confirming your time slot.

[Optional steer based on current bookings, e.g. "We've only got two candidates booked in for that day at the moment, so if you book in the next day or two you'd most likely get something around 10am. If you'd prefer a later slot, it'd be worth waiting a bit longer until more have booked."]

[Optional light-touch site plug — drop in if the recipient seems likely to spread the word (returning family, teacher contacts, candidate going into music college, etc.): "While you're on the site, do have a wander around — it's all new and there's a recognition scheme now: Take a Bow and Standing Ovation certificates, top-scorer awards each quarter, and a prize draw every candidate is entered into. Worth a mention to anyone else you know in the music world."]

Do come back to me if you have any questions.

Thanks,
Paul
TEXT,
            ],
            [
                'id' => 'old-address-nudge',
                'audience' => 'Snippet',
                'title' => 'Old-address nudge (slot near top of reply)',
                'when' => 'Slot in just after the warm opener when the enquiry came into a legacy address (tclexamsliverpool@outlook.com, madmusic6@hotmail.com, etc.).',
                'subject' => null,
                'body' => <<<'TEXT'
Quick note: please use **musicexams@musicexams.help** for any future emails — that's my new address and replaces the old Outlook one you wrote to. Much quicker to reach me there.
TEXT,
            ],
            [
                'id' => 'who-books',
                'audience' => 'Canned answer',
                'title' => '"Do I book or does the teacher?"',
                'when' => 'Drop into the [Direct answer] block of either parent or teacher template when the enquiry is about who places the booking.',
                'subject' => null,
                'body' => <<<'TEXT'
Either the teacher or you can book the exam. The easiest route is usually the teacher, as they can enter the candidate under centre 120 and guide them through the process. If the teacher would rather you book it yourself, you can do so using centre code **120**.
TEXT,
            ],
            [
                'id' => 'dg-show-camera-printed-book',
                'audience' => 'DG Exam',
                'title' => 'Showing the book to camera (printed)',
                'when' => 'When asked how it works with showing music in a digital exam, and they have a printed book.',
                'subject' => null,
                'body' => <<<'TEXT'
For digital exams, Trinity needs to verify a legal copy. At the start of the recording, hold the book up to the camera so the examiner can see the cover. That's all — the music stand doesn't have to stay in shot during the performance.

Trinity's guidance: https://www.trinitycollege.com/qualifications/digital/digital-grades-diplomas/record
TEXT,
            ],
            [
                'id' => 'dg-show-camera-pdf',
                'audience' => 'DG Exam',
                'title' => 'Showing a PDF / ebook to camera',
                'when' => 'When asked how it works with downloaded copies (Trinity ebook store, Faber, Sheet Music Direct etc.).',
                'subject' => null,
                'body' => <<<'TEXT'
Yes — for digital exams, Trinity needs to verify a legal copy. If the candidate is using a PDF (Trinity ebook, Faber, Sheet Music Direct etc.), they show the PDF on their device screen to the camera at the start of the recording. Make sure the watermark is clearly legible — that's the proof of purchase the examiner is looking for.

On Trinity ebooks the watermark reads "Prepared exclusively for [name] ([email]) Order: [number]" — usually on the title or copyright page. Third-party publishers stamp the buyer's email and order ID at the bottom of every page.

Same rule applies if the candidate plays from memory — the watermarked page still needs to be shown at the start.

Trinity's guidance: https://www.trinitycollege.com/qualifications/digital/digital-grades-diplomas/record
TEXT,
            ],
            [
                'id' => 'dg-own-choice-upload',
                'audience' => 'DG Exam',
                'title' => 'Own-choice music — uploading notation',
                'when' => 'When asked whether music notation needs uploading separately for a DG exam.',
                'subject' => null,
                'body' => <<<'TEXT'
Only for own-choice pieces NOT in a Trinity-published book. For those, upload a PDF of the notation via the MyTrinity submission tab so the examiner can follow along during marking. Trinity-published syllabus pieces don't need uploading — Trinity already has them.

Trinity's guidance: https://www.trinitycollege.com/qualifications/digital/digital-grades-diplomas/submit-mytrinity
TEXT,
            ],
            [
                'id' => 'dg-watermark-explanation',
                'audience' => 'DG Exam',
                'title' => "What's a watermark on a PDF?",
                'when' => 'When someone asks "what watermark?" or says they can\'t see one on their PDF.',
                'subject' => null,
                'body' => <<<'TEXT'
When you buy sheet music as a PDF (Trinity ebook store, Faber, Sheet Music Direct etc.), the publisher stamps a personalised line on the page — usually the buyer's name, email and an order number. It's the publisher's anti-piracy mechanism. On Trinity ebooks it reads "Prepared exclusively for [name] ([email]) Order: [number]" on the title or copyright page.

If you genuinely can't see one, check the title or copyright pages first — that's the most common spot. If there's still nothing, keep the order confirmation email handy in case the examiner queries it.
TEXT,
            ],
            [
                'id' => 'dg-submission-link-not-working',
                'audience' => 'DG Exam',
                'title' => 'Submission link not working / option missing on profile',
                'when' => 'Use when a teacher reports that the submission link in Trinity\'s confirmation email isn\'t working, or the submission option isn\'t showing on their MyTrinity profile. Open with a "still having trouble?" check — Trinity sometimes fixes things silently without flagging it. If still stuck, point them at MyTrinity to find the candidate manually under Submissions.',
                'subject' => null,
                'body' => <<<'TEXT'
Hi [Name],

Are you still having trouble with this, or has it sorted itself out in the meantime? Sometimes Trinity gets it working their end without flagging it back to me.

If you're still stuck, try this: head to https://my-trinity.trinitycollege.com/, log in, then click Submissions at the top. You should see [Student name]'s name in the list — drill into it and click View Submission, and the upload option should be available from there.

Let me know how you get on.

Thanks,
Paul
TEXT,
            ],
        ];

        return Inertia::render('admin/QuickReplies/Index', [
            'templates' => $templates,
        ]);
    }
}
