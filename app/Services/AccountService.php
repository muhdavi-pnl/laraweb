<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a user account and its role profile consistent in a single transaction.
 */
class AccountService
{
    private const USER_FIELDS = ['name', 'email', 'password'];

    public function createStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data, Role::Student);

            return $user->student()->create(Arr::except($data, self::USER_FIELDS));
        });
    }

    public function updateStudent(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $student->user->update(Arr::only($data, self::USER_FIELDS));
            $student->update(Arr::except($data, self::USER_FIELDS));

            return $student;
        });
    }

    public function createLecturer(array $data): Lecturer
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data, Role::Lecturer);

            return $user->lecturer()->create(Arr::except($data, self::USER_FIELDS));
        });
    }

    public function updateLecturer(Lecturer $lecturer, array $data): Lecturer
    {
        return DB::transaction(function () use ($lecturer, $data) {
            $lecturer->user->update(Arr::only($data, self::USER_FIELDS));
            $lecturer->update(Arr::except($data, self::USER_FIELDS));

            return $lecturer;
        });
    }

    /** Deletes the profile first so its audit event fires, then the login account. */
    public function delete(Student|Lecturer $profile): void
    {
        DB::transaction(function () use ($profile) {
            $user = $profile->user;
            $profile->delete();
            $user->delete();
        });
    }

    private function createUser(array $data, Role $role): User
    {
        $user = new User(Arr::only($data, self::USER_FIELDS));
        $user->role = $role;
        $user->save();

        return $user;
    }
}
