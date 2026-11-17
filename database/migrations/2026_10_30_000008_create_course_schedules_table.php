<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9);
            $table->string('semester', 10);
            $table->unsignedTinyInteger('day_of_week'); // ISO-8601: 1 = Monday ... 7 = Sunday
            $table->string('start_time', 5); // HH:MM
            $table->string('end_time', 5);
            $table->string('room', 50);
            $table->timestamps();

            $table->index(['academic_year', 'semester', 'day_of_week', 'room'], 'schedule_room_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_schedules');
    }
};
