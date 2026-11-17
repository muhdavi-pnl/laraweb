<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionResource;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;

/**
 * Cascading lookups for address dropdowns: provinsi → kabupaten/kota → kecamatan → kelurahan/desa.
 * Codes follow the Kemendagri numbering, e.g. 11 → 11.73 → 11.73.01 → 11.73.01.2001.
 *
 * @group Regions
 */
class RegionController extends Controller
{
    /**
     * List provinces (provinsi)
     *
     * @queryParam search string Filter by name. Example: aceh
     */
    public function provinces(Request $request)
    {
        return RegionResource::collection(
            Province::query()->when($this->search($request), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))->orderBy('code')->get()
        );
    }

    /**
     * List regencies (kabupaten/kota) of a province
     *
     * @urlParam province string required Province code. Example: 11
     *
     * @queryParam search string Filter by name. Example: lhokseumawe
     */
    public function regencies(Request $request, Province $province)
    {
        return RegionResource::collection(
            $province->regencies()->when($this->search($request), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))->orderBy('code')->get()
        );
    }

    /**
     * List districts (kecamatan) of a regency
     *
     * @urlParam regency string required Regency code. Example: 11.73
     *
     * @queryParam search string Filter by name. Example: banda
     */
    public function districts(Request $request, Regency $regency)
    {
        return RegionResource::collection(
            $regency->districts()->when($this->search($request), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))->orderBy('code')->get()
        );
    }

    /**
     * List villages (kelurahan/desa) of a district
     *
     * @urlParam district string required District code. Example: 11.73.01
     *
     * @queryParam search string Filter by name. Example: meuria
     */
    public function villages(Request $request, District $district)
    {
        return RegionResource::collection(
            $district->villages()->when($this->search($request), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))->orderBy('code')->get()
        );
    }

    private function search(Request $request): ?string
    {
        return $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;
    }
}
