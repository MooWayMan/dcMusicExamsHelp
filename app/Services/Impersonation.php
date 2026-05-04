<?php

// app/Services/Impersonation.php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Admin "Login as user" impersonation.
 *
 * Used so Paul (admin) can verify what real teachers / parents / self-
 * candidates see on their dashboard — particularly to confirm that the
 * teacher → students linkage is correct on prod data.
 *
 * Flow:
 *   1. Admin hits POST /admin/users/{user}/impersonate
 *   2. We stash the admin's id in session under IMPERSONATOR_KEY
 *   3. We Auth::login($targetUser) — every subsequent request acts as them
 *   4. A banner is shown sitewide (via Inertia shared props) with a
 *      "Return to admin" button that hits POST /impersonate/leave
 *   5. Leave restores Auth to the original admin and clears the session key
 *
 * Guardrails:
 *   - Only admin users can start impersonation (route is behind 'admin'
 *     middleware AND we double-check here so it can't be bypassed)
 *   - Cannot impersonate another admin (privilege-escalation guard)
 *   - Cannot impersonate yourself (no-op + would corrupt the session)
 *   - Cannot start a new impersonation while already impersonating
 *     (defence-in-depth — caller should always leave first)
 *   - Every start writes an audit line to the Laravel log
 */
class Impersonation
{
    /** Session key holding the original admin user id. */
    public const IMPERSONATOR_KEY = 'impersonator_id';

    /**
     * Begin impersonating $target as the currently-authenticated admin.
     *
     * @throws AuthorizationException
     */
    public function start(User $target): void
    {
        $admin = Auth::user();

        if (! $admin || ! $admin->isAdmin()) {
            throw new AuthorizationException('Only admins can impersonate users.');
        }

        if (Session::has(self::IMPERSONATOR_KEY)) {
            throw new AuthorizationException('Already impersonating — leave the current session first.');
        }

        if ($target->is($admin)) {
            throw new AuthorizationException('You cannot impersonate yourself.');
        }

        if ($target->isAdmin()) {
            throw new AuthorizationException('You cannot impersonate another admin.');
        }

        Log::info('admin.impersonate.start', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_id' => $target->id,
            'target_email' => $target->email,
            'target_role' => $target->role,
        ]);

        Session::put(self::IMPERSONATOR_KEY, $admin->id);
        Auth::login($target);
        Session::regenerate();
        // Re-stash after regeneration — Session::regenerate() rotates the
        // id but preserves data; explicit put() here is belt + braces in
        // case the regen behaviour changes between Laravel minor versions.
        Session::put(self::IMPERSONATOR_KEY, $admin->id);
    }

    /**
     * Stop impersonating and restore the original admin session.
     *
     * Returns the admin user that was restored, or null if there was no
     * impersonation in progress (caller can decide how to respond).
     */
    public function leave(): ?User
    {
        $adminId = Session::pull(self::IMPERSONATOR_KEY);

        if (! $adminId) {
            return null;
        }

        $admin = User::find($adminId);

        if (! $admin) {
            // The original admin account has vanished mid-impersonation —
            // unrecoverable in a meaningful way. Log them out entirely so
            // we never leave an orphaned session masquerading as someone.
            Log::warning('admin.impersonate.leave.admin_missing', ['admin_id' => $adminId]);
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return null;
        }

        Log::info('admin.impersonate.leave', [
            'admin_id' => $admin->id,
            'restored_from_user_id' => Auth::id(),
        ]);

        Auth::login($admin);
        Session::regenerate();

        return $admin;
    }

    /** True when the current request is an admin impersonating another user. */
    public function isImpersonating(): bool
    {
        return Session::has(self::IMPERSONATOR_KEY);
    }

    /** The admin user who started the current impersonation, or null. */
    public function originalAdmin(): ?User
    {
        $id = Session::get(self::IMPERSONATOR_KEY);

        return $id ? User::find($id) : null;
    }
}
