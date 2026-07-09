<?php

// app/Console/Commands/InspectContact.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * InspectContact
 * --------------
 * READ-ONLY. Dumps every row that references an exam_contact so a human can
 * see the true linkage before merging duplicates. Nothing is written.
 *
 * Contact records can be referenced from many places, and two of them
 * disagree in the admin UI: the Orders LIST keys off orders.created_by_contact_id
 * (a direct FK) while a contact's Orders TAB reads the order_contacts pivot.
 * That's why a contact can be the applicant on an order in one view and show
 * zero orders in another. This command prints both so the gap is visible.
 *
 *   sail artisan contacts:inspect 3 37
 */
class InspectContact extends Command
{
    protected $signature = 'contacts:inspect {id* : One or more exam_contact ids}';

    protected $description = 'READ-ONLY: dump every reference to a contact (entries, orders, pivots, emails, types)';

    public function handle(): int
    {
        foreach ($this->argument('id') as $id) {
            $this->dumpContact((int) $id);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function dumpContact(int $id): void
    {
        $contact = ExamContact::find($id);

        if (! $contact) {
            $this->error("Contact #{$id} not found.");
            return;
        }

        $this->info("── Contact #{$id}: {$contact->name} <".($contact->email ?? 'no email').">");
        $this->line('   user_id: '.($contact->user_id ?? '—'));

        $types = DB::table('contact_types')->where('exam_contact_id', $id)->pluck('type')->all();
        $this->line('   types:   '.($types ? implode(', ', $types) : '—'));

        $emails = DB::table('contact_emails')->where('exam_contact_id', $id)->pluck('email')->all();
        $this->line('   emails:  '.($emails ? implode(', ', $emails) : '—'));

        // exam_entries — as teacher and as submitter
        $asTeacher = DB::table('exam_entries')
            ->where('teacher_contact_id', $id)
            ->select('id', 'candidate_name', 'candidate_number', 'order_id')
            ->get();
        $this->line('   exam_entries (teacher_contact_id): '.$asTeacher->count());
        foreach ($asTeacher as $e) {
            $this->line("     · entry#{$e->id}  {$e->candidate_name}  ({$e->candidate_number})  order_id={$e->order_id}");
        }

        $asSubmitter = DB::table('exam_entries')
            ->where('submitter_contact_id', $id)
            ->select('id', 'candidate_name', 'order_id')
            ->get();
        $this->line('   exam_entries (submitter_contact_id): '.$asSubmitter->count());
        foreach ($asSubmitter as $e) {
            $this->line("     · entry#{$e->id}  {$e->candidate_name}  order_id={$e->order_id}");
        }

        // students where this contact is the teacher
        $students = DB::table('students')
            ->where('teacher_contact_id', $id)
            ->select('id', 'first_name', 'last_name')
            ->get();
        $this->line('   students (teacher_contact_id): '.$students->count());
        foreach ($students as $s) {
            $this->line("     · student#{$s->id}  {$s->first_name} {$s->last_name}");
        }

        // orders via the direct FK (what the Orders LIST + pending Applicant use)
        $ordersFk = DB::table('orders')
            ->where('created_by_contact_id', $id)
            ->select('id', 'trinity_order_number', 'candidates', 'requested_start_date')
            ->get();
        $this->line('   orders (created_by_contact_id — Orders list / pending Applicant): '.$ordersFk->count());
        foreach ($ordersFk as $o) {
            $this->line("     · order#{$o->id}  {$o->trinity_order_number}  cands={$o->candidates}  start={$o->requested_start_date}");
        }

        // orders via the pivot (what the contact detail Orders TAB uses)
        $ordersPivot = DB::table('order_contacts')
            ->join('orders', 'orders.id', '=', 'order_contacts.order_id')
            ->where('order_contacts.exam_contact_id', $id)
            ->select('orders.id', 'orders.trinity_order_number', 'order_contacts.role_in_order')
            ->get();
        $this->line('   orders (order_contacts pivot — contact Orders tab): '.$ordersPivot->count());
        foreach ($ordersPivot as $o) {
            $this->line("     · order#{$o->id}  {$o->trinity_order_number}  role={$o->role_in_order}");
        }
    }
}
