<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['enrollment_id', 'date', 'status', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'status' => AttendanceStatus::class];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
