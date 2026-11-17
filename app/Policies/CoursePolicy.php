<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /** Admins, or the lecturer teaching the course, may record attendance and import grades. */
    public function manage(User $user, Course $course): bool
    {
        return $user->hasRole(Role::Admin)
            || ($user->hasRole(Role::Lecturer)
                && $user->lecturer !== null
                && $course->lecturer_id === $user->lecturer->id);
    }
}
