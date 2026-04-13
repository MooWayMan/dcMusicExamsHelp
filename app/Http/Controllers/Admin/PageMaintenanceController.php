<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageMaintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageMaintenanceController extends Controller
{
    public function index()
    {
        // Ensure all maintainable pages have a database row
        PageMaintenance::seed();

        $pages = PageMaintenance::orderBy('page_name')->get();

        return Inertia::render('Admin/PageMaintenance', [
            'pages' => $pages,
        ]);
    }

    public function toggle(Request $request, PageMaintenance $page)
    {
        $page->update([
            'is_active' => ! $page->is_active,
        ]);

        $status = $page->is_active ? 'in maintenance mode' : 'back online';

        return back()->with('success', "{$page->page_name} is now {$status}.");
    }

    public function updateMessage(Request $request, PageMaintenance $page)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $page->update([
            'message' => $request->message,
        ]);

        return back()->with('success', "Message updated for {$page->page_name}.");
    }
}
