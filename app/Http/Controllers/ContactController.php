<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Send email notification
        try {
            Mail::raw(
                "New contact form submission from musicExams.help\n\n" .
                "Name: {$validated['name']}\n" .
                "Email: {$validated['email']}\n" .
                "Subject: " . ($validated['subject'] ?? 'No subject') . "\n\n" .
                "Message:\n{$validated['message']}",
                function ($mail) use ($validated) {
                    $mail->to('musicexams@musicexams.help')
                        ->replyTo($validated['email'], $validated['name'])
                        ->subject('Contact Form: ' . ($validated['subject'] ?? 'New enquiry from ' . $validated['name']));
                }
            );
        } catch (\Exception $e) {
            Log::error('Contact form email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Message sent successfully.');
    }
}
