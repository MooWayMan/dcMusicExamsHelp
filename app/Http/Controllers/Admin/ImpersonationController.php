<?php

// app/Http/Controllers/Admin/ImpersonationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * HTTP entry points for the Impersonation service.
 *
 * `start` lives behind the `admin` middleware (see routes/admin.php).
 * `leave` MUST live outside admin middleware — by the time the user
 * clicks "Return to admin" they are authenticated as a non-admin, so
 * the `admin` middleware would 403 them out of their own escape hatch.
 */
class ImpersonationController extends Controller
{
    public function __construct(private readonly Impersonation $impersonation) {}

    public function start(Request $request, User $user): RedirectResponse
    {
        $this->impersonation->start($user);

        return redirect()
            ->route('dashboard')
            ->with('success', "You are now logged in as {$user->name}. Use the banner at the top to return to your admin account.");
    }

    public function leave(Request $request): RedirectResponse
    {
        $admin = $this->impersonation->leave();

        if (! $admin) {
            return redirect()->route('login');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Returned to your admin account.');
    }
}
