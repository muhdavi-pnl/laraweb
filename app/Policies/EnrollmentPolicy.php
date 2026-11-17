<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /** Admins can remove any enrollment; students only their own ungraded ones. */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Student)
            && $user->student?->id === $enrollment->student_id
            && ! $enrollment->isGraded();
    }

    /** Admins, or the lecturer who teaches the course. */
    public function grade(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Lecturer)
            && $user->lecturer !== null
            && $enrollment->course->lecturer_id === $user->lecturer->id;
    }
}
