<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kecamatan. */
class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code', 'regency_code', 'name'];

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_code');
    }
}
