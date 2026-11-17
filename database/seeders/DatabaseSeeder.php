<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Sample regions (Aceh › Kota Lhokseumawe). Load the whole country with `php artisan regions:import`.
        Artisan::call('regions:import', ['--file' => database_path('data/regions-lhokseumawe.sql')]);

        User::factory()->role(Role::Admin)->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $lecturers = Lecturer::factory(3)->create();

        $lecturer = Lecturer::factory()->create([
            'user_id' => User::factory()->role(Role::Lecturer)->create(['email' => 'lecturer@example.com']),
        ]);

        $demoStudent = Student::factory()->create([
            'user_id' => User::factory()->role(Role::Student)->create(['email' => 'student@example.com']),
            'advisor_id' => $lecturers->first()->id,
            'address' => 'Jl. Banda Aceh-Medan Km 280',
            'province_code' => '11',
            'regency_code' => '11.73',
            'district_code' => '11.73.04',
            'village_code' => '11.73.04.2010',
            'postal_code' => '24301',
        ]);

        $students = Student::factory(10)->create(['advisor_id' => $lecturers->random()->id]);

        $courses = Course::factory(4)->create(['lecturer_id' => $lecturer->id]);

        foreach ($courses as $course) {
            $enrollment = Enrollment::factory()->create(['student_id' => $demoStudent->id, 'course_id' => $course->id]);
            $enrollment->assignGrade(fake()->numberBetween(40, 100), $lecturer->user);

            Enrollment::factory()->create(['student_id' => $students->random()->id, 'course_id' => $course->id]);
        }
    }
}
