<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\Semester;
use Database\Factories\CourseScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSchedule extends Model
{
    /** @use HasFactory<CourseScheduleFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['course_id', 'academic_year', 'semester', 'day_of_week', 'start_time', 'end_time', 'room'];

    protected function casts(): array
    {
        return ['semester' => Semester::class, 'day_of_week' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
