<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\LecturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecturer extends Model
{
    /** @use HasFactory<LecturerFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['user_id', 'nidn', 'department', 'phone'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'advisor_id');
    }
}
