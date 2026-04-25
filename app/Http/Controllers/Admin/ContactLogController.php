<?php

// app/Http/Controllers/Admin/ContactLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactLog;
use App\Models\ExamContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactLogController extends Controller
{
    /**
     * Store a new contact log for a contact.
     */
    public function store(Request $request, ExamContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'contact_type' => 'required|string|in:email,phone,face_to_face,other',
            'direction' => 'required|string|in:inbound,outbound',
            'subject' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'contacted_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $contact->contactLogs()->create($validated);

        return redirect()->route('admin.contacts.show', $contact)
            ->with('success', 'Contact log added.');
    }

    /**
     * Delete a contact log entry.
     */
    public function destroy(ExamContact $contact, ContactLog $contactLog): RedirectResponse
    {
        if ($contactLog->exam_contact_id !== $contact->id) {
            abort(403);
        }

        $contactLog->delete();

        return redirect()->route('admin.contacts.show', $contact)
            ->with('success', 'Contact log removed.');
    }
}
