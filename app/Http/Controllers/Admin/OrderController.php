<?php

// app/Http/Controllers/Admin/OrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with(['teacher:id,name', 'school:id,name'])
            ->withCount('examEntries');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trinity_order_number', 'ilike', "%{$search}%")
                  ->orWhere('venue', 'ilike', "%{$search}%")
                  ->orWhere('applicant_name', 'ilike', "%{$search}%")
                  ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'ilike', "%{$search}%"))
                  ->orWhereHas('school', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"));
            });
        }

        // Delivery method filter
        if ($method = $request->input('method')) {
            $query->where('delivery_method', $method);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        // Paid filter — has Trinity remitted commission on this order yet?
        $paid = $request->input('paid');
        if ($paid === 'paid') {
            $query->paid();
        } elseif ($paid === 'unpaid') {
            $query->unpaid();
        }

        // Time period filter
        $period = $request->input('period');
        if ($period) {
            $now = Carbon::now();
            match ($period) {
                'this_quarter' => $query->where('created_at', '>=', $now->copy()->startOfQuarter()),
                'last_quarter' => $query->whereBetween('created_at', [
                    $now->copy()->subQuarter()->startOfQuarter(),
                    $now->copy()->subQuarter()->endOfQuarter(),
                ]),
                'this_year' => $query->where('created_at', '>=', $now->copy()->startOfYear()),
                'last_12' => $query->where('created_at', '>=', $now->copy()->subMonths(12)),
                default => null,
            };
        }

        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');
        $allowedSorts = ['trinity_order_number', 'candidates', 'commission_amount', 'order_status', 'delivery_method', 'subject_area', 'requested_start_date', 'created_at'];

        if ($sortBy === 'teacher') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'orders.user_id')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'school') {
            $query->orderBy(
                School::select('name')
                    ->whereColumn('schools.id', 'orders.school_id')
                    ->limit(1),
                $sortDir
            );
        } elseif (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $orders = $query->paginate(15)->withQueryString();

        $orders->through(fn ($order) => [
            'id' => $order->id,
            'trinity_order_number' => $order->trinity_order_number,
            'teacher_name' => $order->teacher->name ?? $order->applicant_name ?? '—',
            'teacher_id' => $order->user_id,
            'school_name' => $order->school->name ?? '—',
            'school_id' => $order->school_id,
            'delivery_method' => $order->isDigital() ? 'DG' : 'F2F',
            'subject_area' => $order->subject_area,
            'candidates' => $order->candidates,
            'venue' => $order->venue,
            'order_status' => $order->order_status,
            'commission_rate' => $order->commission_rate . '%',
            'commission_amount' => number_format($order->commission_amount, 2),
            'commission_paid_at' => $order->commission_paid_at?->format('d M Y'),
            'commission_paid_amount' => $order->commission_paid_amount
                ? number_format($order->commission_paid_amount, 2)
                : null,
            'is_paid' => $order->isPaid(),
            'requested_start_date' => $order->requested_start_date?->format('d M Y'),
            'exam_entries_count' => $order->exam_entries_count,
        ]);

        // Summary stats for the top of the page
        $summaryQuery = Order::query();
        if ($search) {
            $summaryQuery->where(function ($q) use ($search) {
                $q->where('trinity_order_number', 'ilike', "%{$search}%")
                  ->orWhere('applicant_name', 'ilike', "%{$search}%")
                  ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'ilike', "%{$search}%"));
            });
        }
        if ($method) $summaryQuery->where('delivery_method', $method);
        if ($status) $summaryQuery->where('order_status', $status);
        if ($paid === 'paid') $summaryQuery->paid();
        if ($paid === 'unpaid') $summaryQuery->unpaid();
        if ($period) {
            $now = Carbon::now();
            match ($period) {
                'this_quarter' => $summaryQuery->where('created_at', '>=', $now->copy()->startOfQuarter()),
                'last_quarter' => $summaryQuery->whereBetween('created_at', [
                    $now->copy()->subQuarter()->startOfQuarter(),
                    $now->copy()->subQuarter()->endOfQuarter(),
                ]),
                'this_year' => $summaryQuery->where('created_at', '>=', $now->copy()->startOfYear()),
                'last_12' => $summaryQuery->where('created_at', '>=', $now->copy()->subMonths(12)),
                default => null,
            };
        }

        // Clone so we don't mutate the main summary with extra wheres
        $paidSummaryQuery = (clone $summaryQuery)->paid();
        $unpaidSummaryQuery = (clone $summaryQuery)->unpaid();

        $summary = [
            'total_orders' => $summaryQuery->count(),
            'total_commission' => number_format($summaryQuery->sum('commission_amount'), 2),
            'total_candidates' => $summaryQuery->sum('candidates'),
            'total_paid' => number_format($paidSummaryQuery->sum('commission_paid_amount'), 2),
            'total_unpaid' => number_format($unpaidSummaryQuery->sum('commission_amount'), 2),
        ];

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'method' => $method,
                'status' => $status,
                'paid' => $paid,
                'period' => $period,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Orders/Create', [
            'teachers' => User::where('role', 'teacher')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'instruments' => Instrument::orderBy('name')->get(['id', 'name']),
            'options' => [
                'delivery_methods' => [
                    ['value' => 'Digital', 'label' => 'Digital (DG)', 'default_rate' => 20],
                    ['value' => 'Default', 'label' => 'Face-to-Face (F2F)', 'default_rate' => 28],
                    ['value' => 'DigitalTheory', 'label' => 'Digital Theory', 'default_rate' => 12.5],
                ],
                'subject_areas' => ['Music', 'Rock and Pop', 'Drama', 'Rockschool', 'Dance', 'Art'],
                'order_statuses' => ['Submitted', 'In Progress', 'Delivered', 'Cancelled'],
                'grades' => ['Initial', '1', '2', '3', '4', '5', '6', '7', '8', 'ATCL', 'LTCL', 'FTCL'],
                'results' => ['Distinction', 'Merit', 'Pass', 'Below Pass'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'trinity_order_number' => 'required|string|max:255|unique:orders,trinity_order_number',
            'delivery_method' => 'required|string|max:50',
            'subject_area' => 'nullable|string|max:100',
            'order_status' => 'required|string|max:50',
            'requested_start_date' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
            'school_id' => 'nullable|exists:schools,id',
            'venue' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_amount' => 'nullable|numeric|min:0',
            'applicant_name' => 'nullable|string|max:255',
            'applicant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',

            'entries' => 'required|array|min:1',
            'entries.*.candidate_name' => 'required|string|max:255',
            'entries.*.candidate_number' => 'nullable|string|max:100',
            'entries.*.instrument_id' => 'nullable|exists:instruments,id',
            'entries.*.grade' => 'nullable|string|max:50',
            'entries.*.exam_date' => 'nullable|date',
            'entries.*.score' => 'nullable|integer|min:0|max:100',
            'entries.*.result' => 'nullable|string|max:50',
            'entries.*.fee' => 'nullable|numeric|min:0',
            'entries.*.notes' => 'nullable|string',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $entries = $validated['entries'];
            unset($validated['entries']);

            $validated['candidates'] = count($entries);

            $order = Order::create($validated);

            foreach ($entries as $entry) {
                $order->examEntries()->create(array_merge($entry, [
                    'subject_area' => $validated['subject_area'] ?? null,
                    'delivery_method' => $validated['delivery_method'],
                    'source' => 'manual',
                ]));
            }

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order {$order->trinity_order_number} added with {$order->candidates} candidate(s).");
    }

    public function show(Order $order): Response
    {
        $order->load([
            'teacher:id,name,email,phone',
            'school:id,name,city',
            'examEntries' => fn ($q) => $q->with(['student:id,first_name,last_name', 'instrument:id,name']),
        ]);

        $orderData = [
            'id' => $order->id,
            'trinity_order_number' => $order->trinity_order_number,
            'delivery_method' => $order->isDigital() ? 'DG' : 'F2F',
            'delivery_method_raw' => $order->delivery_method,
            'subject_area' => $order->subject_area,
            'candidates' => $order->candidates,
            'venue' => $order->venue,
            'order_status' => $order->order_status,
            'commission_rate' => $order->commission_rate,
            'commission_amount' => number_format($order->commission_amount, 2),
            'requested_start_date' => $order->requested_start_date?->format('d M Y'),
            'notes' => $order->notes,
            'created_at' => $order->created_at->format('d M Y'),
            'teacher' => $order->teacher ? [
                'id' => $order->teacher->id,
                'name' => $order->teacher->name,
                'email' => $order->teacher->email,
                'phone' => $order->teacher->phone,
            ] : null,
            'school' => $order->school ? [
                'id' => $order->school->id,
                'name' => $order->school->name,
                'city' => $order->school->city,
            ] : null,
            'exam_entries' => $order->examEntries->map(fn ($e) => [
                'id' => $e->id,
                'student_name' => $e->student ? "{$e->student->first_name} {$e->student->last_name}" : '—',
                'instrument' => $e->instrument->name ?? '—',
                'grade' => $e->grade,
                'result' => $e->result ?? 'Pending',
                'exam_date' => $e->exam_date?->format('d M Y') ?? '—',
            ]),
        ];

        return Inertia::render('admin/Orders/Show', [
            'order' => $orderData,
        ]);
    }

    public function edit(Order $order): Response
    {
        $order->load(['examEntries']);

        return Inertia::render('admin/Orders/Edit', [
            'order' => [
                'id' => $order->id,
                'trinity_order_number' => $order->trinity_order_number,
                'delivery_method' => $order->delivery_method,
                'subject_area' => $order->subject_area,
                'order_status' => $order->order_status,
                'requested_start_date' => $order->requested_start_date?->format('Y-m-d'),
                'user_id' => $order->user_id,
                'school_id' => $order->school_id,
                'venue' => $order->venue,
                'commission_rate' => $order->commission_rate,
                'commission_amount' => $order->commission_amount,
                'applicant_name' => $order->applicant_name,
                'applicant_email' => $order->applicant_email,
                'notes' => $order->notes,
                'entries' => $order->examEntries->map(fn ($e) => [
                    'id' => $e->id,
                    'candidate_name' => $e->candidate_name,
                    'candidate_number' => $e->candidate_number,
                    'instrument_id' => $e->instrument_id,
                    'grade' => $e->grade,
                    'exam_date' => $e->exam_date?->format('Y-m-d'),
                    'score' => $e->score,
                    'result' => $e->result,
                    'fee' => $e->fee,
                    'notes' => $e->notes,
                ])->values(),
            ],
            'teachers' => User::where('role', 'teacher')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'instruments' => Instrument::orderBy('name')->get(['id', 'name']),
            'options' => [
                'delivery_methods' => [
                    ['value' => 'Digital', 'label' => 'Digital (DG)', 'default_rate' => 20],
                    ['value' => 'Default', 'label' => 'Face-to-Face (F2F)', 'default_rate' => 28],
                    ['value' => 'DigitalTheory', 'label' => 'Digital Theory', 'default_rate' => 12.5],
                ],
                'subject_areas' => ['Music', 'Rock and Pop', 'Drama', 'Rockschool', 'Dance', 'Art'],
                'order_statuses' => ['Submitted', 'In Progress', 'Delivered', 'Cancelled'],
                'grades' => ['Initial', '1', '2', '3', '4', '5', '6', '7', '8', 'ATCL', 'LTCL', 'FTCL'],
                'results' => ['Distinction', 'Merit', 'Pass', 'Below Pass'],
            ],
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'trinity_order_number' => 'required|string|max:255|unique:orders,trinity_order_number,' . $order->id,
            'delivery_method' => 'required|string|max:50',
            'subject_area' => 'nullable|string|max:100',
            'order_status' => 'required|string|max:50',
            'requested_start_date' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
            'school_id' => 'nullable|exists:schools,id',
            'venue' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_amount' => 'nullable|numeric|min:0',
            'applicant_name' => 'nullable|string|max:255',
            'applicant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',

            'entries' => 'required|array|min:1',
            'entries.*.id' => 'nullable|integer|exists:exam_entries,id',
            'entries.*.candidate_name' => 'required|string|max:255',
            'entries.*.candidate_number' => 'nullable|string|max:100',
            'entries.*.instrument_id' => 'nullable|exists:instruments,id',
            'entries.*.grade' => 'nullable|string|max:50',
            'entries.*.exam_date' => 'nullable|date',
            'entries.*.score' => 'nullable|integer|min:0|max:100',
            'entries.*.result' => 'nullable|string|max:50',
            'entries.*.fee' => 'nullable|numeric|min:0',
            'entries.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $entries = $validated['entries'];
            unset($validated['entries']);

            $validated['candidates'] = count($entries);
            $order->update($validated);

            foreach ($entries as $entry) {
                $entryData = array_merge($entry, [
                    'subject_area' => $validated['subject_area'] ?? null,
                    'delivery_method' => $validated['delivery_method'],
                ]);

                if (! empty($entry['id'])) {
                    // Update existing entry — only if it belongs to this order
                    $order->examEntries()
                        ->where('id', $entry['id'])
                        ->update(collect($entryData)->except('id')->toArray());
                } else {
                    // New entry
                    $order->examEntries()->create(array_merge($entryData, ['source' => 'manual']));
                }
            }
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order {$order->trinity_order_number} updated.");
    }
}
