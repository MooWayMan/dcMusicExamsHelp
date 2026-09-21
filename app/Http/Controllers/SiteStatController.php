<?php

// app/Http/Controllers/SiteStatController.php

namespace App\Http\Controllers;

use App\Services\SiteStats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SiteStatController extends Controller
{
    public function store(Request $request, SiteStats $stats): Response
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in([SiteStats::KIND_PAGE, SiteStats::KIND_EVENT])],
            'url' => ['required', 'string', 'max:500'],
            'event' => ['nullable', 'required_if:kind,'.SiteStats::KIND_EVENT, 'string', 'regex:/^[a-z0-9_]{1,64}$/'],
            'detail' => ['nullable', 'string', 'max:100'],
        ]);

        $stats->recordHit($request, $data['kind'], $data['url'], $data['event'] ?? null, $data['detail'] ?? null);

        return response()->noContent();
    }
}
