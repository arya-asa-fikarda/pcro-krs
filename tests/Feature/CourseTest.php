<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_persists_a_course(): void
    {
        $course = Course::factory()->create([
            'code' => 'IF101',
            'name' => 'Algoritma',
            'credits' => 3,
        ]);

        $this->assertModelExists($course);
        $this->assertSame(3, $course->credits);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'IF101',
            'name' => 'Algoritma',
            'credits' => 3,
        ]);
    }

    public function test_has_many_enrollments_returns_related_records(): void
    {
        $course = Course::factory()->create();

        $enrollments = Enrollment::factory()->count(2)->for($course)->create();

        $related = $course->enrollments()->orderBy('id')->get();

        $this->assertSame(2, $related->count());
        $this->assertSame($enrollments->pluck('id')->all(), $related->pluck('id')->all());
    }
}
