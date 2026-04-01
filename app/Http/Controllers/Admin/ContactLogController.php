<?php

// app/Http/Controllers/Admin/ContactLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactLogController extends Controller
{
    /**
     * Store a new contact log for a teacher.
     */
    public function store(Request $request, User $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'contact_type' => 'required|string|in:email,phone,face_to_face,other',
            'direction' => 'required|string|in:inbound,outbound',
            'subject' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'contacted_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $teacher->contactLogs()->create($validated);

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Contact log added.');
    }

    /**
     * Delete a contact log entry.
     */
    public function destroy(User $teacher, ContactLog $contactLog): RedirectResponse
    {
        // Ensure the log belongs to this teacher
        if ($contactLog->user_id !== $teacher->id) {
            abort(403);
        }

        $contactLog->delete();

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Contact log removed.');
    }
}
