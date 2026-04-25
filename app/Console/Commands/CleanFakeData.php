<?php

namespace App\Console\Commands;

use App\Models\ContactLog;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use Illuminate\Console\Command;

class CleanFakeData extends Command
{
    protected $signature = 'data:clean-fake {--dry-run : Show what would be deleted without actually deleting} {--force : Skip confirmation prompt}';
    protected $description = 'Remove ALL seeder/fake data: teachers, schools, students, orders, entries, contact logs';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. Fake teachers = exam_contacts tagged 'teacher' with @example.com emails
        $fakeTeachers = ExamContact::withType('teacher')
            ->where('email', 'ilike', '%@example.com')
            ->get();
        $fakeTeacherIds = $fakeTeachers->pluck('id');

        // 2. Fake orders = orders linked to fake teacher contacts (via order_contacts pivot)
        //    OR with dates before 2026
        $fakeOrderIdsFromContacts = \DB::table('order_contacts')
            ->whereIn('exam_contact_id', $fakeTeacherIds)
            ->pluck('order_id');

        $fakeOrders = Order::withTrashed()
            ->where(function ($q) use ($fakeOrderIdsFromContacts) {
                $q->whereIn('id', $fakeOrderIdsFromContacts)
                  ->orWhere('requested_start_date', '<', '2026-01-01');
            })
            ->get();
        $fakeOrderIds = $fakeOrders->pluck('id');

        // 3. Fake entries = entries tied to fake orders
        $fakeEntryCount = ExamEntry::whereIn('order_id', $fakeOrderIds)->count();

        // 4. Fake contact logs = logs for fake teacher contacts
        $fakeContactLogs = ContactLog::whereIn('exam_contact_id', $fakeTeacherIds)->count();

        // 5. Fake students = students owned by fake teacher contacts
        $fakeStudents = Student::whereIn('teacher_contact_id', $fakeTeacherIds)->count();

        // 6. Schools that only have fake teachers attached (no real teachers)
        // We'll check after deletion which schools have no teachers left

        $this->info('=== Fake Data Summary ===');
        $this->info("Teachers (@example.com): {$fakeTeachers->count()}");
        $this->info("Orders: {$fakeOrders->count()}");
        $this->info("Exam entries: {$fakeEntryCount}");
        $this->info("Contact logs: {$fakeContactLogs}");
        $this->info("Students: {$fakeStudents}");

        if ($dryRun) {
            $this->warn('Dry run — nothing deleted.');
            $this->newLine();
            $this->info('Fake teachers:');
            $this->table(
                ['Name', 'Email'],
                $fakeTeachers->map(fn ($t) => [$t->name, $t->email])->toArray()
            );
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('This will permanently delete all the above. Continue?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        // Delete in dependency order
        $this->info('Deleting exam entries...');
        ExamEntry::whereIn('order_id', $fakeOrderIds)->forceDelete();

        $this->info('Deleting orders...');
        Order::withTrashed()->whereIn('id', $fakeOrderIds)->forceDelete();

        $this->info('Deleting contact logs...');
        ContactLog::whereIn('exam_contact_id', $fakeTeacherIds)->delete();

        $this->info('Deleting students...');
        Student::whereIn('teacher_contact_id', $fakeTeacherIds)->delete();

        // Detach pivot relationships before deleting teacher contacts
        foreach ($fakeTeachers as $teacher) {
            $teacher->schools()->detach();
            $teacher->instruments()->detach();
            // contact_types pivot, contact_emails, order_contacts
            \DB::table('contact_types')->where('exam_contact_id', $teacher->id)->delete();
            \DB::table('contact_emails')->where('exam_contact_id', $teacher->id)->delete();
            \DB::table('order_contacts')->where('exam_contact_id', $teacher->id)->delete();
        }

        $this->info('Deleting fake teacher contacts...');
        ExamContact::whereIn('id', $fakeTeacherIds)->forceDelete();

        // Clean up any remaining orphan students with no exam entries
        $orphanStudents = Student::whereDoesntHave('examEntries')->count();
        if ($orphanStudents > 0) {
            $this->info("Deleting {$orphanStudents} orphan students (no exam entries)...");
            Student::whereDoesntHave('examEntries')->delete();
        }

        // Check for schools with no teachers — report but don't auto-delete
        $emptySchools = School::whereDoesntHave('teachers')->get();
        if ($emptySchools->isNotEmpty()) {
            $this->warn("Schools with no teachers remaining: {$emptySchools->pluck('name')->implode(', ')}");
            $this->info('These were not deleted — some may be real schools. Delete manually if needed.');
        }

        $this->info('Done. All fake seeder data removed.');

        return Command::SUCCESS;
    }
}
