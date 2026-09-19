<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safe Indexing PostgreSQL (Aman walau indeks sudah ada)
        DB::statement('CREATE INDEX IF NOT EXISTS students_nim_idx ON students (nim);');
        DB::statement('CREATE INDEX IF NOT EXISTS students_name_idx ON students (name);');

        DB::statement('CREATE INDEX IF NOT EXISTS courses_code_idx ON courses (code);');
        DB::statement('CREATE INDEX IF NOT EXISTS courses_name_idx ON courses (name);');

        DB::statement('CREATE INDEX IF NOT EXISTS enrollments_student_id_idx ON enrollments (student_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS enrollments_course_id_idx ON enrollments (course_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS enrollments_semester_idx ON enrollments (semester);');
        DB::statement('CREATE INDEX IF NOT EXISTS enrollments_status_idx ON enrollments (status);');
        DB::statement('CREATE INDEX IF NOT EXISTS enrollments_academic_year_idx ON enrollments (academic_year);');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS students_nim_idx;');
        DB::statement('DROP INDEX IF EXISTS students_name_idx;');
        DB::statement('DROP INDEX IF EXISTS courses_code_idx;');
        DB::statement('DROP INDEX IF EXISTS courses_name_idx;');
        DB::statement('DROP INDEX IF EXISTS enrollments_student_id_idx;');
        DB::statement('DROP INDEX IF EXISTS enrollments_course_id_idx;');
        DB::statement('DROP INDEX IF EXISTS enrollments_semester_idx;');
        DB::statement('DROP INDEX IF EXISTS enrollments_status_idx;');
        DB::statement('DROP INDEX IF EXISTS enrollments_academic_year_idx;');
    }
};
