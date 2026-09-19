<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_persists_a_student(): void
    {
        $student = Student::factory()->create([
            'nim' => '1234567890',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);

        $this->assertModelExists($student);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'nim' => '1234567890',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);
    }

    public function test_has_many_enrollments_returns_related_records(): void
    {
        $student = Student::factory()->create();

        $enrollments = Enrollment::factory()->count(2)->for($student)->create();

        $related = $student->enrollments()->orderBy('id')->get();

        $this->assertSame(2, $related->count());
        $this->assertSame($enrollments->pluck('id')->all(), $related->pluck('id')->all());
    }
}
