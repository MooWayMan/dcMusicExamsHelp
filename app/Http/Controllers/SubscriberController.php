<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    /**
     * Store a new subscriber (AJAX endpoint).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'nullable|string|in:teacher,parent,student',
            'source' => 'nullable|string|max:50',
        ]);

        // Check if already subscribed
        $existing = Subscriber::where('email', $validated['email'])->first();

        if ($existing) {
            // If they unsubscribed before, re-subscribe them
            if ($existing->unsubscribed_at) {
                $existing->update([
                    'name' => $validated['name'],
                    'role' => $validated['role'] ?? $existing->role,
                    'unsubscribed_at' => null,
                    'subscribed_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! You have been re-subscribed.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed. Thank you!',
            ]);
        }

        Subscriber::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? null,
            'source' => $validated['source'] ?? 'website',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing!',
        ]);
    }
}
