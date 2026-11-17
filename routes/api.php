<?php

use App\Enums\Role;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GradeImportController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');

        Route::middleware(['auth:api', 'token.current'])->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:api', 'token.current'])->group(function () {
        $admin = 'role:'.Role::Admin->value;
        $staff = 'role:'.Role::Admin->value.','.Role::Lecturer->value;
        $adminOrStudent = 'role:'.Role::Admin->value.','.Role::Student->value;

        // Open to every authenticated role
        Route::prefix('regions')->middleware('cache.headers:private;max_age=3600;etag')->group(function () {
            Route::get('provinces', [RegionController::class, 'provinces']);
            Route::get('provinces/{province}/regencies', [RegionController::class, 'regencies']);
            Route::get('regencies/{regency}/districts', [RegionController::class, 'districts']);
            Route::get('districts/{district}/villages', [RegionController::class, 'villages']);
        });

        Route::middleware('role:'.Role::Student->value)->group(function () {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::patch('profile', [ProfileController::class, 'update']);
        });

        Route::apiResource('courses', CourseController::class)->only(['index', 'show']);
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::get('students/{student}/transcript', [StudentController::class, 'transcript']);
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
        Route::put('enrollments/{enrollment}/grade', [EnrollmentController::class, 'grade'])
            ->middleware($staff);

        Route::get('timetable', [ScheduleController::class, 'timetable']);
        Route::get('courses/{course}/schedules', [ScheduleController::class, 'index']);
        Route::get('students/{student}/attendance', [AttendanceController::class, 'summary']);

        Route::post('enrollments', [EnrollmentController::class, 'store'])->middleware($adminOrStudent);

        Route::middleware($staff)->group(function () {
            Route::get('courses/{course}/attendance', [AttendanceController::class, 'index']);
            Route::post('courses/{course}/attendance', [AttendanceController::class, 'store']);
            Route::post('courses/{course}/grades/import', [GradeImportController::class, 'store']);
            Route::apiResource('students', StudentController::class)->only(['index', 'show']);
        });

        Route::middleware($admin)->group(function () {
            Route::apiResource('students', StudentController::class)->except(['index', 'show']);
            Route::apiResource('lecturers', LecturerController::class);
            Route::apiResource('courses', CourseController::class)->except(['index', 'show']);
            Route::post('courses/{course}/schedules', [ScheduleController::class, 'store']);
            Route::match(['put', 'patch'], 'schedules/{schedule}', [ScheduleController::class, 'update']);
            Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy']);
            Route::get('audit-logs', [AuditLogController::class, 'index']);
        });
    });
});
