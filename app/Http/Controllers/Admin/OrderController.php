<?php

// app/Http/Controllers/Admin/OrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
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
            'teacher_name' => $order->teacher->name ?? '—',
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
            'requested_start_date' => $order->requested_start_date?->format('d M Y'),
            'exam_entries_count' => $order->exam_entries_count,
        ]);

        // Summary stats for the top of the page
        $summaryQuery = Order::query();
        if ($search) {
            $summaryQuery->where(function ($q) use ($search) {
                $q->where('trinity_order_number', 'ilike', "%{$search}%")
                  ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'ilike', "%{$search}%"));
            });
        }
        if ($method) $summaryQuery->where('delivery_method', $method);
        if ($status) $summaryQuery->where('order_status', $status);

        $summary = [
            'total_orders' => $summaryQuery->count(),
            'total_commission' => number_format($summaryQuery->sum('commission_amount'), 2),
            'total_candidates' => $summaryQuery->sum('candidates'),
        ];

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'method' => $method,
                'status' => $status,
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
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
}
