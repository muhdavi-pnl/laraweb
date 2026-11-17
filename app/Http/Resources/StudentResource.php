<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'nim' => $this->nim,
            'study_program' => $this->study_program,
            'entry_year' => $this->entry_year,
            'phone' => $this->phone,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'region' => $this->when($this->relationLoaded('province'), fn () => [
                'province' => $this->region($this->province),
                'regency' => $this->region($this->regency),
                'district' => $this->region($this->district),
                'village' => $this->region($this->village),
            ]),
            'full_address' => $this->when($this->relationLoaded('province'), fn () => $this->fullAddress()),
            'advisor' => new LecturerResource($this->whenLoaded('advisor')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function region(?Model $region): ?array
    {
        return $region ? ['code' => $region->code, 'name' => $region->name] : null;
    }

    /** Street, kelurahan, kecamatan, kabupaten/kota, provinsi, postal code — skipping missing parts. */
    private function fullAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->village?->name,
            $this->district?->name,
            $this->regency?->name,
            $this->province?->name,
            $this->postal_code,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
