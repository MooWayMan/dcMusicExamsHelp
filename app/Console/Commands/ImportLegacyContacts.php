<?php
// app/Console/Commands/ImportLegacyContacts.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyContacts extends Command
{
    protected $signature = 'contacts:import-legacy
                            {--dry-run : Preview changes without saving}
                            {--truncate : Clear exam_contacts before importing}
                            {--link-orders : Link applicant contacts to matching orders}';

    protected $description = 'Safely import applicant and teacher-name contacts from the legacy source database';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');
        $linkOrders = (bool) $this->option('link-orders');

        $source = DB::connection('source_pgsql');
        $target = DB::connection();

        if ($truncate) {
            if ($dryRun) {
                $this->warn('DRY RUN: would truncate exam_contacts before import.');
            } else {
                $target->table('exam_contacts')->truncate();
                $this->info('Truncated exam_contacts.');
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $linked = 0;

        // -------------------------------------------------
        // 1) Applicants from legacy orders
        // -------------------------------------------------
        $applicants = $source->table('orders')
            ->select('trinity_order_number', 'applicant_name', 'applicant_email')
            ->whereNotNull('applicant_name')
            ->where('applicant_name', '!=', '')
            ->distinct()
            ->orderBy('applicant_name')
            ->get();

        foreach ($applicants as $row) {
            $orderNumber = trim((string) $row->trinity_order_number);
            $name = trim((string) $row->applicant_name);
            $email = $row->applicant_email ? strtolower(trim((string) $row->applicant_email)) : null;

            if ($name === '') {
                $skipped++;
                continue;
            }

            $existing = null;

            if ($email) {
                $existing = ExamContact::whereRaw('LOWER(email) = ?', [$email])->first();
            }

            if (! $existing) {
                $existing = ExamContact::whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->where(function ($q) use ($email) {
                        if ($email) {
                            $q->whereNull('email')->orWhereRaw('LOWER(email) = ?', [$email]);
                        } else {
                            $q->whereNull('email');
                        }
                    })
                    ->first();
            }

            $payload = [
                'name' => $name,
                'email' => $email,
                'role' => 'applicant',
                'source' => 'legacy_db',
            ];

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would update applicant: {$name}" . ($email ? " <{$email}>" : ''));
                    $contact = $existing;
                    $updated++;
                } else {
                    $this->line("Would create applicant: {$name}" . ($email ? " <{$email}>" : ''));
                    $contact = null;
                    $created++;
                }

                if ($linkOrders && $orderNumber !== '') {
                    $this->line("Would link applicant '{$name}' to order {$orderNumber}");
                    $linked++;
                }

                continue;
            }

            if ($existing) {
                $existing->fill([
                    'name' => $existing->name ?: $payload['name'],
                    'email' => $existing->email ?: $payload['email'],
                    'role' => $existing->role ?: $payload['role'],
                    'source' => $existing->source ?: $payload['source'],
                ]);

                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }

                $contact = $existing;
            } else {
                $contact = ExamContact::create($payload);
                $created++;
            }

            if ($linkOrders && $orderNumber !== '') {
                $order = Order::where('trinity_order_number', $orderNumber)->first();

                if ($order) {
                    $order->contacts()->syncWithoutDetaching([
                        $contact->id => [
                            'role_in_order' => 'applicant',
                            'is_primary' => true,
                            'notes' => null,
                        ],
                    ]);

                    if (! $order->created_by_contact_id) {
                        $order->update(['created_by_contact_id' => $contact->id]);
                    }

                    $linked++;
                }
            }
        }

        // -------------------------------------------------
        // 2) Teacher-name contacts from legacy exam_entries
        // -------------------------------------------------
        $teacherNames = $source->table('exam_entries')
            ->select('teacher_name')
            ->whereNotNull('teacher_name')
            ->where('teacher_name', '!=', '')
            ->distinct()
            ->orderBy('teacher_name')
            ->pluck('teacher_name');

        foreach ($teacherNames as $teacherName) {
            $name = trim((string) $teacherName);

            if ($name === '') {
                $skipped++;
                continue;
            }

            $existing = ExamContact::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would review existing teacher contact: {$name}");
                    $updated++;
                } else {
                    $this->line("Would create teacher contact: {$name}");
                    $created++;
                }
                continue;
            }

            if ($existing) {
                $existing->fill([
                    'role' => $existing->role ?: 'teacher',
                    'source' => $existing->source ?: 'legacy_db',
                ]);

                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                ExamContact::create([
                    'name' => $name,
                    'role' => 'teacher',
                    'source' => 'legacy_db',
                ]);
                $created++;
            }
        }

        $this->newLine();
        $rows = [
            ['Created', $created],
            ['Updated', $updated],
            ['Skipped/Unchanged', $skipped],
        ];

        if ($linkOrders) {
            $rows[] = ['Applicant links created/checked', $linked];
        }

        $this->table(['Result', 'Count'], $rows);

        $this->info('Legacy contact import complete.');

        return self::SUCCESS;
    }
}