<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default seed for development/testing
        $this->call([
            StudentSeeder::class,
            CourseSeeder::class,
            EnrollmentSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Admin PCRO',
            'email' => 'admin@pcro.test',
            'password' => bcrypt('password'),
        ]);

        $this->command->info('Default seeding completed.');
        $this->command->warn('To run 5M high-volume seeding, use: php artisan app:seed-high-volume');
    }
}
