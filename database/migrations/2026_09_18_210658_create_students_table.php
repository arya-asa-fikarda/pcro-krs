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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 12);
            $table->string('name', 100);
            $table->string('email');
            $table->timestamps();

            $table->unique('nim', 'students_nim_unique');
            $table->unique('email', 'students_email_unique');
            $table->index('name', 'students_name_index');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE students ADD CONSTRAINT students_nim_format_check CHECK (nim ~ '^[0-9]{8,12}$')");
            DB::statement('ALTER TABLE students ADD CONSTRAINT students_name_length_check CHECK (char_length(name) BETWEEN 3 AND 100)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
