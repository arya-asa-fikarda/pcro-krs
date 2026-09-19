<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class SeedHighVolume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-high-volume 
                            {--count=5000000 : Number of enrollments to seed} 
                            {--students=100000 : Number of students to ensure exist}
                            {--courses=500 : Number of courses to ensure exist}
                            {--chunk=5000 : Chunk size for bulk insert}
                            {--truncate : Truncate tables before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with high volume of data (millions of rows) using bulk inserts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $studentCount = (int) $this->option('students');
        $courseCount = (int) $this->option('courses');
        $chunkSize = (int) $this->option('chunk');
        $truncate = $this->option('truncate');

        if ($truncate) {
            $this->warn('Truncating tables...');
            DB::statement('TRUNCATE TABLE enrollments RESTART IDENTITY CASCADE');
            DB::statement('TRUNCATE TABLE students RESTART IDENTITY CASCADE');
            DB::statement('TRUNCATE TABLE courses RESTART IDENTITY CASCADE');
            $this->info('Tables truncated.');
        }

        $faker = Faker::create();
        $now = Carbon::now()->toDateTimeString();

        // 1. Seed Students
        $existingStudents = DB::table('students')->count();
        if ($existingStudents < $studentCount) {
            $toCreate = $studentCount - $existingStudents;
            $this->info("Creating $toCreate students...");
            $bar = $this->output->createProgressBar($toCreate);
            
            for ($i = 0; $i < $toCreate; $i += $chunkSize) {
                $batchSize = min($chunkSize, $toCreate - $i);
                $students = [];
                for ($j = 0; $j < $batchSize; $j++) {
                    $students[] = [
                        'nim' => $faker->unique()->numerify('##########'),
                        'name' => $faker->name(),
                        'email' => $faker->unique()->safeEmail(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('students')->insert($students);
                $bar->advance($batchSize);
            }
            $bar->finish();
            $this->newLine();
        }

        // 2. Seed Courses
        $existingCourses = DB::table('courses')->count();
        if ($existingCourses < $courseCount) {
            $toCreate = $courseCount - $existingCourses;
            $this->info("Creating $toCreate courses...");
            $courses = [];
            for ($i = 0; $i < $toCreate; $i++) {
                $courses[] = [
                    'code' => strtoupper($faker->unique()->bothify('??###')),
                    'name' => $faker->words(3, true),
                    'credits' => $faker->numberBetween(1, 6),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('courses')->insert($courses);
            $this->info("Created $toCreate courses.");
        }

        // 3. Prepare IDs and values
        $this->info('Fetching IDs for enrollment generation...');
        $studentIds = DB::table('students')->pluck('id')->toArray();
        $courseIds = DB::table('courses')->pluck('id')->toArray();
        $semesters = array_column(Semester::cases(), 'value');
        $statuses = array_column(EnrollmentStatus::cases(), 'value');
        $years = [];
        for ($y = 2020; $y <= 2026; $y++) {
            $years[] = "$y/".($y+1);
        }

        // 4. Seed Enrollments
        $this->info("Seeding $count enrollments in chunks of $chunkSize...");
        $bar = $this->output->createProgressBar($count);
        
        $totalInserted = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $count; $i += $chunkSize) {
            $batchSize = min($chunkSize, $count - $i);
            $enrollments = [];
            
            for ($j = 0; $j < $batchSize; $j++) {
                $enrollments[] = [
                    'student_id' => $faker->randomElement($studentIds),
                    'course_id' => $faker->randomElement($courseIds),
                    'academic_year' => $faker->randomElement($years),
                    'semester' => $faker->randomElement($semesters),
                    'status' => $faker->randomElement($statuses),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Using transaction for each chunk for performance and atomicity per batch
            DB::transaction(function () use ($enrollments, &$totalInserted) {
                // insertOrIgnore is crucial to handle potential random collisions on the unique constraint
                // student_id, course_id, academic_year, semester
                $inserted = DB::table('enrollments')->insertOrIgnore($enrollments);
                $totalInserted += $inserted;
            });

            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("Seeding completed in $duration seconds!");
        $this->info("Target: $count, Successfully Inserted: $totalInserted");
        
        $finalCount = DB::table('enrollments')->count();
        $this->info("Final total enrollment count in DB: $finalCount");

        return Command::SUCCESS;
    }
}
