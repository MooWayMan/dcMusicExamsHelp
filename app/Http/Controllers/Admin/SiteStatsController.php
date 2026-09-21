<?php

// app/Http/Controllers/Admin/SiteStatsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteStatsController extends Controller
{
    public function index(Request $request, SiteStats $stats): Response
    {
        return Inertia::render('admin/SiteStats/Index', [
            'stats' => $stats->summary((int) $request->query('days', SiteStats::DEFAULT_RANGE)),
            'ranges' => SiteStats::RANGES,
        ]);
    }
}
