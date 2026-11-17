<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\Grade;
use App\Enums\Semester;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['student_id', 'course_id', 'academic_year', 'semester'];

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'grade' => Grade::class,
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->score !== null;
    }

    /** Assigns score-derived fields; these are guarded from mass assignment on purpose. */
    public function assignGrade(float $score, User $grader): void
    {
        $this->score = $score;
        $this->grade = Grade::fromScore($score);
        $this->graded_at = now();
        $this->graded_by = $grader->id;
        $this->save();
    }
}
