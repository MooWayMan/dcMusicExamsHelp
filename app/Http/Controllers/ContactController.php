<?php

namespace App\Http\Controllers;

use App\Mail\ContactAutoReply;
use App\Mail\ContactFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Send HTML email notification to Paul
        try {
            Mail::to('musicexams@musicexams.help')
                ->send(new ContactFormSubmission(
                    senderName: $validated['name'],
                    senderEmail: $validated['email'],
                    senderSubject: $validated['subject'] ?? null,
                    senderMessage: $validated['message'],
                ));
        } catch (\Exception $e) {
            Log::error('Contact form email failed: ' . $e->getMessage());
        }

        // Send auto-reply to the person who contacted us
        try {
            Mail::to($validated['email'])
                ->send(new ContactAutoReply(
                    senderName: $validated['name'],
                    senderSubject: $validated['subject'] ?? null,
                ));
        } catch (\Exception $e) {
            Log::error('Contact auto-reply failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Message sent successfully.');
    }
}
