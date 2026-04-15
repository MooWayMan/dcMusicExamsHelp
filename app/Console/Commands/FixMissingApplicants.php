<?php
// app/Console/Commands/FixMissingApplicants.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\Order;
use Illuminate\Console\Command;

class FixMissingApplicants extends Command
{
    protected $signature = 'orders:fix-missing-applicants {--dry-run}';
    protected $description = 'Manually link known applicants to orders';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $map = [
            '1-16043046624' => [
                'name' => 'Paul Sheridan',
                'email' => 'madmusic6@hotmail.com',
            ],
            '1-16044396864' => [
                'name' => 'Rachel Jones',
                'email' => 'rachelsimms1969@gmail.com',
            ],
            '1-15549565825' => [
                'name' => 'Mark Shore',
                'email' => 'fionashore@hotmail.co.uk',
            ],
        ];

        foreach ($map as $orderNumber => $contactData) {

            $order = Order::where('trinity_order_number', $orderNumber)->first();

            if (! $order) {
                $this->warn("Order not found: {$orderNumber}");
                continue;
            }

            if ($dryRun) {
                $this->line("Would link {$contactData['name']} → {$orderNumber}");
                continue;
            }

            $contact = ExamContact::firstOrCreate(
                ['email' => $contactData['email']],
                [
                    'name' => $contactData['name'],
                    'role' => 'applicant',
                ]
            );

            $order->contacts()->syncWithoutDetaching([
                $contact->id => [
                    'role_in_order' => 'applicant',
                    'is_primary' => true,
                ]
            ]);

            $order->update([
                'created_by_contact_id' => $contact->id,
            ]);

            $this->info("Linked {$contact->name} → {$orderNumber}");
        }

        return self::SUCCESS;
    }
}