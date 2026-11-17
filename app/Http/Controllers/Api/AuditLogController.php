<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * @group Audit Logs
 */
class AuditLogController extends Controller
{
    /**
     * List audit logs (admin)
     *
     * @queryParam event string created, updated, deleted, login, login_failed, logout, password_reset. Example: updated
     * @queryParam user_id integer Actor. Example: 1
     * @queryParam auditable_type string Model class, e.g. App\Models\Student.
     * @queryParam auditable_id integer Example: 1
     * @queryParam per_page integer Items per page (1-100). Example: 15
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $logs = AuditLog::query()
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['auditable_type'] ?? null, fn ($q, $v) => $q->where('auditable_type', $v))
            ->when($filters['auditable_id'] ?? null, fn ($q, $v) => $q->where('auditable_id', $v))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20);

        return AuditLogResource::collection($logs);
    }
}
