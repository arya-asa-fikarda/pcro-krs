<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class HighVolumeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // By default, we use a smaller count for verification if called without arguments
        // but we can pass arguments via call() if needed.
        // However, the best way to run the 5M seed is via the Artisan command directly
        // as it provides a progress bar and better memory management in CLI.

        $this->command->warn('HighVolumeSeeder is a wrapper for app:seed-high-volume command.');

        $count = $this->command->ask('How many enrollments to seed?', 10000);

        Artisan::call('app:seed-high-volume', [
            '--count' => $count,
            '--truncate' => $this->command->confirm('Truncate tables first?', false),
        ], $this->command->getOutput());
    }
}
