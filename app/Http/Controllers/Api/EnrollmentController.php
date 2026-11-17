<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\GradeEnrollmentRequest;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @group Enrollments & Grades
 */
class EnrollmentController extends Controller
{
    /**
     * List enrollments
     *
     * Scoped by role: admins see all, lecturers see enrollments in their courses,
     * students see their own (including grades).
     *
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam student_id integer Filter by student (admin/lecturer). Example: 1
     * @queryParam academic_year string Example: 2025/2026
     * @queryParam semester string odd or even. Example: odd
     * @queryParam per_page integer Items per page (1-100). Example: 15
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'academic_year' => ['nullable', 'string', 'max:9'],
            'semester' => ['nullable', Rule::in(['odd', 'even'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $user = $request->user();

        $enrollments = Enrollment::query()
            ->with(['student.user', 'course.lecturer.user'])
            ->when($user->hasRole(Role::Student), fn ($q) => $q->where('student_id', $user->student?->id ?? 0))
            ->when($user->hasRole(Role::Lecturer), fn ($q) => $q->whereHas(
                'course', fn ($c) => $c->where('lecturer_id', $user->lecturer?->id ?? 0)
            ))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('course_id', $v))
            ->when($filters['student_id'] ?? null, fn ($q, $v) => $q->where('student_id', $v))
            ->when($filters['academic_year'] ?? null, fn ($q, $v) => $q->where('academic_year', $v))
            ->when($filters['semester'] ?? null, fn ($q, $v) => $q->where('semester', $v))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Enroll in a course
     *
     * Students enroll themselves (`student_id` is ignored); admins must provide `student_id`.
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $enrollment = Enrollment::create($request->validated());

        return (new EnrollmentResource($enrollment->load(['student.user', 'course.lecturer.user'])))
            ->response()->setStatusCode(201);
    }

    /**
     * Remove an enrollment
     *
     * Admins can remove any; students only their own ungraded enrollments.
     */
    public function destroy(Enrollment $enrollment): Response
    {
        Gate::authorize('delete', $enrollment);

        $enrollment->delete();

        return response()->noContent();
    }

    /**
     * Grade an enrollment
     *
     * Allowed for admins and the lecturer teaching the course. The letter grade is derived
     * from the score: A ≥ 85, B ≥ 70, C ≥ 55, D ≥ 40, otherwise E.
     */
    public function grade(GradeEnrollmentRequest $request, Enrollment $enrollment): EnrollmentResource
    {
        Gate::authorize('grade', $enrollment);

        $enrollment->assignGrade((float) $request->validated('score'), $request->user());

        return new EnrollmentResource($enrollment->load(['student.user', 'course.lecturer.user']));
    }
}
