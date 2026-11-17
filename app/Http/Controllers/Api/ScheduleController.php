<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CourseScheduleRequest;
use App\Http\Resources\CourseScheduleResource;
use App\Models\Course;
use App\Models\CourseSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * @group Timetable
 */
class ScheduleController extends Controller
{
    /**
     * My timetable
     *
     * Admins see every slot, lecturers the slots of their courses, students the slots of courses
     * they are enrolled in for the same term. Ordered by weekday and start time.
     *
     * @queryParam academic_year string Example: 2025/2026
     * @queryParam semester string odd or even. Example: odd
     */
    public function timetable(Request $request)
    {
        $filters = $request->validate([
            'academic_year' => ['nullable', 'string', 'max:9'],
            'semester' => ['nullable', Rule::in(['odd', 'even'])],
        ]);

        $user = $request->user();

        $schedules = CourseSchedule::query()
            ->with('course.lecturer.user')
            ->when($user->hasRole(Role::Lecturer), fn ($q) => $q->whereHas(
                'course', fn ($c) => $c->where('lecturer_id', $user->lecturer?->id ?? 0)
            ))
            ->when($user->hasRole(Role::Student), fn ($q) => $q->whereExists(fn ($e) => $e
                ->selectRaw('1')->from('enrollments')
                ->whereColumn('enrollments.course_id', 'course_schedules.course_id')
                ->whereColumn('enrollments.academic_year', 'course_schedules.academic_year')
                ->whereColumn('enrollments.semester', 'course_schedules.semester')
                ->where('enrollments.student_id', $user->student?->id ?? 0)))
            ->when($filters['academic_year'] ?? null, fn ($q, $v) => $q->where('academic_year', $v))
            ->when($filters['semester'] ?? null, fn ($q, $v) => $q->where('semester', $v))
            ->orderBy('day_of_week')->orderBy('start_time')
            ->get();

        return CourseScheduleResource::collection($schedules);
    }

    /**
     * List a course's schedule slots
     */
    public function index(Course $course)
    {
        return CourseScheduleResource::collection(
            $course->schedules()->orderBy('academic_year')->orderBy('day_of_week')->orderBy('start_time')->get()
        );
    }

    /**
     * Add a schedule slot (admin)
     *
     * Rejected with 422 if the room is already booked for an overlapping time in that term.
     */
    public function store(CourseScheduleRequest $request, Course $course): JsonResponse
    {
        $schedule = $course->schedules()->create($request->validated());

        return (new CourseScheduleResource($schedule))->response()->setStatusCode(201);
    }

    /**
     * Update a schedule slot (admin)
     */
    public function update(CourseScheduleRequest $request, CourseSchedule $schedule): CourseScheduleResource
    {
        $schedule->update($request->validated());

        return new CourseScheduleResource($schedule);
    }

    /**
     * Delete a schedule slot (admin)
     */
    public function destroy(CourseSchedule $schedule): Response
    {
        $schedule->delete();

        return response()->noContent();
    }
}
