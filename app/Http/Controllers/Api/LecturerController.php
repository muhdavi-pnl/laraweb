<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lecturer\StoreLecturerRequest;
use App\Http\Requests\Lecturer\UpdateLecturerRequest;
use App\Http\Resources\LecturerResource;
use App\Models\Lecturer;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Lecturers
 */
class LecturerController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    /**
     * List lecturers (admin)
     *
     * @queryParam search string Match on name or NIDN. Example: smith
     * @queryParam department string Example: Informatics
     * @queryParam per_page integer Items per page (1-100). Example: 15
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $lecturers = Lecturer::query()
            ->with('user')
            ->withCount('students')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('nidn', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['department'] ?? null, fn ($q, $v) => $q->where('department', $v))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return LecturerResource::collection($lecturers);
    }

    /**
     * Create a lecturer with a login account (admin)
     */
    public function store(StoreLecturerRequest $request): JsonResponse
    {
        $lecturer = $this->accounts->createLecturer($request->validated());

        return (new LecturerResource($lecturer->load('user')))
            ->response()->setStatusCode(201);
    }

    /**
     * Show a lecturer (admin)
     */
    public function show(Lecturer $lecturer): LecturerResource
    {
        return new LecturerResource($lecturer->load('user')->loadCount('students'));
    }

    /**
     * Update a lecturer (admin)
     */
    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): LecturerResource
    {
        $lecturer = $this->accounts->updateLecturer($lecturer, $request->validated());

        return new LecturerResource($lecturer->load('user'));
    }

    /**
     * Delete a lecturer and their account (admin)
     */
    public function destroy(Lecturer $lecturer): Response
    {
        $this->accounts->delete($lecturer);

        return response()->noContent();
    }
}
