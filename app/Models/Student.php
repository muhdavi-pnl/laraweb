<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id', 'advisor_id', 'nim', 'study_program', 'entry_year', 'phone', 'address',
        'province_code', 'regency_code', 'district_code', 'village_code', 'postal_code',
    ];

    /** Relations needed to render a full StudentResource. */
    public const PROFILE_RELATIONS = ['user', 'advisor.user', 'province', 'regency', 'district', 'village'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_code');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'advisor_id');
    }
}
