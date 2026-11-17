<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Courses
 */
class CourseController extends Controller
{
    /**
     * List courses
     *
     * Available to every authenticated user.
     *
     * @queryParam search string Match on course code or name. Example: algorithms
     * @queryParam lecturer_id integer Filter by lecturer. Example: 1
     * @queryParam per_page integer Items per page (1-100). Example: 15
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'lecturer_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $courses = Course::query()
            ->with('lecturer.user')
            ->withCount('enrollments')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$s}%")
                ->orWhere('name', 'like', "%{$s}%")))
            ->when($filters['lecturer_id'] ?? null, fn ($q, $v) => $q->where('lecturer_id', $v))
            ->orderBy('code')
            ->paginate($filters['per_page'] ?? 15);

        return CourseResource::collection($courses);
    }

    /**
     * Create a course (admin)
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());

        return (new CourseResource($course->load('lecturer.user')))->response()->setStatusCode(201);
    }

    /**
     * Show a course
     */
    public function show(Course $course): CourseResource
    {
        return new CourseResource($course->load('lecturer.user')->loadCount('enrollments'));
    }

    /**
     * Update a course (admin)
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $course->update($request->validated());

        return new CourseResource($course->load('lecturer.user'));
    }

    /**
     * Delete a course (admin)
     *
     * Also removes its enrollments and grades.
     */
    public function destroy(Course $course): Response
    {
        $course->delete();

        return response()->noContent();
    }
}
