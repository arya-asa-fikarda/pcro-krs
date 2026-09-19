<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $courses = Course::all();

        if ($students->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('Students or Courses are empty. Seeding them first...');
            $this->call([
                StudentSeeder::class,
                CourseSeeder::class,
            ]);
            $students = Student::all();
            $courses = Course::all();
        }

        Enrollment::factory(200)->recycle($students)->recycle($courses)->create();
    }
}
