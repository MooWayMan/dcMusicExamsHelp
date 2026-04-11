<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Student;
use Illuminate\Console\Command;

class CleanFakeData extends Command
{
    protected $signature = 'data:clean-fake {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Remove seeder/fake data (orders and entries with dates before 2026)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find orders before 2026 (these are from the FakeDataSeeder)
        $fakeOrders = Order::withTrashed()
            ->where('requested_start_date', '<', '2026-01-01')
            ->get();

        if ($fakeOrders->isEmpty()) {
            $this->info('No fake data found (no orders before 2026).');
            return Command::SUCCESS;
        }

        $fakeOrderIds = $fakeOrders->pluck('id');

        // Count entries tied to these orders
        $entryCount = ExamEntry::whereIn('order_id', $fakeOrderIds)->count();

        $this->info("Found {$fakeOrders->count()} orders before 2026 with {$entryCount} exam entries.");

        if ($dryRun) {
            $this->warn('Dry run — nothing deleted.');
            $this->table(
                ['Order #', 'Date', 'Method', 'Entries'],
                $fakeOrders->map(fn ($o) => [
                    $o->trinity_order_number ?? 'N/A',
                    $o->requested_start_date?->format('d M Y') ?? '—',
                    $o->delivery_method,
                    ExamEntry::where('order_id', $o->id)->count(),
                ])->toArray()
            );
            return Command::SUCCESS;
        }

        // Delete entries first, then orders
        $deletedEntries = ExamEntry::whereIn('order_id', $fakeOrderIds)->forceDelete();
        $deletedOrders = Order::withTrashed()->whereIn('id', $fakeOrderIds)->forceDelete();

        $this->info("Deleted {$deletedEntries} fake entries and {$deletedOrders} fake orders.");

        // Also clean up any students that were created by the seeder and have no remaining entries
        $orphanStudents = Student::whereDoesntHave('examEntries')->count();
        if ($orphanStudents > 0) {
            $this->info("Found {$orphanStudents} orphan students (no exam entries). Deleting...");
            Student::whereDoesntHave('examEntries')->delete();
        }

        $this->info('Done. Only real 2026+ data remains.');

        return Command::SUCCESS;
    }
}
