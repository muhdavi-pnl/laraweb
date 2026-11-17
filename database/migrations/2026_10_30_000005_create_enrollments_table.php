<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9);
            $table->string('semester', 10);
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade', 2)->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'academic_year', 'semester']);
            $table->index(['academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
