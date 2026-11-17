<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * @group Student Profile
 */
class ProfileController extends Controller
{
    /**
     * Show my student profile (student)
     *
     * Includes the address with province, regency, district and village names plus a `full_address` string.
     */
    public function show(Request $request): StudentResource
    {
        return new StudentResource($this->student($request)->load(Student::PROFILE_RELATIONS));
    }

    /**
     * Update my contact and address (student)
     *
     * Only `phone`, `address` (street), `postal_code` and the four region codes can be changed.
     * Region codes are all-or-nothing and must belong together; send all four as null to clear them.
     */
    public function update(UpdateProfileRequest $request): StudentResource
    {
        $student = $this->student($request);
        $student->update($request->validated());

        return new StudentResource($student->load(Student::PROFILE_RELATIONS));
    }

    private function student(Request $request): Student
    {
        return $request->user()->student ?? abort(404, 'No student profile exists for this account.');
    }
}
