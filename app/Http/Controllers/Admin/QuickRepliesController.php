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
        ];

        return Inertia::render('admin/QuickReplies/Index', [
            'templates' => $templates,
        ]);
    }
}
