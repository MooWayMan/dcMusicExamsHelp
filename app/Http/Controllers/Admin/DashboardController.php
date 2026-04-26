<?php

// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactLog;
use App\Models\ExamContact;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Summary stats — teachers now come from the unified contacts model.
        $totalTeachers = ExamContact::withType('teacher')->count();
        $totalStudents = Student::count();
        $totalOrders = Order::count();
        $totalSchools = School::count();

        // Commission stats
        $totalCommission = Order::sum('commission_amount');
        $dgCommission = Order::where('delivery_method', 'Digital')->sum('commission_amount');
        $f2fCommission = Order::where('delivery_method', 'Default')->sum('commission_amount');

        // Order stats
        $dgOrders = Order::where('delivery_method', 'Digital')->count();
        $f2fOrders = Order::where('delivery_method', 'Default')->count();
        $totalCandidates = Order::sum('candidates');

        // Recent orders — teacher resolves via the unified contact only
        // (legacy Order::teacher relation + orders.user_id were dropped
        // 2026_04_26 alongside the rest of the unified contacts refactor).
        $recentOrders = Order::with(['createdByContact:id,name', 'school:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'trinity_order_number' => $order->trinity_order_number,
                'teacher_contact_id' => $order->created_by_contact_id,
                'teacher_name' => $order->createdByContact?->name ?? 'Unknown',
                'school_name' => $order->school->name ?? '—',
                'delivery_method' => $order->isDigital() ? 'DG' : 'F2F',
                'candidates' => $order->candidates,
                'commission_amount' => number_format($order->commission_amount, 2),
                'order_status' => $order->order_status,
                'requested_start_date' => $order->requested_start_date?->format('d M Y'),
            ]);

        // Recent contact logs — unified contacts model only.
        $recentContacts = ContactLog::with(['contact:id,name'])
            ->latest('contacted_at')
            ->take(5)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'teacher_contact_id' => $log->exam_contact_id,
                'teacher_name' => $log->contact?->name ?? 'Unknown',
                'contact_type' => $log->contact_type,
                'direction' => $log->direction,
                'subject' => $log->subject,
                'contacted_at' => $log->contacted_at?->format('d M Y'),
            ]);

        // Teachers who haven't been contacted recently (over 30 days)
        $staleTeachers = ExamContact::withType('teacher')
            ->whereDoesntHave('contactLogs', function ($query) {
                $query->where('contacted_at', '>=', now()->subDays(30));
            })
            ->select('id', 'name', 'email', 'phone')
            ->take(5)
            ->get();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'totalTeachers' => $totalTeachers,
                'totalStudents' => $totalStudents,
                'totalOrders' => $totalOrders,
                'totalSchools' => $totalSchools,
                'totalCommission' => number_format($totalCommission, 2),
                'dgCommission' => number_format($dgCommission, 2),
                'f2fCommission' => number_format($f2fCommission, 2),
                'dgOrders' => $dgOrders,
                'f2fOrders' => $f2fOrders,
                'totalCandidates' => $totalCandidates,
            ],
            'recentOrders' => $recentOrders,
            'recentContacts' => $recentContacts,
            'staleTeachers' => $staleTeachers,
        ]);
    }
}
