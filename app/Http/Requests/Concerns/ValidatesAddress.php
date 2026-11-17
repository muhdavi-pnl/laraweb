<?php

namespace App\Http\Requests\Concerns;

use App\Models\Village;
use Illuminate\Validation\Validator;

/**
 * Address rules shared by every request that edits a student's address.
 * The four region codes are all-or-nothing and must form a real chain (kelurahan ∈ kecamatan ∈ kabupaten ∈ provinsi).
 */
trait ValidatesAddress
{
    protected function addressRules(): array
    {
        $regionFields = ['province_code', 'regency_code', 'district_code', 'village_code'];

        $rule = fn (string $field, string $table) => [
            'nullable',
            'string',
            'required_with:'.implode(',', array_diff($regionFields, [$field])),
            "exists:{$table},code",
        ];

        return [
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'digits:5'],
            'province_code' => $rule('province_code', 'provinces'),
            'regency_code' => $rule('regency_code', 'regencies'),
            'district_code' => $rule('district_code', 'districts'),
            'village_code' => $rule('village_code', 'villages'),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('village_code')) {
                return;
            }

            $consistent = Village::query()
                ->whereKey($this->input('village_code'))
                ->where('district_code', $this->input('district_code'))
                ->whereHas('district', fn ($d) => $d
                    ->where('regency_code', $this->input('regency_code'))
                    ->whereHas('regency', fn ($r) => $r->where('province_code', $this->input('province_code'))))
                ->exists();

            if (! $consistent) {
                $validator->errors()->add('village_code', 'The province, regency, district and village do not belong together.');
            }
        }];
    }
}
