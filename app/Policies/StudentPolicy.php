<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewTranscript(User $user, Student $student): bool
    {
        return $user->hasRole(Role::Admin)
            || $user->hasRole(Role::Lecturer)
            || $user->student?->id === $student->id;
    }

    public function viewAttendance(User $user, Student $student): bool
    {
        return $this->viewTranscript($user, $student);
    }
}
