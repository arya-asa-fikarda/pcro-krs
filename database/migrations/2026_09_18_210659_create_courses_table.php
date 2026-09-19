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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 7);
            $table->string('name', 120);
            $table->unsignedTinyInteger('credits');
            $table->timestamps();

            $table->unique('code', 'courses_code_unique');
            $table->index('name', 'courses_name_index');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_code_format_check CHECK (code ~ '^[A-Z]{2,4}[0-9]{3}$')");
            DB::statement('ALTER TABLE courses ADD CONSTRAINT courses_name_length_check CHECK (char_length(name) BETWEEN 3 AND 120)');
            DB::statement('ALTER TABLE courses ADD CONSTRAINT courses_credits_range_check CHECK (credits BETWEEN 1 AND 6)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
