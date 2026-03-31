<?php

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Paul's admin account
        User::factory()->create([
            'name' => 'Paul Sheridan',
            'email' => 'musicexams@musicexams.help',
            'role' => 'admin',
        ]);

        // Seed lookup tables (instruments, subject areas)
        $this->call([
            LookupSeeder::class,
        ]);

        // Seed fake data for admin panel development
        $this->call([
            FakeDataSeeder::class,
        ]);
    }
}
