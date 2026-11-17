<?php

namespace App\Http\Resources;

use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lecturer */
class LecturerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'nidn' => $this->nidn,
            'department' => $this->department,
            'phone' => $this->phone,
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
