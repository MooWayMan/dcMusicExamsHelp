<?php

// app/Http/Controllers/PiecePlanController.php

namespace App\Http\Controllers;

use App\Models\PiecePlan;
use App\Services\PiecePlans;
use App\Services\SyllabusFacets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher's Piece tracker (/dashboard/pieces). HTTP only — the rules
 * live in App\Services\PiecePlans.
 */
class PiecePlanController extends Controller
{
    public function __construct(private readonly PiecePlans $plans) {}

    public function index(Request $request, SyllabusFacets $facets): Response
    {
        $user = $request->user();
        abort_unless($this->plans->canUse($user), 403);

        return Inertia::render('dashboard/PiecePlans', [
            'plans' => $this->plans->forUser($user),
            'candidates' => $this->plans->candidates($user),
            'sectionLabels' => PiecePlans::SECTION_LABELS,
            'suggestions' => PiecePlans::SUGGESTIONS,
            'maxItems' => PiecePlans::MAX_ITEMS,
            ...$facets->forDropdowns(),
        ]);
    }

    public function syllabus(Request $request): JsonResponse
    {
        abort_unless($this->plans->canUse($request->user()), 403);

        $data = $request->validate([
            'stream' => ['required', 'string'],
            'instrument' => ['required', 'string'],
            'grade' => ['required', 'string'],
        ]);

        return response()->json(
            $this->plans->syllabusOptions($data['stream'], $data['instrument'], $data['grade'])
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->plans->canUse($user), 403);

        $plan = $this->plans->create($user, $request->validate($this->plans->rules($request->input('exam_stream'))));

        return back()->with('success', "Plan added for {$plan->pupil_name}.");
    }

    public function update(Request $request, PiecePlan $plan): RedirectResponse
    {
        $this->authorisePlan($request, $plan);

        $this->plans->update($plan, $request->validate($this->plans->rules($request->input('exam_stream'))));

        return back()->with('success', "Saved {$plan->pupil_name}'s plan.");
    }

    public function destroy(Request $request, PiecePlan $plan): RedirectResponse
    {
        $this->authorisePlan($request, $plan);

        $name = $plan->pupil_name;
        $this->plans->delete($plan);

        return back()->with('success', "Removed {$name}'s plan.");
    }

    /** Someone else's plan is reported as not found, not as forbidden. */
    private function authorisePlan(Request $request, PiecePlan $plan): void
    {
        $user = $request->user();
        abort_unless($this->plans->canUse($user) && $this->plans->owns($user, $plan), 404);
    }
}
