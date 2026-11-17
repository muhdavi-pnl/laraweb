<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function record(
        string $event,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?int $userId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => $userId ?? Auth::guard('api')->id(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr($request->userAgent(), 0, 500) : null,
        ]);
    }
}
