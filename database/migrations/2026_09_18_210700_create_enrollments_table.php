<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->char('academic_year', 9);
            $table->string('semester', 6);
            $table->string('status', 10);
            $table->timestamps();

            $table->unique(
                ['student_id', 'course_id', 'academic_year', 'semester'],
                'enrollments_student_course_period_unique'
            );
            $table->index('course_id', 'enrollments_course_id_index');
            $table->index(
                ['status', 'semester', 'academic_year'],
                'enrollments_status_semester_year_index'
            );
            $table->index(
                ['academic_year', 'semester', 'id'],
                'enrollments_year_semester_id_index'
            );
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_academic_year_format_check CHECK (academic_year ~ '^[0-9]{4}/[0-9]{4}$')");
            DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_semester_check CHECK (semester IN ('GANJIL', 'GENAP'))");
            DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_status_check CHECK (status IN ('DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
