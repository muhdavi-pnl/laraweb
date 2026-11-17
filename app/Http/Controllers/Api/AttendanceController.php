<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\RecordAttendanceRequest;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * @group Attendance
 */
class AttendanceController extends Controller
{
    /**
     * Attendance for a class date (admin, course lecturer)
     *
     * @queryParam date string required Y-m-d. Example: 2025-09-01
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('manage', $course);

        $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];

        $rows = Attendance::query()
            ->whereDate('date', $date)
            ->whereHas('enrollment', fn ($q) => $q->where('course_id', $course->id))
            ->with('enrollment.student.user')
            ->get()
            ->map(fn ($a) => [
                'enrollment_id' => $a->enrollment_id,
                'student' => ['id' => $a->enrollment->student_id, 'nim' => $a->enrollment->student->nim, 'name' => $a->enrollment->student->user->name],
                'status' => $a->status->value,
                'note' => $a->note,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * Record attendance for a class date (admin, course lecturer)
     *
     * Idempotent per enrollment and date: re-submitting a date updates the existing records.
     *
     * @response 200 {"success": true, "date": "2025-09-01", "recorded": 2}
     */
    public function store(RecordAttendanceRequest $request, Course $course, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('manage', $course);

        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $course, $audit) {
            foreach ($data['records'] as $record) {
                Attendance::updateOrCreate(
                    ['enrollment_id' => $record['enrollment_id'], 'date' => $data['date']],
                    ['status' => $record['status'], 'note' => $record['note'] ?? null, 'recorded_by' => $request->user()->id],
                );
            }

            $audit->record('attendance_recorded', $course, null, ['date' => $data['date'], 'records' => count($data['records'])]);
        });

        return response()->json(['success' => true, 'date' => $data['date'], 'recorded' => count($data['records'])]);
    }

    /**
     * Student attendance summary (admin, lecturer, the student)
     *
     * Per course: counts per status and attendance rate (present + excused + sick over all recorded sessions).
     */
    public function summary(Student $student): JsonResponse
    {
        Gate::authorize('viewAttendance', $student);

        $courses = $student->enrollments()->with(['course', 'attendances'])->get()->map(function ($enrollment) {
            $counts = $enrollment->attendances->countBy(fn ($a) => $a->status->value);
            $total = $enrollment->attendances->count();
            $attended = $total - $counts->get(AttendanceStatus::Absent->value, 0);

            return [
                'course' => ['id' => $enrollment->course_id, 'code' => $enrollment->course->code, 'name' => $enrollment->course->name],
                'academic_year' => $enrollment->academic_year,
                'semester' => $enrollment->semester->value,
                'sessions' => $total,
                'present' => $counts->get('present', 0),
                'absent' => $counts->get('absent', 0),
                'excused' => $counts->get('excused', 0),
                'sick' => $counts->get('sick', 0),
                'attendance_rate' => $total > 0 ? round($attended / $total * 100, 1) : null,
            ];
        });

        return response()->json(['data' => $courses->values()]);
    }
}
