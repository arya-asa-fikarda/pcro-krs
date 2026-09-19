<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Models\Enrollment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_persists_enrollment_with_student_and_course(): void
    {
        $enrollment = Enrollment::factory()->ganjil()->approved()->create([
            'academic_year' => '2025/2026',
        ]);

        $this->assertModelExists($enrollment);
        $this->assertModelExists($enrollment->student);
        $this->assertModelExists($enrollment->course);
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'academic_year' => '2025/2026',
            'semester' => 'GANJIL',
            'status' => 'APPROVED',
        ]);
    }

    public function test_casts_semester_and_status_to_enums(): void
    {
        $enrollment = Enrollment::factory()->genap()->rejected()->create();

        $enrollment->refresh();

        $this->assertSame(Semester::Genap, $enrollment->semester);
        $this->assertSame(EnrollmentStatus::Rejected, $enrollment->status);
    }

    public function test_rejects_duplicate_student_course_year_and_semester(): void
    {
        $enrollment = Enrollment::factory()->ganjil()->create([
            'academic_year' => '2025/2026',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Enrollment::factory()->ganjil()->create([
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'academic_year' => '2025/2026',
        ]);
    }
}
