<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Imports `nim,score` rows from a CSV. Valid rows are applied, invalid rows are reported with their line number.
 */
class GradeImportService
{
    public const MAX_ROWS = 1000;

    /** @return array{processed: int, graded: int, failed: int, errors: list<array{line: int, nim: ?string, error: string}>} */
    public function import(Course $course, string $academicYear, string $semester, UploadedFile $file, User $grader): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle, escape: '\\');

        $columns = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header ?: []);
        $nimIndex = array_search('nim', $columns, true);
        $scoreIndex = array_search('score', $columns, true);

        if ($nimIndex === false || $scoreIndex === false) {
            fclose($handle);
            throw new InvalidArgumentException('The CSV header must contain the columns "nim" and "score".');
        }

        $rows = [];
        $line = 1;
        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $line++;
            if ($row === [null]) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                throw new InvalidArgumentException('The CSV may contain at most '.self::MAX_ROWS.' rows.');
            }
            $rows[] = ['line' => $line, 'nim' => trim($row[$nimIndex] ?? ''), 'score' => trim($row[$scoreIndex] ?? '')];
        }
        fclose($handle);

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->whereHas('student', fn ($q) => $q->whereIn('nim', array_column($rows, 'nim')))
            ->with('student')
            ->get()
            ->keyBy(fn ($e) => $e->student->nim);

        $errors = [];
        $valid = [];
        $seen = [];

        foreach ($rows as $row) {
            $error = match (true) {
                $row['nim'] === '' => 'NIM is empty.',
                isset($seen[$row['nim']]) => 'Duplicate NIM in file.',
                ! $enrollments->has($row['nim']) => 'No enrollment found for this NIM in the given course and term.',
                ! is_numeric($row['score']) || $row['score'] < 0 || $row['score'] > 100 => 'Score must be a number between 0 and 100.',
                default => null,
            };

            $seen[$row['nim']] = true;

            if ($error) {
                $errors[] = ['line' => $row['line'], 'nim' => $row['nim'] ?: null, 'error' => $error];

                continue;
            }

            $valid[] = [$enrollments[$row['nim']], (float) $row['score']];
        }

        DB::transaction(function () use ($valid, $grader) {
            foreach ($valid as [$enrollment, $score]) {
                $enrollment->assignGrade($score, $grader);
            }
        });

        return [
            'processed' => count($rows),
            'graded' => count($valid),
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }
}
