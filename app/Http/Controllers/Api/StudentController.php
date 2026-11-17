<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @group Students
 */
class StudentController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    /**
     * List students (admin, lecturer)
     *
     * @queryParam search string Match on name or NIM. Example: 2021
     * @queryParam study_program string Example: Informatics
     * @queryParam entry_year integer Example: 2021
     * @queryParam per_page integer Items per page (1-100). Example: 15
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'entry_year' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $students = Student::query()
            ->with(Student::PROFILE_RELATIONS)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('nim', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['study_program'] ?? null, fn ($q, $v) => $q->where('study_program', $v))
            ->when($filters['entry_year'] ?? null, fn ($q, $v) => $q->where('entry_year', $v))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return StudentResource::collection($students);
    }

    /**
     * Create a student with a login account (admin)
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->accounts->createStudent($request->validated());

        return (new StudentResource($student->load(Student::PROFILE_RELATIONS)))
            ->response()->setStatusCode(201);
    }

    /**
     * Show a student (admin, lecturer)
     */
    public function show(Student $student): StudentResource
    {
        return new StudentResource($student->load(Student::PROFILE_RELATIONS));
    }

    /**
     * Update a student (admin)
     */
    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $student = $this->accounts->updateStudent($student, $request->validated());

        return new StudentResource($student->load(Student::PROFILE_RELATIONS));
    }

    /**
     * Delete a student and their account (admin)
     */
    public function destroy(Student $student): Response
    {
        $this->accounts->delete($student);

        return response()->noContent();
    }

    /**
     * Student transcript
     *
     * Graded courses with grade points and GPA. Admins, lecturers, or the student themself.
     */
    public function transcript(Student $student): JsonResponse
    {
        Gate::authorize('viewTranscript', $student);

        $enrollments = $student->enrollments()->with('course')->whereNotNull('score')
            ->orderBy('academic_year')->orderBy('semester')->get();

        $credits = $enrollments->sum(fn ($e) => $e->course->credits);
        $points = $enrollments->sum(fn ($e) => $e->grade->points() * $e->course->credits);

        return response()->json([
            'data' => [
                'student' => new StudentResource($student->load('user')),
                'total_credits' => $credits,
                'gpa' => $credits > 0 ? round($points / $credits, 2) : null,
                'courses' => $enrollments->map(fn ($e) => [
                    'code' => $e->course->code,
                    'name' => $e->course->name,
                    'credits' => $e->course->credits,
                    'academic_year' => $e->academic_year,
                    'semester' => $e->semester->value,
                    'score' => $e->score,
                    'grade' => $e->grade->value,
                ])->values(),
            ],
        ]);
    }
}
