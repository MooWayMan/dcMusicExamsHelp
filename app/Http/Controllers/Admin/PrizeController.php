<?php

// app/Http/Controllers/Admin/PrizeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PrizeLedger;
use Inertia\Inertia;
use Inertia\Response;

class PrizeController extends Controller
{
    public function index(PrizeLedger $ledger): Response
    {
        return Inertia::render('admin/Prizes/Index', [
            'prizes' => $ledger->rows()->all(),
        ]);
    }
}
