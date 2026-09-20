<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        // Generate 200 data di memori tanpa langsung menyimpan ke DB
        $enrollments = Enrollment::factory(200)
            ->recycle($students)
            ->recycle($courses)
            ->make()
            ->map(function ($item) {
                return [
                    'student_id'    => $item->student_id,
                    'course_id'     => $item->course_id,
                    'academic_year' => $item->academic_year,
                    'semester'      => $item->semester,
                    'status'        => $item->status,
                    'created_at'    => $item->created_at ?? now(),
                    'updated_at'    => $item->updated_at ?? now(),
                ];
            })
            ->toArray();

        // Abaikan baris acak yang duplikat agar proses seeding tidak crash
        DB::table('enrollments')->insertOrIgnore($enrollments);
    }
}
