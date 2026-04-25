<?php

namespace App\Console\Commands;

use App\Models\ContactLog;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

class ExportLiveData extends Command
{
    protected $signature = 'data:export';
    protected $description = 'Export all live data as JSON (run on Cloud, copy output to local)';

    public function handle(): int
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'instruments' => Instrument::all()->toArray(),
            'schools' => School::all()->toArray(),
            'users' => User::all()->makeVisible('password')->toArray(),
            'students' => Student::all()->toArray(),
            'orders' => Order::all()->toArray(),
            'exam_entries' => ExamEntry::all()->toArray(),
            'contact_logs' => ContactLog::all()->toArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Write to storage so it can be downloaded
        $filename = 'exports/live-data-' . now()->format('Y-m-d') . '.json';
        \Storage::disk('local')->put($filename, $json);

        $this->info("Exported to storage/app/private/{$filename}");
        $this->info('Tables exported: instruments, schools, users, students, orders, exam_entries, contact_logs');
        $this->info('Total size: ' . number_format(strlen($json)) . ' bytes');

        return Command::SUCCESS;
    }
}
