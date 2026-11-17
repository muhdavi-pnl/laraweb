<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;

/** What a student may change about themself: contact and address only. */
class UpdateProfileRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['phone' => ['nullable', 'string', 'max:20']] + $this->addressRules();
    }
}
