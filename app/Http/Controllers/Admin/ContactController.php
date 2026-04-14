<?php
// app/Http/Controllers/Admin/ContactController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $query = ExamContact::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $contacts = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Contacts/Index', [
            'contacts' => $contacts,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }
}