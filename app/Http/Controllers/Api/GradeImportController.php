<?php

namespace App\Http\Controllers\Api;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\AuditLogger;
use App\Services\GradeImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * @group Enrollments & Grades
 */
class GradeImportController extends Controller
{
    /**
     * Bulk import grades from CSV (admin, course lecturer)
     *
     * Upload a CSV with the header `nim,score` (max 1000 rows, 1 MB). Valid rows are graded; invalid rows
     * are returned in `errors` with their line number and do not stop the import.
     *
     * @bodyParam file file required CSV file with columns nim and score.
     * @bodyParam academic_year string required Example: 2025/2026
     * @bodyParam semester string required odd or even. Example: odd
     */
    public function store(Request $request, Course $course, GradeImportService $importer, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('manage', $course);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::enum(Semester::class)],
        ]);

        try {
            $result = $importer->import($course, $data['academic_year'], $data['semester'], $request->file('file'), $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['file' => [$e->getMessage()]]], 422);
        }

        $audit->record('grades_imported', $course, null, [
            'academic_year' => $data['academic_year'],
            'semester' => $data['semester'],
            'graded' => $result['graded'],
            'failed' => $result['failed'],
        ]);

        return response()->json(['data' => $result]);
    }
}
