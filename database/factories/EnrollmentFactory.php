<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2026);

        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'academic_year' => $startYear.'/'.($startYear + 1),
            'semester' => fake()->randomElement(Semester::cases()),
            'status' => fake()->randomElement(EnrollmentStatus::cases()),
        ];
    }

    public function ganjil(): static
    {
        return $this->state(fn (array $attributes): array => [
            'semester' => Semester::Ganjil,
        ]);
    }

    public function genap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'semester' => Semester::Genap,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EnrollmentStatus::Draft,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EnrollmentStatus::Submitted,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EnrollmentStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EnrollmentStatus::Rejected,
        ]);
    }
}
