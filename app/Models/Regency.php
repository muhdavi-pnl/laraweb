<?php

namespace App\Models;

use Database\Factories\RegencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kabupaten or kota. */
class Regency extends Model
{
    /** @use HasFactory<RegencyFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code', 'province_code', 'name'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'regency_code');
    }
}
